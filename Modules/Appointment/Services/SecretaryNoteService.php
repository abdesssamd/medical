<?php

namespace Modules\Appointment\Services;

use Modules\Appointment\Models\Appointment;
use Modules\Appointment\Models\SecretaryNote;
use Modules\Appointment\Models\SecretaryTask;
use Modules\Appointment\Models\PatientJourney;

class SecretaryNoteService
{
    /**
     * Crée une note contextuelle rapide.
     */
    public function createNote(
        Appointment $appointment,
        string $tag,
        string $message,
        \App\Models\User $createdBy,
        string $priority = SecretaryNote::PRIORITY_NORMAL
    ): SecretaryNote {
        $note = SecretaryNote::create([
            'appointment_id' => $appointment->id,
            'created_by' => $createdBy->id,
            'tag' => $tag,
            'message' => $message,
            'priority' => $priority,
        ]);

        // Créer une tâche associée si c'est un problème
        if (in_array($tag, [
            SecretaryNote::TAG_DOCUMENT_MISSING,
            SecretaryNote::TAG_INSURANCE_VERIFY,
            SecretaryNote::TAG_CONSENT_PENDING,
            SecretaryNote::TAG_PAYMENT_ISSUE,
        ])) {
            $this->createTaskFromNote($appointment, $note);
        }

        // Notifier les praticiens
        $this->notifyPractitioners($appointment, $note);

        return $note;
    }

    /**
     * Marque une note comme lue.
     */
    public function markAsRead(SecretaryNote $note): void
    {
        $note->markAsRead();
    }

    /**
     * Récupère les notes non lues pour un praticien.
     */
    public function getUnreadNotesForPractitioner(\App\Models\User $practitioner): \Illuminate\Database\Eloquent\Collection
    {
        return SecretaryNote::whereHas('appointment', fn ($q) => $q->where('professional_id', $practitioner->id))
            ->whereNull('read_at')
            ->orderByDesc('priority')
            ->orderByDesc('created_at')
            ->get();
    }

    private function createTaskFromNote(Appointment $appointment, SecretaryNote $note): void
    {
        $taskType = match ($note->tag) {
            SecretaryNote::TAG_DOCUMENT_MISSING => SecretaryTask::TYPE_DOCUMENT_MISSING,
            SecretaryNote::TAG_INSURANCE_VERIFY => SecretaryTask::TYPE_INSURANCE_VERIFY,
            SecretaryNote::TAG_CONSENT_PENDING => SecretaryTask::TYPE_CONSENT_PENDING,
            SecretaryNote::TAG_PAYMENT_ISSUE => SecretaryTask::TYPE_PAYMENT_DUE,
            default => SecretaryTask::TYPE_INFO_INCOMPLETE,
        };

        SecretaryTask::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'task_type' => $taskType,
            'status' => SecretaryTask::STATUS_OPEN,
            'priority' => $note->priority === SecretaryNote::PRIORITY_CRITICAL
                ? SecretaryTask::PRIORITY_CRITICAL
                : SecretaryTask::PRIORITY_HIGH,
            'title' => $note->message,
            'description' => 'Créée depuis note rapide: ' . $note->tag,
            'due_at' => now()->addHours(2),
            'metadata' => [
                'from_note_id' => $note->id,
                'tag' => $note->tag,
            ],
        ]);
    }

    private function notifyPractitioners(Appointment $appointment, SecretaryNote $note): void
    {
        // Implémentation de la notification en temps réel
        // Via WebSocket, Pusher, ou système de notifications Laravel
        \Notification::send(
            [$appointment->professional],
            new \Modules\Appointment\Notifications\SecretaryNoteNotification($appointment, $note)
        );
    }
}
