<?php

namespace Modules\PatientPortal\Services;

use App\Models\Patient;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\PatientPortal\Models\PatientPortalAccess;
use Modules\PatientPortal\Models\PatientPortalAccessLog;
use Modules\RIS\Models\RisOrder;

class PatientPortalAccessService
{
    public function issueForOrder(RisOrder $order): array
    {
        $order->loadMissing(['patient', 'report']);

        if (! $order->patient || ! $order->report || ! $order->report->validated_at) {
            return [
                'access' => null,
                'code' => null,
                'created' => false,
                'rotated' => false,
            ];
        }

        $access = PatientPortalAccess::query()->firstOrNew([
            'order_id' => $order->id,
        ]);

        $created = ! $access->exists;
        $rotated = false;
        $plainCode = null;

        if ($created || $access->isExpired() || $access->isRevoked()) {
            $plainCode = $this->generateAccessCode();
            $rotated = ! $created && $access->exists;

            $access->fill([
                'patient_id' => $order->patient_id,
                'report_id' => $order->report->id,
                'access_token' => (string) Str::uuid(),
                'access_code_hash' => Hash::make($plainCode),
                'access_code_encrypted' => Crypt::encryptString($plainCode),
                'access_code_last4' => substr($plainCode, -4),
                'delivery_channel' => $this->resolveDeliveryChannel($order->patient),
                'delivery_email' => $order->patient->email,
                'delivery_phone' => $order->patient->phone,
                'expires_at' => now()->addDays((int) config('ris.reports.share_valid_days', 30)),
                'verified_at' => null,
                'revoked_at' => null,
                'attempt_count' => 0,
                'locked_until_at' => null,
            ]);

            $access->save();

            $this->log($access, $created ? 'created' : 'rotated', [
                'order_id' => $order->id,
            ]);
        }

        return [
            'access' => $access->fresh(['patient', 'order.report']),
            'code' => $plainCode,
            'created' => $created,
            'rotated' => $rotated,
        ];
    }

    public function authenticate(array $data): PatientPortalAccess
    {
        $token = trim((string) ($data['entry_token'] ?? ''));
        $mrn = trim((string) ($data['medical_record_number'] ?? ''));
        $birthDate = trim((string) ($data['date_of_birth'] ?? ''));
        $accessCode = trim((string) ($data['access_code'] ?? ''));

        $query = PatientPortalAccess::query()->with(['patient', 'order.report'])
            ->whereNull('revoked_at');

        if ($token !== '') {
            $query->where('access_token', $token);
        } else {
            $query->whereHas('patient', function ($patientQuery) use ($mrn, $birthDate): void {
                $patientQuery->where('medical_record_number', $mrn)
                    ->whereDate('date_of_birth', $birthDate);
            });
        }

        $candidateAccesses = $query->orderByDesc('expires_at')->get();

        if ($candidateAccesses->isEmpty()) {
            throw $this->fail('Aucun accès portail correspondant n’a été trouvé.', 'access_code');
        }

        $access = $candidateAccesses->first(function (PatientPortalAccess $candidate) use ($mrn, $birthDate, $accessCode): bool {
            if ($candidate->isExpired() || $candidate->isLocked()) {
                return false;
            }

            if (! $candidate->patient || strcasecmp((string) $candidate->patient->medical_record_number, $mrn) !== 0) {
                return false;
            }

            $patientBirthDate = optional($candidate->patient->date_of_birth)->toDateString();
            if ($patientBirthDate !== $birthDate) {
                return false;
            }

            return Hash::check($accessCode, (string) $candidate->access_code_hash);
        });

        if (! $access) {
            $first = $candidateAccesses->first();

            if ($first?->isLocked()) {
                throw $this->fail('Ce code d’accès est temporairement verrouillé.', 'access_code');
            }

            if ($first?->isExpired()) {
                throw $this->fail('Ce code d’accès a expiré.', 'access_code');
            }

            if ($first?->patient && strcasecmp((string) $first->patient->medical_record_number, $mrn) !== 0) {
                $this->recordFailedAttempt($first, 'mrn_mismatch');
                throw $this->fail('Le numéro de dossier est incorrect.', 'medical_record_number');
            }

            if ($first?->patient && optional($first->patient->date_of_birth)->toDateString() !== $birthDate) {
                $this->recordFailedAttempt($first, 'birthdate_mismatch');
                throw $this->fail('La date de naissance est incorrecte.', 'date_of_birth');
            }

            if ($first) {
                $this->recordFailedAttempt($first, 'code_mismatch');
            }

            throw $this->fail('Le code d’accès est incorrect.', 'access_code');
        }

        $access->forceFill([
            'verified_at' => $access->verified_at ?? now(),
            'attempt_count' => 0,
            'last_attempt_at' => now(),
            'locked_until_at' => null,
            'last_access_at' => now(),
        ])->save();

        $this->log($access, 'login_success', [
            'patient_id' => $access->patient_id,
            'order_id' => $access->order_id,
        ]);

        return $access->fresh(['patient', 'order.report']);
    }

