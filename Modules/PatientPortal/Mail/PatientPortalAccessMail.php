<?php

namespace Modules\PatientPortal\Mail;

use Illuminate\Mail\Mailable;
use Modules\PatientPortal\Models\PatientPortalAccess;
use Modules\PatientPortal\Services\PatientPortalAccessService;

class PatientPortalAccessMail extends Mailable
{
    public function __construct(
        public PatientPortalAccess $access,
        public string $plainCode
    ) {
    }

    public function build(): self
    {
        $service = app(PatientPortalAccessService::class);

        return $this->subject('Acces a vos resultats MediOffice')
            ->view('patient_portal::emails.access-code', [
                'access' => $this->access,
                'plainCode' => $this->plainCode,
                'portalUrl' => $service->buildPortalUrl($this->access),
                'qrCodeSvg' => $service->buildLoginQrSvg($this->access),
            ]);
    }
}
