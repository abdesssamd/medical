<?php

namespace Modules\Scheduling\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AvailabilityBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'practitioner_id',
        'room_id',
        'appointment_type_id',
        'date',
        'start_time',
        'end_time',
        'type',
        'label',
        'max_patients',
        'is_booked',
    ];

    protected $casts = [
        'date' => 'date',
        'is_booked' => 'boolean',
    ];

    /**
     * Block types.
     */
    const TYPE_AVAILABLE = 'available';
    const TYPE_BREAK = 'break';
    const TYPE_FORMATION = 'formation';
    const TYPE_ABSENCE = 'absence';

    /**
     * Get the practitioner this block belongs to.
     */
    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'practitioner_id');
    }

    /**
     * Get the room this block is in.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Room::class);
    }

    /**
     * Get the appointment type for this block.
     */
    public function appointmentType(): BelongsTo
    {
        return $this->belongsTo(AppointmentType::class);
    }

    /**
     * Scope: Filter by date.
     */
    public function scopeForDate($query, Carbon|string $date)
    {
        return $query->where('date', $date instanceof Carbon ? $date->toDateString() : $date);
    }

    /**
     * Scope: Filter by date range.
     */
    public function scopeBetweenDates($query, Carbon|string $from, Carbon|string $to)
    {
        return $query->whereBetween('date', [
            $from instanceof Carbon ? $from->toDateString() : $from,
            $to instanceof Carbon ? $to->toDateString() : $to,
        ]);
    }

    /**
     * Scope: Available blocks only.
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_booked', false)
            ->where('type', self::TYPE_AVAILABLE);
    }

    /**
     * Scope: Filter by practitioner.
     */
    public function scopeForPractitioner($query, int $practitionerId)
    {
        return $query->where('practitioner_id', $practitionerId);
    }

    /**
     * Scope: Filter by room.
     */
    public function scopeForRoom($query, int $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    /**
     * Scope: Filter by appointment type.
     */
    public function scopeForAppointmentType($query, int $appointmentTypeId)
    {
        return $query->where('appointment_type_id', $appointmentTypeId);
    }

    /**
     * Scope: Filter by block type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Check if block is available for booking.
     */
    public function isAvailable(): bool
    {
        return $this->type === self::TYPE_AVAILABLE && ! $this->is_booked;
    }

    /**
     * Mark block as booked.
     */
    public function markAsBooked(): void
    {
        $this->update(['is_booked' => true]);
    }

    /**
     * Mark block as available again.
     */
    public function markAsAvailable(): void
    {
        $this->update(['is_booked' => false]);
    }

    /**
     * Get the duration in minutes.
     */
    public function getDurationMinutesAttribute(): int
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        return $start->diffInMinutes($end);
    }

    /**
     * Check if block overlaps with another time range.
     */
    public function overlapsWith(string $startTime, string $endTime): bool
    {
        return $this->start_time < $endTime && $this->end_time > $startTime;
    }

    /**
     * Create recurring blocks for a date range.
     */
    public static function createRecurringBlocks(
        int $practitionerId,
        string $startTime,
        string $endTime,
        Carbon $fromDate,
        Carbon $toDate,
        array $excludeDays = [],
        ?int $roomId = null,
        ?int $appointmentTypeId = null,
        string $type = self::TYPE_AVAILABLE,
        ?int $maxPatients = null
    ): void {
        $currentDate = $fromDate->copy();
        $blocks = [];

        while ($currentDate->lte($toDate)) {
            // Skip excluded days (e.g., weekends)
            if (in_array($currentDate->dayOfWeek, $excludeDays)) {
                $currentDate->addDay();
                continue;
            }

            $blocks[] = [
                'practitioner_id' => $practitionerId,
                'room_id' => $roomId,
                'appointment_type_id' => $appointmentTypeId,
                'date' => $currentDate->toDateString(),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'type' => $type,
                'max_patients' => $maxPatients,
                'is_booked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $currentDate->addDay();
        }

        if (! empty($blocks)) {
            self::insert($blocks);
        }
    }
}
