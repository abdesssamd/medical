<?php

namespace Tests\Unit;

use App\Models\Patient;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class PatientTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_generates_medical_record_number_on_creation(): void
    {
        $patient = Patient::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => now()->subYears(30),
        ]);

        $this->assertMatchesRegularExpression('/^MRN-\d{4}-\d{4}$/', $patient->medical_record_number);
    }

    /** @test */
    public function it_generates_sequential_medical_record_numbers(): void
    {
        $patient1 = Patient::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => now()->subYears(30),
        ]);

        $patient2 = Patient::create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'date_of_birth' => now()->subYears(25),
        ]);

        $sequence1 = (int) substr($patient1->medical_record_number, -4);
        $sequence2 = (int) substr($patient2->medical_record_number, -4);

        $this->assertEquals($sequence1 + 1, $sequence2);
    }

    /** @test */
    public function it_calculates_age_correctly(): void
    {
        $patient = Patient::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => now()->subYears(35)->subMonths(6),
        ]);

        $this->assertEquals(35, $patient->age);
    }

    /** @test */
    public function it_returns_full_name(): void
    {
        $patient = Patient::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => now()->subYears(30),
        ]);

        $this->assertEquals('John Doe', $patient->full_name);
    }

    /** @test */
    public function it_scopes_active_patients(): void
    {
        $activePatient = Patient::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => now()->subYears(30),
            'is_active' => true,
        ]);

        $inactivePatient = Patient::create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'date_of_birth' => now()->subYears(25),
            'is_active' => false,
        ]);

        $activePatients = Patient::active()->get();

        $this->assertTrue($activePatients->contains('id', $activePatient->id));
        $this->assertFalse($activePatients->contains('id', $inactivePatient->id));
    }

    /** @test */
    public function it_searches_patients_by_name_or_cin(): void
    {
        Patient::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'cin' => 'ABC123',
            'date_of_birth' => now()->subYears(30),
        ]);

        Patient::create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'cin' => 'XYZ789',
            'date_of_birth' => now()->subYears(25),
        ]);

        $results = Patient::search('Doe')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('John', $results->first()->first_name);

        $results = Patient::search('ABC')->get();
        $this->assertCount(1, $results);

        $results = Patient::search('Jane')->get();
        $this->assertCount(1, $results);
    }

    /** @test */
    public function it_detects_allergies(): void
    {
        $patientWithAllergies = Patient::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => now()->subYears(30),
            'allergies' => ['pénicilline', 'latex'],
        ]);

        $patientWithoutAllergies = Patient::create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'date_of_birth' => now()->subYears(25),
            'allergies' => null,
        ]);

        $this->assertTrue($patientWithAllergies->hasAllergies());
        $this->assertFalse($patientWithoutAllergies->hasAllergies());
    }

    /** @test */
    public function it_checks_medical_history(): void
    {
        $patient = Patient::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => now()->subYears(30),
            'medical_history' => ['diabète', 'hypertension'],
        ]);

        $this->assertTrue($patient->hasMedicalHistory('diabète'));
        $this->assertTrue($patient->hasMedicalHistory('Diabète')); // Case insensitive
        $this->assertFalse($patient->hasMedicalHistory('asthme'));
    }

    /** @test */
    public function it_builds_the_same_deduplication_key_for_equivalent_identity_values(): void
    {
        $keyA = Patient::buildDeduplicationKey('06 61 23 45 67', 'ab-123', '1990-04-21');
        $keyB = Patient::buildDeduplicationKey('0661234567', 'AB-123', '1990-04-21');

        $this->assertNotNull($keyA);
        $this->assertSame($keyA, $keyB);
    }

    /** @test */
    public function it_prevents_duplicate_patients_with_same_phone_cin_and_birth_date(): void
    {
        Patient::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'cin' => 'ab-123',
            'date_of_birth' => '1990-04-21',
            'phone' => '06 61 23 45 67',
        ]);

        $this->expectException(QueryException::class);

        Patient::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'cin' => 'AB-123',
            'date_of_birth' => '1990-04-21',
            'phone' => '0661234567',
        ]);
    }
}
