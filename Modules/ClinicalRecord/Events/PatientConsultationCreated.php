<?php

namespace Modules\ClinicalRecord\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\ClinicalRecord\Models\PatientConsultation;

class PatientConsultationCreated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public PatientConsultation $consultation)
    {
    }

    public function broadcastOn(): Channel|array
    {
        return [
            new Channel('care.module2'),
            new PrivateChannel('care.module3.patient.'.$this->consultation->patient_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'PatientFlowUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'patient_id' => $this->consultation->patient_id,
            'consultation_id' => $this->consultation->id,
            'consultation_status' => $this->consultation->consultation_status,
            'consultation_type' => $this->consultation->consultation_type,
        ];
    }
}