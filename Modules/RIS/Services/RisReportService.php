<?php

namespace Modules\RIS\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\RIS\Jobs\SendSignedReportJob;
use Modules\RIS\Events\RisOrderCompleted;
use Modules\RIS\Mail\RisSignedReportMail;
use Modules\RIS\Models\RisOrder;
use Modules\RIS\Models\RisReport;

class RisReportService
{
    public function generatePdfResponse(RisOrder $order)
    {
        $order->loadMissing(['patient', 'procedure', 'modality', 'report.signingPhysician', 'requestedBy']);
        $filename = 'ris-report-'.($order->accession_number ?: $order->id).'.pdf';
        $html = view('ris::reports.pdf', [
            'order' => $order,
            'qrCodeSvg' => $this->buildQrCodeSvg($order),
            'publicExamUrl' => $this->resolvePublicExamUrl($order),
        ])->render();

        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]);
        }

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    public function signReport(RisOrder $order, ?User $physician = null, ?string $content = null, bool $sendCopy = false): RisReport
    {
        $order->loadMissing(['patient', 'procedure', 'modality', 'report.signingPhysician', 'requestedBy']);
        $physician ??= auth()->user();
        $report = $order->report ?: new RisReport(['order_id' => $order->id]);
        $report->content = $content ? $this->normalizeReportContent($content) : ($report->content ?: 'Compte rendu a completer.');
        $report->signing_physician_id = $physician?->id;
        $report->signing_physician_name = $physician?->display_name ?: $physician?->name;
        $report->validated_at = now();
        $report->share_token = $report->share_token ?: Str::random(48);
        $report->share_expires_at = now()->addDays((int) config('ris.reports.share_valid_days', 30));
        $report->share_url = route('ris.reports.shared', ['token' => $report->share_token]);
        $report->save();

        $order->forceFill([
            'status' => RisOrder::STATUS_TERMINE,
            'completed_at' => now(),
            'received_at' => $order->received_at ?? now(),
        ])->save();

        $freshOrder = $order->fresh(['patient', 'procedure', 'modality', 'report.signingPhysician', 'requestedBy']);
        $this->persistPdf($freshOrder ?? $order);

        if ($sendCopy) {
            $this->sendSignedReport($freshOrder ?? $order);
        }

        event(new RisOrderCompleted($freshOrder ?? $order));

        return ($freshOrder ?? $order)->report;
    }

    public function sendSignedReport(RisOrder $order): array
    {
        $order->loadMissing(['patient', 'procedure', 'modality', 'report.signingPhysician', 'requestedBy']);

        if (! $order->report || ! $order->report->validated_at) {
            return ['queued' => 0, 'recipients' => []];
        }

        $recipients = $this->resolveEmailRecipients($order);

        if ($recipients === []) {
            return ['queued' => 0, 'recipients' => []];
        }

        SendSignedReportJob::dispatch((int) $order->id);

        return ['queued' => count($recipients), 'recipients' => array_keys($recipients)];
    }

    public function sendSignedReportNow(RisOrder $order): array
    {
        $order->loadMissing(['patient', 'procedure', 'modality', 'report.signingPhysician', 'requestedBy']);

        if (! $order->report || ! $order->report->validated_at) {
            return ['sent' => 0, 'recipients' => []];
        }

        $this->persistPdf($order);

        $recipients = $this->resolveEmailRecipients($order);

        if ($recipients === []) {
            return ['sent' => 0, 'recipients' => []];
        }

        foreach ($recipients as $email => $label) {
            Mail::to($email)->send(new RisSignedReportMail($order, $label));
        }

        return ['sent' => count($recipients), 'recipients' => array_keys($recipients)];
    }

    private function persistPdf(RisOrder $order): void
    {
        if (! class_exists(\Dompdf\Dompdf::class) || ! $order->report) {
            return;
        }

        $html = view('ris::reports.pdf', [
            'order' => $order,
            'qrCodeSvg' => $this->buildQrCodeSvg($order),
            'publicExamUrl' => $this->resolvePublicExamUrl($order),
        ])->render();
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $path = 'ris/reports/'.($order->accession_number ?: $order->id).'.pdf';
        Storage::disk('local')->put($path, $dompdf->output());

        $order->report->forceFill(['pdf_path' => $path])->save();
    }

    private function normalizeReportContent(string $content): string
    {
        $content = trim($content);

        if ($content === '') {
            return '';
        }

        return strip_tags($content, '<p><br><strong><b><em><i><ul><ol><li><table><thead><tbody><tr><th><td><div><span>');
    }

    private function resolvePublicExamUrl(RisOrder $order): ?string
    {
        return $order->report?->share_url ?: null;
    }

    private function buildQrCodeSvg(RisOrder $order): ?string
    {
        $url = $this->resolvePublicExamUrl($order);
        if ($url === null || $url === '' || ! class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
            return null;
        }

        try {
            $svg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(118)
                ->margin(1)
                ->generate($url);

            return $svg;
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveEmailRecipients(RisOrder $order): array
    {
        $recipients = [];

        foreach ([
            [$order->patient?->email, 'Patient'],
            [$order->requestedBy?->email, 'Prescripteur'],
        ] as [$candidate, $label]) {
            $email = trim((string) $candidate);

            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $recipients[$email] = $label;
            }
        }

        return $recipients;
    }
}
