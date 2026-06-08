<?php

namespace Modules\Billing\Models;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsuranceCompany extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'contact_phone',
        'contact_email',
        'address',
        'coverage_rules',
        'is_active',
    ];

    protected $casts = [
        'coverage_rules' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get all patient subscriptions for this insurance.
     */
    public function patientSubscriptions(): HasMany
    {
        return $this->hasMany(PatientInsuranceSubscription::class);
    }

    /**
     * Get all insurance claims.
     */
    public function claims(): HasMany
    {
        return $this->hasMany(InsuranceClaim::class);
    }

    /**
     * Get coverage percentage for a specific procedure.
     */
    public function getCoverageForProcedure(string $procedureCode): float
    {
        return (float) ($this->coverage_rules[$procedureCode] ?? 0);
    }

    /**
     * Scope: Active insurance companies only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Search by name or code.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%");
        });
    }
}
