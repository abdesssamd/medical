<?php

namespace Modules\Pediatrics\Services;

use Carbon\Carbon;
use Modules\Pediatrics\Models\Vaccine;
use Modules\Pediatrics\Models\VaccinationRecord;

class VaccinationScheduleService
{
    public function generateSchedule(int $patientId, ?Carbon $birthDate = null): array
    {
        $patient = \App\Models\Patient::find($patientId);

        if (! $birthDate && $patient?->date_of_birth) {
            $birthDate = Carbon::parse($patient->date_of_birth);
        }

        if (! $birthDate) {
            return [];
        }

        $vaccines = Vaccine::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $existingRecords = VaccinationRecord::where('patient_id', $patientId)
            ->get()
            ->keyBy('vaccine_id');

        $schedule = [];
        $today = Carbon::today();

        foreach ($vaccines as $vaccine) {
            $scheduledDate = $birthDate->copy()->addMonths($vaccine->recommended_age_months ?? 0);

            $record = $existingRecords->get($vaccine->id);

            $status = $this->determineStatus($record, $scheduledDate, $today);

            $schedule[] = [
                'vaccine_id' => $vaccine->id,
                'vaccine_code' => $vaccine->code,
                'vaccine_name' => $vaccine->name,
                'vaccine_name_ar' => $vaccine->name_ar,
                'disease' => $vaccine->disease,
                'disease_ar' => $vaccine->disease_ar,
                'dose_number' => $vaccine->dose_number,
                'total_doses' => $vaccine->total_doses,
                'display_name' => $vaccine->display_name,
                'recommended_age_months' => $vaccine->recommended_age_months,
                'recommended_age_label' => $this->formatAgeLabel($vaccine->recommended_age_months),
                'scheduled_date' => $scheduledDate->format('Y-m-d'),
                'administered_date' => $record?->administered_date?->format('Y-m-d'),
                'batch_number' => $record?->batch_number,
                'manufacturer' => $record?->manufacturer,
                'status' => $status,
                'status_label' => $this->getStatusLabel($status),
                'is_mandatory' => $vaccine->is_mandatory,
                'route' => $vaccine->route,
                'site' => $vaccine->site,
                'record_id' => $record?->id,
                'days_overdue' => $status === 'overdue' ? $scheduledDate->diffInDays($today) : 0,
            ];
        }

        return $schedule;
    }

    public function storeVaccinationRecord(int $patientId, array $data): VaccinationRecord
    {
        $status = ! empty($data['administered_date'])
            ? VaccinationRecord::STATUS_ADMINISTERED
            : ($data['status'] ?? VaccinationRecord::STATUS_PENDING);

        return VaccinationRecord::updateOrCreate(
            ['id' => $data['id'] ?? null],
            array_merge($data, [
                'patient_id' => $patientId,
                'status' => $status,
                'practitioner_id' => auth()->id(),
            ])
        );
    }

    public function getVaccinationSummary(int $patientId): array
    {
        $patient = \App\Models\Patient::find($patientId);
        $birthDate = $patient?->date_of_birth ? Carbon::parse($patient->date_of_birth) : null;

        if (! $birthDate) {
            return [
                'total' => 0,
                'administered' => 0,
                'pending' => 0,
                'overdue' => 0,
                'coverage_rate' => 0,
            ];
        }

        $schedule = $this->generateSchedule($patientId, $birthDate);

        $total = count($schedule);
        $administered = collect($schedule)->where('status', 'administered')->count();
        $pending = collect($schedule)->where('status', 'pending')->count();
        $overdue = collect($schedule)->where('status', 'overdue')->count();

        return [
            'total' => $total,
            'administered' => $administered,
            'pending' => $pending,
            'overdue' => $overdue,
            'coverage_rate' => $total > 0 ? round(($administered / $total) * 100, 1) : 0,
        ];
    }

    private function determineStatus(?VaccinationRecord $record, Carbon $scheduledDate, Carbon $today): string
    {
        if ($record) {
            if ($record->administered_date) {
                return VaccinationRecord::STATUS_ADMINISTERED;
            }

            if ($record->status === VaccinationRecord::STATUS_REFUSED) {
                return VaccinationRecord::STATUS_REFUSED;
            }

            if ($record->status === VaccinationRecord::STATUS_CONTRAINDICATED) {
                return VaccinationRecord::STATUS_CONTRAINDICATED;
            }
        }

        if ($scheduledDate->lt($today)) {
            return VaccinationRecord::STATUS_OVERDUE;
        }

        return VaccinationRecord::STATUS_PENDING;
    }

    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            VaccinationRecord::STATUS_ADMINISTERED => 'Administré',
            VaccinationRecord::STATUS_PENDING => 'En attente',
            VaccinationRecord::STATUS_OVERDUE => 'En retard',
            VaccinationRecord::STATUS_REFUSED => 'Refusé',
            VaccinationRecord::STATUS_CONTRAINDICATED => 'Contre-indiqué',
            default => $status,
        };
    }

    private function formatAgeLabel(?int $months): string
    {
        if ($months === null || $months === 0) {
            return 'Naissance';
        }

        if ($months < 12) {
            return $months . ' mois';
        }

        $years = intdiv($months, 12);
        $remainingMonths = $months % 12;

        if ($remainingMonths === 0) {
            return $years . ' an' . ($years > 1 ? 's' : '');
        }

        return $years . ' an' . ($years > 1 ? 's' : '') . ' ' . $remainingMonths . ' mois';
    }
}
