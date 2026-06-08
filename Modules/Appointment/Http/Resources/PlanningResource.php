<?php

namespace Modules\Appointment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanningResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'professional_id' => $this->professional_id,
            'day_of_week' => $this->day_of_week,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'consultation_minutes' => $this->consultation_minutes,
            'max_patients_per_day' => $this->max_patients_per_day,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
