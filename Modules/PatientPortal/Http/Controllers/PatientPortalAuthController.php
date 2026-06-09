<?php

namespace Modules\PatientPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\PatientPortal\Models\PatientPortalAccess;
use Modules\PatientPortal\Services\PatientPortalAccessService;

class PatientPortalAuthController extends Controller
{
    public function showLogin(Request $request, ?string $token = null): View|RedirectResponse
    {
        $entryToken = $token ?: trim((string) $request->query('token', ''));

        $access = null;
        if ($entryToken !== '') {
            $access = PatientPortalAccess::query()
                ->with(['patient', 'order.report'])
                ->where('access_token', $entryToken)
                ->first();
        }

        if ($request->session()->has('patient_portal_access_id')) {
            return redirect()->route('patient-portal.dashboard');
        }

        return view('patient_portal::auth.login', [
            'entryToken' => $entryToken,
            'access' => $access,
        ]);
    }

    public function authenticate(Request $request, PatientPortalAccessService $service): RedirectResponse
    {
        $validated = $request->validate([
            'entry_token' => ['nullable', 'string', 'max:80'],
            'medical_record_number' => ['required', 'string', 'max:40'],
            'date_of_birth' => ['required', 'date'],
            'access_code' => ['required', 'string', 'max:20'],
        ]);

        $access = $service->authenticate($validated);

        $request->session()->regenerate();
        $request->session()->put([
            'patient_portal_access_id' => $access->id,
            'patient_portal_patient_id' => $access->patient_id,
            'patient_portal_access_token' => $access->access_token,
            'patient_portal_authenticated_at' => now()->toIso8601String(),
        ]);

        return redirect()
            ->route('patient-portal.dashboard')
            ->with('success', 'Connexion reussie. Vos resultats sont maintenant disponibles.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget([
            'patient_portal_access_id',
            'patient_portal_patient_id',
            'patient_portal_access_token',
            'patient_portal_authenticated_at',
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('patient-portal.login')->with('success', 'Vous avez ete deconnecte.');
    }
}
