<?php

namespace Modules\RIS\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RisEquipment extends Model
{
    use HasFactory;

    protected $table = 'ris_equipments';

    protected $fillable = [
        'name',
        'modality_id',
        'ae_title',
        'ip_address',
        'location',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function modality(): BelongsTo
    {
        return $this->belongsTo(RisModality::class, 'modality_id');
    }
}
