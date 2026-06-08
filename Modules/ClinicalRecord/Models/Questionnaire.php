<?php

namespace Modules\ClinicalRecord\Models;

use App\Models\Patient;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Questionnaire extends Model
{
    use HasFactory;

    protected $table = 'questionnaires';

    protected $fillable = [
        'specialty_id',
        'practitioner_id',
        'group_name',
        'created_by',
        'name',
        'description',
        'field_schema',
        'is_active',
    ];

    protected $casts = [
        'field_schema' => 'array',
        'is_active' => 'boolean',
    ];

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(PatientQuestionnaireResponse::class, 'questionnaire_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForPatient($query, Patient $patient)
    {
        return $query->where(function ($builder) use ($patient): void {
            $builder->whereNull('specialty_id')
                ->orWhereIn('specialty_id', $patient->consultations()->pluck('specialty_id')->filter()->unique()->all());
        });
    }
}
