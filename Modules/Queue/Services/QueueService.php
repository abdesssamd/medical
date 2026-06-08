<?php

namespace Modules\Queue\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Queue\Contracts\QueueManagerInterface;
use Modules\Queue\Events\PatientCheckedIn;
use Modules\Queue\Models\Service;
use Modules\Queue\Models\Ticket;

class QueueService implements QueueManagerInterface
{
    public function addToQueue(int $patientId, int $doctorId): Ticket
    {
        $service = Service::findOrFail($doctorId);

        return DB::transaction(function () use ($patientId, $service): Ticket {
            $day = Carbon::today();

            $lastSequence = Ticket::where('service_id', $service->id)
                ->whereDate('ticket_date', $day->toDateString())
                ->lockForUpdate()
                ->max('sequence_number');

            $sequence = ($lastSequence ?? 0) + 1;

            $ticket = Ticket::create([
                'organization_id' => $service->organization_id,
                'service_id' => $service->id,
                'ticket_date' => $day->toDateString(),
                'sequence_number' => $sequence,
                'ticket_number' => sprintf('%s-%03d', $service->prefix, $sequence),
                'public_code' => strtoupper(str()->random(12)),
                'status' => 'waiting',
                'estimated_wait_minutes' => 0,
                'arrived_at' => now(),
            ]);

            event(new PatientCheckedIn($ticket));

            return $ticket;
        });
    }
}
