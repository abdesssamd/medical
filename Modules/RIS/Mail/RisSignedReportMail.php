<?php

namespace Modules\RIS\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Storage;
use Modules\RIS\Models\RisOrder;

class RisSignedReportMail extends Mailable
{
    public function __construct(public RisOrder $order, public string $recipientLabel = 'Destinataire')
    {
    }

    public function build(): self
    {
        $mail = $this->subject('Compte rendu RIS signé - '.($this->order->accession_number ?: 'RIS-'.$this->order->id))
            ->view('ris::emails.report-signed', [
                'order' => $this->order,
                'report' => $this->order->report,
                'recipientLabel' => $this->recipientLabel,
                'shareUrl' => $this->order->report?->share_url,
                'publicExamUrl' => $this->order->report?->share_url,
            ]);

        $pdfPath = $this->order->report?->pdf_path;

        if ($pdfPath && Storage::disk('local')->exists($pdfPath)) {
            $mail->attach(Storage::disk('local')->path($pdfPath), [
                'as' => basename($pdfPath),
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}