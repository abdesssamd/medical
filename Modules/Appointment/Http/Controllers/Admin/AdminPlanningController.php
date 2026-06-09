<?php

namespace Modules\Appointment\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Appointment\Models\Planning;
use Modules\Queue\Models\Organization;
use Modules\Scheduling\Models\AppointmentType;
use Modules\Scheduling\Services\FlexiblePlanningService;

class AdminPlanningController extends Controller
{
    public function __construct(
        private readonly FlexiblePlanningService $flexiblePlanningService
    ) {}

    public function index(Request $request): View
    {
        $organizations = Organization::orderBy('name')->get();
        $organizationId = $request->input('organization_id', $organizations->first()?->id);

        $professionals = User::whereIn('role', ['professional', 'doctor', 'medecin'])
            ->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId))
            ->orderBy('name')
            ->get();

        $selectedProfessionalId = $request->input('professional_id', $professionals->first()?->id);
        $selectedProfessional = $selectedProfessionalId
            ? User::with('plannings.appointmentType', 'specialties')->find($selectedProfessionalId)
            : null;

        $grid = $selectedProfessional
            ? $this->flexiblePlanningService->generateWeeklyGrid($selectedProfessional->id)
            : [];

        $appointmentTypes = AppointmentType::active()->with('specialty')->orderBy('name')->get()
            ->groupBy(fn ($at) => $at->specialty?->name ?? 'Général');

        $planningModes = [
            'by_specialist' => 'Par Spécialiste (durée fixe)',
            'by_act'        => "Par Type d'Acte",
            'mixed'         => 'Mixte (Spécialiste + Acte)',
        ];

        return view('appointment::admin.planning-settings', compact(
            'organizations', 'organizationId', 'professionals',
            'selectedProfessional', 'selectedProfessionalId',
            'grid', 'appointmentTypes', 'planningModes'
        ));
    }

    public function storePlanning(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'professional_id'      => ['required', 'exists:users,id'],
            'day_of_week'          => ['required', 'integer', 'between:0,6'],
            'start_time'           => ['required', 'date_format:H:i'],
            'end_time'             => ['required', 'date_format:H:i', 'after:start_time'],
            'planning_mode'        => ['required', 'in:by_specialist,by_act,mixed'],
            'appointment_type_id'  => ['nullable', 'required_if:planning_mode,by_act', 'exists:appointment_types,id'],
            'consultation_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
            'max_patients_per_day' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $validated['consultation_minutes'] ??= config('appointment.default_consultation_minutes', 20);

        Planning::create($validated);

        return redirect()->back()->with('success', 'Créneau de planning ajouté.');
    }

    public function updatePlanning(Request $request, Planning $planning): RedirectResponse
    {
        $validated = $request->validate([
            'start_time'           => ['required', 'date_format:H:i'],
            'end_time'             => ['required', 'date_format:H:i', 'after:start_time'],
            'planning_mode'        => ['required', 'in:by_specialist,by_act,mixed'],
            'appointment_type_id'  => ['nullable', 'required_if:planning_mode,by_act', 'exists:appointment_types,id'],
            'consultation_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
            'max_patients_per_day' => ['nullable', 'integer', 'min:1', 'max:200'],
            'is_active'            => ['nullable', 'boolean'],
        ]);

        $planning->update($validated);

        return redirect()->back()->with('success', 'Planning mis à jour.');
    }

    public function destroyPlanning(Planning $planning): RedirectResponse
    {
        $planning->delete();
        return redirect()->back()->with('success', 'Créneau supprimé.');
    }
}
