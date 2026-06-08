<?php

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLineItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'procedure_id',
        'description',
        'procedure_code',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /**
     * Get the invoice this line item belongs to.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the clinical procedure linked to this line item.
     */
    public function procedure(): BelongsTo
    {
        return $this->belongsTo(\Modules\ClinicalRecord\Models\ClinicalProcedure::class, 'procedure_id');
    }

    protected static function booted(): void
    {
        static::saving(function (InvoiceLineItem $lineItem) {
            // Auto-calculate total
            $lineItem->total_price = (float) $lineItem->quantity * (float) $lineItem->unit_price;
        });
    }
}
