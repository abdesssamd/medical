<?php

namespace Modules\Billing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeletransmissionBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_number',
        'created_by',
        'generated_on',
        'status',
        'invoice_count',
        'total_amount',
        'payload',
    ];

    protected $casts = [
        'generated_on' => 'date',
        'invoice_count' => 'integer',
        'total_amount' => 'decimal:2',
        'payload' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
