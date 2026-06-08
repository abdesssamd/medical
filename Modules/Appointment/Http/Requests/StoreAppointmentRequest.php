<?php

namespace Modules\Appointment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'professional_id' => ['required', 'exists:users,id'],
            'secretary_id' => ['nullable', 'exists:users,id'],
            'patient_name' => ['required', 'string', 'max:255'],
            'patient_phone' => ['nullable', 'string', 'max:30'],
            'patient_email' => ['nullable', 'email', 'max:255'],
            'consultation_reason' => ['nullable', 'string', 'max:255'],
            'consultation_type' => ['nullable', 'string', 'max:40'],
            'appointment_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
