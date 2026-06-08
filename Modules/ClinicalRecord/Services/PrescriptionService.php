<?php

namespace Modules\ClinicalRecord\Services;

use App\Models\Patient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\ClinicalRecord\Models\HealthQuestionnaire;
use Modules\ClinicalRecord\Models\Medication;
use Modules\ClinicalRecord\Models\Prescription;
use Modules\ClinicalRecord\Models\PrescriptionItem;
use Modules\ClinicalRecord\Models\PrescriptionTemplate;

class PrescriptionService
{
    public function __construct(
        private readonly OfficialMedicationApiClient $officialMedicationApiClient
    ) {}

    public function searchMedications(string $query, int $limit = 12)
    {
        $official = collect($this->officialMedicationApiClient->search($query, $limit));

        $local = Medication::query()
            ->where('is_active', true)
            ->where(function ($q) use ($query): void {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('category', 'like', "%{$query}%")
                    ->orWhere('strength', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Medication $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'category' => $m->category,
                'strength' => $m->strength,
                'default_unit' => $m->default_unit,
                'default_frequency' => $m->default_frequency,
                'default_duration_days' => $m->default_duration_days,
                'allergen_keywords' => $m->allergen_keywords ?? [],
                'contraindication_tags' => $m->contraindication_tags ?? [],
                'source' => 'local',
            ]);

        return $official
            ->concat($local)
            ->unique(fn ($row) => mb_strtolower((string) ($row['name'] ?? '')))
            ->take($limit)
            ->values();
    }

    public function templateData(int $templateId): array
    {
        $template = PrescriptionTemplate::with('items.medication')->findOrFail($templateId);

        return [
            'id' => $template->id,
            'name' => $template->name,
            'items' => $template->items->map(function ($item): array {
                return [
                    'medication_id' => $item->medication_id,
                    'medication_name' => $item->medication_name,
                    'dosage' => $item->dosage,
                    'unit' => $item->unit,
                    'frequency' => $item->frequency,
                    'duration_days' => $item->duration_days,
                    'instructions' => $item->instructions,
                ];
            })->values()->all(),
        ];
    }

    public function analyzeSafety(Patient $patient, array $items): array
    {
        $allergies = collect($patient->allergies ?? [])->map(fn ($v) => mb_strtolower((string) $v))->filter();
        $historyTags = collect($patient->medical_history ?? [])->map(fn ($v) => mb_strtolower((string) $v))->filter();

        $q = HealthQuestionnaire::where('patient_id', $patient->id)->latest('filled_on')->first();
        $riskTags = collect($q?->risk_tags ?? [])->map(fn ($v) => mb_strtolower((string) $v))->filter();

        $blocking = [];
        $warnings = [];
        $resolvedItems = [];

        foreach ($items as $row) {
            $med = null;
            if (! empty($row['medication_id'])) {
                $med = Medication::find($row['medication_id']);
            }
            if (! $med && ! empty($row['medication_name'])) {
                $med = Medication::where('name', $row['medication_name'])->first();
            }

            $name = $med?->name ?? (string) ($row['medication_name'] ?? 'Medicament');

            $allergenKeywords = collect($med?->allergen_keywords ?? [])->map(fn ($v) => mb_strtolower((string) $v));
            $contraTags = collect($med?->contraindication_tags ?? [])->map(fn ($v) => mb_strtolower((string) $v));
            $itemAlerts = [];

            foreach ($allergenKeywords as $kw) {
                if ($kw === '') continue;
                if ($allergies->contains(fn ($a) => str_contains($a, $kw) || str_contains($kw, $a))) {
                    $msg = "Alerte allergie bloquante: {$name} incompatible avec allergie patient ({$kw}).";
                    $blocking[] = $msg;
                    $itemAlerts[] = $msg;
                }
            }

            foreach ($contraTags as $tag) {
                if ($tag === '') continue;
                $hasTag = $riskTags->contains(fn ($r) => str_contains($r, $tag) || str_contains($tag, $r))
                    || $historyTags->contains(fn ($h) => str_contains($h, $tag) || str_contains($tag, $h));
                if ($hasTag) {
                    $msg = "Contre-indication: {$name} non recommande pour terrain {$tag}.";
                    $warnings[] = $msg;
                    $itemAlerts[] = $msg;
                }
            }

            $resolvedItems[] = [
                'medication_id' => $med?->id,
                'medication_name' => $name,
                'dosage' => (string) ($row['dosage'] ?? ''),
                'unit' => (string) ($row['unit'] ?? ''),
                'frequency' => (string) ($row['frequency'] ?? ''),
                'duration_days' => (int) ($row['duration_days'] ?? 0),
                'instructions' => (string) ($row['instructions'] ?? ''),
                'alerts' => $itemAlerts,
            ];
        }

        $pairWarnings = $this->detectPairInteractions($resolvedItems);
        $warnings = array_values(array_unique(array_merge($warnings, $pairWarnings)));

        $officialSafety = $this->officialMedicationApiClient->safetyCheck(
            patient: [
                'id' => $patient->id,
                'allergies' => $patient->allergies ?? [],
                'medical_history' => $patient->medical_history ?? [],
                'risk_tags' => $riskTags->values()->all(),
            ],
            items: $resolvedItems
        );

        return [
            'blocking' => array_values(array_unique(array_merge($blocking, $officialSafety['blocking'] ?? []))),
            'warnings' => array_values(array_unique(array_merge($warnings, $officialSafety['warnings'] ?? []))),
            'items' => $resolvedItems,
        ];
    }

    private function detectPairInteractions(array $items): array
    {
        $warnings = [];
        $names = collect($items)->pluck('medication_name')->map(fn ($v) => mb_strtolower((string) $v))->values();

        $pairs = [
            ['ibuprofene', 'warfarine', 'Interaction potentielle: AINS + anticoagulant (risque hemorragique).'],
            ['ibuprofene', 'anticoagulant', 'Interaction potentielle: AINS + anticoagulant (risque hemorragique).'],
            ['amoxicilline', 'methotrexate', 'Interaction a surveiller: Amoxicilline + methotrexate.'],
        ];

        foreach ($pairs as [$a, $b, $msg]) {
            $hasA = $names->contains(fn ($n) => str_contains($n, $a));
            $hasB = $names->contains(fn ($n) => str_contains($n, $b));
            if ($hasA && $hasB) {
                $warnings[] = $msg;
            }
        }

        return $warnings;
    }

    public function createPrescription(Patient $patient, array $payload): Prescription
    {
        return DB::transaction(function () use ($patient, $payload): Prescription {
            $itemsInput = $payload['items'] ?? [];
            $analysis = $this->analyzeSafety($patient, $itemsInput);

            if (! empty($analysis['blocking'])) {
                abort(422, 'Prescription bloquee pour risque allergique/interaction critique.');
            }

            $issuedAt = now();
            $prescription = Prescription::create([
                'patient_id' => $patient->id,
                'consultation_id' => $payload['consultation_id'] ?? null,
                'practitioner_id' => $payload['practitioner_id'] ?? auth()->id(),
                'prescription_template_id' => $payload['prescription_template_id'] ?? null,
                'prescription_number' => Prescription::generateNumber(),
                'issued_at' => $issuedAt,
                'status' => 'issued',
                'qr_token' => Str::random(40),
                'signature_mode' => $payload['signature_mode'] ?? 'digital',
                'signature_payload' => $payload['signature_payload'] ?? null,
                'safety_alerts' => [
                    'blocking' => $analysis['blocking'],
                    'warnings' => $analysis['warnings'],
                ],
                'immutable_payload' => [
                    'patient_snapshot' => [
                        'full_name' => $patient->full_name,
                        'mrn' => $patient->medical_record_number,
                        'dob' => optional($patient->date_of_birth)->toDateString(),
                        'allergies' => $patient->allergies,
                    ],
                    'items' => $analysis['items'],
                    'notes' => $payload['notes'] ?? null,
                    'issued_at' => $issuedAt->toDateTimeString(),
                ],
            ]);

            foreach ($analysis['items'] as $item) {
                PrescriptionItem::create([
                    'prescription_id' => $prescription->id,
                    'medication_id' => $item['medication_id'],
                    'medication_name' => $item['medication_name'],
                    'dosage' => $item['dosage'],
                    'unit' => $item['unit'],
                    'frequency' => $item['frequency'],
                    'duration_days' => $item['duration_days'] ?: null,
                    'instructions' => $item['instructions'],
                    'interaction_level' => ! empty($item['alerts']) ? 'warning' : 'ok',
                    'alerts' => $item['alerts'],
                ]);
            }

            return $prescription->fresh(['items', 'patient', 'practitioner', 'template']);
        });
    }

    public function buildPdfData(Prescription $prescription): array
    {
        $prescription->loadMissing(['patient', 'practitioner', 'items', 'template']);

        return [
            'prescription' => $prescription,
            'patient' => $prescription->patient,
            'practitioner' => $prescription->practitioner,
            'items' => $prescription->items,
            'verifyUrl' => route('care.module3.prescriptions.verify', ['token' => $prescription->qr_token]),
            'issuedAt' => $prescription->issued_at,
        ];
    }

    public function sendToEmail(Prescription $prescription, string $email): void
    {
        Log::info('prescription.email.send', [
            'prescription_id' => $prescription->id,
            'to' => $email,
        ]);

        $prescription->update([
            'sent_to_email' => $email,
            'sent_at' => now(),
        ]);
    }
}
