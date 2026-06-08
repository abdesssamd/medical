<?php

namespace Modules\RIS\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RisModality extends Model
{
    use HasFactory;

    public const TYPE_RADIO = 'radio';
    public const TYPE_SCANNER = 'scanner';
    public const TYPE_PANORAMIQUE = 'panoramique';

    protected $table = 'ris_modalities';

    protected $fillable = [
        'name',
        'type',
        'ae_title',
        'ip_address',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(RisOrder::class, 'modality_id');
    }
}
