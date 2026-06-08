<?php

namespace Modules\Queue\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Queue\Models\Call;
use Modules\Queue\Models\Ticket;
use Illuminate\Http\JsonResponse;

class VoiceController extends Controller
{
    public function ticket(Ticket $ticket): JsonResponse
    {
        $ticket->load(['counter', 'service']);

        return response()->json([
            'ticket_number' => $ticket->ticket_number,
            'counter' => $ticket->counter?->name,
            'counter_ar' => $ticket->counter?->name_ar,
            'service' => $ticket->service?->name,
            'service_ar' => $ticket->service?->name_ar,
            'fr' => sprintf('Numero %s, guichet %s', $ticket->ticket_number, $ticket->counter?->name ?? '-'),
            'ar' => sprintf('????? %s? ?????? %s', $ticket->ticket_number, $ticket->counter?->name_ar ?? $ticket->counter?->name ?? '-'),
            'fallback_audio' => [
                'fr' => '/audio/fr/announcement.mp3',
                'ar' => '/audio/ar/announcement.mp3',
            ],
        ]);
    }

    public function lastCall(int $organizationId): JsonResponse
    {
        $call = Call::where('organization_id', $organizationId)->latest('called_at')->first();

        return response()->json([
            'call' => $call,
        ]);
    }
}

