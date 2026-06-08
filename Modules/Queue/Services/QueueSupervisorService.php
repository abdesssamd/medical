<?php

namespace Modules\Queue\Services;

use Modules\Queue\Models\AppSetting;
use Modules\Queue\Models\Counter;
use Modules\Queue\Models\Service;
use Modules\Queue\Models\SupervisorAlert;
use Modules\Queue\Models\Ticket;

class QueueSupervisorService
{
    public function getEscalationThresholdMinutes(): int
    {
        return max(1, (int) AppSetting::getValue('escalation_wait_minutes', 15));
    }

    public function ensureEscalations(int $organizationId): void
    {
        $threshold = $this->getEscalationThresholdMinutes();
        $cutoff = now()->subMinutes($threshold);

        $waitingTickets = Ticket::where('organization_id', $organizationId)
            ->whereDate('ticket_date', today())
            ->where('status', 'waiting')
            ->where('arrived_at', '<=', $cutoff)
            ->get();

        foreach ($waitingTickets as $ticket) {
            $exists = SupervisorAlert::where('ticket_id', $ticket->id)
                ->where('type', 'wait_timeout')
                ->where('is_resolved', false)
                ->exists();

            if ($exists) {
                continue;
            }

            $minutes = (int) $ticket->arrived_at->diffInMinutes(now());
            SupervisorAlert::create([
                'organization_id' => $organizationId,
                'ticket_id' => $ticket->id,
                'type' => 'wait_timeout',
                'message' => sprintf('Ticket %s en attente %d min (seuil %d min)', $ticket->ticket_number, $minutes, $threshold),
                'is_resolved' => false,
            ]);
        }

        $toResolveIds = Ticket::where('organization_id', $organizationId)
            ->whereDate('ticket_date', today())
            ->where('status', '!=', 'waiting')
            ->pluck('id');

        if ($toResolveIds->isNotEmpty()) {
            SupervisorAlert::whereIn('ticket_id', $toResolveIds)
                ->where('is_resolved', false)
                ->update([
                    'is_resolved' => true,
                    'resolved_at' => now(),
                ]);
        }
    }

    public function buildLiveOverview(int $organizationId): array
    {
        $this->ensureEscalations($organizationId);

        $todayTickets = Ticket::with(['service', 'counter'])
            ->where('organization_id', $organizationId)
            ->whereDate('ticket_date', today())
            ->get();

        $counters = Counter::where('organization_id', $organizationId)->where('is_active', true)->get();

        $total = $todayTickets->count();
        $served = $todayTickets->where('status', 'served')->count();
        $waiting = $todayTickets->where('status', 'waiting')->count();
        $called = $todayTickets->where('status', 'called')->count();
        $sla = $total > 0 ? round(($served / $total) * 100, 1) : 0;

        $counterLoad = $counters->map(function ($counter) use ($todayTickets) {
            $servedByCounter = $todayTickets->where('counter_id', $counter->id)->where('status', 'served')->count();
            $calledByCounter = $todayTickets->where('counter_id', $counter->id)->where('status', 'called')->count();

            return [
                'counter' => $counter->name,
                'served' => $servedByCounter,
                'in_progress' => $calledByCounter,
            ];
        })->values();

        $bottlenecks = Service::where('organization_id', $organizationId)->get()->map(function ($service) use ($todayTickets) {
            $waitingCount = $todayTickets->where('service_id', $service->id)->where('status', 'waiting')->count();
            $servedCount = $todayTickets->where('service_id', $service->id)->where('status', 'served')->count();

            return [
                'service' => $service->name,
                'waiting' => $waitingCount,
                'served' => $servedCount,
            ];
        })->sortByDesc('waiting')->take(5)->values();

        $alerts = SupervisorAlert::with('ticket')
            ->where('organization_id', $organizationId)
            ->where('is_resolved', false)
            ->latest('id')
            ->limit(30)
            ->get()
            ->map(function (SupervisorAlert $alert) {
                return [
                    'id' => $alert->id,
                    'message' => $alert->message,
                    'ticket' => $alert->ticket?->ticket_number,
                    'created_at' => $alert->created_at?->toDateTimeString(),
                ];
            })->values();

        return [
            'summary' => [
                'total' => $total,
                'served' => $served,
                'waiting' => $waiting,
                'called' => $called,
                'sla' => $sla,
                'escalation_threshold_minutes' => $this->getEscalationThresholdMinutes(),
            ],
            'counter_load' => $counterLoad,
            'bottlenecks' => $bottlenecks,
            'alerts' => $alerts,
            'updated_at' => now()->toDateTimeString(),
        ];
    }
}

