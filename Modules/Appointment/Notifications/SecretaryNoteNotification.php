<?php

namespace Modules\Appointment\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Modules\Appointment\Models\Appointment;
use Modules\Appointment\Models\SecretaryNote;

class SecretaryNoteNotification extends Notification
{
    public function __construct(
        private readonly Appointment $appointment,
        private readonly SecretaryNote $note,
    ) {}

    public function via($notifiable)
    {
        return ['broadcast', 'database'];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'appointment_id' => $this->appointment->id,
            'patient_name' => $this->appointment->patient?->full_name,
            'tag' => $this->note->tag,
            'message' => $this->note->message,
            'priority' => $this->note->priority,
            'created_by' => $this->note->createdBy->name,
            'created_at' => $this->note->created_at->toDateTimeString(),
        ]);
    }

    public function toDatabase($notifiable)
    {
        return [
            'appointment_id' => $this->appointment->id,
            'patient_name' => $this->appointment->patient?->full_name,
            'note_id' => $this->note->id,
            'tag' => $this->note->tag,
            'message' => $this->note->message,
            'priority' => $this->note->priority,
            'url' => route('secretary.dashboard', ['highlight' => $this->appointment->id]),
        ];
    }
}
