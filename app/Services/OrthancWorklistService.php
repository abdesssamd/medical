<?php

namespace App\Services;

use App\Models\Patient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\ClinicalRecord\Models\RadiologyRequest;

class OrthancWorklistService
{
    public function createAndDispatch(Patient $patient, RadiologyRequest $request): array
    {
        $dataset = $this->buildDataset($patient, $request);
        $filePath = $this->writeWorklistFile($request, $dataset);
        $orthanc = $this->pushToOrthanc($dataset);

        return [
            'dataset' => $dataset,
            'worklist_file_path' => $filePath,
            'orthanc' => $orthanc,
        ];
    }

    public function buildDataset(Patient $patient, RadiologyRequest $request): array
    {
        $patientName = trim((string) $patient->last_name).'^'.trim((string) $patient->first_name);
        $birthDate = optional($patient->date_of_birth)->format('Ymd');
        $sex = $this->normalizeSex($patient->gender);
        $procedureDescription = $request->requested_procedure_description;
        $now = now();

        return [
            '0008,0050' => $request->accession_number,
            '0010,0010' => $patientName,
            '0010,0020' => (string) ($patient->medical_record_number ?: $patient->id),
            '0010,0030' => $birthDate,
            '0010,0040' => $sex,
            '0020,000D' => $request->study_instance_uid,
            '0032,1060' => $procedureDescription,
            '0040,1001' => $request->accession_number,
            '0040,0100' => [
                [
                    '0008,0060' => $request->target_modality,
                    '0040,0001' => $request->scheduled_station_ae_title,
                    '0040,0002' => $now->format('Ymd'),
                    '0040,0003' => $now->format('His'),
                    '0040,0006' => (string) optional($request->prescribingPhysician)->name,
                    '0040,0007' => $procedureDescription,
                    '0040,0009' => $request->accession_number.'-1',
                ],
            ],
        ];
    }

    public function writeWorklistFile(RadiologyRequest $request, array $dataset): ?string
    {
        $dir = trim((string) config('services.orthanc.worklist_directory', ''));
        if ($dir === '') {
            return null;
        }

        $payload = json_encode($dataset, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            return null;
        }

        $filename = $request->accession_number.'.wl.json';
        $jsonPath = null;
        $jsonFsPath = null;

        if (str_starts_with($dir, 'storage:')) {
            $relative = trim(substr($dir, 8), '/');
            $path = $relative.'/'.$filename;
            Storage::disk('local')->put($path, $payload);
            $jsonPath = 'storage/app/'.$path;
            $jsonFsPath = Storage::disk('local')->path($path);
        } else {
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }

            $jsonFsPath = rtrim($dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$filename;
            @file_put_contents($jsonFsPath, $payload);
            $jsonPath = $jsonFsPath;
        }

        // Optional conversion to native DICOM worklist file if dcmtk dump2dcm is available.
        $dump2dcm = $this->resolveDump2DcmCommand();
        if ($dump2dcm !== null && $jsonFsPath !== null) {
            $targetDir = dirname($jsonFsPath);
            $wlPath = $targetDir.DIRECTORY_SEPARATOR.$request->accession_number.'.wl';
            $dumpPath = $targetDir.DIRECTORY_SEPARATOR.$request->accession_number.'.dump';
            @file_put_contents($dumpPath, $this->toDcmtkDump($dataset));
            @shell_exec('"'.$dump2dcm.'" "'.$dumpPath.'" "'.$wlPath.'" 2>&1');
            if (is_file($wlPath) && filesize($wlPath) > 0) {
                if (str_starts_with($dir, 'storage:')) {
                    $relativeDir = trim(substr($dir, 8), '/');
                    return 'storage/app/'.$relativeDir.'/'.$request->accession_number.'.wl';
                }

                return $wlPath;
            }
        }

        return $jsonPath;
    }

