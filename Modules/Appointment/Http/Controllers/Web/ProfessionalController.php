<?php

namespace Modules\Appointment\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\Appointment\Models\Appointment;
use Modules\Appointment\Models\Commission;
use Modules\Appointment\Models\Planning;
use Modules\Appointment\Models\Setting;
use Modules\Appointment\Events\PlanningUpdated;

class ProfessionalController extends Controller
{
    public function dashboard(Request $request)
    {
        $this->ensureProfessional($request);

        $professionalId = (int) $request->user()->id;
        $selectedProfessionalId = (int) $request->input('professional_id', $professionalId);
        $selectedRoomId = $request->filled('room_id') ? (int) $request->input('room_id') : null;
        $today = today()->toDateString();
        $now = now();

        $base = Appointment::query()
            ->where('professional_id', $selectedProfessionalId)
            ->when($selectedRoomId, fn ($q) => $q->where('room_id', $selectedRoomId));

        $settings = Setting::firstOrCreate(
            ['professional_id' => $professionalId],
            ['currency' => 'MAD', 'default_commission_amount' => 20]
        );

        $todayAppointments = (clone $base)->whereDate('appointment_date', $today);
        $todayCount = (clone $todayAppointments)->count();
        $todayConsulted = (clone $todayAppointments)->where('status', 'consulted')->count();
        $todayNoShow = (clone $todayAppointments)->where('status', 'no_show')->count();
        $todayCancelled = (clone $todayAppointments)->where('status', 'cancelled')->count();

        $monthCommissions = (float) Commission::query()
            ->where('professional_id', $selectedProfessionalId)
            ->whereMonth('earned_on', $now->month)
            ->whereYear('earned_on', $now->year)
            ->sum('amount');

        $monthPaidCommissions = (float) Commission::query()
            ->where('professional_id', $selectedProfessionalId)
            ->whereMonth('earned_on', $now->month)
            ->whereYear('earned_on', $now->year)
            ->where('status', 'paid')
            ->sum('amount');

        $avgBasket = $todayConsulted > 0 ? round($monthCommissions / max(1, $todayConsulted), 2) : 0.0;

        $goal = (float) ($settings->weekly_revenue_target ?? 0);
        $collectionRate = $monthCommissions > 0 ? round(($monthPaidCommissions / $monthCommissions) * 100, 1) : 0.0;

        $weeksTrend = collect(range(3, 0))->map(function (int $offset) use ($base): array {
            $start = now()->startOfWeek()->subWeeks($offset);
            $end = (clone $start)->endOfWeek();
            $appointments = (clone $base)
                ->whereBetween('appointment_date', [$start->toDateString(), $end->toDateString()])
                ->get(['appointment_date', 'start_time', 'end_time']);

            $occupied = $appointments->sum(function (Appointment $apt): int {
                $start = Carbon::createFromFormat('H:i:s', (string) ($apt->start_time ?? '09:00:00'));
                $end = Carbon::createFromFormat('H:i:s', (string) ($apt->end_time ?? '09:20:00'));
                return max(5, $start->diffInMinutes($end));
            });

            $open = (7 * 8 * 60);
            $rate = $open > 0 ? round(min(100, ($occupied / $open) * 100), 1) : 0.0;

            return [
                'label' => $start->format('d/m'),
                'rate' => $rate,
                'occupied_minutes' => $occupied,
                'open_minutes' => $open,
            ];
        })->values();

        $nextPatient = (clone $base)
            ->with('patient:id,first_name,last_name')
            ->whereDate('appointment_date', $today)
            ->whereTime('start_time', '>=', now()->format('H:i:s'))
            ->orderBy('start_time')
            ->first();

        $noShowPatients = (clone $base)
            ->with('patient:id,first_name,last_name,phone,email')
            ->where('status', 'no_show')
            ->whereDate('appointment_date', '>=', now()->subDays(30)->toDateString())
            ->latest('appointment_date')
            ->limit(60)
            ->get(['id', 'patient_id', 'appointment_date', 'start_time', 'status']);

        $rooms = Room::active()->orderBy('name')->get(['id', 'name', 'code']);
        $professionals = User::whereIn('role', ['professional', 'doctor', 'super_admin'])
            ->orderBy('name')
            ->get(['id', 'name']);

        $emergencySlotsToday = (int) ($settings->emergency_slots_per_day ?? 0);
        $bookedEmergencyToday = (clone $todayAppointments)
            ->where(function ($q): void {
                $q->where('notes', 'like', '%urgence%')
                    ->orWhere('follow_up_status', 'urgent');
            })->count();
        $remainingEmergencySlots = max(0, $emergencySlotsToday - $bookedEmergencyToday);

        $stats = [
            'today_appointments' => $todayCount,
            'today_consulted' => $todayConsulted,
            'month_commissions' => $monthCommissions,
            'month_paid_commissions' => $monthPaidCommissions,
            'collection_rate' => $collectionRate,
            'avg_basket' => $avgBasket,
            'revenue_goal' => $goal,
            'today_no_show' => $todayNoShow,
            'today_cancelled' => $todayCancelled,
            'no_show_rate' => $todayCount > 0 ? round(($todayNoShow / $todayCount) * 100, 1) : 0,
            'remaining_emergency_slots' => $remainingEmergencySlots,
        ];

        $plannings = Planning::where('professional_id', $professionalId)->orderBy('day_of_week')->get()->keyBy('day_of_week');
        $startWeek = now()->startOfWeek();
        $endWeek = now()->endOfWeek();
        $weekAppointments = (clone $base)
            ->whereBetween('appointment_date', [$startWeek->toDateString(), $endWeek->toDateString()])
            ->get(['appointment_date', 'notes', 'follow_up_status']);

        $weekAppointmentsStats = collect(range(0, 6))->mapWithKeys(function (int $day) use ($weekAppointments): array {
            $rows = $weekAppointments->filter(fn (Appointment $apt): bool => (int) $apt->appointment_date?->dayOfWeek === $day);
            $urgent = $rows->filter(function (Appointment $apt): bool {
                return ($apt->follow_up_status === 'urgent')
                    || str_contains(mb_strtolower((string) $apt->notes), 'urgence');
            })->count();
            return [
                $day => [
                    'scheduled' => $rows->count(),
                    'urgent' => $urgent,
                ],
            ];
        })->all();

        return view('appointment::professional.dashboard', compact(
            'stats',
            'plannings',
            'professionalId',
            'selectedProfessionalId',
            'selectedRoomId',
            'professionals',
            'rooms',
            'weeksTrend',
            'nextPatient',
            'settings',
            'noShowPatients',
            'weekAppointmentsStats'
        ));
    }

