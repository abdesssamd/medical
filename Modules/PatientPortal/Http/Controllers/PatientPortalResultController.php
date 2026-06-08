<?php

namespace Modules\PatientPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\PatientPortal\Models\PatientPortalAccess;
use Modules\PatientPortal\Services\PatientPortalAccessService;
use Modules\RIS\Models\RisOrder;
use Modules\RIS\Services\RisReportService;

class PatientPortalResultController extends Controller
{
    public function show(Request $request, PatientPortalAccessService $service): View
    {
        /** @var PatientPortalAccess $access */
        $access = $request->attributes->get('patient_portal_access')
            ?: PatientPortalAccess::query()->with(['patient', 'order.procedure', 'order.modality', 'order.report'])->findOrFail((int) $request->session()->get('patient_portal_access_id'));

        $order = $access->order()->with(['patient', 'procedure', 'modality', 'report'])->first();
        $report = $order?->report;

        $service->markAccessed($access, 'result_view');

        return view('patient_portal::results.show', [
            'access' => $access,
            'patient' => $access->patient,
            'order' => $order,
            'report' => $report,
            'pdfUrl' => route('patient-portal.results.pdf'),
            'viewerUrl' => $order ? $service->resolveViewerUrl($order) : null,
            'qrCodeSvg' => $service->buildLoginQrSvg($access),
            'portalUrl' => $service->buildPortalUrl($access),
        ]);
    }

    public function downloadPdf(Request $request, RisReportService $reportService): mixed
    {
        /** @var PatientPortalAccess $access */
        $access = $request->attributes->get('patient_portal_access');
        $order = $access?->order()->with(['patient', 'procedure', 'modality', 'report.signingPhysician'])->first();

        if (! $order || ! $order->report) {
            abort(404);
        }

        $access->logs()->create([
            'event_type' => 'pdf_download',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'context' => [
                'order_id' => $order->id,
            ],
        ]);

        if ($order->report->pdf_path && Storage::disk('local')->exists($order->report->pdf_path)) {
            return Storage::disk('local')->download($order->report->pdf_path, 'resultat-'.$order->accession_number.'.pdf');
        }

        return $reportService->generatePdfResponse($order);
    }

    public function viewer(Request $request, PatientPortalAccessService $service): View
    {
        /** @var PatientPortalAccess $access */
        $access = $request->attributes->get('patient_portal_access');
        $order = $access?->order()->with(['patient', 'procedure', 'modality', 'report'])->first();

        $service->markAccessed($access, 'viewer_open');

        return view('patient_portal::results.viewer', [
            'access' => $access,
            'order' => $order,
            'viewerUrl' => $order ? $service->resolveViewerUrl($order) : null,
        ]);
    }
}
