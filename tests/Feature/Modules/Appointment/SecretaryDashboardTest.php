<?php

namespace Tests\Feature\Modules\Appointment;

use App\Models\User;
use Modules\Appointment\Models\Appointment;
use Modules\Appointment\Models\SecretaryTask;
use Modules\Appointment\Models\SecretaryNote;
use Modules\Appointment\Services\SecretaryDashboardService;
use Modules\Appointment\Services\SecretaryNoteService;
use Tests\TestCase;

class SecretaryDashboardTest extends TestCase
{
    private SecretaryDashboardService $dashboardService;
    private SecretaryNoteService $noteService;
    private User $secretary;
    private Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dashboardService = app(SecretaryDashboardService::class);
        $this->noteService = app(SecretaryNoteService::class);
        
        $this->secretary = User::factory()->create(['role' => 'secretary']);
        $this->appointment = Appointment::factory()->create();
    }

    public function test_dashboard_aggregates_appointments()
    {
        // Créer plusieurs rendez-vous
        Appointment::factory(3)->create(['appointment_date' => today()]);

        $data = $this->dashboardService->getDashboardData(today()->toDateString());

        $this->assertGreaterThanOrEqual(3, $data['total']);
        $this->assertArrayHasKey('kpis', $data);
        $this->assertArrayHasKey('items', $data);
    }

    public function test_dashboard_calculates_kpis()
    {
        $appointment = Appointment::factory()->create(['appointment_date' => today()]);
        SecretaryTask::factory()->create([
            'appointment_id' => $appointment->id,
            'status' => 'open',
        ]);

        $data = $this->dashboardService->getDashboardData(today()->toDateString());

        $this->assertGreaterThan(0, $data['kpis']['incomplete_files_count']);
        $this->assertEquals(100, $data['kpis']['incomplete_files_percent']);
    }

    public function test_secretary_can_create_note()
    {
        $note = $this->noteService->createNote(
            $this->appointment,
            SecretaryNote::TAG_DOCUMENT_MISSING,
            'Document d\'identité manquant',
            $this->secretary,
            SecretaryNote::PRIORITY_CRITICAL
        );

        $this->assertDatabaseHas('secretary_notes', [
            'appointment_id' => $this->appointment->id,
            'tag' => SecretaryNote::TAG_DOCUMENT_MISSING,
        ]);

        // Vérifier création tâche associée
        $this->assertDatabaseHas('secretary_tasks', [
            'appointment_id' => $this->appointment->id,
        ]);
    }

    public function test_secretary_can_mark_note_as_read()
    {
        $note = SecretaryNote::factory()->create();
        $this->assertNull($note->read_at);

        $this->noteService->markAsRead($note);

        $this->assertNotNull($note->fresh()->read_at);
    }

    public function test_unread_notes_retrieved_for_professional()
    {
        $professional = User::factory()->create(['role' => 'professional']);
        $appointment = Appointment::factory()->create([
            'professional_id' => $professional->id,
        ]);

        SecretaryNote::factory()->create([
            'appointment_id' => $appointment->id,
            'read_at' => null,
        ]);

        $unread = $this->noteService->getUnreadNotesForPractitioner($professional);

        $this->assertGreaterThan(0, $unread->count());
    }
}
