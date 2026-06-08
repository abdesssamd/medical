<?php

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreatmentQuoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'treatment_quote_id',
        'procedure_id',
        'code',
        'label',
        'phase_number',
        'quantity',
        'unit_price',
        'total_price',
        'insurance_coverage_rate',
        'insurance_share',
        'patient_share',
    ];

    protected $casts = [
        'phase_number' => 'integer',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'insurance_coverage_rate' => 'decimal:2',
        'insurance_share' => 'decimal:2',
        'patient_share' => 'decimal:2',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(TreatmentQuote::class, 'treatment_quote_id');
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(\Modules\ClinicalRecord\Models\ClinicalProcedure::class, 'procedure_id');
    }
}
