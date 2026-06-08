<?php

namespace Modules\PatientPortal\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\PatientPortal\Models\PatientPortalAccess;

class EnsurePatientPortalAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        $accessId = (int) $request->session()->get('patient_portal_access_id', 0);

        if ($accessId <= 0) {
            return redirect()->route('patient-portal.login');
        }

        $access = PatientPortalAccess::query()
            ->with(['patient', 'order.report'])
            ->find($accessId);

        if (! $access || $access->isRevoked() || $access->isExpired()) {
            $request->session()->forget([
                'patient_portal_access_id',
                'patient_portal_patient_id',
                'patient_portal_access_token',
                'patient_portal_authenticated_at',
            ]);

            return redirect()->route('patient-portal.login')->with('error', 'Votre session a expire. Merci de vous reconnecter.');
        }

        $request->attributes->set('patient_portal_access', $access);

        return $next($request);
    }
}
