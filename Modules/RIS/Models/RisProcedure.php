<?php

namespace Modules\RIS\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RisProcedure extends Model
{
    use HasFactory;

    protected $table = 'ris_procedures';

    protected $fillable = [
        'code',
        'label',
        'price',
        'modality_type',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(RisOrder::class, 'procedure_id');
    }
}
