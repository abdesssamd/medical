<?php

namespace Modules\Queue\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TvPlaylistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'display_screen_id',
        'title',
        'type',
        'media_url',
        'message',
        'start_time',
        'end_time',
        'days',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function screen(): BelongsTo
    {
        return $this->belongsTo(DisplayScreen::class, 'display_screen_id');
    }

    public function activeOnWeekday(int $weekday): bool
    {
        if (! $this->days) {
            return true;
        }

        $days = collect(explode(',', (string) $this->days))
            ->map(fn (string $d): int => (int) trim($d))
            ->filter(fn (int $d): bool => $d >= 1 && $d <= 7)
            ->all();

        return in_array($weekday, $days, true);
    }
}

