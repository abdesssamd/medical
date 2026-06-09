<?php

namespace Modules\PatientPortal\Listeners;

use Illuminate\Support\Facades\Mail;
use Modules\PatientPortal\Mail\PatientPortalAccessMail;
use Modules\PatientPortal\Services\PatientPortalAccessService;
use Modules\RIS\Events\RisOrderCompleted;

class CreatePatientPortalAccessForCompletedReport
{
    public function __construct(
        private readonly PatientPortalAccessService $service,
    ) {}

    public function handle(RisOrderCompleted $event): void
    {
        $result = $this->service->issueForOrder($event->order);
        $access = $result['access'] ?? null;
        $plainCode = $result['code'] ?? null;

        if (! $access || ! $plainCode) {
            return;
        }

        $patient = $access->patient;
        if (! $patient || ! $patient->email) {
            return;
        }

        Mail::to($patient->email)->queue(new PatientPortalAccessMail($access, $plainCode));
    }
}
