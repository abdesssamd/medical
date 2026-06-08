<?php

namespace Modules\Queue\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Queue\Models\Call;
use Modules\Queue\Models\Counter;
use Modules\Queue\Models\Service;
use Modules\Queue\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function dashboard(Request $request)
    {
        $agent = $request->user()?->agent;

        abort_unless($agent && $agent->is_active, 403);

        $organization = $agent->organization()->firstOrFail();

        $counters = $agent->counters()->with('services')->where('is_active', true)->orderBy('name')->get();
        $serviceIds = $counters->flatMap(fn ($counter) => $counter->services->pluck('id'))->unique()->values();
        $services = Service::where('organization_id', $organization->id)
            ->whereIn('id', $serviceIds)
            ->orderBy('name')
            ->get();

        $tickets = $this->getQueueTickets($organization->id);

        return view('agent.dashboard', compact('organization', 'agent', 'counters', 'services', 'tickets'));
    }

    public function queueStatus(Request $request): JsonResponse
    {
        $agent = $request->user()?->agent;
        abort_unless($agent && $agent->is_active, 403);

        return response()->json([
            'tickets' => $this->getQueueTickets($agent->organization_id),
            'server_time' => now()->toDateTimeString(),
        ]);
    }

    public function callNext(Request $request): RedirectResponse
    {
        $agent = $request->user()?->agent;
        abort_unless($agent && $agent->is_active, 403);

        $validated = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'counter_id' => ['required', 'exists:counters,id'],
        ]);

        $counter = $agent->counters()->where('counters.id', $validated['counter_id'])->first();
        if (! $counter) {
            return back()->with('error', __('queue.counter_not_assigned'));
        }

        if (! $counter->services()->where('services.id', $validated['service_id'])->exists()) {
            return back()->with('error', __('queue.service_not_assigned_to_counter'));
        }

        $ticket = Ticket::where('organization_id', $agent->organization_id)
            ->where('service_id', $validated['service_id'])
            ->where('status', 'waiting')
            ->whereDate('ticket_date', today())
            ->orderByRaw("CASE
                WHEN is_appointment = 1 AND appointment_at IS NOT NULL AND appointment_at <= NOW() THEN 0
                WHEN is_appointment = 0 OR appointment_at IS NULL THEN 1
                ELSE 2
            END")
            ->orderBy('appointment_at')
            ->orderBy('arrived_at')
            ->first();

        if (! $ticket) {
            return back()->with('error', __('queue.no_ticket_waiting'));
        }

        $ticket->update([
            'status' => 'called',
            'counter_id' => $validated['counter_id'],
            'agent_id' => $agent->id,
            'called_at' => now(),
        ]);

        $this->storeCall($ticket, 'call');

        return back()->with('success', __('queue.ticket_called', ['ticket' => $ticket->ticket_number]));
    }

    public function recall(Request $request, Ticket $ticket): RedirectResponse
    {
        $agent = $request->user()?->agent;
        abort_unless($agent && $agent->is_active, 403);

        if ($ticket->organization_id !== $agent->organization_id || $ticket->agent_id !== $agent->id) {
            abort(403);
        }

        if ($ticket->status !== 'called') {
            return back()->with('error', __('queue.ticket_not_callable'));
        }

        $this->storeCall($ticket, 'recall');

        return back()->with('success', __('queue.ticket_recalled', ['ticket' => $ticket->ticket_number]));
    }

    public function transfer(Request $request, Ticket $ticket): RedirectResponse
    {
        $agent = $request->user()?->agent;
        abort_unless($agent && $agent->is_active, 403);

        if ($ticket->organization_id !== $agent->organization_id || $ticket->agent_id !== $agent->id) {
            abort(403);
        }

        $validated = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
        ]);

        $target = Service::where('organization_id', $agent->organization_id)
            ->where('id', $validated['service_id'])
            ->first();

        if (! $target) {
            return back()->with('error', __('queue.service_not_allowed'));
        }

        $previousCounterId = $ticket->counter_id;
        $previousAgentId = $ticket->agent_id;

        $ticket->update([
            'status' => 'waiting',
            'transferred_to_service_id' => $target->id,
            'service_id' => $target->id,
            'counter_id' => null,
            'agent_id' => null,
            'called_at' => null,
            'served_at' => null,
        ]);

        if ($previousCounterId && $previousAgentId) {
            $this->storeCall($ticket, 'transfer', $previousCounterId, $previousAgentId);
        }

        return back()->with('success', __('queue.ticket_transferred', ['ticket' => $ticket->ticket_number]));
    }

    public function markServed(Request $request, Ticket $ticket): RedirectResponse
    {
        $agent = $request->user()?->agent;
        abort_unless($agent && $agent->is_active, 403);

        if ($ticket->organization_id !== $agent->organization_id || $ticket->agent_id !== $agent->id) {
            abort(403);
        }

        $ticket->update([
            'status' => 'served',
            'served_at' => now(),
        ]);

        return back()->with('success', __('queue.ticket_served', ['ticket' => $ticket->ticket_number]));
    }

    public function markAbsent(Request $request, Ticket $ticket): RedirectResponse
    {
        $agent = $request->user()?->agent;
        abort_unless($agent && $agent->is_active, 403);

        if ($ticket->organization_id !== $agent->organization_id || $ticket->agent_id !== $agent->id) {
            abort(403);
        }

        $ticket->update([
            'status' => 'absent',
        ]);

        return back()->with('success', __('queue.ticket_absent', ['ticket' => $ticket->ticket_number]));
    }

    private function getQueueTickets(int $organizationId)
    {
        return Ticket::with(['service', 'counter', 'agent'])
            ->where('organization_id', $organizationId)
            ->whereDate('ticket_date', today())
            ->whereIn('status', ['waiting', 'called'])
            ->orderByRaw("CASE WHEN status = 'called' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE
                WHEN is_appointment = 1 AND appointment_at IS NOT NULL AND appointment_at <= NOW() THEN 0
                WHEN is_appointment = 0 OR appointment_at IS NULL THEN 1
                ELSE 2
            END")
            ->orderBy('appointment_at')
            ->orderBy('arrived_at')
            ->limit(80)
            ->get();
    }

    private function storeCall(Ticket $ticket, string $type, ?int $counterId = null, ?int $agentId = null): void
    {
        $counterId ??= $ticket->counter_id;
        $agentId ??= $ticket->agent_id;

        if (! $counterId || ! $agentId) {
            return;
        }

        $counter = Counter::find($counterId);
        $service = Service::find($ticket->service_id);

        $voicePayload = [
            'fr' => sprintf('Numero %s, guichet %s', $ticket->ticket_number, $counter?->name ?? ''),
            'ar' => sprintf('????? %s? ?????? %s', $ticket->ticket_number, $counter?->name_ar ?? $counter?->name ?? ''),
            'ticket_number' => $ticket->ticket_number,
            'counter_name' => $counter?->name,
            'counter_name_ar' => $counter?->name_ar,
            'service_name' => $service?->name,
            'service_name_ar' => $service?->name_ar,
        ];

        Call::create([
            'organization_id' => $ticket->organization_id,
            'service_id' => $ticket->service_id,
            'ticket_id' => $ticket->id,
            'counter_id' => $counterId,
            'agent_id' => $agentId,
            'type' => $type,
            'voice_payload' => $voicePayload,
            'called_at' => now(),
        ]);
    }
}

