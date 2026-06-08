<?php

namespace Modules\Billing\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Billing\Models\CashSession;
use Modules\Billing\Models\CashTransaction;
use Modules\Billing\Models\Invoice;

class CashSessionService
{
    /**
     * Ouvre une nouvelle session de caisse.
     */
    public function openSession(User $user, float $initialBalance = 0): CashSession
    {
        // Vérifier qu'une session est ouverte
        $openSession = CashSession::where('user_id', $user->id)
            ->where('status', CashSession::STATUS_OPEN)
            ->first();

        if ($openSession) {
            throw new \Exception('Une session de caisse est déjà ouverte pour cet utilisateur.');
        }

        return CashSession::create([
            'user_id' => $user->id,
            'opened_at' => now(),
            'initial_balance' => $initialBalance,
            'status' => CashSession::STATUS_OPEN,
        ]);
    }

    /**
     * Enregistre une transaction de caisse.
     */
    public function recordTransaction(
        CashSession $session,
        float $amount,
        string $method,
        User $recordedBy,
        ?int $invoiceId = null,
        ?int $patientId = null,
        ?string $reference = null
    ): CashTransaction {
        if (!$session->isOpen()) {
            throw new \Exception('La session de caisse n\'est pas ouverte.');
        }

        $transaction = CashTransaction::create([
            'cash_session_id' => $session->id,
            'invoice_id' => $invoiceId,
            'patient_id' => $patientId,
            'recorded_by' => $recordedBy->id,
            'method' => $method,
            'amount' => $amount,
            'reference' => $reference,
            'recorded_at' => now(),
        ]);

        // Mettre à jour le total théorique
        $session->theoretical_total = $session->calculateTheoretical();
        $session->save();

        // Mettre à jour le statut de la facture si applicable
        if ($invoiceId) {
            $this->updateInvoicePaymentStatus($invoiceId, $amount);
        }

        return $transaction;
    }

    /**
     * Ferme une session de caisse.
     */
    public function closeSession(CashSession $session, float $actualTotal, ?string $reason = null): void
    {
        DB::transaction(function () use ($session, $actualTotal, $reason) {
            $session->close($actualTotal, $reason);

            // Audit log
            \Log::info('cash_session.closed', [
                'session_id' => $session->id,
                'user_id' => $session->user_id,
                'theoretical_total' => $session->theoretical_total,
                'actual_total' => $actualTotal,
                'difference' => $session->difference,
            ]);
        });
    }

    /**
     * Récupère le tableau de bord caisse du jour.
     */
    public function getCashDashboard(?User $user = null): array
    {
        $today = today();

        $openSession = null;
        if ($user) {
            $openSession = CashSession::where('user_id', $user->id)
                ->where('status', CashSession::STATUS_OPEN)
                ->first();
        }

        $closedSessions = CashSession::whereDate('closed_at', $today)
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->with('user')
            ->orderByDesc('closed_at')
            ->get();

        $stats = [
            'total_opened' => (float) $closedSessions->sum('initial_balance'),
            'total_theoretical' => (float) $closedSessions->sum('theoretical_total'),
            'total_actual' => (float) $closedSessions->sum('actual_total'),
            'total_difference' => (float) $closedSessions->sum('difference'),
            'sessions_with_variance' => $closedSessions->filter(fn ($s) => abs($s->difference) > 0)->count(),
            'open_sessions_count' => CashSession::where('status', CashSession::STATUS_OPEN)->count(),
        ];

        return [
            'open_session' => $openSession,
            'closed_sessions' => $closedSessions,
            'stats' => $stats,
        ];
    }

    /**
     * Récupère les transactions d'une session.
     */
    public function getSessionTransactions(CashSession $session): array
    {
        $transactions = $session->transactions()->with(['invoice', 'patient', 'recordedBy'])->get();

        $byMethod = $transactions->groupBy('method')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total' => (float) $group->sum('amount'),
            ];
        });

        return [
            'transactions' => $transactions,
            'by_method' => $byMethod,
            'total' => (float) $transactions->sum('amount'),
            'count' => $transactions->count(),
        ];
    }

    /**
     * Met à jour le statut de paiement de la facture.
     */
    private function updateInvoicePaymentStatus(int $invoiceId, float $paidAmount): void
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $invoice->increment('paid_amount', $paidAmount);
        // Le modèle Invoice s'auto-met à jour via les observers
    }

    /**
     * Exporte le journal de caisse.
     */
    public function exportCashJournal(CashSession $session, string $format = 'pdf'): string
    {
        $data = [
            'session' => $session->load('user', 'transactions'),
            'transactions' => $this->getSessionTransactions($session),
        ];

        if ($format === 'csv') {
            return $this->generateCSVJournal($data);
        }

        return $this->generatePDFJournal($data);
    }

    private function generateCSVJournal(array $data): string
    {
        $lines = [
            ['JOURNAL DE CAISSE'],
            ['Utilisateur', $data['session']->user->name],
            ['Date d\'ouverture', $data['session']->opened_at->format('d/m/Y H:i')],
            ['Date de clôture', $data['session']->closed_at?->format('d/m/Y H:i') ?? 'N/A'],
            [''],
            ['Fonds initial', number_format($data['session']->initial_balance, 2, ',', ' ')],
            ['Total théorique', number_format($data['session']->theoretical_total, 2, ',', ' ')],
            ['Total réel', number_format($data['session']->actual_total, 2, ',', ' ')],
            ['Écart', number_format($data['session']->difference, 2, ',', ' ')],
            [''],
            ['DÉTAIL DES TRANSACTIONS'],
            ['Méthode', 'Montant', 'Patient', 'Référence'],
        ];

        foreach ($data['transactions']['transactions'] as $t) {
            $lines[] = [
                $t->method,
                number_format($t->amount, 2, ',', ' '),
                $t->patient?->full_name ?? '-',
                $t->reference ?? '-',
            ];
        }

        return implode("\n", array_map(fn ($line) => implode(',', $line), $lines));
    }

    private function generatePDFJournal(array $data): string
    {
        // À implémenter avec une librairie PDF (ex: Barryvdh\DomPDF)
        return 'PDF export not yet implemented';
    }
}
