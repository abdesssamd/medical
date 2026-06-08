<?php

namespace Modules\Rehab\Services;

use Illuminate\Support\Carbon;
use Modules\Rehab\Models\RehabEvaluation;
use Modules\Rehab\Models\RehabPrescription;
use Modules\Rehab\Models\RehabSession;

class RehabService
{
    public function patientDashboard(int $patientId): array
    {
        $prescriptions = RehabPrescription::where('patient_id', $patientId)
            ->with(['doctor:id,name', 'sessions' => fn ($q) => $q->orderBy('session_number'), 'evaluations' => fn ($q) => $q->orderBy('evaluation_date')])
            ->orderByDesc('created_at')
            ->get();

        $activePrescription = $prescriptions->firstWhere('status', 'in_progress')
            ?? $prescriptions->firstWhere('status', 'pending');

        $stats = $this->computeStats($activePrescription);

        return [
            'prescriptions' => $prescriptions,
            'active_prescription' => $activePrescription,
            'stats' => $stats,
            'evaluations' => $activePrescription?->evaluations ?? collect(),
            'sessions' => $activePrescription?->sessions ?? collect(),
        ];
    }

    public function storePrescription(array $data, int $doctorId): RehabPrescription
    {
        $prescription = RehabPrescription::create([
            'doctor_id' => $doctorId,
            'patient_id' => $data['patient_id'],
            'diagnosis' => $data['diagnosis'],
            'prescribed_sessions_count' => $data['prescribed_sessions_count'],
            'objectives' => $data['objectives'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'start_date' => $data['start_date'] ?? now()->toDateString(),
            'end_date' => $data['end_date'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return $prescription;
    }

    public function updatePrescription(RehabPrescription $prescription, array $data): RehabPrescription
    {
        $prescription->update(array_filter($data, fn ($v) => $v !== null));

        return $prescription->fresh();
    }

    public function storeEvaluation(array $data): RehabEvaluation
    {
        return RehabEvaluation::create([
            'prescription_id' => $data['prescription_id'],
            'type' => $data['type'],
            'evaluation_date' => $data['evaluation_date'] ?? now()->toDateString(),
            'goniometry' => $data['goniometry'] ?? null,
            'muscle_testing' => $data['muscle_testing'] ?? null,
            'functional_tests' => $data['functional_tests'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function storeSession(array $data, int $physiotherapistId): RehabSession
    {
        $prescription = RehabPrescription::findOrFail($data['prescription_id']);

        if (! $prescription->canAddSession()) {
            throw new \InvalidArgumentException(
                $prescription->status !== 'in_progress'
                    ? 'La prescription n\'est pas active.'
                    : 'Le nombre maximum de séances prescrites a été atteint. Une nouvelle prescription est nécessaire.'
            );
        }

        $nextSessionNumber = $prescription->sessions()->max('session_number') + 1;

        $session = RehabSession::create([
            'prescription_id' => $prescription->id,
            'physiotherapist_id' => $physiotherapistId,
            'session_number' => $nextSessionNumber,
            'session_date' => $data['session_date'] ?? now()->toDateString(),
            'pain_score' => $data['pain_score'] ?? null,
            'notes' => $data['notes'] ?? null,
            'exercises_performed' => $data['exercises_performed'] ?? null,
            'status' => $data['status'] ?? 'completed',
            'duration_minutes' => $data['duration_minutes'] ?? null,
        ]);

        if ($prescription->remaining_sessions === 0) {
            $prescription->update(['status' => 'completed']);
        }

        return $session;
    }

    public function computeStats(?RehabPrescription $prescription): array
    {
        if (! $prescription) {
            return [
                'total_prescribed' => 0,
                'total_completed' => 0,
                'remaining' => 0,
                'progress_percentage' => 0,
                'average_pain' => null,
                'pain_evolution' => [],
                'session_timeline' => [],
            ];
        }

        $sessions = $prescription->sessions()->orderBy('session_number')->get();
        $completedSessions = $sessions->where('status', 'completed');

        $painScores = $completedSessions->pluck('pain_score')->filter()->values();
        $averagePain = $painScores->isNotEmpty() ? round($painScores->avg(), 1) : null;

        $painEvolution = $completedSessions->map(fn ($s) => [
            'session' => $s->session_number,
            'date' => $s->session_date->format('d/m/Y'),
            'pain' => $s->pain_score,
        ])->values()->all();

        $sessionTimeline = $sessions->map(fn ($s) => [
            'session' => $s->session_number,
            'date' => $s->session_date->format('d/m/Y'),
            'status' => $s->status,
            'status_label' => $s->status_label,
            'pain' => $s->pain_score,
            'physiotherapist' => $s->physiotherapist?->full_name ?? '-',
        ])->values()->all();

        return [
            'total_prescribed' => $prescription->prescribed_sessions_count,
            'total_completed' => $completedSessions->count(),
            'remaining' => $prescription->remaining_sessions,
            'progress_percentage' => $prescription->progress_percentage,
            'average_pain' => $averagePain,
            'pain_evolution' => $painEvolution,
            'session_timeline' => $sessionTimeline,
            'first_session_date' => $sessions->first()?->session_date?->format('d/m/Y'),
            'last_session_date' => $completedSessions->last()?->session_date?->format('d/m/Y'),
        ];
    }
}
