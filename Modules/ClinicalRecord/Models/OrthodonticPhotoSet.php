<?php

namespace Modules\ClinicalRecord\Models;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrthodonticPhotoSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'created_by',
        'label',
        'captured_on',
        'before_image_path',
        'after_image_path',
        'notes',
    ];

    protected $casts = [
        'captured_on' => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
