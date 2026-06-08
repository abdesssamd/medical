<?php

namespace Modules\ClinicalRecord\Models;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'consultation_id',
        'practitioner_id',
        'prescription_template_id',
        'prescription_number',
        'issued_at',
        'status',
        'qr_token',
        'signature_mode',
        'signature_payload',
        'safety_alerts',
        'immutable_payload',
        'sent_to_email',
        'sent_at',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'signature_payload' => 'array',
        'safety_alerts' => 'array',
        'immutable_payload' => 'array',
        'sent_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(PatientConsultation::class, 'consultation_id');
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PrescriptionTemplate::class, 'prescription_template_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    public static function generateNumber(): string
    {
        $prefix = 'RX-'.now()->format('Ymd');
        $last = self::where('prescription_number', 'like', $prefix.'-%')->latest('id')->first();
        $n = $last ? ((int) substr((string) $last->prescription_number, -4) + 1) : 1;

        return sprintf('%s-%04d', $prefix, $n);
    }
}
