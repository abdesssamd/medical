<?php

namespace Modules\Queue\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Queue\Models\Ticket;

class PatientCheckedIn
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Ticket $ticket)
    {
    }
}