    public function pushToOrthanc(array $dataset): array
    {
        $baseUrl = rtrim((string) config('services.orthanc.base_url', ''), '/');
        if ($baseUrl === '') {
            return ['ok' => false, 'message' => 'Orthanc base URL not configured'];
        }

        $endpoint = $baseUrl.'/'.ltrim((string) config('services.orthanc.worklist_path', '/worklists'), '/');
        $username = (string) config('services.orthanc.username', '');
        $password = (string) config('services.orthanc.password', '');
        $timeout = (int) config('services.orthanc.timeout', 8);

        $client = Http::timeout($timeout);
        if ($username !== '' || $password !== '') {
            $client = $client->withBasicAuth($username, $password);
        }

        try {
            $response = $client->post($endpoint, ['Tags' => $dataset]);
            if ($response->successful()) {
                return ['ok' => true, 'body' => $response->json()];
            }

            $retry = $client->post($endpoint, $dataset);
            if ($retry->successful()) {
                return ['ok' => true, 'body' => $retry->json()];
            }

            return [
                'ok' => false,
                'status' => $retry->status(),
                'body' => $retry->json() ?: $retry->body(),
            ];
        } catch (\Throwable $e) {
            Log::warning('Orthanc worklist push failed', ['message' => $e->getMessage()]);

            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    private function normalizeSex(?string $gender): string
    {
        $value = strtolower((string) $gender);

        return match ($value) {
            'male', 'm', 'homme' => 'M',
            'female', 'f', 'femme' => 'F',
            default => 'O',
        };
    }

    private function toDcmtkDump(array $dataset): string
    {
        $lines = [
            '# DICOM dump generated by MediOffice for Orthanc MWL',
            '(0008,0005) CS [ISO_IR 100]',
            '(0008,0050) SH ['.($dataset['0008,0050'] ?? '').']',
            '(0010,0010) PN ['.($dataset['0010,0010'] ?? '').']',
            '(0010,0020) LO ['.($dataset['0010,0020'] ?? '').']',
            '(0010,0030) DA ['.($dataset['0010,0030'] ?? '').']',
            '(0010,0040) CS ['.($dataset['0010,0040'] ?? 'O').']',
            '(0020,000D) UI ['.($dataset['0020,000D'] ?? '').']',
            '(0032,1060) LO ['.($dataset['0032,1060'] ?? '').']',
            '(0040,1001) SH ['.($dataset['0040,1001'] ?? '').']',
            '(0040,0100) SQ (Sequence with explicit length #=1)',
            '  (fffe,e000) na (Item with explicit length #=7)',
            '    (0008,0060) CS ['.($dataset['0040,0100'][0]['0008,0060'] ?? 'OT').']',
            '    (0040,0001) AE ['.($dataset['0040,0100'][0]['0040,0001'] ?? 'MODALITY_AE').']',
            '    (0040,0002) DA ['.($dataset['0040,0100'][0]['0040,0002'] ?? now()->format('Ymd')).']',
            '    (0040,0003) TM ['.($dataset['0040,0100'][0]['0040,0003'] ?? now()->format('His')).']',
            '    (0040,0006) PN ['.($dataset['0040,0100'][0]['0040,0006'] ?? '').']',
            '    (0040,0007) LO ['.($dataset['0040,0100'][0]['0040,0007'] ?? '').']',
            '    (0040,0009) SH ['.($dataset['0040,0100'][0]['0040,0009'] ?? '').']',
            '  (fffe,e00d) na (ItemDelimitationItem)',
            '(fffe,e0dd) na (SequenceDelimitationItem)',
        ];

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    private function resolveDump2DcmCommand(): ?string
    {
        $configured = trim((string) config('services.orthanc.dump2dcm_path', ''));
        if ($configured !== '' && is_file($configured)) {
            return $configured;
        }

        $lookupCommand = PHP_OS_FAMILY === 'Windows' ? 'where dump2dcm' : 'command -v dump2dcm';
        $lookup = trim((string) @shell_exec($lookupCommand));
        if ($lookup === '') {
            return null;
        }

        $first = preg_split('/\r\n|\r|\n/', $lookup)[0] ?? '';
        $candidate = trim((string) $first);
        if ($candidate === '') {
            return null;
        }

        return $candidate;
    }
}
