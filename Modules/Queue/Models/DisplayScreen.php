<?php

namespace Modules\Queue\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DisplayScreen extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'location',
        'video_url',
        'audio_enabled',
        'audio_order',
        'audio_repeat',
        'adhkar_enabled',
        'adhkar_text',
        'tv_primary_color',
        'tv_secondary_color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'audio_enabled' => 'boolean',
        'adhkar_enabled' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)->withTimestamps();
    }

    public function playlistItems(): HasMany
    {
        return $this->hasMany(TvPlaylistItem::class, 'display_screen_id');
    }
}

