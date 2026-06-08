<?php

namespace Modules\Appointment\Listeners;

use Modules\Appointment\Models\SecretaryTask;
use Modules\Appointment\Models\Appointment;

class CreateInitialOnboardingTasks
{
    /**
     * Crée les tâches initiales lors de création de rendez-vous.
     * Écoute: AppointmentCreated (ou event équivalent).
     */
    public function handle($event): void
    {
        $appointment = $event->appointment;

        // Ne créer que si patient confirmé
        if (!$appointment->patient_id) {
            return;
        }

        // Créer tâche d'onboarding
        SecretaryTask::firstOrCreate(
            ['appointment_id' => $appointment->id, 'task_type' => SecretaryTask::TYPE_INFO_INCOMPLETE],
            [
                'patient_id' => $appointment->patient_id,
                'status' => SecretaryTask::STATUS_OPEN,
                'priority' => SecretaryTask::PRIORITY_HIGH,
                'title' => 'Vérifier complétude dossier',
                'description' => 'Assurer que tous les documents et infos patient sont présents',
                'due_at' => $appointment->appointment_date->copy()->subDay(),
            ]
        );
    }
}
