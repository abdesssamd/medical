<?php

namespace Modules\Appointment\Services;

use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Modules\Appointment\Models\Appointment;
use Modules\Appointment\Models\SecretaryTask;
use Modules\Appointment\Models\SecretaryNote;
use Modules\Appointment\Models\PatientJourney;
use Modules\Queue\Models\QueuePriority;

class SecretaryDashboardService
{
    /**
     * Récupère les données du dashboard avec priorisation opérationnelle.
     */
    public function getDashboardData(string $date = null, ?int $professionalId = null): array
    {
        $date = $date ? Carbon::parse($date)->toDateString() : today()->toDateString();

        $appointments = Appointment::query()
            ->with(['patient', 'professional', 'queueTicket', 'journey', 'tasks', 'notes'])
            ->whereDate('appointment_date', $date)
            ->when($professionalId, fn ($q) => $q->where('professional_id', $professionalId))
            ->orderBy('start_time')
            ->get();

        $rows = $appointments->map(function (Appointment $apt) {
            $journey = $apt->journey ?? PatientJourney::where('appointment_id', $apt->id)->first();
            $tasks = $this->tasksFor($apt);
            $notes = $this->secretaryNotesFor($apt);
            $nextAction = $this->determineNextAction($apt, $journey);
            $urgencyLevel = $this->calculateUrgency($apt, $journey);

            return [
                'appointment_id' => $apt->id,
                'patient_id' => $apt->patient_id,
                'patient_name' => $apt->patient?->full_name ?? $apt->patient_name,
                'phone' => $apt->patient?->phone ?? $apt->patient_phone,
                'professional_id' => $apt->professional_id,
                'professional' => $apt->professional?->display_name,
                'appointment_date' => $apt->appointment_date?->toDateString(),
                'start_time' => $apt->start_time,
                'end_time' => $apt->end_time,
                'status' => $apt->status,
                'flow_status' => $journey?->current_status ?? 'booked',
                'next_action' => $nextAction,
                'urgency_level' => $urgencyLevel,
                'urgency_color' => $this->getUrgencyColor($urgencyLevel),
                'has_open_tasks' => $tasks->where('status', 'open')->count() > 0,
                'open_tasks' => $tasks->where('status', 'open')->map(fn ($t) => [
                    'id' => $t->id,
                    'type' => $t->task_type,
                    'title' => $t->title,
                    'priority' => $t->priority,
                ])->values(),
                'has_unread_notes' => $notes->some(fn ($n) => $n->isUnread()),
                'unread_notes_count' => $notes->filter(fn ($n) => $n->isUnread())->count(),
                'arrived_at' => $journey?->arrived_at?->toDateTimeString(),
                'in_care_at' => $journey?->in_care_at?->toDateTimeString(),
                'wait_minutes' => $this->calculateWaitMinutes($apt, $journey),
                'late_threshold_exceeded' => $this->isLateThresholdExceeded($apt, $journey),
                'queue_position' => $apt->queueTicket?->sequence_number ?? null,
                'queue_ticket' => $apt->queueTicket?->ticket_number,
            ];
        });

        // Trier par priorité opérationnelle
        $rows = $rows->sortBy(function ($row) {
            $urgencyOrder = ['critical' => 0, 'high' => 1, 'normal' => 2, 'low' => 3];
            $actionOrder = [
                'check_in' => 0,
                'document_missing' => 1,
                'payment_pending' => 2,
                'notify_practitioner' => 3,
                'checkout' => 4,
                'none' => 5,
            ];
            return ($urgencyOrder[$row['urgency_level']] ?? 2) * 1000 +
                   ($actionOrder[$row['next_action']] ?? 5);
        })->values();

        return [
            'date' => $date,
            'total' => $rows->count(),
            'by_status' => $rows->groupBy('flow_status')->map->count(),
            'by_urgency' => $rows->groupBy('urgency_level')->map->count(),
            'kpis' => $this->calculateKPIs($appointments),
            'items' => $rows,
        ];
    }

    /**
     * Détermine l'action suivante pour un patient.
     */
    private function determineNextAction(Appointment $apt, ?PatientJourney $journey): string
    {
        $tasks = $this->tasksFor($apt);
        $notes = $this->secretaryNotesFor($apt);

        if (!$journey || $journey->current_status === PatientJourney::STATUS_BOOKED) {
            return 'check_in';
        }

        if ($tasks->where('status', 'open')->count() > 0) {
            $criticalTask = $tasks->where('status', 'open')->first();
            return match ($criticalTask->task_type) {
                SecretaryTask::TYPE_DOCUMENT_MISSING => 'document_missing',
                SecretaryTask::TYPE_PAYMENT_DUE => 'payment_pending',
                default => 'task_pending',
            };
        }

        if ($notes->some(fn ($n) => $n->priority === SecretaryNote::PRIORITY_CRITICAL && $n->isUnread())) {
            return 'notify_practitioner';
        }

        if ($journey->current_status === PatientJourney::STATUS_AWAITING_PAYMENT) {
            return 'payment_pending';
        }

        if ($journey->current_status === PatientJourney::STATUS_IN_CARE || 
            $journey->current_status === PatientJourney::STATUS_AWAITING_PAYMENT) {
            return 'checkout';
        }

        return 'none';
    }

