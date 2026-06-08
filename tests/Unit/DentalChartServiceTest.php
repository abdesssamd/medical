<?php

namespace Tests\Unit;

use App\Models\Patient;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ClinicalRecord\Models\ClinicalProcedure;
use Modules\ClinicalRecord\Models\DentalChart;
use Modules\ClinicalRecord\Services\DentalChartService;
use Tests\TestCase;

class DentalChartServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DentalChartService $dentalChartService;
    protected Patient $patient;
    protected User $practitioner;
    protected Specialty $specialty;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dentalChartService = app(DentalChartService::class);

        $this->patient = Patient::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => now()->subYears(30),
        ]);

        $this->practitioner = User::factory()->create([
            'role' => 'professional',
            'name' => 'Dr. Test',
        ]);

        $this->specialty = Specialty::create([
            'code' => 'OMNI',
            'name' => 'Omnipraticien',
            'default_color' => '#3b82f6',
            'default_duration_minutes' => 30,
        ]);
    }

    /** @test */
    public function it_creates_dental_chart_with_default_status(): void
    {
        $chart = $this->dentalChartService->createChart($this->patient->id, 'adult', $this->practitioner->id);

        $this->assertInstanceOf(DentalChart::class, $chart);
        $this->assertEquals('adult', $chart->version);
        $this->assertEquals($this->patient->id, $chart->patient_id);
        $this->assertNotEmpty($chart->teeth_status);
    }

    /** @test */
    public function it_gets_or_creates_latest_chart(): void
    {
        // First call should create
        $chart1 = $this->dentalChartService->getOrCreateLatestChart($this->patient->id, 'adult', $this->practitioner->id);
        
        // Second call should return existing
        $chart2 = $this->dentalChartService->getOrCreateLatestChart($this->patient->id, 'adult', $this->practitioner->id);

        $this->assertEquals($chart1->id, $chart2->id);
    }

    /** @test */
    public function it_updates_tooth_status(): void
    {
        $chart = $this->dentalChartService->createChart($this->patient->id, 'adult', $this->practitioner->id);

        $updatedChart = $this->dentalChartService->updateToothStatus(
            $chart,
            18,
            'extracted',
            $this->practitioner->id,
            [
                'type' => 'extraction',
                'details' => ['reason' => 'sagesse'],
            ]
        );

        $toothStatus = $updatedChart->getLatestToothStatus(18);
        $this->assertEquals('extracted', $toothStatus['status']);
        $this->assertCount(1, $toothStatus['procedures']);
        $this->assertEquals('extraction', $toothStatus['procedures'][0]['type']);
    }

    /** @test */
    public function it_records_procedure_on_tooth(): void
    {
        $procedure = $this->dentalChartService->recordProcedureOnTooth(
            $this->patient->id,
            17,
            'D2750',
            'Crown',
            1500.00,
            $this->practitioner->id,
            $this->specialty->id
        );

        $this->assertInstanceOf(ClinicalProcedure::class, $procedure);
        $this->assertEquals(17, $procedure->tooth_number);
        $this->assertEquals('D2750', $procedure->procedure_code);
        $this->assertEquals(1500.00, $procedure->price);

        // Verify chart was updated
        $chart = $this->dentalChartService->getOrCreateLatestChart($this->patient->id);
        $toothStatus = $chart->getLatestToothStatus(17);
        $this->assertEquals('crown', $toothStatus['status']);
    }

    /** @test */
    public function it_gets_dental_history(): void
    {
        // Create some procedures
        $this->dentalChartService->recordProcedureOnTooth(
            $this->patient->id,
            18,
            'D7140',
            'Extraction',
            500.00,
            $this->practitioner->id,
            $this->specialty->id
        );

        $this->dentalChartService->recordProcedureOnTooth(
            $this->patient->id,
            17,
            'D2750',
            'Crown',
            1500.00,
            $this->practitioner->id,
            $this->specialty->id
        );

        $history = $this->dentalChartService->getDentalHistory($this->patient->id);

        $this->assertArrayHasKey('chart', $history);
        $this->assertArrayHasKey('teeth_summary', $history);
        $this->assertArrayHasKey('procedures', $history);
        $this->assertGreaterThanOrEqual(2, count($history['procedures']));
    }

    /** @test */
    public function it_calculates_estimated_cost_for_planned_procedures(): void
    {
        ClinicalProcedure::create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'specialty_id' => $this->specialty->id,
            'tooth_number' => 16,
            'procedure_code' => 'D2750',
            'name' => 'Crown',
            'price' => 1500.00,
            'status' => ClinicalProcedure::STATUS_PLANNED,
        ]);

        ClinicalProcedure::create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'specialty_id' => $this->specialty->id,
            'tooth_number' => 15,
            'procedure_code' => 'D2140',
            'name' => 'Filling',
            'price' => 800.00,
            'status' => ClinicalProcedure::STATUS_PLANNED,
        ]);

        // Already completed procedure
        ClinicalProcedure::create([
            'patient_id' => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'specialty_id' => $this->specialty->id,
            'tooth_number' => 14,
            'procedure_code' => 'D1110',
            'name' => 'Detartrage',
            'price' => 300.00,
            'status' => ClinicalProcedure::STATUS_COMPLETED,
        ]);

        $estimatedCost = $this->dentalChartService->getEstimatedCostForPlannedProcedures($this->patient->id);

        $this->assertEquals(2300.00, $estimatedCost);
    }

    /** @test */
    public function it_exports_chart_data(): void
    {
        $chart = $this->dentalChartService->createChart($this->patient->id, 'adult', $this->practitioner->id);

        $exportData = $this->dentalChartService->exportChartAsArray($chart);

        $this->assertEquals($this->patient->full_name, $exportData['patient_name']);
        $this->assertEquals($this->patient->medical_record_number, $exportData['patient_mrn']);
        $this->assertArrayHasKey('teeth_summary', $exportData);
        $this->assertArrayHasKey('full_chart_data', $exportData);
    }
}