    public function updatePlanning(Request $request, int $day): JsonResponse
    {
        $this->ensureProfessional($request);
        $this->assertValidDay($day);

        $validated = $request->validate([
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'consultation_minutes' => ['required', 'integer', 'min:5', 'max:180'],
            'max_patients_per_day' => ['nullable', 'integer', 'min:1', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $plan = Planning::updateOrCreate(
            [
                'professional_id' => (int) $request->user()->id,
                'day_of_week' => $day,
            ],
            [
                'start_time' => $validated['start_time'].':00',
                'end_time' => $validated['end_time'].':00',
                'consultation_minutes' => (int) $validated['consultation_minutes'],
                'max_patients_per_day' => $validated['max_patients_per_day'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]
        );

        event(new PlanningUpdated((int) $request->user()->id, 'planning.updated'));

        return response()->json([
            'message' => 'Planning mis a jour.',
            'plan' => $this->serializePlan($plan),
        ]);
    }

    public function togglePlanning(Request $request, int $day): JsonResponse
    {
        $this->ensureProfessional($request);
        $this->assertValidDay($day);

        $isActive = (bool) $request->boolean('is_active');
        $professionalId = (int) $request->user()->id;

        $plan = Planning::firstOrCreate(
            ['professional_id' => $professionalId, 'day_of_week' => $day],
            [
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'consultation_minutes' => 20,
                'max_patients_per_day' => 16,
                'is_active' => true,
            ]
        );

        $plan->update(['is_active' => $isActive]);

        event(new PlanningUpdated($professionalId, 'planning.toggled'));

        return response()->json([
            'message' => $isActive ? 'Jour active.' : 'Jour desactive.',
            'plan' => $this->serializePlan($plan->fresh()),
        ]);
    }

    public function duplicateMondayToWeek(Request $request): JsonResponse
    {
        $this->ensureProfessional($request);
        $professionalId = (int) $request->user()->id;

        $monday = Planning::where('professional_id', $professionalId)->where('day_of_week', 1)->first();
        if (! $monday) {
            return response()->json([
                'message' => 'Aucun planning du lundi a dupliquer.',
            ], 422);
        }

        foreach ([2, 3, 4, 5, 6, 0] as $day) {
            Planning::updateOrCreate(
                ['professional_id' => $professionalId, 'day_of_week' => $day],
                [
                    'start_time' => $monday->start_time,
                    'end_time' => $monday->end_time,
                    'consultation_minutes' => $monday->consultation_minutes,
                    'max_patients_per_day' => $monday->max_patients_per_day,
                    'is_active' => $monday->is_active,
                ]
            );
        }

        event(new PlanningUpdated($professionalId, 'planning.duplicated'));

        return response()->json([
            'message' => 'Planning du lundi applique a tous les jours.',
        ]);
    }

    public function optimizeWeek(Request $request): JsonResponse
    {
        $this->ensureProfessional($request);

        $validated = $request->validate([
            'professional_id' => ['nullable', 'integer', 'exists:users,id'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
        ]);

        $professionalId = (int) ($validated['professional_id'] ?? $request->user()->id);
        $start = now()->startOfWeek();
        $end = now()->endOfWeek();

        $appointments = Appointment::query()
            ->where('professional_id', $professionalId)
            ->whereBetween('appointment_date', [$start->toDateString(), $end->toDateString()])
            ->when(! empty($validated['room_id']), fn ($q) => $q->where('room_id', $validated['room_id']))
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->get(['id', 'appointment_date', 'start_time', 'end_time', 'notes', 'patient_name']);

        $shortSlots = $appointments->filter(function (Appointment $apt): bool {
            $start = Carbon::createFromFormat('H:i:s', (string) ($apt->start_time ?? '09:00:00'));
            $end = Carbon::createFromFormat('H:i:s', (string) ($apt->end_time ?? '09:20:00'));
            return $start->diffInMinutes($end) <= 20;
        })->values();

        $suggestions = [];
        foreach ($shortSlots->take(10) as $apt) {
            $date = Carbon::parse((string) $apt->appointment_date);
            $targetDay = $date->isFriday() ? $date->copy()->subDay() : $date->copy()->addDay();
            $suggestions[] = [
                'appointment_id' => $apt->id,
                'patient_name' => $apt->patient_name,
                'from' => $date->toDateString().' '.substr((string) $apt->start_time, 0, 5),
                'to' => $targetDay->toDateString().' '.substr((string) $apt->start_time, 0, 5),
                'reason' => 'Deplacer un acte court pour liberer des plages longues (implant/chirurgie).',
            ];
        }

        return response()->json([
            'message' => count($suggestions) > 0
                ? 'Suggestions IA pretes. Validation manuelle recommandee.'
                : 'Aucun deplacement recommande cette semaine.',
            'suggestions' => $suggestions,
        ]);
    }

    public function noShowList(Request $request): JsonResponse
    {
        $this->ensureProfessional($request);

        $validated = $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:180'],
            'professional_id' => ['nullable', 'integer', 'exists:users,id'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
        ]);

        $days = (int) ($validated['days'] ?? 30);
        $professionalId = (int) ($validated['professional_id'] ?? $request->user()->id);

        $items = Appointment::query()
            ->with('patient:id,first_name,last_name,phone,email')
            ->where('professional_id', $professionalId)
            ->where('status', 'no_show')
            ->whereDate('appointment_date', '>=', now()->subDays($days)->toDateString())
            ->when(! empty($validated['room_id']), fn ($q) => $q->where('room_id', $validated['room_id']))
            ->latest('appointment_date')
            ->limit(120)
            ->get(['id', 'patient_id', 'appointment_date', 'start_time', 'status'])
            ->map(function (Appointment $apt): array {
                return [
                    'appointment_id' => $apt->id,
                    'patient' => $apt->patient?->full_name ?? $apt->patient_name ?? 'Patient',
                    'phone' => $apt->patient?->phone,
                    'email' => $apt->patient?->email,
                    'slot' => $apt->appointment_date?->format('Y-m-d').' '.substr((string) $apt->start_time, 0, 5),
                ];
            })
            ->values();

        return response()->json([
            'count' => $items->count(),
            'items' => $items,
        ]);
    }

    public function updateCapacitySettings(Request $request): JsonResponse
    {
        $this->ensureProfessional($request);

        $validated = $request->validate([
            'emergency_slots_per_day' => ['nullable', 'integer', 'min:0', 'max:20'],
            'weekly_revenue_target' => ['nullable', 'numeric', 'min:0'],
            'exceptions' => ['nullable', 'string', 'max:4000'],
            'external_sync_enabled' => ['nullable', 'boolean'],
            'external_sync_provider' => ['nullable', 'string', 'in:google,outlook'],
        ]);

        $exceptions = collect(preg_split('/[\r\n,;]+/', (string) ($validated['exceptions'] ?? '')) ?: [])
            ->map(fn ($date) => trim((string) $date))
            ->filter(fn ($date) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1)
            ->values()
            ->all();

        $setting = Setting::updateOrCreate(
            ['professional_id' => (int) $request->user()->id],
            [
                'emergency_slots_per_day' => (int) ($validated['emergency_slots_per_day'] ?? 0),
                'weekly_revenue_target' => (float) ($validated['weekly_revenue_target'] ?? 0),
                'capacity_exceptions' => $exceptions,
                'external_sync_enabled' => (bool) ($validated['external_sync_enabled'] ?? false),
                'external_sync_provider' => ! empty($validated['external_sync_enabled'])
                    ? ($validated['external_sync_provider'] ?? null)
                    : null,
            ]
        );

        event(new PlanningUpdated((int) $request->user()->id, 'capacity.settings.updated'));

        return response()->json([
            'message' => 'Parametres de capacite enregistres.',
            'settings' => [
                'emergency_slots_per_day' => (int) ($setting->emergency_slots_per_day ?? 0),
                'weekly_revenue_target' => (float) ($setting->weekly_revenue_target ?? 0),
                'capacity_exceptions' => $setting->capacity_exceptions ?? [],
                'external_sync_enabled' => (bool) ($setting->external_sync_enabled ?? false),
                'external_sync_provider' => $setting->external_sync_provider,
            ],
        ]);
    }

    private function ensureProfessional(Request $request): void
    {
        if ($request->user()?->role !== 'professional') {
            abort(403);
        }
    }

    private function assertValidDay(int $day): void
    {
        if ($day < 0 || $day > 6) {
            abort(422, 'Jour invalide.');
        }
    }

    private function serializePlan(Planning $plan): array
    {
        $start = Carbon::parse((string) $plan->start_time);
        $end = Carbon::parse((string) $plan->end_time);
        $openMinutes = max(0, $start->diffInMinutes($end));
        $occupiedMinutes = (int) ($plan->consultation_minutes * ((int) ($plan->max_patients_per_day ?? 0)));
        $capacityPct = $openMinutes > 0 ? min(100, (int) round(($occupiedMinutes / $openMinutes) * 100)) : 0;

        return [
            'id' => $plan->id,
            'day_of_week' => $plan->day_of_week,
            'start_time' => substr((string) $plan->start_time, 0, 5),
            'end_time' => substr((string) $plan->end_time, 0, 5),
            'consultation_minutes' => $plan->consultation_minutes,
            'max_patients_per_day' => $plan->max_patients_per_day,
            'is_active' => (bool) $plan->is_active,
            'open_minutes' => $openMinutes,
            'occupied_minutes' => $occupiedMinutes,
            'capacity_pct' => $capacityPct,
        ];
    }
}
