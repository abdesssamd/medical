<?php

namespace Tests\Unit;

use App\Models\Patient;
use App\Models\Specialty;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Appointment\Models\Appointment;
use Modules\Appointment\Models\Planning;
use Modules\Scheduling\Models\AvailabilityBlock;
use Modules\Scheduling\Services\AvailabilityService;
use Tests\TestCase;

class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AvailabilityService $availabilityService;
    protected User $practitioner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->availabilityService = app(AvailabilityService::class);
        
        // Create a test practitioner
        $this->practitioner = User::factory()->create([
            'role' => 'professional',
            'name' => 'Dr. Test Practitioner',
        ]);

        // Create a planning for Monday (day 1)
        Planning::create([
            'professional_id' => $this->practitioner->id,
            'day_of_week' => 1, // Monday
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'consultation_minutes' => 30,
            'max_patients_per_day' => 10,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_returns_not_available_for_non_working_day(): void
    {
        // Sunday (day 0) - no planning configured
        $sunday = Carbon::create(2026, 4, 19); // A Sunday

        $result = $this->availabilityService->getAvailability($this->practitioner->id, $sunday);

        $this->assertFalse($result['available']);
        $this->assertEquals('not_working_day', $result['reason']);
        $this->assertEmpty($result['slots']);
    }

    /** @test */
    public function it_generates_available_slots_for_working_day(): void
    {
        // Next Monday
        $monday = Carbon::create(2026, 4, 20); // A Monday

        $result = $this->availabilityService->getAvailability($this->practitioner->id, $monday);

        $this->assertTrue($result['available']);
        $this->assertNotEmpty($result['slots']);
        $this->assertEquals(10, $result['max_patients_per_day']);
        $this->assertEquals(0, $result['booked_count']);
        
        // Check first slot starts at 09:00
        $this->assertEquals('09:00:00', $result['slots'][0]['start_time']);
    }

    /** @test */
    public function it_marks_slots_as_booked_when_appointment_exists(): void
    {
        $monday = Carbon::create(2026, 4, 20);

        // Create an existing appointment at 09:00
        Appointment::create([
            'professional_id' => $this->practitioner->id,
            'patient_name' => 'Test Patient',
            'appointment_date' => $monday->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '09:30:00',
            'status' => 'booked',
        ]);

        $result = $this->availabilityService->getAvailability($this->practitioner->id, $monday);

        $this->assertTrue($result['available']);
        $this->assertEquals(1, $result['booked_count']);
        
        // 09:00 slot should not be available
        $this->assertFalse(
            collect($result['slots'])->contains('start_time', '09:00:00')
        );
    }

    /** @test */
    public function it_returns_quota_reached_when_max_patients_exceeded(): void
    {
        $monday = Carbon::create(2026, 4, 20);

        // Create 10 appointments (max_patients_per_day = 10)
        for ($i = 0; $i < 10; $i++) {
            $hour = 9 + $i;
            Appointment::create([
                'professional_id' => $this->practitioner->id,
                'patient_name' => "Patient {$i}",
                'appointment_date' => $monday->toDateString(),
                'start_time' => sprintf('%02d:00:00', $hour),
                'end_time' => sprintf('%02d:30:00', $hour),
                'status' => 'booked',
            ]);
        }

        $result = $this->availabilityService->getAvailability($this->practitioner->id, $monday);

        $this->assertFalse($result['available']);
        $this->assertEquals('quota_reached', $result['reason']);
        $this->assertTrue($result['quota_reached']);
        $this->assertEmpty($result['slots']);
    }

    /** @test */
    public function it_ensures_slot_is_available_throws_exception_when_unavailable(): void
    {
        $sunday = Carbon::create(2026, 4, 19); // Non-working day

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->availabilityService->ensureSlotIsAvailable(
            $this->practitioner->id,
            $sunday,
            '10:00:00'
        );
    }

    /** @test */
    public function it_finds_first_available_slot(): void
    {
        $monday = Carbon::create(2026, 4, 20);

        $slot = $this->availabilityService->getFirstAvailableSlot($this->practitioner->id, $monday);

        $this->assertNotNull($slot);
        $this->assertEquals('09:00:00', $slot['start_time']);
    }
}
