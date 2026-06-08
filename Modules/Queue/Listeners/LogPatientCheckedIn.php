<?php

namespace Modules\Queue\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Queue\Events\PatientCheckedIn;

class LogPatientCheckedIn
{
    public function handle(PatientCheckedIn $event): void
    {
        Log::info('queue.patient_checked_in', [
            'ticket_id' => $event->ticket->id,
            'ticket_number' => $event->ticket->ticket_number,
            'service_id' => $event->ticket->service_id,
            'organization_id' => $event->ticket->organization_id,
        ]);
    }
}