    /**
     * Calcule le niveau d'urgence global.
     */
    private function calculateUrgency(Appointment $apt, ?PatientJourney $journey): string
    {
        $tasks = $this->tasksFor($apt);
        $notes = $this->secretaryNotesFor($apt);

        // Urgence explicite
        $priority = QueuePriority::where('appointment_id', $apt->id)->first();
        if ($priority?->priority_level === QueuePriority::PRIORITY_CRITICAL) {
            return 'critical';
        }

        // Tâches critiques
        if ($tasks->where('priority', SecretaryTask::PRIORITY_CRITICAL)->count() > 0) {
            return 'critical';
        }

        // Notes critiques
        if ($notes->where('priority', SecretaryNote::PRIORITY_CRITICAL)->count() > 0) {
            return 'high';
        }

        // Attente excessive
        if ($this->isLateThresholdExceeded($apt, $journey)) {
            return 'high';
        }

        if ($tasks->where('priority', SecretaryTask::PRIORITY_HIGH)->count() > 0) {
            return 'high';
        }

        return 'normal';
    }

    /**
     * Vérifie si le patient dépasse le seuil d'attente.
     */
    private function isLateThresholdExceeded(Appointment $apt, ?PatientJourney $journey): bool
    {
        if (!$journey) {
            return false;
        }

        $thresholds = [
            PatientJourney::STATUS_BOOKED => 0, // N/A
            PatientJourney::STATUS_ARRIVED => 20, // 20 min en salle attente
            PatientJourney::STATUS_IN_CARE => 10, // 10 min en consultation
            PatientJourney::STATUS_AWAITING_PAYMENT => 5, // 5 min en attente paiement
        ];

        $threshold = $thresholds[$journey->current_status] ?? 0;
        if ($threshold === 0) {
            return false;
        }

        $referenceTime = match ($journey->current_status) {
            PatientJourney::STATUS_ARRIVED => $journey->arrived_at,
            PatientJourney::STATUS_IN_CARE => $journey->in_care_at,
            PatientJourney::STATUS_AWAITING_PAYMENT => $journey->awaiting_payment_at,
            default => null,
        };

        if (!$referenceTime) {
            return false;
        }

        return now()->diffInMinutes($referenceTime) > $threshold;
    }

    private function calculateWaitMinutes(Appointment $apt, ?PatientJourney $journey): int
    {
        if (!$journey?->arrived_at) {
            return 0;
        }
        return now()->diffInMinutes($journey->arrived_at);
    }

    private function getUrgencyColor(string $urgency): string
    {
        return match ($urgency) {
            'critical' => 'red',
            'high' => 'orange',
            'normal' => 'gray',
            'low' => 'green',
            default => 'gray',
        };
    }

    /**
     * Calcule les KPIs du jour.
     */
    private function calculateKPIs(Collection $appointments): array
    {
        $completed = $appointments->where('status', 'completed');
        $arrived = $appointments->filter(fn ($a) => $a->journey?->arrived_at);

        $avgWaitTime = 0;
        if ($arrived->count() > 0) {
            $avgWaitTime = intval($arrived->average(function ($apt) {
                return $apt->journey?->arrived_at
                    ? now()->diffInMinutes($apt->journey->arrived_at)
                    : 0;
            }));
        }

        $avgCheckoutTime = 0;
        if ($completed->count() > 0) {
            $avgCheckoutTime = intval($completed->average(function ($apt) {
                $journey = $apt->journey;
                if ($journey?->completed_at && $journey->arrived_at) {
                    return $journey->completed_at->diffInMinutes($journey->arrived_at);
                }
                return 0;
            }));
        }

        $incompleteCount = $appointments
            ->filter(fn ($a) => $this->tasksFor($a)->where('status', 'open')->count() > 0)
            ->count();

        return [
            'total_patients' => $appointments->count(),
            'completed' => $completed->count(),
            'in_progress' => $appointments->where('status', '!=', 'completed')->count(),
            'avg_wait_minutes' => $avgWaitTime,
            'avg_checkout_minutes' => $avgCheckoutTime,
            'incomplete_files_count' => $incompleteCount,
            'incomplete_files_percent' => $appointments->count() > 0 
                ? round(($incompleteCount / $appointments->count()) * 100) 
                : 0,
            'critical_urgencies' => $appointments
                ->filter(fn ($a) => $this->calculateUrgency($a, $a->journey ?? null) === 'critical')
                ->count(),
        ];
    }

    private function tasksFor(Appointment $appointment): \Illuminate\Support\Collection
    {
        if ($appointment->relationLoaded('tasks')) {
            return $appointment->getRelation('tasks') ?? collect();
        }

        return $appointment->tasks()->get();
    }

    private function secretaryNotesFor(Appointment $appointment): \Illuminate\Support\Collection
    {
        if ($appointment->relationLoaded('notes')) {
            return $appointment->getRelation('notes') ?? collect();
        }

        return $appointment->notes()->get();
    }
}
