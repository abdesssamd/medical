<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuditService $auditService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auditService = app(AuditService::class);
        
        $this->user = User::factory()->create([
            'role' => 'professional',
            'name' => 'Dr. Test',
        ]);
    }

    /** @test */
    public function it_logs_model_creation(): void
    {
        $patient = Patient::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => now()->subYears(30),
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'model_type' => Patient::class,
            'model_id' => $patient->id,
            'resource_type' => 'patient',
        ]);
    }

    /** @test */
    public function it_logs_model_update(): void
    {
        $patient = Patient::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => now()->subYears(30),
            'phone' => '0661234567',
        ]);

        $patient->update(['phone' => '0667654321']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'updated',
            'model_type' => Patient::class,
            'model_id' => $patient->id,
            'resource_type' => 'patient',
        ]);
    }

    /** @test */
    public function it_logs_model_deletion(): void
    {
        $patient = Patient::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => now()->subYears(30),
        ]);

        $patientId = $patient->id;
        $patient->delete();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'deleted',
            'model_type' => Patient::class,
            'model_id' => $patientId,
            'resource_type' => 'patient',
        ]);
    }

    /** @test */
    public function it_gets_audit_trail_for_model(): void
    {
        $patient = Patient::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => now()->subYears(30),
        ]);

        $patient->update(['phone' => '0661234567']);

        $trail = $this->auditService->getAuditTrailForModel($patient);

        $this->assertCount(2, $trail); // created + updated
        $this->assertEquals('updated', $trail->first()->action);
        $this->assertEquals('created', $trail->last()->action);
    }

    /** @test */
    public function it_gets_audit_trail_for_user(): void
    {
        actingAs($this->user);

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

        $trail = $this->auditService->getAuditTrailForUser($this->user);

        $this->assertGreaterThanOrEqual(2, $trail->count());
    }

    /** @test */
    public function it_gets_audit_trail_for_resource_type(): void
    {
        $patient = Patient::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => now()->subYears(30),
        ]);

        $trail = $this->auditService->getAuditTrailForResource('patient');

        $this->assertNotEmpty($trail);
        $this->assertEquals('patient', $trail->first()->resource_type);
    }

    /** @test */
    public function it_logs_custom_action(): void
    {
        $patient = Patient::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => now()->subYears(30),
        ]);

        $this->auditService->logCustom(
            'exported_to_pdf',
            $patient,
            'patient',
            $patient->id,
            'Exported patient record to PDF',
            null,
            ['format' => 'pdf']
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'exported_to_pdf',
            'model_type' => Patient::class,
            'model_id' => $patient->id,
            'resource_type' => 'patient',
        ]);
    }

    /** @test */
    public function it_gets_audit_statistics(): void
    {
        $fromDate = now()->subDays(7)->toDateString();
        $toDate = now()->toDateString();

        actingAs($this->user);

        Patient::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => now()->subYears(30),
        ]);

        $stats = $this->auditService->getAuditStatistics($fromDate, $toDate);

        $this->assertArrayHasKey('total_logs', $stats);
        $this->assertArrayHasKey('by_action', $stats);
        $this->assertGreaterThanOrEqual(1, $stats['total_logs']);
    }
}
