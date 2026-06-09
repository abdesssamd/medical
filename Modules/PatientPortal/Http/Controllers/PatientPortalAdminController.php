<?php

namespace Modules\PatientPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Modules\PatientPortal\Mail\PatientPortalAccessMail;
use Modules\PatientPortal\Models\PatientPortalAccess;
use Modules\PatientPortal\Services\PatientPortalAccessService;

class PatientPortalAdminController extends Controller
{
    public function index(Request $request): View
    {
        $query = PatientPortalAccess::query()
            ->with(['patient', 'order.procedure', 'order.modality', 'report', 'logs'])
            ->withCount('logs')
            ->orderByDesc('created_at');

        if ($search = trim((string) $request->string('search'))) {
            $query->where(function ($innerQuery) use ($search): void {
                $innerQuery->where('access_token', 'like', '%'.$search.'%')
                    ->orWhere('access_code_last4', 'like', '%'.$search.'%')
                    ->orWhereHas('patient', function ($patientQuery) use ($search): void {
                        $patientQuery->where('medical_record_number', 'like', '%'.$search.'%')
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%'.$search.'%']);
                    })
                    ->orWhereHas('order', function ($orderQuery) use ($search): void {
                        $orderQuery->where('accession_number', 'like', '%'.$search.'%');
                    });
            });
        }

        if ($state = $request->string('state')->toString()) {
            match ($state) {
                'active' => $query->whereNull('revoked_at')->where(function ($innerQuery): void {
                    $innerQuery->whereNull('expires_at')->orWhere('expires_at', '>', now());
                }),
                'expired' => $query->whereNotNull('expires_at')->where('expires_at', '<=', now()),
                'locked' => $query->whereNotNull('locked_until_at')->where('locked_until_at', '>', now()),
                'revoked' => $query->whereNotNull('revoked_at'),
                default => null,
            };
        }

        $accesses = $query->paginate(12)->withQueryString();

        $stats = [
            'total' => PatientPortalAccess::count(),
            'active' => PatientPortalAccess::query()->whereNull('revoked_at')->where(function ($q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->count(),
            'expired' => PatientPortalAccess::query()->whereNotNull('expires_at')->where('expires_at', '<=', now())->count(),
            'locked' => PatientPortalAccess::query()->whereNotNull('locked_until_at')->where('locked_until_at', '>', now())->count(),
        ];

        $recentLogs = PatientPortalAccess::query()
            ->with(['patient', 'logs' => fn ($logQuery) => $logQuery->latest()])
            ->whereHas('logs')
            ->latest('last_access_at')
            ->limit(8)
            ->get();

        return view('patient_portal::admin.index', compact('accesses', 'stats', 'recentLogs'));
    }

    public function show(PatientPortalAccess $access, PatientPortalAccessService $service): View
    {
        $access->load(['patient', 'order.procedure', 'order.modality', 'order.report', 'logs' => fn ($query) => $query->latest()]);

        return view('patient_portal::admin.show', [
            'access' => $access,
            'portalUrl' => $service->buildPortalUrl($access),
            'qrCodeSvg' => $service->buildLoginQrSvg($access),
            'printableCode' => $service->getPrintableAccessCode($access),
            'viewerUrl' => $service->resolveViewerUrl($access->order),
        ]);
    }

    public function memo(PatientPortalAccess $access, PatientPortalAccessService $service): View
    {
        $access->load(['patient', 'order.procedure', 'order.modality', 'order.report']);

        return view('patient_portal::admin.memo', [
            'access' => $access,
            'portalUrl' => $service->buildPortalUrl($access),
            'qrCodeSvg' => $service->buildLoginQrSvg($access),
            'printableCode' => $service->getPrintableAccessCode($access),
        ]);
    }

    public function sendEmail(PatientPortalAccess $access, PatientPortalAccessService $service): RedirectResponse
    {
        $patient = $access->patient;
        $email = $patient?->email;

        if (! $email) {
            return back()->with('error', 'Aucune adresse email renseignée pour ce patient.');
        }

        $plainCode = $service->getPrintableAccessCode($access);

        if (! $plainCode) {
            return back()->with('error', 'Impossible de décrypter le code d\'accès.');
        }

        Mail::to($email)->send(new PatientPortalAccessMail($access, $plainCode));

        $service->markAccessed($access, 'email_sent', [
            'to' => $email,
            'delivery' => 'direct',
        ]);

        return back()->with('success', "Email envoyé à {$email} avec les informations d'accès.");
    }
}
