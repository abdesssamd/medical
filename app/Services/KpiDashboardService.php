<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class KpiDashboardService
{
    public function build(array $filters): array
    {
        $from = $filters['from'];
        $to = $filters['to'];
        $organizationId = $filters['organization_id'] ?? null;
        $practitionerId = $filters['practitioner_id'] ?? null;

        $invoiceQuery = DB::table('invoices')
            ->whereBetween('invoice_date', [$from, $to]);

        if ($organizationId) {
            $invoiceQuery->where('organization_id', $organizationId);
        }
        if ($practitionerId) {
            $invoiceQuery->where('practitioner_id', $practitionerId);
        }

        $caTotal = (float) (clone $invoiceQuery)->sum('total');

        $caBySpecialty = DB::table('invoice_line_items as ili')
            ->join('invoices as i', 'i.id', '=', 'ili.invoice_id')
            ->leftJoin('clinical_procedures as cp', 'cp.id', '=', 'ili.procedure_id')
            ->leftJoin('specialties as s', 's.id', '=', 'cp.specialty_id')
            ->whereBetween('i.invoice_date', [$from, $to])
            ->when($organizationId, fn ($q) => $q->where('i.organization_id', $organizationId))
            ->when($practitionerId, fn ($q) => $q->where('i.practitioner_id', $practitionerId))
            ->selectRaw('COALESCE(s.name, "Non assigne") as specialty_name, SUM(ili.total_price) as total')
            ->groupBy('s.name')
            ->orderByDesc('total')
            ->get();

        $plansBase = DB::table('treatment_plans')
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59']);

        if ($practitionerId) {
            $plansBase->where('practitioner_id', $practitionerId);
        }

        $quotesTotal = (int) (clone $plansBase)->count();
        $quotesAccepted = (int) (clone $plansBase)
            ->whereIn('status', ['approved', 'in_progress', 'completed'])
            ->count();

        $acceptanceRate = $quotesTotal > 0 ? round(($quotesAccepted / $quotesTotal) * 100, 2) : 0.0;

        $appointmentsBase = DB::table('appointments')
            ->whereBetween('appointment_date', [$from, $to]);

        if ($practitionerId) {
            $appointmentsBase->where('professional_id', $practitionerId);
        }

        $appointmentsTotal = (int) (clone $appointmentsBase)->count();
        $appointmentsCancelled = (int) (clone $appointmentsBase)
            ->whereIn('status', ['cancelled', 'no_show'])
            ->count();

        $cancellationRate = $appointmentsTotal > 0 ? round(($appointmentsCancelled / $appointmentsTotal) * 100, 2) : 0.0;

        return [
            'period' => ['from' => $from, 'to' => $to],
            'filters' => [
                'organization_id' => $organizationId,
                'practitioner_id' => $practitionerId,
            ],
            'ca_total' => $caTotal,
            'ca_by_specialty' => $caBySpecialty,
            'quotes' => [
                'total' => $quotesTotal,
                'accepted' => $quotesAccepted,
                'acceptance_rate_percent' => $acceptanceRate,
            ],
            'appointments' => [
                'total' => $appointmentsTotal,
                'cancelled_or_no_show' => $appointmentsCancelled,
                'cancellation_rate_percent' => $cancellationRate,
            ],
        ];
    }
}

