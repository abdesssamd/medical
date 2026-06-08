<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\RIS\Jobs\SendSignedReportJob;
use Modules\RIS\Models\RisModality;
use Modules\RIS\Models\RisOrder;
use Modules\RIS\Models\RisProcedure;
use Modules\RIS\Models\RisReport;
use Tests\TestCase;

class RisModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('ris.enabled', true);
    }

    public function test_authenticated_user_can_create_ris_order(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $patient = Patient::query()->create([
            'medical_record_number' => 'MRN-2026-1001',
            'first_name' => 'Amina',
            'last_name' => 'Radiologie',
            'date_of_birth' => '1990-01-01',
            'phone' => '0600000000',
        ]);
        $modality = RisModality::query()->create([
            'name' => 'CBCT',
            'type' => RisModality::TYPE_SCANNER,
            'ae_title' => 'CBCT_AE',
            'ip_address' => '127.0.0.1',
        ]);
        $procedure = RisProcedure::query()->create([
            'code' => 'RX-CBCT',
            'label' => 'Cone Beam CT',
            'price' => 900,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('ris.exams.store'), [
                'patient_id' => $patient->id,
                'procedure_id' => $procedure->id,
                'modality_id' => $modality->id,
                'priority' => RisOrder::PRIORITY_URGENT,
                'clinical_indication' => 'Bilan pre-implantaire secteur 46',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ris_orders', [
            'patient_id' => $patient->id,
            'procedure_id' => $procedure->id,
            'modality_id' => $modality->id,
            'priority' => RisOrder::PRIORITY_URGENT,
            'status' => RisOrder::STATUS_ORDONNE,
        ]);
    }

    public function test_authenticated_user_can_save_report_and_complete_ris_order(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $patient = Patient::query()->create([
            'medical_record_number' => 'MRN-2026-1002',
            'first_name' => 'Yassine',
            'last_name' => 'Imagerie',
            'date_of_birth' => '1988-02-12',
        ]);
        $modality = RisModality::query()->create([
            'name' => 'Panoramique',
            'type' => RisModality::TYPE_PANORAMIQUE,
            'ae_title' => 'PANO_AE',
            'ip_address' => '127.0.0.1',
        ]);
        $procedure = RisProcedure::query()->create([
            'code' => 'RX-PANO',
            'label' => 'Panoramique',
            'price' => 250,
        ]);
        $order = RisOrder::query()->create([
            'patient_id' => $patient->id,
            'procedure_id' => $procedure->id,
            'modality_id' => $modality->id,
            'accession_number' => 'RIS-TEST-0001',
            'priority' => RisOrder::PRIORITY_ROUTINE,
            'requested_by_user_id' => $user->id,
            'status' => RisOrder::STATUS_IMAGES_RECUES,
            'requested_at' => now(),
        ]);

        $this
            ->actingAs($user)
            ->put(route('ris.exams.report', $order), [
                'report_text' => 'Absence de lesion osseuse evoquant une complication.',
                'signing_physician_id' => $user->id,
                'validated_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect();

        $this
            ->actingAs($user)
            ->patch(route('ris.exams.complete', $order), [
                'report_text' => 'Absence de lesion osseuse evoquant une complication.',
                'signing_physician_id' => $user->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ris_orders', [
            'id' => $order->id,
            'status' => RisOrder::STATUS_TERMINE,
        ]);

        $this->assertDatabaseHas('ris_reports', [
            'order_id' => $order->id,
            'signing_physician_id' => $user->id,
        ]);
    }

    public function test_authenticated_user_can_call_ai_analysis_endpoint(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $patient = Patient::query()->create([
            'medical_record_number' => 'MRN-2026-1003',
            'first_name' => 'Nadia',
            'last_name' => 'Dental',
            'date_of_birth' => '1992-05-04',
            'email' => 'nadia@example.test',
        ]);
        $modality = RisModality::query()->create([
            'name' => 'CBCT',
            'type' => RisModality::TYPE_SCANNER,
            'ae_title' => 'CBCT_AE',
            'ip_address' => '127.0.0.1',
        ]);
        $procedure = RisProcedure::query()->create([
            'code' => 'RX-CBCT-AI',
            'label' => 'CBCT maxillaire',
            'price' => 900,
        ]);

        $order = RisOrder::query()->create([
            'patient_id' => $patient->id,
            'procedure_id' => $procedure->id,
            'modality_id' => $modality->id,
            'accession_number' => 'RIS-TEST-0003',
            'priority' => RisOrder::PRIORITY_ROUTINE,
            'requested_by_user_id' => $user->id,
            'status' => RisOrder::STATUS_IMAGES_RECUES,
            'requested_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson(route('ris.exams.ai.analyze', $order), [
                'content' => '<p>Examen sans anomalie osseuse visible.</p>',
                'mode' => 'correction',
            ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'mode',
                'summary',
                'conclusion',
                'suggestions',
                'pre_report_html',
                'corrected_html',
                'source',
            ]);
    }

    public function test_send_report_copy_queues_signed_report_job(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'role' => 'admin',
            'email' => 'doctor@example.test',
        ]);
        $patient = Patient::query()->create([
            'medical_record_number' => 'MRN-2026-1004',
            'first_name' => 'Samir',
            'last_name' => 'Radiologie',
            'date_of_birth' => '1985-11-15',
            'email' => 'samir@example.test',
        ]);
        $modality = RisModality::query()->create([
            'name' => 'Panoramique',
            'type' => RisModality::TYPE_PANORAMIQUE,
            'ae_title' => 'PANO_AE',
            'ip_address' => '127.0.0.1',
        ]);
        $procedure = RisProcedure::query()->create([
            'code' => 'RX-PANO-SEND',
            'label' => 'Panoramique dentaire',
            'price' => 250,
        ]);

        $order = RisOrder::query()->create([
            'patient_id' => $patient->id,
            'procedure_id' => $procedure->id,
            'modality_id' => $modality->id,
            'accession_number' => 'RIS-TEST-0004',
            'priority' => RisOrder::PRIORITY_URGENT,
            'requested_by_user_id' => $user->id,
            'status' => RisOrder::STATUS_IMAGES_RECUES,
            'requested_at' => now(),
        ]);

        RisReport::query()->create([
            'order_id' => $order->id,
            'content' => '<p>Compte-rendu validé.</p>',
            'signing_physician_id' => $user->id,
            'signing_physician_name' => $user->display_name,
            'validated_at' => now(),
            'share_token' => 'tokentest'.$order->id,
            'share_url' => 'https://example.test/ris/reports/share/tokentest'.$order->id,
            'share_expires_at' => now()->addDays(7),
        ]);

        $this
            ->actingAs($user)
            ->post(route('ris.exams.send-report', $order))
            ->assertRedirect()
            ->assertSessionHas('success');

        Queue::assertPushed(SendSignedReportJob::class, function (SendSignedReportJob $job) use ($order): bool {
            return $job->orderId === (int) $order->id;
        });
    }
}
