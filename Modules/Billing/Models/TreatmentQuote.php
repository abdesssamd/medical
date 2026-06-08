<?php

namespace Modules\Billing\Models;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TreatmentQuote extends Model
{
    use HasFactory;

    protected $fillable = [
        'treatment_plan_id',
        'patient_id',
        'practitioner_id',
        'quote_number',
        'quote_date',
        'valid_until',
        'subtotal',
        'insurance_rate',
        'insurance_amount',
        'mutual_amount',
        'patient_amount',
        'status',
        'consent_status',
        'signed_at',
        'signed_by_patient_name',
        'signature_payload',
        'notes',
    ];

    protected $casts = [
        'quote_date' => 'date',
        'valid_until' => 'date',
        'signed_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'insurance_rate' => 'decimal:2',
        'insurance_amount' => 'decimal:2',
        'mutual_amount' => 'decimal:2',
        'patient_amount' => 'decimal:2',
        'signature_payload' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(\Modules\ClinicalRecord\Models\TreatmentPlan::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TreatmentQuoteItem::class);
    }

    public static function generateQuoteNumber(): string
    {
        $prefix = 'DEV-'.now()->format('Ymd');
        $last = static::where('quote_number', 'like', $prefix.'-%')->latest('id')->first();
        $n = $last ? ((int) substr((string) $last->quote_number, -4) + 1) : 1;

        return sprintf('%s-%04d', $prefix, $n);
    }
}
