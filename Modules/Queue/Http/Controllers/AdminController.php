<?php

namespace Modules\Queue\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Queue\Models\Agent;
use Modules\Queue\Models\AppSetting;
use Modules\Queue\Models\Counter;
use Modules\Queue\Models\DisplayScreen;
use Modules\Queue\Models\Kiosk;
use Modules\Queue\Models\Organization;
use Modules\Queue\Models\Service;
use Modules\Queue\Models\Ticket;
use Modules\Queue\Models\TvPlaylistItem;
use Modules\Queue\Services\QueueSupervisorService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function __construct(private readonly QueueSupervisorService $queueSupervisorService)
    {
    }

    public function dashboard(Request $request): View
    {
        $organizations = Organization::with(['services', 'counters', 'agents'])->orderBy('name')->get();
        $organization = $request->filled('organization_id')
            ? Organization::find($request->integer('organization_id'))
            : $organizations->first();

        if (! $organization) {
            abort(404);
        }

        $this->queueSupervisorService->ensureEscalations($organization->id);

        $todayTickets = Ticket::where('organization_id', $organization->id)
            ->whereDate('ticket_date', today())
            ->with('service')
            ->get();

        $stats = [
            'today_total' => $todayTickets->count(),
            'today_waiting' => $todayTickets->where('status', 'waiting')->count(),
            'today_served' => $todayTickets->where('status', 'served')->count(),
            'today_absent' => $todayTickets->where('status', 'absent')->count(),
            'avg_wait_minutes' => 0,
        ];

        $servedWithCall = $todayTickets->filter(fn (Ticket $ticket): bool => $ticket->served_at && $ticket->called_at);
        if ($servedWithCall->isNotEmpty()) {
            $stats['avg_wait_minutes'] = (int) round($servedWithCall->avg(fn (Ticket $ticket): float => $ticket->arrived_at->diffInMinutes($ticket->called_at)));
        }

        $serviceStats = $todayTickets->groupBy('service_id')->map(function ($tickets, $serviceId) {
            $sample = $tickets->first();

            return [
                'service_name' => $sample?->service?->name ?? ('#'.$serviceId),
                'total' => $tickets->count(),
                'served' => $tickets->where('status', 'served')->count(),
                'absent_rate' => $tickets->count() > 0 ? round(($tickets->where('status', 'absent')->count() / $tickets->count()) * 100, 1) : 0,
            ];
        })->values();

        $recentTickets = Ticket::with(['service', 'counter', 'agent'])
            ->where('organization_id', $organization->id)
            ->latest('id')
            ->limit(12)
            ->get();

        return view('queue::admin.dashboard-new', compact('organizations', 'organization', 'stats', 'serviceStats', 'recentTickets'));
    }

    public function supervisorDashboard(Request $request): View
    {
        $organizations = Organization::orderBy('name')->get();
        $organization = $request->filled('organization_id')
            ? Organization::find($request->integer('organization_id'))
            : $organizations->first();

        if (! $organization) {
            abort(404);
        }

        $initial = $this->queueSupervisorService->buildLiveOverview($organization->id);

        return view('admin.supervisor', compact('organizations', 'organization', 'initial'));
    }

    public function supervisorLive(Request $request): JsonResponse
    {
        $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
        ]);

        $data = $this->queueSupervisorService->buildLiveOverview($request->integer('organization_id'));

        return response()->json($data);
    }

    public function statistics(Request $request): View
    {
        $request->validate([
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $organizations = Organization::orderBy('name')->get();
        $organization = $request->filled('organization_id')
            ? Organization::find($request->integer('organization_id'))
            : $organizations->first();

        if (! $organization) {
            abort(404);
        }

        $from = $request->filled('from') ? Carbon::parse($request->string('from')) : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->string('to')) : now()->endOfDay();

        $tickets = Ticket::with('service')
            ->where('organization_id', $organization->id)
            ->whereBetween('ticket_date', [$from->toDateString(), $to->toDateString()])
            ->get();

        $global = [
            'total' => $tickets->count(),
            'served' => $tickets->where('status', 'served')->count(),
            'absent' => $tickets->where('status', 'absent')->count(),
            'waiting' => $tickets->where('status', 'waiting')->count(),
            'called' => $tickets->where('status', 'called')->count(),
        ];

        $global['served_rate'] = $global['total'] > 0 ? round(($global['served'] / $global['total']) * 100, 1) : 0;
        $global['absent_rate'] = $global['total'] > 0 ? round(($global['absent'] / $global['total']) * 100, 1) : 0;

        $byService = $tickets->groupBy('service_id')->map(function ($items) {
            $sample = $items->first();

            return [
                'service' => $sample?->service?->name,
                'total' => $items->count(),
                'served' => $items->where('status', 'served')->count(),
                'absent' => $items->where('status', 'absent')->count(),
                'avg_wait' => (int) round($items->filter(fn (Ticket $t) => $t->called_at)->avg(fn (Ticket $t) => $t->arrived_at->diffInMinutes($t->called_at)) ?? 0),
            ];
        })->values();

        return view('admin.statistics', compact('organizations', 'organization', 'from', 'to', 'global', 'byService'));
    }

    public function history(Request $request): View
    {
        $request->validate([
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'service_id' => ['nullable', 'exists:services,id'],
            'status' => ['nullable', 'in:waiting,called,served,absent,transferred'],
            'date' => ['nullable', 'date'],
        ]);

        $organizations = Organization::orderBy('name')->get();
        $organization = $request->filled('organization_id')
            ? Organization::find($request->integer('organization_id'))
            : $organizations->first();

        if (! $organization) {
            abort(404);
        }

        $query = Ticket::with(['service', 'counter', 'agent'])
            ->where('organization_id', $organization->id)
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->integer('service_id'));
        }

        if ($request->filled('date')) {
            $query->whereDate('ticket_date', Carbon::parse($request->string('date'))->toDateString());
        }

        $tickets = $query->paginate(40)->withQueryString();
        $services = Service::where('organization_id', $organization->id)->orderBy('name')->get();

        return view('admin.history', compact('organizations', 'organization', 'tickets', 'services'));
    }

    public function appointments(Request $request): View
    {
        $request->validate([
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'service_id' => ['nullable', 'exists:services,id'],
            'status' => ['nullable', 'in:waiting,called,served,absent'],
            'date' => ['nullable', 'date'],
        ]);

        $organizations = Organization::with('services')->orderBy('name')->get();
        $organization = $request->filled('organization_id')
            ? Organization::find($request->integer('organization_id'))
            : $organizations->first();

        if (! $organization) {
            abort(404);
        }

        $query = Ticket::with(['service', 'counter', 'agent'])
            ->where('organization_id', $organization->id)
            ->where('is_appointment', true)
            ->orderByDesc('appointment_at')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->integer('service_id'));
        }

        if ($request->filled('date')) {
            $query->whereDate('appointment_at', Carbon::parse($request->string('date'))->toDateString());
        }

        $appointments = $query->paginate(30)->withQueryString();
        $services = Service::where('organization_id', $organization->id)->orderBy('name')->get();

        return view('admin.appointments', compact('organizations', 'organization', 'services', 'appointments'));
    }

    public function storeAppointment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
            'service_id' => ['required', 'exists:services,id'],
            'appointment_at' => ['required', 'date'],
        ]);

        $service = Service::where('organization_id', $validated['organization_id'])
            ->where('id', $validated['service_id'])
            ->firstOrFail();

        $appointmentAt = Carbon::parse($validated['appointment_at']);
        $day = $appointmentAt->copy()->startOfDay();

        DB::transaction(function () use ($validated, $service, $appointmentAt, $day): void {
            $lastSequence = Ticket::where('service_id', $service->id)
                ->whereDate('ticket_date', $day->toDateString())
                ->lockForUpdate()
                ->max('sequence_number');

            $sequence = ($lastSequence ?? 0) + 1;
            $waitingBefore = Ticket::where('service_id', $service->id)
                ->whereDate('ticket_date', $day->toDateString())
                ->where('status', 'waiting')
                ->count();

            Ticket::create([
                'organization_id' => $validated['organization_id'],
                'service_id' => $service->id,
                'ticket_date' => $day->toDateString(),
                'sequence_number' => $sequence,
                'ticket_number' => sprintf('%s-%03d', $service->prefix, $sequence),
                'public_code' => strtoupper(Str::random(12)),
                'status' => 'waiting',
                'is_appointment' => true,
                'appointment_at' => $appointmentAt,
                'estimated_wait_minutes' => max(0, $waitingBefore * (int) $service->average_service_minutes),
                'arrived_at' => now(),
            ]);
        });

        return back()->with('success', __('queue.appointment_created'));
    }

    public function updateAppointment(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->is_appointment, 404);

        $validated = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
            'service_id' => ['required', 'exists:services,id'],
            'appointment_at' => ['required', 'date'],
            'status' => ['required', 'in:waiting,called,served,absent'],
        ]);

        $service = Service::where('organization_id', $validated['organization_id'])
            ->where('id', $validated['service_id'])
            ->firstOrFail();

        $appointmentAt = Carbon::parse($validated['appointment_at']);
        $targetDate = $appointmentAt->toDateString();
        $serviceChanged = (int) $ticket->service_id !== (int) $service->id || $ticket->ticket_date?->toDateString() !== $targetDate;

        DB::transaction(function () use ($ticket, $validated, $service, $appointmentAt, $targetDate, $serviceChanged): void {
            $sequenceNumber = $ticket->sequence_number;
            $ticketNumber = $ticket->ticket_number;

            if ($serviceChanged) {
                $lastSequence = Ticket::where('service_id', $service->id)
                    ->whereDate('ticket_date', $targetDate)
                    ->lockForUpdate()
                    ->max('sequence_number');

                $sequenceNumber = ($lastSequence ?? 0) + 1;
                $ticketNumber = sprintf('%s-%03d', $service->prefix, $sequenceNumber);
            }

            $updates = [
                'organization_id' => $validated['organization_id'],
                'service_id' => $service->id,
                'ticket_date' => $targetDate,
                'sequence_number' => $sequenceNumber,
                'ticket_number' => $ticketNumber,
                'is_appointment' => true,
                'appointment_at' => $appointmentAt,
                'status' => $validated['status'],
            ];

            if ($validated['status'] === 'waiting') {
                $updates['counter_id'] = null;
                $updates['agent_id'] = null;
                $updates['called_at'] = null;
                $updates['served_at'] = null;
            }

            $ticket->update($updates);
        });

        return back()->with('success', __('queue.appointment_updated'));
    }

    public function destroyAppointment(Ticket $ticket): RedirectResponse
    {
        abort_unless($ticket->is_appointment, 404);

        $ticket->delete();

        return back()->with('success', __('queue.appointment_deleted'));
    }

    public function users(): View
    {
        $organizations = Organization::orderBy('name')->get();
        $specialties = \App\Models\Specialty::active()->orderBy('name')->get(['id', 'name', 'code']);
        $users = User::with(['organization', 'agent', 'specialty'])->orderByDesc('id')->paginate(30);

        return view('admin.users', compact('users', 'organizations', 'specialties'));
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:super_admin,admin,professional,secretary,assistant,agent'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'specialty_id' => ['nullable', 'exists:specialties,id'],
            'professional_title' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:6'],
            'agent_phone' => ['nullable', 'string', 'max:30'],
            'agent_active' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'organization_id' => $validated['role'] === 'agent' ? ($validated['organization_id'] ?? null) : null,
            'specialty_id' => $validated['specialty_id'] ?? null,
            'professional_title' => $validated['professional_title'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);

        if ($validated['role'] === 'agent' && ! empty($validated['organization_id'])) {
            Agent::create([
                'organization_id' => $validated['organization_id'],
                'user_id' => $user->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['agent_phone'] ?? null,
                'is_active' => (bool) ($validated['agent_active'] ?? true),
            ]);
        }

        return back()->with('success', __('queue.user_created'));
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role' => ['required', 'in:super_admin,admin,professional,secretary,assistant,agent'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'specialty_id' => ['nullable', 'exists:specialties,id'],
            'professional_title' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:6'],
            'agent_phone' => ['nullable', 'string', 'max:30'],
            'agent_active' => ['nullable', 'boolean'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'organization_id' => $validated['role'] === 'agent' ? ($validated['organization_id'] ?? null) : null,
            'specialty_id' => $validated['specialty_id'] ?? null,
            'professional_title' => $validated['professional_title'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'password' => ! empty($validated['password']) ? Hash::make($validated['password']) : $user->password,
        ]);

        if ($validated['role'] === 'agent' && ! empty($validated['organization_id'])) {
            Agent::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'organization_id' => $validated['organization_id'],
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['agent_phone'] ?? null,
                    'is_active' => (bool) ($validated['agent_active'] ?? true),
                ]
            );
        } else {
            $user->agent()?->delete();
        }

        return back()->with('success', __('queue.user_updated'));
    }

    public function destroyUser(User $user): RedirectResponse
    {
        $user->agent()?->delete();
        $user->delete();

        return back()->with('success', __('queue.user_deleted'));
    }

    public function screens(): View
    {
        $organizations = Organization::with('services')->orderBy('name')->get();
        $screens = DisplayScreen::with(['organization', 'services'])->orderByDesc('id')->get();

        return view('admin.screens', compact('organizations', 'screens'));
    }

    public function playlists(): View
    {
        $organizations = Organization::with('services')->orderBy('name')->get();
        $screens = DisplayScreen::with('organization')->orderBy('name')->get();
        $items = TvPlaylistItem::with(['organization', 'screen'])->orderBy('sort_order')->orderByDesc('id')->get();

        return view('admin.playlists', compact('organizations', 'screens', 'items'));
    }

    public function storePlaylist(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
            'display_screen_id' => ['nullable', 'exists:display_screens,id'],
            'title' => ['required', 'string', 'max:180'],
            'type' => ['required', 'in:video,image,message'],
            'media_url' => ['nullable', 'string', 'max:500'],
            'message' => ['nullable', 'string', 'max:1000'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'days' => ['nullable', 'string', 'max:32'],
            'sort_order' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'media_file' => ['nullable', 'file', 'mimes:mp4,webm,mov,png,jpg,jpeg,webp', 'max:102400'],
        ]);

        $mediaUrl = $this->storeMediaFile($request) ?? ($validated['media_url'] ?? null);

        TvPlaylistItem::create([
            'organization_id' => $validated['organization_id'],
            'display_screen_id' => $validated['display_screen_id'] ?? null,
            'title' => $validated['title'],
            'type' => $validated['type'],
            'media_url' => $mediaUrl,
            'message' => $validated['message'] ?? null,
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'days' => $validated['days'] ?? null,
            'sort_order' => (int) ($validated['sort_order'] ?? 100),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return back()->with('success', __('queue.playlist_created'));
    }

    public function updatePlaylist(Request $request, TvPlaylistItem $item): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
            'display_screen_id' => ['nullable', 'exists:display_screens,id'],
            'title' => ['required', 'string', 'max:180'],
            'type' => ['required', 'in:video,image,message'],
            'media_url' => ['nullable', 'string', 'max:500'],
            'message' => ['nullable', 'string', 'max:1000'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'days' => ['nullable', 'string', 'max:32'],
            'sort_order' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'media_file' => ['nullable', 'file', 'mimes:mp4,webm,mov,png,jpg,jpeg,webp', 'max:102400'],
        ]);

        $uploaded = $this->storeMediaFile($request);
        $mediaUrl = $uploaded ?? ($validated['media_url'] ?? null);

        if ($uploaded && $item->media_url && str_starts_with($item->media_url, '/media/')) {
            $oldPath = public_path(ltrim($item->media_url, '/'));
            if (File::exists($oldPath)) {
                File::delete($oldPath);
            }
        }

        $item->update([
            'organization_id' => $validated['organization_id'],
            'display_screen_id' => $validated['display_screen_id'] ?? null,
            'title' => $validated['title'],
            'type' => $validated['type'],
            'media_url' => $mediaUrl,
            'message' => $validated['message'] ?? null,
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'days' => $validated['days'] ?? null,
            'sort_order' => (int) ($validated['sort_order'] ?? 100),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return back()->with('success', __('queue.playlist_updated'));
    }

    public function destroyPlaylist(TvPlaylistItem $item): RedirectResponse
    {
        if ($item->media_url && str_starts_with($item->media_url, '/media/')) {
            $oldPath = public_path(ltrim($item->media_url, '/'));
            if (File::exists($oldPath)) {
                File::delete($oldPath);
            }
        }

        $item->delete();

        return back()->with('success', __('queue.playlist_deleted'));
    }

    public function settings(): View
    {
        $defaultTvTemplate = AppSetting::getValue('tv_display_template', 'classic');
        $tvLogoUrl = AppSetting::getValue('tv_logo_url', '');
        $tvInfoMessages = AppSetting::getValue('tv_info_messages', '');
        $cabinetLogoUrl = AppSetting::getValue('cabinet_logo_url', '');
        $cabinetDsp = AppSetting::getValue('cabinet_dsp', 'DSP-2026-MEDIOFFICE');
        $cabinetAddress = AppSetting::getValue('cabinet_address', '');
        $cabinetChairCount = (int) AppSetting::getValue('cabinet_chair_count', 1);
        $cabinetChairs = $this->decodeListSetting(AppSetting::getValue('cabinet_chairs', []));
        $cabinetUserIds = array_map('intval', $this->decodeListSetting(AppSetting::getValue('cabinet_user_ids', [])));
        $favoriteProtocols = $this->decodeListSetting(AppSetting::getValue('favorite_protocols', []));
        $stockAlertItems = $this->decodeListSetting(AppSetting::getValue('stock_alert_items', []));
        $consultationMotifs = $this->decodeListSetting(AppSetting::getValue('consultation_motifs', []));
        $stockAlertThreshold = (int) AppSetting::getValue('stock_alert_threshold', 10);
        $users = User::orderBy('name')->get(['id', 'name', 'role']);

        return view('admin.settings', compact(
            'defaultTvTemplate',
            'tvLogoUrl',
            'tvInfoMessages',
            'cabinetLogoUrl',
            'cabinetDsp',
            'cabinetAddress',
            'cabinetChairCount',
            'cabinetChairs',
            'cabinetUserIds',
            'favoriteProtocols',
            'stockAlertItems',
            'consultationMotifs',
            'stockAlertThreshold',
            'users'
        ));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tv_display_template' => ['required', 'in:classic,split'],
            'tv_logo_url' => ['nullable', 'string', 'max:500'],
            'tv_logo_file' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'tv_info_messages' => ['nullable', 'string', 'max:4000'],
            'cabinet_logo_url' => ['nullable', 'string', 'max:500'],
            'cabinet_logo_file' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'cabinet_dsp' => ['nullable', 'string', 'max:120'],
            'cabinet_address' => ['nullable', 'string', 'max:2000'],
            'cabinet_chair_count' => ['nullable', 'integer', 'min:0', 'max:100'],
            'cabinet_chairs' => ['nullable', 'string', 'max:4000'],
            'cabinet_user_ids' => ['nullable', 'array'],
            'cabinet_user_ids.*' => ['integer', 'exists:users,id'],
            'favorite_protocols' => ['nullable', 'string', 'max:4000'],
            'stock_alert_threshold' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'stock_alert_items' => ['nullable', 'string', 'max:4000'],
            'consultation_motifs' => ['nullable', 'string', 'max:4000'],
        ]);

        AppSetting::setValue('tv_display_template', $validated['tv_display_template']);
        $uploadedLogo = $this->storeLogoFile($request);
        $logoUrl = $uploadedLogo ?? ($validated['tv_logo_url'] ?? '');
        $oldLogo = AppSetting::getValue('tv_logo_url', '');

        if ($uploadedLogo && str_starts_with((string) $oldLogo, '/logos/')) {
            $oldPath = public_path(ltrim((string) $oldLogo, '/'));
            if (File::exists($oldPath)) {
                File::delete($oldPath);
            }
        }

        AppSetting::setValue('tv_logo_url', $logoUrl);
        AppSetting::setValue('tv_info_messages', $validated['tv_info_messages'] ?? '');

        $cabinetLogoUpload = $this->storeCabinetLogoFile($request);
        $cabinetLogo = $cabinetLogoUpload ?? ($validated['cabinet_logo_url'] ?? '');
        $oldCabinetLogo = AppSetting::getValue('cabinet_logo_url', '');

        if ($cabinetLogoUpload && str_starts_with((string) $oldCabinetLogo, '/logos/')) {
            $oldPath = public_path(ltrim((string) $oldCabinetLogo, '/'));
            if (File::exists($oldPath)) {
                File::delete($oldPath);
            }
        }

        AppSetting::setValue('cabinet_logo_url', $cabinetLogo);
        AppSetting::setValue('cabinet_dsp', $validated['cabinet_dsp'] ?? 'DSP-2026-MEDIOFFICE');
        AppSetting::setValue('cabinet_address', $validated['cabinet_address'] ?? '');
        AppSetting::setValue('cabinet_chair_count', (int) ($validated['cabinet_chair_count'] ?? 1));
        AppSetting::setValue('cabinet_chairs', $this->normalizeListInput($validated['cabinet_chairs'] ?? ''));
        AppSetting::setValue('cabinet_user_ids', array_values(array_map('intval', $validated['cabinet_user_ids'] ?? [])));
        AppSetting::setValue('favorite_protocols', $this->normalizeListInput($validated['favorite_protocols'] ?? ''));
        AppSetting::setValue('stock_alert_threshold', (int) ($validated['stock_alert_threshold'] ?? 10));
        AppSetting::setValue('stock_alert_items', $this->normalizeListInput($validated['stock_alert_items'] ?? ''));
        AppSetting::setValue('consultation_motifs', $this->normalizeListInput($validated['consultation_motifs'] ?? ''));

        return back()->with('success', __('queue.settings_updated'));
    }

    private function normalizeListInput(?string $value): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[\r\n,;]+/', (string) $value) ?: [])));
    }

    private function decodeListSetting(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : $this->normalizeListInput($value);
    }

    private function storeCabinetLogoFile(Request $request): ?string
    {
        if (! $request->hasFile('cabinet_logo_file')) {
            return null;
        }

        $file = $request->file('cabinet_logo_file');
        if (! $file) {
            return null;
        }

        $path = $file->store('logos', ['disk' => 'public']);

        return '/storage/'.$path;
    }

    public function storeScreen(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30', 'unique:display_screens,code'],
            'location' => ['nullable', 'string', 'max:255'],
            'video_url' => ['nullable', 'string', 'max:500'],
            'video_file' => ['nullable', 'file', 'mimes:mp4,webm,mov', 'max:102400'],
            'audio_enabled' => ['nullable', 'boolean'],
            'audio_order' => ['nullable', 'in:fr_ar,ar_fr,fr_only,ar_only'],
            'audio_repeat' => ['nullable', 'integer', 'min:1', 'max:3'],
            'adhkar_enabled' => ['nullable', 'boolean'],
            'adhkar_text' => ['nullable', 'string', 'max:2000'],
            'tv_primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'tv_secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['exists:services,id'],
        ]);

        $videoUrl = $this->storeVideoFile($request) ?? ($validated['video_url'] ?? null);

        $screen = DisplayScreen::create([
            'organization_id' => $validated['organization_id'],
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'location' => $validated['location'] ?? null,
            'video_url' => $videoUrl,
            'audio_enabled' => (bool) ($validated['audio_enabled'] ?? true),
            'audio_order' => $validated['audio_order'] ?? 'fr_ar',
            'audio_repeat' => (int) ($validated['audio_repeat'] ?? 1),
            'adhkar_enabled' => (bool) ($validated['adhkar_enabled'] ?? false),
            'adhkar_text' => $validated['adhkar_text'] ?? null,
            'tv_primary_color' => strtoupper($validated['tv_primary_color'] ?? '#1D4ED8'),
            'tv_secondary_color' => strtoupper($validated['tv_secondary_color'] ?? '#0F172A'),
            'is_active' => true,
        ]);

        $screen->services()->sync($validated['service_ids'] ?? []);

        return back()->with('success', __('queue.screen_created'));
    }

    public function updateScreen(Request $request, DisplayScreen $screen): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30', 'unique:display_screens,code,'.$screen->id],
            'location' => ['nullable', 'string', 'max:255'],
            'video_url' => ['nullable', 'string', 'max:500'],
            'video_file' => ['nullable', 'file', 'mimes:mp4,webm,mov', 'max:102400'],
            'audio_enabled' => ['nullable', 'boolean'],
            'audio_order' => ['nullable', 'in:fr_ar,ar_fr,fr_only,ar_only'],
            'audio_repeat' => ['nullable', 'integer', 'min:1', 'max:3'],
            'adhkar_enabled' => ['nullable', 'boolean'],
            'adhkar_text' => ['nullable', 'string', 'max:2000'],
            'tv_primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'tv_secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['nullable', 'boolean'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['exists:services,id'],
        ]);

        $uploadedVideo = $this->storeVideoFile($request);
        $videoUrl = $uploadedVideo ?? ($validated['video_url'] ?? null);

        if ($uploadedVideo && $screen->video_url && str_starts_with($screen->video_url, '/videos/')) {
            $oldPath = public_path(ltrim($screen->video_url, '/'));
            if (File::exists($oldPath)) {
                File::delete($oldPath);
            }
        }

        $screen->update([
            'organization_id' => $validated['organization_id'],
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'location' => $validated['location'] ?? null,
            'video_url' => $videoUrl,
            'audio_enabled' => (bool) ($validated['audio_enabled'] ?? false),
            'audio_order' => $validated['audio_order'] ?? 'fr_ar',
            'audio_repeat' => (int) ($validated['audio_repeat'] ?? 1),
            'adhkar_enabled' => (bool) ($validated['adhkar_enabled'] ?? false),
            'adhkar_text' => $validated['adhkar_text'] ?? null,
            'tv_primary_color' => strtoupper($validated['tv_primary_color'] ?? '#1D4ED8'),
            'tv_secondary_color' => strtoupper($validated['tv_secondary_color'] ?? '#0F172A'),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        $screen->services()->sync($validated['service_ids'] ?? []);

        return back()->with('success', __('queue.screen_updated'));
    }

    public function destroyScreen(DisplayScreen $screen): RedirectResponse
    {
        if ($screen->video_url && str_starts_with($screen->video_url, '/videos/')) {
            $oldPath = public_path(ltrim($screen->video_url, '/'));
            if (File::exists($oldPath)) {
                File::delete($oldPath);
            }
        }

        $screen->delete();

        return back()->with('success', __('queue.screen_deleted'));
    }

    public function counters(): View
    {
        $organizations = Organization::with('services')->orderBy('name')->get();
        $counters = Counter::with(['organization', 'services'])->orderByDesc('id')->get();

        return view('admin.counters', compact('organizations', 'counters'));
    }

    public function storeCounter(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30'],
            'location' => ['nullable', 'string', 'max:255'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['exists:services,id'],
        ]);

        $counter = Counter::create([
            'organization_id' => $validated['organization_id'],
            'name' => $validated['name'],
            'name_ar' => $validated['name_ar'] ?? null,
            'code' => strtoupper($validated['code']),
            'location' => $validated['location'] ?? null,
            'is_active' => true,
        ]);

        $counter->services()->sync($validated['service_ids'] ?? []);

        return back()->with('success', __('queue.counter_created'));
    }

    public function updateCounter(Request $request, Counter $counter): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['exists:services,id'],
        ]);

        $counter->update([
            'organization_id' => $validated['organization_id'],
            'name' => $validated['name'],
            'name_ar' => $validated['name_ar'] ?? null,
            'code' => strtoupper($validated['code']),
            'location' => $validated['location'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        $counter->services()->sync($validated['service_ids'] ?? []);

        return back()->with('success', __('queue.counter_updated'));
    }

    public function destroyCounter(Counter $counter): RedirectResponse
    {
        $counter->delete();

        return back()->with('success', __('queue.counter_deleted'));
    }

    public function kiosks(): View
    {
        $organizations = Organization::orderBy('name')->get();
        $kiosks = Kiosk::with('organization')->orderByDesc('id')->get();

        return view('admin.kiosks', compact('organizations', 'kiosks'));
    }

    public function storeKiosk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        Kiosk::create([
            'organization_id' => $validated['organization_id'],
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'location' => $validated['location'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', __('queue.kiosk_created'));
    }

    public function updateKiosk(Request $request, Kiosk $kiosk): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:30'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $kiosk->update([
            'organization_id' => $validated['organization_id'],
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'location' => $validated['location'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return back()->with('success', __('queue.kiosk_updated'));
    }

    public function destroyKiosk(Kiosk $kiosk): RedirectResponse
    {
        $kiosk->delete();

        return back()->with('success', __('queue.kiosk_deleted'));
    }

    private function storeVideoFile(Request $request): ?string
    {
        if (! $request->hasFile('video_file')) {
            return null;
        }

        $file = $request->file('video_file');
        $ext = $file->getClientOriginalExtension();
        $filename = now()->format('YmdHis').'-'.Str::random(6).'.'.$ext;
        $targetDir = public_path('videos');

        if (! File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        $file->move($targetDir, $filename);

        return '/videos/'.$filename;
    }

    private function storeLogoFile(Request $request): ?string
    {
        if (! $request->hasFile('tv_logo_file')) {
            return null;
        }

        $file = $request->file('tv_logo_file');
        $ext = $file->getClientOriginalExtension();
        $filename = now()->format('YmdHis').'-'.Str::random(6).'.'.$ext;
        $targetDir = public_path('logos');

        if (! File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        $file->move($targetDir, $filename);

        return '/logos/'.$filename;
    }

    private function storeMediaFile(Request $request): ?string
    {
        if (! $request->hasFile('media_file')) {
            return null;
        }

        $file = $request->file('media_file');
        $ext = $file->getClientOriginalExtension();
        $filename = now()->format('YmdHis').'-'.Str::random(6).'.'.$ext;
        $targetDir = public_path('media');

        if (! File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        $file->move($targetDir, $filename);

        return '/media/'.$filename;
    }

    public function exportReport(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
            'period' => ['required', 'in:daily,monthly'],
            'date' => ['nullable', 'date'],
        ]);

        $organization = Organization::findOrFail($validated['organization_id']);
        $date = isset($validated['date']) ? Carbon::parse($validated['date']) : now();

        $query = Ticket::with('service')->where('organization_id', $organization->id);

        if ($validated['period'] === 'daily') {
            $query->whereDate('ticket_date', $date->toDateString());
            $filename = 'report-'.$organization->code.'-'.$date->format('Y-m-d').'.csv';
        } else {
            $query->whereYear('ticket_date', $date->year)->whereMonth('ticket_date', $date->month);
            $filename = 'report-'.$organization->code.'-'.$date->format('Y-m').'.csv';
        }

        $tickets = $query->orderBy('ticket_date')->orderBy('id')->get();

        return response()->streamDownload(function () use ($tickets): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Date', 'Service', 'Ticket', 'Status', 'Arrival', 'Called', 'Served']);

            foreach ($tickets as $ticket) {
                fputcsv($output, [
                    $ticket->ticket_date?->format('Y-m-d'),
                    $ticket->service?->name,
                    $ticket->ticket_number,
                    $ticket->status,
                    $ticket->arrived_at?->format('Y-m-d H:i:s'),
                    $ticket->called_at?->format('Y-m-d H:i:s'),
                    $ticket->served_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}