    public function markAccessed(PatientPortalAccess $access, string $eventType, array $context = []): void
    {
        $access->forceFill([
            'last_access_at' => now(),
            'last_ip' => request()->ip(),
            'last_user_agent' => request()->userAgent(),
        ])->save();

        $this->log($access, $eventType, $context);
    }

    public function buildPortalUrl(PatientPortalAccess $access): string
    {
        return route('patient-portal.entry', ['token' => $access->access_token]);
    }

    public function buildLoginQrSvg(PatientPortalAccess $access): ?string
    {
        if (! class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
            return null;
        }

        try {
            return \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(180)
                ->margin(1)
                ->generate($this->buildPortalUrl($access));
        } catch (\Throwable) {
            return null;
        }
    }

    public function getPrintableAccessCode(PatientPortalAccess $access): ?string
    {
        $encrypted = trim((string) ($access->access_code_encrypted ?? ''));

        if ($encrypted === '') {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable) {
            return null;
        }
    }

    public function resolveViewerUrl(RisOrder $order): ?string
    {
        $payload = (array) ($order->orthanc_payload ?? []);

        $studyId = data_get($payload, 'study_uid')
            ?? data_get($payload, 'orthanc_study_id')
            ?? data_get($payload, 'reconciliation.matched_study.study_instance_uid')
            ?? data_get($payload, 'webhook_tags.StudyInstanceUID')
            ?? data_get($payload, 'webhook_tags.0020,000D');

        if (! $studyId) {
            return null;
        }

        $baseUrl = rtrim((string) config('ris.orthanc.viewer_base_url', config('ris.orthanc.base_url', config('services.orthanc.base_url', 'http://127.0.0.1:8042'))), '/');

        return $baseUrl.'/stone-webviewer/index.html?study='.urlencode((string) $studyId);
    }

    private function resolveDeliveryChannel(?Patient $patient): string
    {
        $channels = [];

        if ($patient?->email) {
            $channels[] = 'email';
        }

        if ($patient?->phone) {
            $channels[] = 'sms';
        }

        return $channels !== [] ? implode(',', $channels) : 'manual';
    }

    private function generateAccessCode(): string
    {
        $code = strtoupper(Str::random(8));

        return str_replace(['O', '0', 'I', '1', 'L'], ['X', '8', 'K', '7', 'M'], $code);
    }

    private function recordFailedAttempt(PatientPortalAccess $access, string $reason): void
    {
        $attemptCount = ((int) $access->attempt_count) + 1;
        $lockedUntil = $attemptCount >= 5 ? now()->addMinutes(15) : null;

        $access->forceFill([
            'attempt_count' => $attemptCount,
            'last_attempt_at' => now(),
            'locked_until_at' => $lockedUntil,
        ])->save();

        $this->log($access, 'login_failed', [
            'reason' => $reason,
            'attempt_count' => $attemptCount,
        ]);
    }

    private function log(PatientPortalAccess $access, string $eventType, array $context = []): void
    {
        PatientPortalAccessLog::query()->create([
            'patient_portal_access_id' => $access->id,
            'event_type' => $eventType,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'context' => $context,
        ]);

        Log::info('patient_portal.'.$eventType, [
            'access_id' => $access->id,
            'patient_id' => $access->patient_id,
            'order_id' => $access->order_id,
            'context' => $context,
        ]);
    }

    private function fail(string $message, string $field = 'access_code'): \Illuminate\Validation\ValidationException
    {
        return \Illuminate\Validation\ValidationException::withMessages([
            $field => $message,
        ]);
    }
}
