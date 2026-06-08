<?php

namespace Modules\Appointment\Services;

use App\Models\Patient;
use Modules\Appointment\Models\Appointment;
use Modules\Appointment\Models\SecretaryTask;
use Modules\Appointment\Models\PatientJourney;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PatientOnboardingService
{
    /**
     * Crée un patient ultra-rapidement (Nom, Prénom, Tél uniquement).
     * Intègre numérisation intelligent de documents.
     */
    public function quickOnboard(
        string $firstName,
        string $lastName,
        string $phone,
        ?Appointment $appointment = null,
        ?array $documentScans = null
    ): Patient {
        return DB::transaction(function () use ($firstName, $lastName, $phone, $appointment, $documentScans) {
            // Chercher patient existant par téléphone
            $patient = Patient::where('phone', $phone)
                ->orWhere('first_name', $firstName)
                ->where('last_name', $lastName)
                ->first();

            if (!$patient) {
                // Créer nouveau patient minimal
                $patient = Patient::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => $phone,
                    'email' => null, // À remplir plus tard
                    'date_of_birth' => null, // À remplir plus tard
                    'registration_date' => now(),
                    'status' => 'active',
                ]);
            }

            // Associer à rendez-vous si fourni
            if ($appointment) {
                $appointment->update(['patient_id' => $patient->id]);

                // Initier le journey
                $this->initializeJourney($appointment);

                // Créer tâche pour compléter dossier
                $this->createOnboardingTasks($appointment, $patient);
            }

            // Traiter documents numérisés
            if ($documentScans && !empty($documentScans)) {
                $this->processDocumentScans($patient, $documentScans);
            }

            \Log::info('patient.quick_onboarded', [
                'patient_id' => $patient->id,
                'appointment_id' => $appointment?->id,
                'document_count' => count($documentScans ?? []),
            ]);

            return $patient;
        });
    }

    /**
     * Initialise le journey clinique du patient.
     */
    private function initializeJourney(Appointment $appointment): PatientJourney
    {
        return PatientJourney::updateOrCreate(
            ['appointment_id' => $appointment->id],
            [
                'patient_id' => $appointment->patient_id,
                'current_status' => PatientJourney::STATUS_BOOKED,
                'status_history' => [
                    ['status' => PatientJourney::STATUS_BOOKED, 'at' => now()->toIso8601String()],
                ],
            ]
        );
    }

    /**
     * Crée les tâches initiales de dossier.
     */
    private function createOnboardingTasks(Appointment $appointment, Patient $patient): void
    {
        $tasks = [
            [
                'type' => SecretaryTask::TYPE_INFO_INCOMPLETE,
                'title' => 'Compléter identité patient',
                'description' => 'Email, date de naissance, adresse, etc.',
                'priority' => SecretaryTask::PRIORITY_HIGH,
            ],
            [
                'type' => SecretaryTask::TYPE_DOCUMENT_MISSING,
                'title' => 'Vérifier documents requis',
                'description' => 'Pièce d\'identité, assurance, consentements...',
                'priority' => SecretaryTask::PRIORITY_HIGH,
            ],
            [
                'type' => SecretaryTask::TYPE_INSURANCE_VERIFY,
                'title' => 'Vérifier couverture assurance',
                'description' => 'Confirmer validité couverture',
                'priority' => SecretaryTask::PRIORITY_NORMAL,
            ],
        ];

        foreach ($tasks as $taskData) {
            SecretaryTask::create(array_merge([
                'appointment_id' => $appointment->id,
                'patient_id' => $patient->id,
                'status' => SecretaryTask::STATUS_OPEN,
                'assigned_to' => null, // Sera assignée à la secrétaire
                'due_at' => now()->addHours(2),
                'metadata' => ['from_onboarding' => true],
            ], $taskData));
        }
    }

    /**
     * Traite numérisation intelligente de documents.
     * Associe automatiquement au dossier patient.
     */
    private function processDocumentScans(Patient $patient, array $documentScans): void
    {
        foreach ($documentScans as $scanData) {
            // Télécharger scan
            if (isset($scanData['file'])) {
                $path = Storage::disk('patients')->put(
                    "documents/{$patient->id}/" . now()->format('Y-m-d'),
                    $scanData['file']
                );

                // Créer enregistrement document
                $patient->documents()->create([
                    'type' => $scanData['type'] ?? 'other', // 'id', 'insurance', 'consent', etc.
                    'filename' => $path,
                    'file_path' => Storage::disk('patients')->url($path),
                    'uploaded_at' => now(),
                    'verified_at' => null, // À vérifier manuellement si besoin
                ]);
            }
        }

        // Si documents manquants détectés, créer tâche
        $requiredTypes = ['id', 'insurance', 'consent'];
        $uploadedTypes = $patient->documents->pluck('type')->toArray();
        $missingTypes = array_diff($requiredTypes, $uploadedTypes);

        if (!empty($missingTypes)) {
            // Créer tâche pour documents manquants
            \Log::warning('patient.documents_missing', [
                'patient_id' => $patient->id,
                'missing_types' => $missingTypes,
            ]);
        }
    }

    /**
     * Complète le profil patient après intégration.
     */
    public function completeProfile(
        Patient $patient,
        array $data
    ): Patient {
        return DB::transaction(function () use ($patient, $data) {
            $patient->update(array_filter([
                'email' => $data['email'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'emergency_contact' => $data['emergency_contact'] ?? null,
                'emergency_phone' => $data['emergency_phone'] ?? null,
                'medical_history' => $data['medical_history'] ?? null,
                'allergies' => $data['allergies'] ?? null,
            ]));

            // Marquer tâches incomplètes comme complétées
            $patient->appointments()
                ->whereDate('appointment_date', '>=', today())
                ->get()
                ->each(function ($apt) {
                    $apt->tasks()
                        ->where('task_type', SecretaryTask::TYPE_INFO_INCOMPLETE)
                        ->where('status', 'open')
                        ->update(['status' => 'completed', 'completed_at' => now()]);
                });

            \Log::info('patient.profile_completed', [
                'patient_id' => $patient->id,
                'fields_updated' => count($data),
            ]);

            return $patient->fresh();
        });
    }
}
