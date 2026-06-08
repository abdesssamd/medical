<?php

namespace Modules\Appointment\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Appointment\Models\Appointment;

class PatientFlowUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public Appointment $appointment, public string $reason = 'updated')
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('care.module2');
    }

    public function broadcastAs(): string
    {
        return 'PatientFlowUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'appointment_id' => $this->appointment->id,
            'patient_id' => $this->appointment->patient_id,
            'status' => $this->appointment->status,
            'reason' => $this->reason,
        ];
    }
}