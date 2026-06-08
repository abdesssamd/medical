<?php

namespace Modules\Appointment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'appointment_id' => $this->appointment_id,
            'professional_id' => $this->professional_id,
            'secretary_id' => $this->secretary_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'earned_on' => $this->earned_on?->toDateString(),
            'paid_at' => $this->paid_at?->toDateTimeString(),
            'notes' => $this->notes,
        ];
    }
}
