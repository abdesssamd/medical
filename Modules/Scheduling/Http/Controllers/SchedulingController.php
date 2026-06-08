<?php

namespace Modules\Scheduling\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Specialty;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Appointment\Models\Appointment;
use Modules\Appointment\Models\Planning;
use Modules\Scheduling\Models\AvailabilityBlock;
use Modules\Scheduling\Models\AppointmentType;
use Modules\Scheduling\Services\AvailabilityService;
use Modules\Scheduling\Services\MultiSpecialtyCoordinationService;

class SchedulingController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
        private readonly MultiSpecialtyCoordinationService $coordinationService
    ) {}

    /**
     * Dashboard principal de planification.
     */
    public function dashboard(Request $request): View
    {
        $practitioners = User::whereHas('specialties')
            ->orWhere('role', 'professional')
            ->orWhere('role', 'doctor')
            ->with(['specialties', 'primaryRoom'])
            ->orderBy('name')
            ->get();

        $selectedPractitionerId = $request->integer('practitioner_id', $practitioners->first()?->id);
        $selectedDate = $request->date('date', now()->format('Y-m-d'));

        $availability = [];
        $selectedPractitioner = null;

        if ($selectedPractitionerId) {
            $selectedPractitioner = User::with(['specialties', 'primaryRoom'])->find($selectedPractitionerId);
            
            $availability = $this->availabilityService->getAvailability(
                $selectedPractitionerId,
                Carbon::parse($selectedDate)
            );

            // Get today's appointments
            $appointments = Appointment::where('professional_id', $selectedPractitionerId)
                ->whereDate('appointment_date', $selectedDate)
                ->with(['patient', 'appointmentType', 'room'])
                ->orderBy('start_time')
                ->get();
        }

        // Get appointments for the week
        $weekAppointments = [];
        if ($selectedPractitionerId) {
            $weekStart = Carbon::parse($selectedDate)->startOfWeek();
            $weekEnd = Carbon::parse($selectedDate)->endOfWeek();
            
            $weekAppointments = Appointment::where('professional_id', $selectedPractitionerId)
                ->whereBetween('appointment_date', [$weekStart, $weekEnd])
                ->with(['patient', 'appointmentType'])
                ->orderBy('appointment_date')
                ->orderBy('start_time')
                ->get()
                ->groupBy(fn ($apt) => $apt->appointment_date->format('Y-m-d'));
        }

        return view('scheduling::dashboard', compact(
            'practitioners',
            'selectedPractitioner',
            'selectedPractitionerId',
            'selectedDate',
            'availability',
            'appointments',
            'weekAppointments'
        ));
    }

    /**
     * Gérer les types de rendez-vous par spécialité.
     */
    public function appointmentTypes(Request $request): View
    {
        $specialties = Specialty::active()->orderBy('name')->get();
        $selectedSpecialtyId = $request->integer('specialty_id');

        $appointmentTypes = collect();
        if ($selectedSpecialtyId) {
            $appointmentTypes = AppointmentType::forSpecialty($selectedSpecialtyId)
                ->with('specialty')
                ->orderBy('name')
                ->get();
        }

        return view('scheduling::appointment-types', compact('specialties', 'selectedSpecialtyId', 'appointmentTypes'));
    }

    /**
     * Stocker un nouveau type de rendez-vous.
     */
    public function storeAppointmentType(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'specialty_id' => 'required|exists:specialties,id',
            'code' => 'required|string|unique:appointment_types,code',
            'name' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'duration_minutes' => 'required|integer|min:5|max:480',
            'base_price' => 'nullable|numeric|min:0',
            'requires_follow_up' => 'boolean',
            'follow_up_days' => 'nullable|integer|min:1',
            'required_equipment' => 'nullable|array',
            'required_material' => 'nullable|array',
            'description' => 'nullable|string',
        ]);

        AppointmentType::create($validated);

        return back()->with('success', __('Type de rendez-vous créé avec succès.'));
    }

    /**
     * Gérer les blocs de disponibilité.
     */
    public function availabilityBlocks(Request $request): View
    {
        $practitioners = User::where('role', 'professional')
            ->orWhere('role', 'doctor')
            ->orderBy('name')
            ->get();

        $selectedPractitionerId = $request->integer('practitioner_id');
        $fromDate = $request->date('from_date', now()->startOfWeek()->format('Y-m-d'));
        $toDate = $request->date('to_date', now()->endOfWeek()->format('Y-m-d'));

        $blocks = collect();
        if ($selectedPractitionerId) {
            $blocks = AvailabilityBlock::forPractitioner($selectedPractitionerId)
                ->betweenDates($fromDate, $toDate)
                ->with(['room', 'appointmentType'])
                ->orderBy('date')
                ->orderBy('start_time')
                ->get();
        }

        $rooms = Room::active()->orderBy('name')->get();
        $appointmentTypes = AppointmentType::active()->with('specialty')->orderBy('name')->get();

        return view('scheduling::availability-blocks', compact(
            'practitioners',
            'selectedPractitionerId',
            'fromDate',
            'toDate',
            'blocks',
            'rooms',
            'appointmentTypes'
        ));
    }

    /**
     * Créer un bloc de disponibilité récurrent.
     */
    public function storeRecurringBlock(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'practitioner_id' => 'required|exists:users,id',
            'room_id' => 'nullable|exists:rooms,id',
            'appointment_type_id' => 'nullable|exists:appointment_types,id',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'exclude_days' => 'nullable|array',
            'exclude_days.*' => 'integer|between:0,6',
            'type' => 'nullable|string|in:available,break,formation,absence',
            'max_patients' => 'nullable|integer|min:1',
        ]);

        AvailabilityBlock::createRecurringBlocks(
            practitionerId: $validated['practitioner_id'],
            startTime: $validated['start_time'],
            endTime: $validated['end_time'],
            fromDate: Carbon::parse($validated['from_date']),
            toDate: Carbon::parse($validated['to_date']),
            excludeDays: $validated['exclude_days'] ?? [],
            roomId: $validated['room_id'] ?? null,
            appointmentTypeId: $validated['appointment_type_id'] ?? null,
            type: $validated['type'] ?? 'available',
            maxPatients: $validated['max_patients'] ?? null
        );

        return back()->with('success', __('Blocs de disponibilité créés avec succès.'));
    }

    /**
     * Coordination multi-spécialités.
     */
    public function multiSpecialtyCoordination(Request $request): View
    {
        $specialties = Specialty::active()->orderBy('name')->get();
        $practitioners = User::whereHas('specialties')->orderBy('name')->get();

        return view('scheduling::multi-specialty-coordination', compact('specialties', 'practitioners'));
    }

    /**
     * Recherche optimale pour patient multi-spécialités.
     */
    public function findOptimalDay(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'specialties' => 'required|array|min:1',
            'specialties.*.specialty_id' => 'required|exists:specialties,id',
            'specialties.*.appointment_type_id' => 'nullable|exists:appointment_types,id',
            'specialties.*.priority' => 'nullable|integer|min:1',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $result = $this->coordinationService->findOptimalDayForMultipleSpecialties(
            patientId: $validated['patient_id'],
            requiredSpecialties: $validated['specialties'],
            fromDate: Carbon::parse($validated['from_date']),
            toDate: Carbon::parse($validated['to_date'])
        );

        return response()->json([
            'success' => (bool) $result,
            'optimal_day' => $result,
        ]);
    }
}
