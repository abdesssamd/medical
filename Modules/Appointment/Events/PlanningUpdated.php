<?php

namespace Modules\Appointment\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlanningUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public int $professionalId, public string $reason = 'planning.updated')
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('care.module2');
    }

    public function broadcastAs(): string
    {
        return 'PlanningUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'professional_id' => $this->professionalId,
            'reason' => $this->reason,
        ];
    }
}