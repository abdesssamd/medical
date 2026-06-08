<?php

namespace Modules\Queue\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Modules\Queue\Http\Resources\QueueTicketResource;
use Modules\Queue\Models\Call;
use Modules\Queue\Models\AppSetting;
use Modules\Queue\Models\DisplayScreen;
use Modules\Queue\Models\Organization;
use Modules\Queue\Models\Service;
use Modules\Queue\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    public function create(Request $request)
    {
        $organizations = Organization::with('services')->orderBy('name')->get();
        $organization = $organizations->firstWhere('id', $request->integer('organization_id')) ?? $organizations->first();
        $services = $organization ? $organization->services()->where('is_active', true)->orderBy('name')->get() : collect();
        $screens = $organization
            ? DisplayScreen::where('organization_id', $organization->id)->where('is_active', true)->orderBy('name')->get()
            : collect();
        $realtimeEtaByService = $organization
            ? $this->buildRealtimeEtaByService($organization->id, today())
            : [];

        return view('tickets.create', compact('organizations', 'organization', 'services', 'screens', 'realtimeEtaByService'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
            'service_id' => ['required', 'exists:services,id'],
            'appointment_at' => ['nullable', 'date'],
        ]);

        $service = Service::where('organization_id', $validated['organization_id'])
            ->where('id', $validated['service_id'])
            ->firstOrFail();

        $appointmentAt = ! empty($validated['appointment_at']) ? Carbon::parse($validated['appointment_at']) : null;
        $ticketDate = $appointmentAt?->toDateString() ?? today()->toDateString();

        $ticket = DB::transaction(function () use ($service, $validated, $appointmentAt, $ticketDate): Ticket {
            $day = Carbon::parse($ticketDate)->startOfDay();

            $last = Ticket::where('service_id', $service->id)
                ->whereDate('ticket_date', $day->toDateString())
                ->lockForUpdate()
                ->max('sequence_number');

            $sequence = ($last ?? 0) + 1;

            $averageMinutes = $this->resolveRealtimeServiceMinutes($service->id, $day, (int) $service->average_service_minutes);
            $positionAhead = Ticket::where('service_id', $service->id)
                ->whereDate('ticket_date', $day->toDateString())
                ->where('status', 'waiting')
                ->where(function ($query) use ($appointmentAt): void {
                    if (! $appointmentAt) {
                        $query->where(function ($q): void {
                            $q->where('is_appointment', false)
                                ->orWhereNull('appointment_at')
                                ->orWhere('appointment_at', '>', now());
                        });

                        return;
                    }

                    $query->where(function ($q) use ($appointmentAt): void {
                        $q->where(function ($qq) use ($appointmentAt): void {
                            $qq->where('is_appointment', true)
                                ->whereNotNull('appointment_at')
                                ->where('appointment_at', '<=', $appointmentAt);
                        })->orWhere(function ($qq): void {
                            $qq->where(function ($x): void {
                                $x->where('is_appointment', false)->orWhereNull('appointment_at');
                            })->where('created_at', '<=', now());
                        });
                    });
                })
                ->count();

            return Ticket::create([
                'organization_id' => $validated['organization_id'],
                'service_id' => $service->id,
                'ticket_date' => $day->toDateString(),
                'sequence_number' => $sequence,
                'ticket_number' => sprintf('%s-%03d', $service->prefix, $sequence),
                'public_code' => strtoupper(Str::random(12)),
                'status' => 'waiting',
                'is_appointment' => (bool) $appointmentAt,
                'appointment_at' => $appointmentAt,
                'estimated_wait_minutes' => max(0, (int) round($positionAhead * $averageMinutes)),
                'arrived_at' => now(),
            ]);
        });

        if ($request->boolean('direct_print')) {
            return response()->json([
                'ok' => true,
                'ticket_number' => $ticket->ticket_number,
                'print_url' => route('tickets.print', ['ticket' => $ticket->id, 'auto' => 1, 'redirect' => route('tickets.create', ['organization_id' => $ticket->organization_id])]),
                'track_url' => $ticket->public_code ? route('tickets.track', $ticket->public_code) : null,
            ]);
        }

        return redirect()->route('tickets.print', $ticket)->with('success', __('queue.ticket_created', ['ticket' => $ticket->ticket_number]));
    }

    public function print(Ticket $ticket)
    {
        $ticket->load('service.organization');

        return view('tickets.print', compact('ticket'));
    }

    public function track(string $publicCode)
    {
        $ticket = Ticket::with(['service.organization', 'counter'])
            ->where('public_code', strtoupper($publicCode))
            ->firstOrFail();

        return view('tickets.track', compact('ticket'));
    }

    public function trackStatus(string $publicCode): JsonResponse
    {
        $ticket = Ticket::with(['service', 'counter'])
            ->where('public_code', strtoupper($publicCode))
            ->firstOrFail();

        $metrics = $this->buildTicketRealtimeMetrics($ticket);

        return (new QueueTicketResource($ticket))
            ->additional([
                'position' => $metrics['position'],
                'eta_minutes' => $metrics['eta_minutes'],
                'updated_at' => now()->toDateTimeString(),
            ])
            ->response();
    }

    public function publicDisplay(Organization $organization)
    {
        $defaultScreen = DisplayScreen::where('organization_id', $organization->id)->where('is_active', true)->first();
        $tvTemplate = AppSetting::getValue('tv_display_template', 'classic');

        return view('display.public', [
            'organization' => $organization,
            'screenCode' => $defaultScreen?->code,
            'videoUrl' => $defaultScreen?->video_url,
            'tvTemplate' => $tvTemplate,
            'tvLogoUrl' => $this->tvLogoUrl(),
            'tvInfoMessages' => $this->tvInfoMessages(),
        ]);
    }

    public function publicDisplayByCode(string $code)
    {
        $screen = DisplayScreen::with('organization')->where('code', strtoupper($code))->where('is_active', true)->firstOrFail();
        $tvTemplate = AppSetting::getValue('tv_display_template', 'classic');

        return view('display.public', [
            'organization' => $screen->organization,
            'screenCode' => $screen->code,
            'videoUrl' => $screen->video_url,
            'tvTemplate' => $tvTemplate,
            'tvLogoUrl' => $this->tvLogoUrl(),
            'tvInfoMessages' => $this->tvInfoMessages(),
        ]);
    }

    public function openDisplay(Request $request)
    {
        $code = strtoupper((string) $request->string('code'));
        $screens = DisplayScreen::with('organization')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        if ($code !== '') {
            return redirect()->route('display.public.code', $code);
        }

        return view('display.open', compact('screens'));
    }

    public function publicStatus(Organization $organization): JsonResponse
    {
        $activeCalls = Call::with(['ticket', 'counter', 'service'])
            ->where('organization_id', $organization->id)
            ->orderByDesc('called_at')
            ->limit(8)
            ->get();

        $waitingByService = Ticket::with('service')
            ->where('organization_id', $organization->id)
            ->whereDate('ticket_date', today())
            ->where('status', 'waiting')
            ->get()
            ->groupBy('service_id')
            ->map(function ($tickets) {
                $first = $tickets->first();

                return [
                    'service' => $first?->service?->name,
                    'service_ar' => $first?->service?->name_ar,
                    'count' => $tickets->count(),
                ];
            })->values();

        return response()->json([
            'organization' => $organization->only(['id', 'name', 'name_ar']),
            'active_calls' => $activeCalls,
            'waiting_by_service' => $waitingByService,
            'server_time' => now()->toDateTimeString(),
            'video_url' => null,
            'audio_enabled' => true,
            'audio_order' => 'fr_ar',
            'audio_repeat' => 1,
            'adhkar_enabled' => false,
            'adhkar_text' => null,
            'tv_primary_color' => '#1D4ED8',
            'tv_secondary_color' => '#0F172A',
            'tv_template' => AppSetting::getValue('tv_display_template', 'classic'),
            'tv_logo_url' => $this->tvLogoUrl(),
            'tv_info_messages' => $this->tvInfoMessages(),
        ]);
    }

    public function publicStatusByCode(string $code): JsonResponse
    {
        $screen = DisplayScreen::with(['services', 'organization'])->where('code', strtoupper($code))->where('is_active', true)->firstOrFail();

        $serviceIds = $screen->services->pluck('id')->values();

        $callsQuery = Call::with(['ticket', 'counter', 'service'])
            ->where('organization_id', $screen->organization_id)
            ->orderByDesc('called_at');

        if ($serviceIds->isNotEmpty()) {
            $callsQuery->whereIn('service_id', $serviceIds);
        }

        $activeCalls = $callsQuery->limit(10)->get();

        $waitingQuery = Ticket::with('service')
            ->where('organization_id', $screen->organization_id)
            ->whereDate('ticket_date', today())
            ->where('status', 'waiting');

        if ($serviceIds->isNotEmpty()) {
            $waitingQuery->whereIn('service_id', $serviceIds);
        }

        $waitingByService = $waitingQuery->get()->groupBy('service_id')->map(function ($tickets) {
            $first = $tickets->first();

            return [
                'service' => $first?->service?->name,
                'service_ar' => $first?->service?->name_ar,
                'count' => $tickets->count(),
            ];
        })->values();

        return response()->json([
            'organization' => $screen->organization->only(['id', 'name', 'name_ar']),
            'screen' => $screen->only(['id', 'name', 'code', 'video_url']),
            'active_calls' => $activeCalls,
            'waiting_by_service' => $waitingByService,
            'server_time' => now()->toDateTimeString(),
            'video_url' => $screen->video_url,
            'audio_enabled' => $screen->audio_enabled,
            'audio_order' => $screen->audio_order,
            'audio_repeat' => $screen->audio_repeat,
            'adhkar_enabled' => $screen->adhkar_enabled,
            'adhkar_text' => $screen->adhkar_text,
            'tv_primary_color' => $screen->tv_primary_color,
            'tv_secondary_color' => $screen->tv_secondary_color,
            'tv_template' => AppSetting::getValue('tv_display_template', 'classic'),
            'tv_logo_url' => $this->tvLogoUrl(),
            'tv_info_messages' => $this->tvInfoMessages(),
        ]);
    }

    private function tvLogoUrl(): string
    {
        return (string) AppSetting::getValue('tv_logo_url', '');
    }

    private function tvInfoMessages(): array
    {
        $raw = (string) AppSetting::getValue('tv_info_messages', '');
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];

        return collect($lines)
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    private function buildRealtimeEtaByService(int $organizationId, Carbon $day): array
    {
        $services = Service::where('organization_id', $organizationId)->where('is_active', true)->get();
        $etas = [];

        foreach ($services as $service) {
            $etas[$service->id] = $this->buildRealtimeServiceMetrics((int) $service->id, $day, (int) $service->average_service_minutes);
        }

        return $etas;
    }

    private function buildRealtimeServiceMetrics(int $serviceId, Carbon $day, int $fallbackMinutes): array
    {
        $averageMinutes = $this->resolveRealtimeServiceMinutes($serviceId, $day, $fallbackMinutes);
        $waitingCount = Ticket::where('service_id', $serviceId)
            ->whereDate('ticket_date', $day->toDateString())
            ->where('status', 'waiting')
            ->count();

        return [
            'waiting_count' => $waitingCount,
            'avg_service_minutes' => $averageMinutes,
            'eta_for_new_ticket' => max(0, (int) round($waitingCount * $averageMinutes)),
        ];
    }

    private function resolveRealtimeServiceMinutes(int $serviceId, Carbon $day, int $fallbackMinutes): float
    {
        $avg = Ticket::where('service_id', $serviceId)
            ->whereDate('ticket_date', $day->toDateString())
            ->where('status', 'served')
            ->whereNotNull('called_at')
            ->whereNotNull('served_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, called_at, served_at)) AS avg_minutes')
            ->value('avg_minutes');

        if ($avg === null) {
            return max(1, $fallbackMinutes);
        }

        return max(1, (float) $avg);
    }

    private function buildTicketRealtimeMetrics(Ticket $ticket): array
    {
        if ($ticket->status !== 'waiting') {
            return [
                'position' => 0,
                'eta_minutes' => 0,
            ];
        }

        $day = Carbon::parse($ticket->ticket_date);
        $fallback = (int) ($ticket->service?->average_service_minutes ?? 5);
        $averageMinutes = $this->resolveRealtimeServiceMinutes((int) $ticket->service_id, $day, $fallback);

        $ahead = Ticket::where('service_id', $ticket->service_id)
            ->whereDate('ticket_date', $day->toDateString())
            ->where('status', 'waiting')
            ->where('id', '!=', $ticket->id)
            ->where(function ($query) use ($ticket): void {
                if ($ticket->is_appointment && $ticket->appointment_at) {
                    $query->where(function ($q) use ($ticket): void {
                        $q->where(function ($qq) use ($ticket): void {
                            $qq->where('is_appointment', true)
                                ->whereNotNull('appointment_at')
                                ->where('appointment_at', '<=', $ticket->appointment_at);
                        })->orWhere(function ($qq): void {
                            $qq->where(function ($x): void {
                                $x->where('is_appointment', false)->orWhereNull('appointment_at');
                            })->where('created_at', '<=', now());
                        });
                    });

                    return;
                }

                $query->where(function ($q): void {
                    $q->where(function ($qq): void {
                        $qq->where('is_appointment', true)
                            ->whereNotNull('appointment_at')
                            ->where('appointment_at', '<=', now());
                    })->orWhere(function ($qq): void {
                        $qq->where('is_appointment', false)->orWhereNull('appointment_at');
                    });
                });
            })
            ->count();

        return [
            'position' => $ahead + 1,
            'eta_minutes' => max(0, (int) round($ahead * $averageMinutes)),
        ];
    }
}

