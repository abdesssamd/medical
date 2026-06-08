<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Audit Trail Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration controls the audit trail behavior for
    | health data security compliance (HDS / RGPD).
    |
    */

    // Enable/disable audit logging
    'enabled' => env('AUDIT_ENABLED', true),

    // Retention period in days (default: 2555 = 7 years for medical standards)
    'retention_days' => env('AUDIT_RETENTION_DAYS', 2555),

    // Models to audit automatically
    'auditable_models' => [
        \App\Models\Patient::class,
        \Modules\Appointment\Models\Appointment::class,
        \Modules\ClinicalRecord\Models\DentalChart::class,
        \Modules\ClinicalRecord\Models\ClinicalProcedure::class,
        \Modules\ClinicalRecord\Models\PatientConsultation::class,
        \Modules\ClinicalRecord\Models\TreatmentPlan::class,
        \Modules\Billing\Models\Invoice::class,
        \Modules\Billing\Models\Payment::class,
    ],

    // Actions to log
    'actions' => [
        'created' => true,
        'updated' => true,
        'deleted' => true,
        'viewed' => false, // Enable with caution (performance impact)
        'exported' => true,
        'printed' => true,
    ],

    // Sensitive fields that should always be audited
    'sensitive_fields' => [
        'allergies',
        'medical_history',
        'diagnosis',
        'diagnosis_code',
        'diagnosis_label',
        'consultation_reason',
        'consultation_type',
        'consultation_status',
        'observations',
        'payment_status',
        'treatment_notes',
        'password',
        'email',
        'phone',
    ],

    // Alert thresholds for suspicious activity
    'alerts' => [
        'max_deletions_per_hour' => 10,
        'max_exports_per_hour' => 20,
        'max_viewoutsider_hours' => 5, // Views outside working hours
        'working_hours' => [
            'start' => '07:00',
            'end' => '20:00',
        ],
    ],
];
