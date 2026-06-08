<?php

namespace Modules\RIS\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Modules\RIS\Models\RisOrder;

class OrthancService
{
    /**
     * Verifie le statut du serveur Orthanc.
     */
    public function checkServerStatus(): array
    {
        try {
            $response = $this->client()->get('/system');

            return [
                'ok' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
            ];
        } catch (\Throwable $exception) {
            Log::warning('ris.orthanc.unreachable', ['error' => $exception->getMessage()]);

            return [
                'ok' => false,
                'status' => null,
                'message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Retourne les studies Orthanc a partir du PatientID DICOM.
     */
    public function getPatientStudies(string $patientId): array
    {
        try {
            $searchResponse = $this->client()->post('/tools/find', [
                'Level' => 'Study',
                'Query' => [
                    'PatientID' => $patientId,
                ],
            ]);

            if (! $searchResponse->successful()) {
                return [
                    'ok' => false,
                    'status' => $searchResponse->status(),
                    'message' => $searchResponse->body(),
                    'studies' => [],
                ];
            }

            $studyIds = collect($searchResponse->json())->filter()->values();
            $studies = $studyIds
                ->map(function (string $studyId): array {
                    $response = $this->client()->get('/studies/'.$studyId);

                    return [
                        'id' => $studyId,
                        'ok' => $response->successful(),
                        'data' => $response->successful() ? $response->json() : null,
                    ];
                })
                ->all();

            return [
                'ok' => true,
                'status' => 200,
                'studies' => $studies,
            ];
        } catch (\Throwable $exception) {
            Log::warning('ris.orthanc.find_studies_failed', [
                'patient_id' => $patientId,
                'error' => $exception->getMessage(),
            ]);

            return [
                'ok' => false,
                'status' => null,
                'message' => $exception->getMessage(),
                'studies' => [],
            ];
        }
    }

    /**
     * Retourne les etudes Orthanc associees a un PatientID DICOM via les ressources patient.
     */
    public function findStudiesByPatientIdentifier(string $patientIdentifier): array
    {
        $patientIdentifier = trim($patientIdentifier);
        if ($patientIdentifier === '') {
            return [
                'ok' => false,
                'status' => 422,
                'message' => 'Missing patient identifier',
                'studies' => [],
            ];
        }

        try {
            $patientsResponse = $this->client()->post('/tools/find', [
                'Level' => 'Patient',
                'Expand' => false,
                'Query' => [
                    'PatientID' => $patientIdentifier,
                ],
            ]);

            if (! $patientsResponse->successful()) {
                return [
                    'ok' => false,
                    'status' => $patientsResponse->status(),
                    'message' => $patientsResponse->body(),
                    'studies' => [],
                ];
            }

            $orthancPatientIds = collect($patientsResponse->json())->filter()->values();
            $studies = [];

            foreach ($orthancPatientIds as $orthancPatientId) {
                $studyIdsResponse = $this->client()->get('/patients/'.$orthancPatientId.'/studies');
                if (! $studyIdsResponse->successful()) {
                    continue;
                }

                foreach (collect($studyIdsResponse->json())->filter()->values() as $studyEntry) {
                    if (is_array($studyEntry)) {
                        $study = $studyEntry;
                        $studyId = (string) ($study['ID'] ?? '');
                    } else {
                        $studyId = (string) $studyEntry;
                        $studyResponse = $this->client()->get('/studies/'.$studyId);
                        if (! $studyResponse->successful()) {
                            continue;
                        }

                        $study = (array) $studyResponse->json();
                    }

                    if ($studyId === '') {
                        continue;
                    }

                    $mainTags = (array) ($study['MainDicomTags'] ?? []);
                    $patientTags = (array) ($study['PatientMainDicomTags'] ?? []);

                    $studies[] = [
                        'orthanc_patient_id' => $orthancPatientId,
                        'study_id' => (string) ($study['ID'] ?? $studyId),
                        'patient_id' => (string) ($patientTags['PatientID'] ?? $mainTags['PatientID'] ?? data_get($study, 'PatientID', '')),
                        'study_date' => (string) ($mainTags['StudyDate'] ?? data_get($study, 'StudyDate', '')),
                        'accession_number' => (string) ($mainTags['AccessionNumber'] ?? data_get($study, 'AccessionNumber', '')),
                        'study_instance_uid' => (string) ($mainTags['StudyInstanceUID'] ?? data_get($study, 'StudyInstanceUID', '')),
                        'last_update' => (string) ($study['LastUpdate'] ?? ''),
                        'patient_main_dicom_tags' => $patientTags,
                        'main_dicom_tags' => $mainTags,
                        'raw' => $study,
                    ];
                }
            }

            if (count($studies) === 0) {
                $studySearchResponse = $this->client()->post('/tools/find', [
                    'Level' => 'Study',
                    'Expand' => true,
                    'Query' => [
                        'PatientID' => $patientIdentifier,
                    ],
                ]);

                if ($studySearchResponse->successful()) {
                    foreach (collect($studySearchResponse->json())->filter()->values() as $studyEntry) {
                        $study = (array) $studyEntry;
                        $studyId = (string) ($study['ID'] ?? '');
                        if ($studyId === '') {
                            continue;
                        }

                        $mainTags = (array) ($study['MainDicomTags'] ?? []);
                        $patientTags = (array) ($study['PatientMainDicomTags'] ?? []);

                        $studies[] = [
                            'orthanc_patient_id' => (string) ($study['ParentPatient'] ?? ''),
                            'study_id' => $studyId,
                            'patient_id' => (string) ($patientTags['PatientID'] ?? $mainTags['PatientID'] ?? data_get($study, 'PatientID', '')),
                            'study_date' => (string) ($mainTags['StudyDate'] ?? data_get($study, 'StudyDate', '')),
                            'accession_number' => (string) ($mainTags['AccessionNumber'] ?? data_get($study, 'AccessionNumber', '')),
                            'study_instance_uid' => (string) ($mainTags['StudyInstanceUID'] ?? data_get($study, 'StudyInstanceUID', '')),
                            'last_update' => (string) ($study['LastUpdate'] ?? ''),
                            'patient_main_dicom_tags' => $patientTags,
                            'main_dicom_tags' => $mainTags,
                            'raw' => $study,
                        ];
                    }
                }
            }

            return [
                'ok' => true,
                'status' => 200,
                'count' => count($studies),
                'studies' => $studies,
            ];
        } catch (\Throwable $exception) {
            Log::warning('ris.orthanc.find_patient_studies_failed', [
                'patient_identifier' => $patientIdentifier,
                'error' => $exception->getMessage(),
            ]);

            return [
                'ok' => false,
                'status' => null,
                'message' => $exception->getMessage(),
                'studies' => [],
            ];
        }
    }

    /**
     * Liste toutes les etudes Orthanc (quel que soit le patient).
     * Utilise /tools/find avec Expand pour eviter un appel par etude.
     */
    public function listAllStudies(int $limit = 100): array
    {
        try {
            $response = $this->client()->post('/tools/find', [
                'Level' => 'Study',
                'Expand' => true,
                'Limit' => $limit,
                'Query' => (object) [],
            ]);

            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'status' => $response->status(),
                    'message' => $response->body(),
                    'studies' => [],
                ];
            }

            $studies = [];
            foreach (collect($response->json())->filter()->values() as $studyEntry) {
                $study = (array) $studyEntry;
                $studyId = (string) ($study['ID'] ?? '');
                if ($studyId === '') {
                    continue;
                }

                $mainTags = (array) ($study['MainDicomTags'] ?? []);
                $patientTags = (array) ($study['PatientMainDicomTags'] ?? []);
                $patientName = (string) ($patientTags['PatientName'] ?? $mainTags['PatientName'] ?? '');

                $studies[] = [
                    'orthanc_patient_id' => (string) ($study['ParentPatient'] ?? ''),
                    'study_id' => $studyId,
                    'patient_id' => (string) ($patientTags['PatientID'] ?? $mainTags['PatientID'] ?? ''),
                    'patient_name' => str_replace('^', ' ', $patientName),
                    'study_date' => (string) ($mainTags['StudyDate'] ?? ''),
                    'study_description' => (string) ($mainTags['StudyDescription'] ?? ''),
                    'accession_number' => (string) ($mainTags['AccessionNumber'] ?? ''),
                    'study_instance_uid' => (string) ($mainTags['StudyInstanceUID'] ?? ''),
                    'modality' => (string) ($mainTags['ModalitiesInStudy'] ?? $mainTags['Modality'] ?? ''),
                    'last_update' => (string) ($study['LastUpdate'] ?? ''),
                    'patient_main_dicom_tags' => $patientTags,
                    'main_dicom_tags' => $mainTags,
                    'raw' => $study,
                ];
            }

            return [
                'ok' => true,
                'status' => 200,
                'count' => count($studies),
                'studies' => $studies,
            ];
        } catch (\Throwable $exception) {
            Log::warning('ris.orthanc.list_all_studies_failed', ['error' => $exception->getMessage()]);

            return [
                'ok' => false,
                'status' => null,
                'message' => $exception->getMessage(),
                'studies' => [],
            ];
        }
    }

    /**
     * Tente de faire correspondre une demande RIS avec les etudes Orthanc du patient.
     */
    public function reconcileOrderFromOrthanc(RisOrder $order, ?array $studies = null): array
    {
        $order->loadMissing(['patient', 'procedure', 'modality', 'requestedBy']);
        $patientIdentifier = (string) ($order->patient?->medical_record_number ?: '');

        if ($patientIdentifier === '') {
            return [
                'ok' => false,
                'matched' => false,
                'message' => 'Missing patient MRN',
                'order_id' => $order->id,
            ];
        }

        $lookup = $studies === null ? $this->findStudiesByPatientIdentifier($patientIdentifier) : ['ok' => true, 'studies' => $studies];
        $studies = (array) data_get($lookup, 'studies', []);
        $today = Carbon::today()->format('Ymd');
        $orderAccession = trim((string) ($order->accession_number ?? ''));

        $candidateStudies = collect($studies)->filter(function (array $study) use ($patientIdentifier): bool {
            $studyPatientId = trim((string) data_get($study, 'patient_id', ''));

            return $studyPatientId !== '' && $this->samePatientIdentifier($patientIdentifier, $studyPatientId);
        })->values();

        $matchedStudy = $candidateStudies->first(function (array $study) use ($orderAccession): bool {
            if ($orderAccession === '') {
                return false;
            }

            $studyAccession = trim((string) data_get($study, 'accession_number', ''));

            return $studyAccession !== '' && hash_equals($orderAccession, $studyAccession);
        });

        $matchedStudy ??= $candidateStudies->first(function (array $study) use ($today): bool {
            $studyDate = trim((string) data_get($study, 'study_date', ''));

            return $studyDate === $today;
        });

        $matchedStudy ??= $candidateStudies
            ->sortByDesc(fn (array $study): string => (string) data_get($study, 'last_update', ''))
            ->first();

        if (! is_array($matchedStudy)) {
            return [
                'ok' => true,
                'matched' => false,
                'order_id' => $order->id,
                'patient_identifier' => $patientIdentifier,
                'lookup_ok' => (bool) data_get($lookup, 'ok', true),
                'lookup_message' => data_get($lookup, 'message'),
                'studies_count' => count($studies),
                'candidate_count' => $candidateStudies->count(),
            ];
        }

        $payload = (array) ($order->orthanc_payload ?? []);
        $payload['orthanc_study_id'] = (string) data_get($matchedStudy, 'study_id', '');
        $payload['study_uid'] = (string) data_get($matchedStudy, 'study_instance_uid', '');
        $payload['reconciliation'] = [
            'source' => 'orthanc_sync',
            'patient_identifier' => $patientIdentifier,
            'matched_at' => now()->toIso8601String(),
            'matched_study' => $matchedStudy,
        ];

        $order->forceFill([
            'status' => RisOrder::STATUS_IMAGES_RECUES,
            'received_at' => $order->received_at ?? now(),
            'orthanc_payload' => $payload,
        ])->save();

        return [
            'ok' => true,
            'matched' => true,
            'order_id' => $order->id,
            'patient_identifier' => $patientIdentifier,
            'study' => $matchedStudy,
        ];
    }

    private function samePatientIdentifier(string $expected, string $actual): bool
    {
        $normalize = static fn (string $value): string => strtoupper(preg_replace('/[^A-Z0-9]/i', '', trim($value)) ?? '');

        return $normalize($expected) !== '' && hash_equals($normalize($expected), $normalize($actual));
    }

    /**
     * Cree une entree MWL dans Orthanc pour affichage sur la modalite.
     */
    public function createModalityWorklist(array $mwlDataset): array
    {
        try {
            $response = $this->client()->post($this->worklistPath(), [
                'Tags' => $mwlDataset,
            ]);

            if ($response->failed()) {
                return [
                    'ok' => false,
                    'status' => $response->status(),
                    'message' => $response->body(),
                ];
            }

            return [
                'ok' => true,
                'status' => $response->status(),
                'data' => $response->json(),
            ];
        } catch (\Throwable $exception) {
            Log::warning('ris.orthanc.create_mwl_failed', ['error' => $exception->getMessage()]);

            return [
                'ok' => false,
                'status' => null,
                'message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Cree une entree MWL a partir d'une demande RIS.
     */
    public function createModalityWorklistForOrder(RisOrder $order): array
    {
        $dataset = $this->buildMwlDatasetFromOrder($order->loadMissing(['patient', 'procedure', 'modality']));
        $filePath = $this->writeWorklistFile($order, $dataset);
        $result = $this->createModalityWorklist($dataset);

        return [
            'dataset' => $dataset,
            'worklist_file_path' => $filePath,
            'result' => $result,
        ];
    }

    /**
     * Recupere les tags simplifies d'une instance stockee dans Orthanc.
     */
    public function getInstanceSimplifiedTags(string $orthancInstanceId): array
    {
        try {
            $response = $this->client()->get('/instances/'.$orthancInstanceId.'/simplified-tags');

            if ($response->failed()) {
                return [
                    'ok' => false,
                    'status' => $response->status(),
                    'message' => $response->body(),
                ];
            }

            return [
                'ok' => true,
                'status' => $response->status(),
                'data' => $response->json(),
            ];
        } catch (\Throwable $exception) {
            Log::warning('ris.orthanc.instance_tags_failed', [
                'orthanc_id' => $orthancInstanceId,
                'error' => $exception->getMessage(),
            ]);

            return [
                'ok' => false,
                'status' => null,
                'message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Construit un jeu de tags minimal depuis une demande RIS.
     */
    public function buildMwlDatasetFromOrder(RisOrder $order): array
    {
        $patient = $order->patient;
        $procedure = $order->procedure;
        $modality = $order->modality;
        $scheduledAt = $order->scheduled_at ?? $order->requested_at ?? now();
        $accessionNumber = (string) ($order->accession_number ?: 'RIS-'.$order->id);
        $procedureLabel = (string) ($procedure?->label ?? 'Examen radiologique');
        $patientId = (string) ($patient?->medical_record_number ?: $patient?->id);
        $patientBirthDate = $patient?->date_of_birth?->format('Ymd');
        $patientSex = match (strtolower((string) $patient?->gender)) {
            'm', 'male', 'homme' => 'M',
            'f', 'female', 'femme' => 'F',
            default => 'O',
        };

        return [
            '0010,0010' => trim((string) $patient?->last_name).'^'.trim((string) $patient?->first_name),
            '0010,0020' => $patientId,
            '0010,0030' => $patientBirthDate,
            '0010,0040' => $patientSex,
            '0008,0050' => $accessionNumber,
            '0032,1060' => $procedureLabel,
            '0040,1001' => (string) ($order->requestedBy?->display_name ?? auth()->user()?->display_name ?? 'Cabinet'),
            '0038,0300' => (string) ($order->clinical_indication ?? ''),
            '0040,0100' => [
                [
                    '0008,0060' => $this->mapModalityTypeToDicom((string) ($modality?->type ?? '')),
                    '0040,0001' => (string) ($modality?->ae_title ?? 'MODALITY_AE'),
                    '0040,0002' => $scheduledAt->format('Ymd'),
                    '0040,0003' => $scheduledAt->format('His'),
                    '0040,0006' => strtoupper((string) ($procedure?->code ?? 'RISPROC')),
                    '0040,0007' => $procedureLabel,
                    '0040,0009' => $accessionNumber.'-1',
                ],
            ],
        ];
    }

    private function writeWorklistFile(RisOrder $order, array $dataset): ?string
    {
        $dir = trim((string) config('services.orthanc.worklist_directory', ''));
        if ($dir === '') {
            return null;
        }

        $payload = json_encode($dataset, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            return null;
        }

        $accessionNumber = (string) ($order->accession_number ?: 'RIS-'.$order->id);
        $filename = $accessionNumber.'.wl.json';
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

        $dump2dcm = $this->resolveDump2DcmCommand();
        if ($dump2dcm !== null && $jsonFsPath !== null) {
            $targetDir = dirname($jsonFsPath);
            $wlPath = $targetDir.DIRECTORY_SEPARATOR.$accessionNumber.'.wl';
            $dumpPath = $targetDir.DIRECTORY_SEPARATOR.$accessionNumber.'.dump';
            @file_put_contents($dumpPath, $this->toDcmtkDump($dataset));
            @shell_exec('"'.$dump2dcm.'" "'.$dumpPath.'" "'.$wlPath.'" 2>&1');
            if (is_file($wlPath) && filesize($wlPath) > 0) {
                if (str_starts_with($dir, 'storage:')) {
                    $relativeDir = trim(substr($dir, 8), '/');

                    return 'storage/app/'.$relativeDir.'/'.$accessionNumber.'.wl';
                }

                return $wlPath;
            }
        }

        return $jsonPath;
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

    private function toDcmtkDump(array $dataset): string
    {
        $scheduledProcedureStep = (array) (($dataset['0040,0100'][0] ?? []));

        $lines = [
            '# DICOM dump generated by MediOffice for Orthanc MWL',
            '(0008,0005) CS [ISO_IR 100]',
            '(0008,0050) SH ['.(string) ($dataset['0008,0050'] ?? '').']',
            '(0010,0010) PN ['.(string) ($dataset['0010,0010'] ?? '').']',
            '(0010,0020) LO ['.(string) ($dataset['0010,0020'] ?? '').']',
            '(0010,0030) DA ['.(string) ($dataset['0010,0030'] ?? '').']',
            '(0010,0040) CS ['.(string) ($dataset['0010,0040'] ?? 'O').']',
            '(0020,000D) UI ['.(string) ($dataset['0020,000D'] ?? '').']',
            '(0032,1060) LO ['.(string) ($dataset['0032,1060'] ?? '').']',
            '(0040,1001) SH ['.(string) ($dataset['0040,1001'] ?? '').']',
            '(0040,0100) SQ (Sequence with explicit length #=1)',
            '  (fffe,e000) na (Item with explicit length #=7)',
            '    (0008,0060) CS ['.(string) ($scheduledProcedureStep['0008,0060'] ?? 'OT').']',
            '    (0040,0001) AE ['.(string) ($scheduledProcedureStep['0040,0001'] ?? 'MODALITY_AE').']',
            '    (0040,0002) DA ['.(string) ($scheduledProcedureStep['0040,0002'] ?? now()->format('Ymd')).']',
            '    (0040,0003) TM ['.(string) ($scheduledProcedureStep['0040,0003'] ?? now()->format('His')).']',
            '    (0040,0006) PN ['.(string) ($scheduledProcedureStep['0040,0006'] ?? '').']',
            '    (0040,0007) LO ['.(string) ($scheduledProcedureStep['0040,0007'] ?? '').']',
            '    (0040,0009) SH ['.(string) ($scheduledProcedureStep['0040,0009'] ?? '').']',
            '  (fffe,e00d) na (ItemDelimitationItem)',
            '(fffe,e0dd) na (SequenceDelimitationItem)',
        ];

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    private function mapModalityTypeToDicom(string $type): string
    {
        return match (strtolower($type)) {
            'radio' => 'DX',
            'scanner' => 'CT',
            'panoramique' => 'PX',
            default => 'OT',
        };
    }

    private function client(): PendingRequest
    {
        $baseUrl = rtrim((string) config('ris.orthanc.base_url', ''), '/');
        $username = (string) config('ris.orthanc.username', '');
        $password = (string) config('ris.orthanc.password', '');
        $timeout = (int) config('ris.orthanc.timeout', 8);

        $client = Http::baseUrl($baseUrl)->timeout($timeout);

        if ($username !== '' || $password !== '') {
            $client = $client->withBasicAuth($username, $password);
        }

        return $client;
    }

    private function worklistPath(): string
    {
        $path = (string) config('ris.orthanc.worklist_path', '/worklists');

        return '/'.ltrim($path, '/');
    }
}
