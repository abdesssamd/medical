<?php

namespace Modules\ClinicalRecord\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OfficialMedicationApiClient
{
    public function isEnabled(): bool
    {
        return (bool) config('services.medication_api.enabled', false)
            && ! empty(config('services.medication_api.base_url'))
            && ! empty(config('services.medication_api.api_key'));
    }

    public function search(string $query, int $limit = 12): array
    {
        if (! $this->isEnabled()) {
            return [];
        }

        try {
            $url = rtrim((string) config('services.medication_api.base_url'), '/')
                .(string) config('services.medication_api.search_path', '/medications/search');
            $response = $this->baseRequest()
                ->get($url, [
                    'q' => $query,
                    'limit' => $limit,
                ]);

            if (! $response->ok()) {
                Log::warning('medication_api.search.http_error', ['status' => $response->status()]);
                return [];
            }

            $payload = $response->json();
            $rows = Arr::get($payload, 'items', Arr::get($payload, 'data', []));
            if (! is_array($rows)) {
                return [];
            }

            return collect($rows)->map(function ($row): array {
                return [
                    'id' => $row['id'] ?? null,
                    'name' => $row['name'] ?? $row['label'] ?? 'Medicament',
                    'category' => $row['category'] ?? $row['class'] ?? null,
                    'strength' => $row['strength'] ?? $row['dosage_form'] ?? null,
                    'default_unit' => $row['default_unit'] ?? 'comprime',
                    'default_frequency' => $row['default_frequency'] ?? 'Matin/Midi/Soir',
                    'default_duration_days' => (int) ($row['default_duration_days'] ?? 3),
                    'allergen_keywords' => $row['allergen_keywords'] ?? [],
                    'contraindication_tags' => $row['contraindication_tags'] ?? [],
                    'source' => 'official_api',
                ];
            })->values()->all();
        } catch (\Throwable $e) {
            Log::warning('medication_api.search.exception', ['message' => $e->getMessage()]);
            return [];
        }
    }

    public function safetyCheck(array $patient, array $items): array
    {
        if (! $this->isEnabled()) {
            return ['blocking' => [], 'warnings' => []];
        }

        try {
            $url = rtrim((string) config('services.medication_api.base_url'), '/')
                .(string) config('services.medication_api.safety_path', '/medications/safety-check');
            $response = $this->baseRequest()
                ->post($url, [
                    'patient' => $patient,
                    'items' => $items,
                ]);

            if (! $response->ok()) {
                Log::warning('medication_api.safety.http_error', ['status' => $response->status()]);
                return ['blocking' => [], 'warnings' => []];
            }

            $payload = $response->json() ?: [];

            return [
                'blocking' => array_values(array_filter((array) ($payload['blocking'] ?? []))),
                'warnings' => array_values(array_filter((array) ($payload['warnings'] ?? []))),
            ];
        } catch (\Throwable $e) {
            Log::warning('medication_api.safety.exception', ['message' => $e->getMessage()]);
            return ['blocking' => [], 'warnings' => []];
        }
    }

    public function ping(): array
    {
        if (! $this->isEnabled()) {
            return ['ok' => false, 'message' => 'Medication API disabled'];
        }

        try {
            $url = rtrim((string) config('services.medication_api.base_url'), '/')
                .(string) config('services.medication_api.ping_path', '/status');

            $response = $this->baseRequest()->get($url);

            return [
                'ok' => $response->ok(),
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    private function baseRequest(): \Illuminate\Http\Client\PendingRequest
    {
        $request = Http::timeout((int) config('services.medication_api.timeout', 8))
            ->acceptJson();

        $apiKey = (string) config('services.medication_api.api_key');
        $authMode = (string) config('services.medication_api.auth_mode', 'x_api_key');

        if ($apiKey !== '') {
            if ($authMode === 'bearer') {
                $request = $request->withToken($apiKey);
            } else {
                $headerName = (string) config('services.medication_api.auth_header', 'X-API-KEY');
                $request = $request->withHeaders([$headerName => $apiKey]);
            }
        }

        return $request;
    }
}
