<?php

namespace Modules\RIS\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RisModality extends Model
{
    use HasFactory;

    public const TYPES = [
        'DX' => 'Radiographie Numérique',
        'CR' => 'Radiologie Calculée',
        'CT' => 'Scanner / TDM',
        'MR' => 'IRM',
        'US' => 'Échographie / Doppler',
        'MG' => 'Mammographie',
        'XA' => 'Angiographie / Radiologie Interventionnelle',
        'PX' => 'Panoramique Dentaire',
    ];

    protected $table = 'ris_modalities';

    protected $fillable = [
        'name',
        'type',
        'description',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(RisOrder::class, 'modality_id');
    }

    public function equipments(): HasMany
    {
        return $this->hasMany(RisEquipment::class, 'modality_id');
    }
}
