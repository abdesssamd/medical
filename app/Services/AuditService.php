<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AuditService
{
    /**
     * Log a model creation.
     */
    public function logCreated(Model $model, ?string $resourceType = null, ?array $values = null): AuditLog
    {
        return AuditLog::logCreated($model, $resourceType, $values);
    }

    /**
     * Log a model update with only changed fields.
     */
    public function logUpdated(Model $model, ?string $resourceType = null): ?AuditLog
    {
        $changes = $model->getChanges();

        if (empty($changes)) {
            return null;
        }

        return AuditLog::logUpdated(
            $model,
            $resourceType,
            $model->getOriginal(),
            $changes
        );
    }

    /**
     * Log a model deletion.
     */
    public function logDeleted(Model $model, ?string $resourceType = null): AuditLog
    {
        return AuditLog::logDeleted($model, $resourceType);
    }

    /**
     * Log a model view/access.
     */
    public function logViewed(Model $model, ?string $resourceType = null, ?string $description = null): AuditLog
    {
        return AuditLog::logViewed($model, $resourceType, $description);
    }

    /**
     * Log an export action.
     */
    public function logExported(Model $model, ?string $resourceType = null): AuditLog
    {
        return AuditLog::logExported($model, $resourceType);
    }

    /**
     * Log a custom action.
     */
    public function logCustom(
        string $action,
        Model $model,
        ?string $resourceType = null,
        ?int $resourceId = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): AuditLog {
        return AuditLog::log(
            $action,
            get_class($model),
            $model->id,
            $resourceType,
            $resourceId,
            $description,
            $oldValues,
            $newValues
        );
    }

    /**
     * Get audit trail for a specific model.
     */
    public function getAuditTrailForModel(Model $model): Collection
    {
        return AuditLog::forModel(get_class($model), $model->id)
            ->with('user')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get audit trail for a specific user.
     */
    public function getAuditTrailForUser(User $user, ?string $fromDate = null, ?string $toDate = null): Collection
    {
        $query = AuditLog::byUser($user->id)->with('model');

        if ($fromDate && $toDate) {
            $query->betweenDates($fromDate, $toDate);
        }

        return $query->orderByDesc('created_at')->get();
    }

    /**
     * Get audit trail for a resource type.
     */
    public function getAuditTrailForResource(string $resourceType, ?int $resourceId = null): Collection
    {
        $query = AuditLog::forResourceType($resourceType);

        if ($resourceId) {
            $query->where('resource_id', $resourceId);
        }

        return $query->with('user')->orderByDesc('created_at')->get();
    }

    /**
     * Get suspicious activity (multiple deletions, access outside hours, etc.)
     */
    public function getSuspiciousActivity(string $fromDate, string $toDate): Collection
    {
        return AuditLog::betweenDates($fromDate, $toDate)
            ->whereIn('action', [AuditLog::ACTION_DELETED, AuditLog::ACTION_EXPORTED])
            ->with('user')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get audit statistics for a date range.
     */
    public function getAuditStatistics(string $fromDate, string $toDate): array
    {
        $logs = AuditLog::betweenDates($fromDate, $toDate);

        return [
            'total_logs' => (clone $logs)->count(),
            'by_action' => (clone $logs)->groupBy('action')->map->count(),
            'by_user' => (clone $logs)->groupBy('user_id')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'user_name' => $group->first()?->user?->name,
                ];
            }),
            'by_resource_type' => (clone $logs)->groupBy('resource_type')->map->count(),
        ];
    }

    /**
     * Clean old audit logs (for compliance/retention policies).
     * Default: keep logs for 7 years (2555 days) as per medical standards.
     */
    public function cleanOldLogs(int $retentionDays = 2555): int
    {
        $cutoffDate = now()->subDays($retentionDays)->toDateString();

        return AuditLog::where('created_at', '<', $cutoffDate)->delete();
    }
}
