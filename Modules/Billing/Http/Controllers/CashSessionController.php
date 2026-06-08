<?php

namespace Modules\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Billing\Models\CashSession;
use Modules\Billing\Services\CashSessionService;

class CashSessionController extends Controller
{
    public function __construct(private readonly CashSessionService $cashService) {}

    /**
     * Affiche le dashboard caisse.
     */
    public function index(Request $request)
    {
        $data = $this->cashService->getCashDashboard($request->user());

        return view('billing::cash-session.index', $data);
    }

    /**
     * Ouvre une session de caisse.
     */
    public function open(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'initial_balance' => 'required|numeric|min:0',
        ]);

        try {
            $session = $this->cashService->openSession(
                $request->user(),
                (float) $validated['initial_balance']
            );

            return response()->json([
                'success' => true,
                'session' => $session,
                'message' => 'Session de caisse ouverte.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Enregistre une transaction de caisse.
     */
    public function recordTransaction(Request $request, CashSession $session): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|string|in:cash,card,check,bank_transfer,insurance',
            'invoice_id' => 'nullable|exists:invoices,id',
            'patient_id' => 'nullable|exists:patients,id',
            'reference' => 'nullable|string|max:100',
        ]);

        try {
            $transaction = $this->cashService->recordTransaction(
                $session,
                (float) $validated['amount'],
                $validated['method'],
                $request->user(),
                $validated['invoice_id'] ?? null,
                $validated['patient_id'] ?? null,
                $validated['reference'] ?? null
            );

            return response()->json([
                'success' => true,
                'transaction' => $transaction,
                'theoretical_total' => $session->theoretical_total,
                'message' => 'Transaction enregistrée.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Ferme une session de caisse.
     */
    public function close(Request $request, CashSession $session): JsonResponse
    {
        $validated = $request->validate([
            'actual_total' => 'required|numeric|min:0',
            'variance_reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->cashService->closeSession(
                $session,
                (float) $validated['actual_total'],
                $validated['variance_reason'] ?? null
            );

            return response()->json([
                'success' => true,
                'session' => $session->fresh(),
                'message' => 'Session fermée avec succès.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Affiche le détail d'une session.
     */
    public function show(CashSession $session)
    {
        $transactions = $this->cashService->getSessionTransactions($session);

        return view('billing::cash-session.show', compact('session', 'transactions'));
    }

    /**
     * Exporte le journal de caisse.
     */
    public function export(Request $request, CashSession $session): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $format = $request->query('format', 'csv');

        $journal = $this->cashService->exportCashJournal($session, $format);

        $filename = "journal_caisse_{$session->id}." . ($format === 'csv' ? 'csv' : 'pdf');

        if ($format === 'csv') {
            return response()->streamDownload(
                fn () => print($journal),
                $filename,
                ['Content-Type' => 'text/csv']
            );
        }

        return response()->streamDownload(
            fn () => print($journal),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }
}
