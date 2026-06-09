<?php

namespace Modules\Appointment\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Appointment\Models\AppointmentRequest;
use Modules\Appointment\Models\PatientDocument;
use Modules\Queue\Models\Organization;
use Modules\Queue\Models\Service;
use Modules\Scheduling\Models\AppointmentType;
use Modules\Scheduling\Services\AvailabilityService;

class PublicAppointmentController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
    ) {}

    public function landing(): View
    {
        $services = Service::where('is_active', true)->orderBy('name')->get();
        return view('appointment::public.landing', compact('services'));
    }

    public function searchForm(Request $request): View
    {
        $services = Service::where('is_active', true)->with('organization')->orderBy('name')->get();
        $appointmentTypes = AppointmentType::active()->with('specialty')->orderBy('name')->get();
        $professionals = User::whereIn('role', ['professional', 'doctor', 'medecin'])
            ->orderBy('name')
            ->get();

        return view('appointment::public.search', compact(
            'services', 'appointmentTypes', 'professionals'
        ));
    }

    public function getSlotsJson(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id'         => ['nullable', 'exists:services,id'],
            'professional_id'    => ['nullable', 'exists:users,id'],
            'appointment_type_id'=> ['nullable', 'exists:appointment_types,id'],
            'date_from'          => ['required', 'date', 'after_or_equal:today'],
            'date_to'            => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $dateFrom = Carbon::parse($validated['date_from']);
        $dateTo = $validated['date_to']
            ? Carbon::parse($validated['date_to'])
            : $dateFrom->copy()->addDays(14);

        $practitionerIds = $this->resolvePractitioners(
            professionalId: $validated['professional_id'] ?? null,
            serviceId: $validated['service_id'] ?? null,
            appointmentTypeId: $validated['appointment_type_id'] ?? null,
        );

        $results = [];
        foreach ($practitionerIds as $pId) {
            $slotsInRange = $this->availabilityService->findAvailableSlotsInRange(
                $pId,
                $dateFrom,
                $dateTo,
                $validated['appointment_type_id'] ?? null
            );

            if (!empty($slotsInRange)) {
                $professional = User::find($pId);
                foreach ($slotsInRange as &$slot) {
                    $slot['professional_id']    = $pId;
                    $slot['professional_name']  = $professional?->display_name ?? 'Inconnu';
                    $slot['professional_title'] = $professional?->professional_title;
                }
                $results = array_merge($results, $slotsInRange);
            }
        }

        $grouped = collect($results)->groupBy('date')->sortKeys();

        return response()->json([
            'available' => !$grouped->isEmpty(),
            'dates'     => $grouped->map(fn ($slots, $date) => [
                'date'      => $date,
                'day_name'  => Carbon::parse($date)->locale('fr')->dayName,
                'formatted' => Carbon::parse($date)->locale('fr')->isoFormat('dddd D MMMM YYYY'),
                'slots'     => $slots->sortBy('first_slot.start_time')->values(),
            ]),
        ]);
    }

    public function submitRequest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nin'            => ['required', 'string', 'max:50'],
            'first_name'     => ['required', 'string', 'max:100'],
            'last_name'      => ['required', 'string', 'max:100'],
            'date_of_birth'  => ['required', 'date', 'before:today'],
            'phone'          => ['nullable', 'string', 'max:30'],
            'email'          => ['nullable', 'email', 'max:255'],
            'service_id'          => ['nullable', 'exists:services,id'],
            'appointment_type_id' => ['nullable', 'exists:appointment_types,id'],
            'professional_id'     => ['nullable', 'exists:users,id'],
            'preferred_date_from' => ['nullable', 'date', 'after_or_equal:today'],
            'preferred_date_to'   => ['nullable', 'date', 'after_or_equal:preferred_date_from'],
            'time_preference'     => ['nullable', 'in:morning,afternoon,any'],
            'notes'               => ['nullable', 'string', 'max:1000'],
            'prescription' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,webp', 'max:20480'],
        ]);

        try {
            DB::beginTransaction();

            $patient = Patient::where('nin', $validated['nin'])->first();

            if (!$patient) {
                $patient = Patient::create([
                    'organization_id'       => $this->resolveOrganizationId($validated),
                    'medical_record_number' => Patient::generateMedicalRecordNumber(),
                    'nin'                   => $validated['nin'],
                    'first_name'            => $validated['first_name'],
                    'last_name'             => $validated['last_name'],
                    'date_of_birth'         => $validated['date_of_birth'],
                    'phone'                 => $validated['phone'] ?? null,
                    'email'                 => $validated['email'] ?? null,
                    'is_active'             => true,
                ]);
            }

            $requestModel = AppointmentRequest::create([
                'patient_id'          => $patient->id,
                'nin'                 => $validated['nin'],
                'first_name'          => $validated['first_name'],
                'last_name'           => $validated['last_name'],
                'date_of_birth'       => $validated['date_of_birth'],
                'phone'               => $validated['phone'] ?? $patient->phone,
                'email'               => $validated['email'] ?? $patient->email,
                'service_id'          => $validated['service_id'] ?? null,
                'appointment_type_id' => $validated['appointment_type_id'] ?? null,
                'professional_id'     => $validated['professional_id'] ?? null,
                'preferred_date_from' => $validated['preferred_date_from'] ?? null,
                'preferred_date_to'   => $validated['preferred_date_to'] ?? null,
                'time_preference'     => $validated['time_preference'] ?? 'any',
                'notes'               => $validated['notes'] ?? null,
                'status'              => 'pending',
            ]);

            if ($request->hasFile('prescription')) {
                $file = $request->file('prescription');
                $path = $file->store('patient-documents/'.$patient->id, 'public');

                PatientDocument::create([
                    'patient_id'            => $patient->id,
                    'appointment_request_id' => $requestModel->id,
                    'type'                  => 'prescription',
                    'file_path'             => '/storage/'.$path,
                    'original_name'         => $file->getClientOriginalName(),
                    'mime_type'             => $file->getMimeType(),
                    'file_size'             => $file->getSize(),
                ]);
            }

            DB::commit();

            $token = md5($requestModel->id.'-'.$patient->id.'-'.now()->timestamp);

            return redirect()->route('public.appointment.confirmation', ['token' => $token])
                ->with('success', 'Votre demande de rendez-vous a été reçue. Nous vous contacterons sous 24h.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Erreur lors de la soumission : '.$e->getMessage());
        }
    }

    public function confirmation(Request $request): View
    {
        return view('appointment::public.confirmation');
    }

    private function resolvePractitioners(
        ?int $professionalId = null,
        ?int $serviceId = null,
        ?int $appointmentTypeId = null
    ): array {
        if ($professionalId) {
            return [$professionalId];
        }

        $query = User::whereIn('role', ['professional', 'doctor', 'medecin']);

        if ($serviceId) {
            $service = Service::find($serviceId);
            if ($service) {
                $query->where('organization_id', $service->organization_id);
            }
        }

        if ($appointmentTypeId) {
            $acte = AppointmentType::with('specialty')->find($appointmentTypeId);
            if ($acte?->specialty) {
                $query->where(function ($q) use ($acte) {
                    $q->where('specialty_id', $acte->specialty_id)
                      ->orWhereHas('specialties', fn ($sq) => $sq->where('specialty_id', $acte->specialty_id));
                });
            }
        }

        return $query->pluck('id')->toArray();
    }

    private function resolveOrganizationId(array $data): ?int
    {
        if (!empty($data['service_id'])) {
            return Service::find($data['service_id'])?->organization_id;
        }
        if (!empty($data['professional_id'])) {
            return User::find($data['professional_id'])?->organization_id;
        }
        return Organization::first()?->id;
    }
}
