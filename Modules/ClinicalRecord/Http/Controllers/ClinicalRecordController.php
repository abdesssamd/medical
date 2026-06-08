<?php

namespace Modules\ClinicalRecord\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\ClinicalRecord\Models\ClinicalProcedure;
use Modules\ClinicalRecord\Models\DentalChart;
use Modules\ClinicalRecord\Models\MedicalImage;
use Modules\ClinicalRecord\Models\TreatmentPlan;
use Modules\ClinicalRecord\Services\DentalChartService;
use Modules\ClinicalRecord\Services\TreatmentPlanService;

class ClinicalRecordController extends Controller
{
    public function __construct(
        private readonly DentalChartService $dentalChartService,
        private readonly TreatmentPlanService $treatmentPlanService
    ) {}

    /**
     * Liste des patients avec dossiers cliniques.
     */
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $patients = Patient::query()
            ->when($search, fn ($q) => $q->search($search))
            ->withCount(['appointments', 'clinicalProcedures'])
            ->orderBy('last_name')
            ->paginate(20);

        return view('clinical_record::patients.index', compact('patients', 'search'));
    }

    /**
     * Dossier clinique complet d'un patient.
     */
    public function show(int $patientId): View
    {
        $patient = Patient::with(['organization', 'latestAppointment'])->findOrFail($patientId);
        
        $dentalHistory = $this->dentalChartService->getDentalHistory($patientId);
        $chart = $dentalHistory['chart'];
        $teethSummary = $dentalHistory['teeth_summary'];
        $procedures = $dentalHistory['procedures'];

        $treatmentPlans = TreatmentPlan::forPatient($patientId)
            ->with(['practitioner'])
            ->orderByDesc('created_at')
            ->get();

        $medicalImages = MedicalImage::forPatient($patientId)
            ->recentFirst()
            ->limit(10)
            ->get();

        $estimatedCost = $this->dentalChartService->getEstimatedCostForPlannedProcedures($patientId);

        return view('clinical_record::patient-show', compact(
            'patient',
            'chart',
            'teethSummary',
            'procedures',
            'treatmentPlans',
            'medicalImages',
            'estimatedCost'
        ));
    }

    /**
     * Afficher/modifier l'odontogramme.
     */
    public function dentalChart(int $patientId): View
    {
        $patient = Patient::findOrFail($patientId);
        $chart = $this->dentalChartService->getOrCreateLatestChart($patientId);
        
        return view('clinical_record::dental-chart', compact('patient', 'chart'));
    }

    /**
     * Mettre à jour le statut d'une dent.
     */
    public function updateToothStatus(Request $request, int $patientId, int $toothNumber): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:'.implode(',', DentalChart::STATUSES),
            'procedure_type' => 'nullable|string',
            'details' => 'nullable|array',
        ]);

        $chart = $this->dentalChartService->getOrCreateLatestChart($patientId);
        
        $updatedChart = $this->dentalChartService->updateToothStatus(
            $chart,
            $toothNumber,
            $validated['status'],
            auth()->id(),
            !empty($validated['procedure_type']) ? [
                'type' => $validated['procedure_type'],
                'details' => $validated['details'] ?? [],
            ] : null
        );

        return response()->json([
            'success' => true,
            'chart' => $updatedChart,
        ]);
    }

    /**
     * Enregistrer un acte clinique.
     */
    public function storeProcedure(Request $request, int $patientId): RedirectResponse
    {
        $validated = $request->validate([
            'appointment_id' => 'nullable|exists:appointments,id',
            'practitioner_id' => 'required|exists:users,id',
            'specialty_id' => 'required|exists:specialties,id',
            'tooth_number' => 'nullable|integer|between:11,88',
            'procedure_code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'tooth_surfaces' => 'nullable|array',
            'materials_used' => 'nullable|array',
            'status' => 'nullable|string|in:planned,in_progress,completed,cancelled',
            'planned_date' => 'nullable|date',
        ]);

        $this->dentalChartService->recordProcedureOnTooth(
            patientId: $patientId,
            toothNumber: $validated['tooth_number'] ?? 0,
            procedureCode: $validated['procedure_code'],
            procedureName: $validated['name'],
            price: (float) $validated['price'],
            practitionerId: $validated['practitioner_id'],
            specialtyId: $validated['specialty_id'],
            appointmentId: $validated['appointment_id'] ?? null,
            surfaces: $validated['tooth_surfaces'] ?? null,
            materialsUsed: $validated['materials_used'] ?? null,
            description: $validated['description'] ?? null
        );

        return back()->with('success', 'Acte clinique enregistré avec succès.');
    }

    /**
     * Plans de traitement.
     */
    public function treatmentPlans(int $patientId): View
    {
        $patient = Patient::findOrFail($patientId);
        $treatmentPlans = TreatmentPlan::forPatient($patientId)
            ->with(['practitioner', 'procedures.procedure'])
            ->orderByDesc('created_at')
            ->get();

        $practitioners = User::whereHas('specialties')->orderBy('name')->get();
        $specialties = Specialty::active()->orderBy('name')->get();

        return view('clinical_record::treatment-plans', compact(
            'patient',
            'treatmentPlans',
            'practitioners',
            'specialties'
        ));
    }

    /**
     * Créer un plan de traitement.
     */
    public function storeTreatmentPlan(Request $request, int $patientId): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'practitioner_id' => 'required|exists:users,id',
            'objective' => 'nullable|string',
        ]);

        $plan = $this->treatmentPlanService->createTreatmentPlan(
            patientId: $patientId,
            practitionerId: $validated['practitioner_id'],
            name: $validated['name'],
            objective: $validated['objective'] ?? null
        );

        return redirect()->route('clinical.treatment-plans', ['patientId' => $patientId])
            ->with('success', 'Plan de traitement créé.');
    }

    /**
     * Ajouter un acte à un plan de traitement.
     */
    public function addProcedureToPlan(Request $request, int $planId): RedirectResponse
    {
        $validated = $request->validate([
            'practitioner_id' => 'required|exists:users,id',
            'specialty_id' => 'required|exists:specialties,id',
            'procedure_code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'phase_number' => 'required|integer|min:1',
            'order_in_phase' => 'required|integer|min:1',
            'tooth_number' => 'nullable|integer',
            'description' => 'nullable|string',
        ]);

        $plan = TreatmentPlan::findOrFail($planId);
        
        $this->treatmentPlanService->addProcedureToPlan(
            treatmentPlanId: $planId,
            patientId: $plan->patient_id,
            practitionerId: $validated['practitioner_id'],
            specialtyId: $validated['specialty_id'],
            procedureCode: $validated['procedure_code'],
            procedureName: $validated['name'],
            price: (float) $validated['price'],
            phaseNumber: $validated['phase_number'],
            orderInPhase: $validated['order_in_phase'],
            toothNumber: $validated['tooth_number'] ?? null,
            description: $validated['description'] ?? null
        );

        return back()->with('success', 'Acte ajouté au plan de traitement.');
    }

    /**
     * Imagerie médicale.
     */
    public function medicalImages(int $patientId): View
    {
        $patient = Patient::findOrFail($patientId);
        $images = MedicalImage::forPatient($patientId)
            ->with(['treatmentPlan', 'procedure'])
            ->recentFirst()
            ->paginate(20);

        return view('clinical_record::medical-images', compact('patient', 'images'));
    }
}
