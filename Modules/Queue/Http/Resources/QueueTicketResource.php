<?php

namespace Modules\Queue\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QueueTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'public_code' => $this->public_code,
            'status' => $this->status,
            'organization_id' => $this->organization_id,
            'service_id' => $this->service_id,
            'service' => $this->service?->name,
            'counter' => $this->counter?->name,
            'is_appointment' => (bool) $this->is_appointment,
            'appointment_at' => $this->appointment_at?->toDateTimeString(),
            'arrived_at' => $this->arrived_at?->toDateTimeString(),
            'called_at' => $this->called_at?->toDateTimeString(),
            'served_at' => $this->served_at?->toDateTimeString(),
            'estimated_wait_minutes' => $this->estimated_wait_minutes,
        ];
    }
}
