<?php

namespace Modules\PatientPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\PatientPortal\Models\PatientPortalAccess;

class PatientPortalDashboardController extends Controller
{
    public function index(Request $request): View
    {
        /** @var PatientPortalAccess $access */
        $access = $request->attributes->get('patient_portal_access')
            ?: PatientPortalAccess::query()->with(['patient', 'order.procedure', 'order.modality', 'order.report'])->findOrFail((int) $request->session()->get('patient_portal_access_id'));

        return view('patient_portal::dashboard.index', [
            'access' => $access,
            'patient' => $access->patient,
            'order' => $access->order,
            'report' => $access->order?->report,
            'viewerUrl' => app(\Modules\PatientPortal\Services\PatientPortalAccessService::class)->resolveViewerUrl($access->order),
        ]);
    }
}
