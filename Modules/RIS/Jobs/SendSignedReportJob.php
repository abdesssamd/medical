<?php

namespace Modules\RIS\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\RIS\Models\RisOrder;
use Modules\RIS\Services\RisReportService;

class SendSignedReportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $orderId)
    {
    }

    public function handle(RisReportService $reportService): void
    {
        $order = RisOrder::query()->find($this->orderId);

        if (! $order) {
            return;
        }

        $reportService->sendSignedReportNow($order);
    }
}
