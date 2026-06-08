<?php

namespace Modules\Appointment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'professional_id' => $this->professional_id,
            'secretary_id' => $this->secretary_id,
            'created_by' => $this->created_by,
            'patient_name' => $this->patient_name,
            'patient_phone' => $this->patient_phone,
            'patient_email' => $this->patient_email,
            'appointment_date' => $this->appointment_date?->toDateString(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'status' => $this->status,
            'notes' => $this->notes,
            'consulted_at' => $this->consulted_at?->toDateTimeString(),
            'queue_ticket_id' => $this->queue_ticket_id,
            'commission' => $this->whenLoaded('commission', fn () => [
                'id' => $this->commission?->id,
                'amount' => $this->commission?->amount,
                'currency' => $this->commission?->currency,
                'status' => $this->commission?->status,
            ]),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
