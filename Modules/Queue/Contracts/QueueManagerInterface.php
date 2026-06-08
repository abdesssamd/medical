<?php

namespace Modules\Queue\Contracts;

use Modules\Queue\Models\Ticket;

interface QueueManagerInterface
{
    public function addToQueue(int $patientId, int $doctorId): Ticket;
}
