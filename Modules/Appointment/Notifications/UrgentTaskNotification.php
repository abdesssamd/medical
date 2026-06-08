<?php

namespace Modules\Appointment\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Modules\Appointment\Models\SecretaryTask;

class UrgentTaskNotification extends Notification
{
    public function __construct(private readonly SecretaryTask $task) {}

    public function via($notifiable)
    {
        return ['broadcast', 'database'];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'task_id' => $this->task->id,
            'appointment_id' => $this->task->appointment_id,
            'patient_name' => $this->task->patient?->full_name,
            'task_type' => $this->task->task_type,
            'title' => $this->task->title,
            'priority' => $this->task->priority,
        ]);
    }

    public function toDatabase($notifiable)
    {
        return [
            'task_id' => $this->task->id,
            'title' => $this->task->title,
            'priority' => $this->task->priority,
            'url' => route('secretary.dashboard', ['highlight' => $this->task->appointment_id]),
        ];
    }
}
