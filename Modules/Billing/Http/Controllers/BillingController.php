<?php

namespace Modules\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Billing\Models\InsuranceClaim;
use Modules\Billing\Models\InsuranceCompany;
use Modules\Billing\Models\Invoice;
use Modules\Billing\Models\Payment;
use Modules\Billing\Services\BillingService;
use Modules\Billing\Services\FinancialOperationsService;
use Modules\ClinicalRecord\Models\ClinicalProcedure;
use Modules\ClinicalRecord\Models\TreatmentPlan;

class BillingController extends Controller
{
    public function __construct(
        private readonly BillingService $billingService,
        private readonly FinancialOperationsService $financialOperationsService
    ) {}

    /**
     * Dashboard facturation.
     */
    public function dashboard(Request $request): View
    {
        $fromDate = $request->input('from_date', now()->startOfMonth()->format('Y-m-d'));
        $toDate = $request->input('to_date', now()->endOfMonth()->format('Y-m-d'));

        $stats = $this->billingService->getRevenueStatistics($fromDate, $toDate);

        $recentInvoices = Invoice::with(['patient', 'practitioner'])
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        $pendingClaims = InsuranceClaim::pending()
            ->with(['insuranceCompany', 'invoice.patient'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $overdueInvoices = Invoice::overdue()
            ->with('patient')
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        $unpaidDashboard = $this->financialOperationsService->unpaidDashboardData();

        return view('billing::dashboard', compact(
            'stats', 'recentInvoices', 'pendingClaims', 'overdueInvoices', 'fromDate', 'toDate', 'unpaidDashboard'
        ));
    }

    /**
     * Liste des factures.
     */
    public function invoices(Request $request): View
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $query = Invoice::with(['patient', 'practitioner', 'treatmentPlan']);

        if ($search) {
            $query->whereHas('patient', fn ($q) => $q->search($search));
        }

        if ($status) {
            $query->status($status);
        }

        if ($fromDate && $toDate) {
            $query->betweenDates($fromDate, $toDate);
        }

        $invoices = $query->orderByDesc('invoice_date')->paginate(20);

        return view('billing::invoices.index', compact('invoices', 'search', 'status', 'fromDate', 'toDate'));
    }

    /**
     * Créer une facture depuis des actes cliniques.
     */
    public function createInvoiceFromProcedures(int $patientId): View
    {
        $patient = Patient::findOrFail($patientId);

        $unbilledProcedures = ClinicalProcedure::forPatient($patientId)
            ->where('status', ClinicalProcedure::STATUS_COMPLETED)
            ->whereNotIn('id', function ($query) {
                $query->select('procedure_id')->from('invoice_line_items')->whereNotNull('procedure_id');
            })
            ->with(['practitioner', 'specialty'])
            ->get();

        $treatmentPlans = TreatmentPlan::forPatient($patientId)->active()->get();
        $practitioners = User::whereHas('specialties')->orderBy('name')->get();

        return view('billing::invoices.create-from-procedures', compact(
            'patient', 'unbilledProcedures', 'treatmentPlans', 'practitioners'
        ));
    }

    /**
     * Stocker une facture depuis des actes.
     */
    public function storeInvoiceFromProcedures(Request $request, int $patientId): RedirectResponse
    {
        $validated = $request->validate([
            'procedure_ids' => 'required|array|min:1',
            'procedure_ids.*' => 'exists:clinical_procedures,id',
            'practitioner_id' => 'nullable|exists:users,id',
            'treatment_plan_id' => 'nullable|exists:treatment_plans,id',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        try {
            $invoice = $this->billingService->createInvoiceFromProcedures(
                patientId: $patientId,
                procedureIds: $validated['procedure_ids'],
                practitionerId: $validated['practitioner_id'] ?? null,
                treatmentPlanId: $validated['treatment_plan_id'] ?? null,
                discountAmount: $validated['discount_amount'] ?? 0,
                taxRate: $validated['tax_rate'] ?? 0,
                notes: $validated['notes'] ?? null
            );

            return redirect()->route('billing.invoices.show', ['invoiceId' => $invoice->id])
                ->with('success', 'Facture créée avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la création de la facture: '.$e->getMessage());
        }
    }

    /**
     * Détail d'une facture.
     */
    public function showInvoice(int $invoiceId): View
    {
        $invoice = Invoice::with(['patient', 'practitioner', 'treatmentPlan', 'lineItems.procedure', 'payments.receivedBy', 'insuranceClaims.insuranceCompany'])
            ->findOrFail($invoiceId);

        return view('billing::invoices.show', compact('invoice'));
    }

    /**
     * Enregistrer un paiement.
     */
    public function recordPayment(Request $request, int $invoiceId): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|string|in:cash,card,check,bank_transfer,insurance',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        try {
            $payment = $this->billingService->recordPayment(
                invoiceId: $invoiceId,
                amount: (float) $validated['amount'],
                method: $validated['method'],
                reference: $validated['reference'] ?? null,
                receivedBy: auth()->user()
            );

            return back()->with('success', 'Paiement enregistré: '.$payment->payment_number);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Compagnies d'assurance.
     */
    public function insuranceCompanies(): View
    {
        $companies = InsuranceCompany::withCount(['claims', 'patientSubscriptions'])
            ->orderBy('name')
            ->get();

        return view('billing::insurance.companies', compact('companies'));
    }

    /**
     * Réclamations d'assurance.
     */
    public function insuranceClaims(Request $request): View
    {
        $status = $request->input('status');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $query = InsuranceClaim::with(['invoice.patient', 'insuranceCompany']);

        if ($status) {
            $query->status($status);
        }

        if ($fromDate && $toDate) {
            $query->whereBetween('submitted_at', [$fromDate, $toDate]);
        }

        $claims = $query->orderByDesc('created_at')->paginate(20);

        return view('billing::insurance.claims', compact('claims', 'status', 'fromDate', 'toDate'));
    }

    /**
     * Submit a claim to insurance company.
     */
    public function submitClaim(int $claimId): RedirectResponse
    {
        try {
            $claim = $this->billingService->submitClaim($claimId);
            return back()->with('success', 'Réclamation soumise: '.$claim->claim_number);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Approuver une réclamation.
     */
    public function approveClaim(Request $request, int $claimId): RedirectResponse
    {
        $validated = $request->validate([
            'approved_amount' => 'required|numeric|min:0',
        ]);

        try {
            $claim = $this->billingService->approveClaim($claimId, (float) $validated['approved_amount']);
            return back()->with('success', 'Réclamation approuvée. Montant patient: '.$claim->patient_remaining);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Solde restant dû d'un patient.
     */
    public function patientBalance(int $patientId): JsonResponse
    {
        $balance = $this->billingService->getPatientOutstandingBalance($patientId);

        return response()->json($balance);
    }

    public function teletransmissionGenerate(Request $request): RedirectResponse
    {
        $batch = $this->financialOperationsService->generateTeletransmissionBatch(auth()->id());

        return back()->with('success', "Bordereau teletransmission {$batch->batch_number} genere.");
    }

    public function remindInvoice(Request $request, int $invoiceId): RedirectResponse
    {
        $validated = $request->validate([
            'channel' => ['nullable', 'string', 'in:sms,email'],
        ]);

        $invoice = Invoice::with('patient')->findOrFail($invoiceId);
        $channel = $validated['channel'] ?? 'sms';
        $this->financialOperationsService->sendUnpaidReminder($invoice, $channel, auth()->id());

        return back()->with('success', "Relance {$channel} envoyee pour {$invoice->invoice_number}.");
    }
}
