<?php

namespace Modules\Gynecology\Models;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GynecologicalHistory extends Model
{
    protected $fillable = [
        'patient_id',
        'practitioner_id',
        'consultation_id',
        'gestity',
        'parity',
        'abortions',
        'living_children',
        'cesarean_sections',
        'ectopic_pregnancies',
        'menarche_age',
        'menopause_age',
        'cycle_duration_days',
        'menstruation_duration_days',
        'cycle_regularity',
        'contraception_method',
        'last_menstrual_period',
        'last_fcv_date',
        'last_fcv_result',
        'family_history_cancers',
        'gynecological_conditions',
        'obstetric_complications_history',
        'notes',
    ];

    protected $casts = [
        'last_menstrual_period' => 'date',
        'last_fcv_date' => 'date',
        'family_history_cancers' => 'array',
        'gynecological_conditions' => 'array',
        'obstetric_complications_history' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    public function pregnancyRecords(): HasMany
    {
        return $this->hasMany(PregnancyRecord::class);
    }

    public function getGpaFormulaAttribute(): string
    {
        return "G{$this->gestity} P{$this->parity} A{$this->abortions} V{$this->living_children}";
    }
}
