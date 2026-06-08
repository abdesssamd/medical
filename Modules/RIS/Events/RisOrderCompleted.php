<?php

namespace Modules\RIS\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\RIS\Models\RisOrder;

class RisOrderCompleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly RisOrder $order)
    {
    }
}
