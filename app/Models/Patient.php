<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'medical_record_number',
        'patient_photo_path',
        'first_name',
        'last_name',
        'cin',
        'date_of_birth',
        'gender',
        'blood_group',
        'height_cm',
        'weight_kg',
        'phone',
        'email',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'allergies',
        'medical_history',
        'current_medications',
        'critical_conditions',
        'family_history',
        'personal_history',
        'is_active',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'height_cm' => 'float',
        'weight_kg' => 'float',
        'allergies' => 'array',
        'medical_history' => 'array',
        'current_medications' => 'array',
        'critical_conditions' => 'array',
        'family_history' => 'array',
        'personal_history' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Patient $patient): void {
            $patient->deduplication_key = self::buildDeduplicationKey(
                $patient->phone,
                $patient->cin,
                $patient->date_of_birth
            );
        });

        static::creating(function (Patient $patient) {
            if (empty($patient->medical_record_number)) {
                $patient->medical_record_number = self::generateMedicalRecordNumber();
            }
        });
    }

    public static function normalizePhone(?string $phone): ?string
    {
        $normalized = preg_replace('/\D+/', '', (string) $phone) ?: '';

        return $normalized !== '' ? $normalized : null;
    }

    public static function normalizeCin(?string $cin): ?string
    {
        $normalized = strtoupper(trim((string) $cin));

        return $normalized !== '' ? $normalized : null;
    }

    public static function buildDeduplicationKey(?string $phone, ?string $cin, mixed $dateOfBirth): ?string
    {
        $normalizedPhone = self::normalizePhone($phone);
        $normalizedCin = self::normalizeCin($cin);

        if ($normalizedPhone === null || $normalizedCin === null || empty($dateOfBirth)) {
            return null;
        }

        $normalizedDate = Carbon::parse($dateOfBirth)->toDateString();

        return hash('sha256', implode('|', [$normalizedPhone, $normalizedCin, $normalizedDate]));
    }

    /**
     * Generate a unique medical record number.
     * Format: MRN-YYYY-NNNN
     */
    public static function generateMedicalRecordNumber(): string
    {
        $year = Carbon::now()->year;
        $lastPatient = self::whereYear('created_at', $year)->latest('id')->first();
        $sequence = $lastPatient ? (int) substr($lastPatient->medical_record_number, -4) + 1 : 1;

        return sprintf('MRN-%d-%04d', $year, $sequence);
    }

    /**
     * Get the full name of the patient.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the age of the patient.
     */
    public function getAgeAttribute(): int
    {
        return Carbon::parse($this->date_of_birth)->age;
    }

    public function getBmiAttribute(): ?float
    {
        if (! $this->height_cm || ! $this->weight_kg) {
            return null;
        }

        $heightMeters = ((float) $this->height_cm) / 100;
        if ($heightMeters <= 0.0) {
            return null;
        }

        return round(((float) $this->weight_kg) / ($heightMeters * $heightMeters), 2);
    }

    public function getCriticalHealthAlertsAttribute(): array
    {
        $alerts = [];

        foreach (($this->allergies ?? []) as $allergy) {
            $value = trim((string) $allergy);
            if ($value !== '') {
                $alerts[] = 'Allergie: '.$value;
            }
        }

        foreach (($this->critical_conditions ?? []) as $condition) {
            $value = trim((string) $condition);
            if ($value !== '') {
                $alerts[] = 'Risque: '.$value;
            }
        }

        return array_values(array_unique($alerts));
    }

    /**
     * Get the organization this patient belongs to.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get all appointments for this patient.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(\Modules\Appointment\Models\Appointment::class);
    }

    /**
     * Get the latest appointment for this patient.
     */
    public function latestAppointment(): HasOne
    {
        return $this->hasOne(\Modules\Appointment\Models\Appointment::class)
            ->latestOfMany('appointment_date');
    }

    /**
     * Get all dental charts for this patient.
     */
    public function dentalCharts(): HasMany
    {
        return $this->hasMany(\Modules\ClinicalRecord\Models\DentalChart::class);
    }

    /**
     * Get the latest dental chart.
     */
    public function latestDentalChart(): HasOne
    {
        return $this->hasOne(\Modules\ClinicalRecord\Models\DentalChart::class)
            ->latestOfMany();
    }

    /**
     * Get all clinical procedures for this patient.
     */
    public function clinicalProcedures(): HasMany
    {
        return $this->hasMany(\Modules\ClinicalRecord\Models\ClinicalProcedure::class);
    }

    /**
     * Get all medical consultations for this patient.
     */
    public function consultations(): HasMany
    {
        return $this->hasMany(\Modules\ClinicalRecord\Models\PatientConsultation::class);
    }

    /**
     * Get all treatment plans for this patient.
     */
    public function treatmentPlans(): HasMany
    {
        return $this->hasMany(\Modules\ClinicalRecord\Models\TreatmentPlan::class);
    }

    public function portalAccesses(): HasMany
    {
        return $this->hasMany(\Modules\PatientPortal\Models\PatientPortalAccess::class);
    }

    /**
     * Get all invoices for this patient.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(\Modules\Billing\Models\Invoice::class);
    }

    /**
     * Get all sterilization traces for this patient.
     */
    public function sterilizationTraces(): HasMany
    {
        return $this->hasMany(\Modules\Logistics\Models\PatientSterilizationTrace::class);
    }

    /**
     * Get all prescriptions for this patient.
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(\Modules\ClinicalRecord\Models\Prescription::class);
    }

    public function gynecologicalHistories(): HasMany
    {
        return $this->hasMany(\Modules\Gynecology\Models\GynecologicalHistory::class);
    }

    public function pregnancyRecords(): HasMany
    {
        return $this->hasMany(\Modules\Gynecology\Models\PregnancyRecord::class);
    }

    public function activePregnancy(): HasOne
    {
        return $this->hasOne(\Modules\Gynecology\Models\PregnancyRecord::class)
            ->where('pregnancy_status', 'active')
            ->latestOfMany('lmp_date');
    }

    public function gynecologicalExams(): HasMany
    {
        return $this->hasMany(\Modules\Gynecology\Models\GynecologicalExam::class);
    }

    public function ultrasoundBiometries(): HasMany
    {
        return $this->hasMany(\Modules\Gynecology\Models\UltrasoundBiometry::class);
    }

    /**
     * Scope: Active patients only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Search patients by name or CIN.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('cin', 'like', "%{$search}%")
              ->orWhere('medical_record_number', 'like', "%{$search}%");
        });
    }

    /**
     * Check if patient has allergies.
     */
    public function hasAllergies(): bool
    {
        return ! empty($this->allergies) && count($this->allergies) > 0;
    }

    /**
     * Check if patient has specific medical history condition.
     */
    public function hasMedicalHistory(string $condition): bool
    {
        return in_array(strtolower($condition), array_map('strtolower', $this->medical_history ?? []));
    }
}
