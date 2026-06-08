<?php

namespace Modules\Queue\Actions;

use Modules\Queue\Contracts\QueueManagerInterface;
use Modules\Queue\Models\Ticket;

class AddPatientToQueueAction
{
    public function __construct(private readonly QueueManagerInterface $queueManager)
    {
    }

    public function execute(int $patientId, int $doctorId): Ticket
    {
        return $this->queueManager->addToQueue($patientId, $doctorId);
    }
}
