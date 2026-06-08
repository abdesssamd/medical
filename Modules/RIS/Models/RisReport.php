<?php

namespace Modules\RIS\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RisReport extends Model
{
    use HasFactory;

    protected $table = 'ris_reports';

    protected $fillable = [
        'order_id',
        'content',
        'signing_physician_id',
        'signing_physician_name',
        'validated_at',
        'pdf_path',
        'share_token',
        'share_url',
        'share_expires_at',
    ];

    protected $casts = [
        'validated_at' => 'datetime',
        'share_expires_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(RisOrder::class, 'order_id');
    }

    public function signingPhysician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signing_physician_id');
    }
}
