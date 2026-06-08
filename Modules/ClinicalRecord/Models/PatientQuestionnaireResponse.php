<?php

namespace Modules\ClinicalRecord\Models;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ClinicalRecord\Models\PatientConsultation;

class PatientQuestionnaireResponse extends Model
{
    use HasFactory;

    protected $table = 'patient_responses';

    protected $fillable = [
        'questionnaire_id',
        'patient_id',
        'consultation_id',
        'practitioner_id',
        'answers',
        'answered_at',
        'notes',
        'source',
    ];

    protected $casts = [
        'answers' => 'array',
        'answered_at' => 'datetime',
    ];

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(PatientConsultation::class, 'consultation_id');
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }
}
