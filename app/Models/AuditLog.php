<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'resource_type',
        'resource_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'session_id',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Audit action types.
     */
    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_DELETED = 'deleted';
    public const ACTION_VIEWED = 'viewed';
    public const ACTION_EXPORTED = 'exported';
    public const ACTION_PRINTED = 'printed';
    public const ACTION_ACCESSED = 'accessed';

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the model that was affected.
     */
    public function model(): BelongsTo
    {
        return $this->morphTo();
    }

    /**
     * Scope: Filter by action.
     */
    public function scopeAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: Filter by user.
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter by model type.
     */
    public function scopeForModelType(Builder $query, string $modelType): Builder
    {
        return $query->where('model_type', $modelType);
    }

    /**
     * Scope: Filter by resource type.
     */
    public function scopeForResourceType(Builder $query, string $resourceType): Builder
    {
        return $query->where('resource_type', $resourceType);
    }

    /**
     * Scope: Filter by date range.
     */
    public function scopeBetweenDates(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope: Filter by specific user action on a model.
     */
    public function scopeForModel(Builder $query, string $modelType, int $modelId): Builder
    {
        return $query->where('model_type', $modelType)
            ->where('model_id', $modelId);
    }

    /**
     * Create an audit log entry automatically.
     */
    public static function log(
        string $action,
        string $modelType,
        int $modelId,
        ?string $resourceType = null,
        ?int $resourceId = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): self {
        return self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'session_id' => session()->getId(),
        ]);
    }

    /**
     * Log a model creation.
     */
    public static function logCreated(Model $model, ?string $resourceType = null, ?array $values = null): self
    {
        return self::log(
            self::ACTION_CREATED,
            get_class($model),
            $model->id,
            $resourceType ?? self::guessResourceType($model),
            null,
            self::ACTION_CREATED.' '.class_basename($model).' #'.$model->id,
            null,
            $values ?? $model->getAttributes()
        );
    }

    /**
     * Log a model update.
     */
    public static function logUpdated(Model $model, ?string $resourceType = null, ?array $oldValues = null, ?array $newValues = null): self
    {
        return self::log(
            self::ACTION_UPDATED,
            get_class($model),
            $model->id,
            $resourceType ?? self::guessResourceType($model),
            null,
            self::ACTION_UPDATED.' '.class_basename($model).' #'.$model->id,
            $oldValues ?? $model->getOriginal(),
            $newValues ?? $model->getAttributes()
        );
    }

    /**
     * Log a model deletion.
     */
    public static function logDeleted(Model $model, ?string $resourceType = null, ?array $values = null): self
    {
        return self::log(
            self::ACTION_DELETED,
            get_class($model),
            $model->id,
            $resourceType ?? self::guessResourceType($model),
            null,
            self::ACTION_DELETED.' '.class_basename($model).' #'.$model->id,
            $values ?? $model->getAttributes(),
            null
        );
    }

    /**
     * Log a model view/access.
     */
    public static function logViewed(Model $model, ?string $resourceType = null, ?string $description = null): self
    {
        return self::log(
            self::ACTION_VIEWED,
            get_class($model),
            $model->id,
            $resourceType ?? self::guessResourceType($model),
            null,
            $description ?? 'Viewed '.class_basename($model).' #'.$model->id
        );
    }

    /**
     * Log an export action.
     */
    public static function logExported(Model $model, ?string $resourceType = null): self
    {
        return self::log(
            self::ACTION_EXPORTED,
            get_class($model),
            $model->id,
            $resourceType ?? self::guessResourceType($model),
            null,
            'Exported '.class_basename($model).' #'.$model->id
        );
    }

    /**
     * Guess the resource type from the model.
     */
    private static function guessResourceType(Model $model): string
    {
        $class = class_basename($model);

        $mapping = [
            'Patient' => 'patient',
            'Appointment' => 'appointment',
            'DentalChart' => 'clinical_record',
            'ClinicalProcedure' => 'clinical_record',
            'PatientConsultation' => 'clinical_record',
            'TreatmentPlan' => 'clinical_record',
            'Invoice' => 'invoice',
            'Payment' => 'payment',
            'Ticket' => 'queue_ticket',
        ];

        return $mapping[$class] ?? strtolower($class);
    }
}
