<?php

namespace Modules\ClinicalRecord\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\ClinicalRecord\Models\HealthQuestionnaire;
use Modules\ClinicalRecord\Models\PatientQuestionnaireResponse;
use Modules\ClinicalRecord\Models\Questionnaire;

class QuestionnaireResponseController extends Controller
{
    /**
     * Vérifier que l'utilisateur est authentifié et peut accéder
     */
    public function ensureAuthorized(): void
    {
        if (!auth()->check()) {
            throw new AuthorizationException('Unauthorized');
        }
    }

    /**
     * Constructor pour les middlewares
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->ensureAuthorized();
            return $next($request);
        });
    }

    /**
     * Obtenir les questionnaires disponibles pour un patient (API)
     */
    public function getAvailableQuestionnaires(int $patientId): JsonResponse
    {
        $patient = Patient::findOrFail($patientId);

        // Obtenir les questionnaires actifs
        $questionnaires = Questionnaire::active()
            ->forPatient($patient)
            ->with(['specialty', 'creator'])
            ->get()
            ->map(fn ($q) => [
                'id' => $q->id,
                'name' => $q->name,
                'description' => $q->description,
                'specialty' => $q->specialty?->name ?? 'Tous',
                'fields_count' => count($q->field_schema ?? []),
                'group' => $q->group_name ?? 'Autres',
            ]);

        return response()->json([
            'success' => true,
            'questionnaires' => $questionnaires,
            'patient_id' => $patientId,
        ]);
    }

    /**
     * Afficher le formulaire de questionnaire
     */
    public function show(int $patientId, int $questionnaireId): View
    {
        $patient = Patient::findOrFail($patientId);
        $questionnaire = Questionnaire::findOrFail($questionnaireId);

        // Vérifier les permissions
        if (!auth()->user()->isPractitioner() && !auth()->user()->isAdmin()) {
            abort(403, 'Vous n\'avez pas accès à ce questionnaire.');
        }

        return view('clinical_record::questionnaire-response.form', compact('patient', 'questionnaire'));
    }

    /**
     * Enregistrer les réponses au questionnaire
     */
    public function store(Request $request, int $patientId, int $questionnaireId): RedirectResponse
    {
        $patient = Patient::findOrFail($patientId);
        $questionnaire = Questionnaire::findOrFail($questionnaireId);

        // Vérifier les permissions
        if (!auth()->user()->isPractitioner() && !auth()->user()->isAdmin()) {
            abort(403, 'Vous n\'avez pas le droit de soumettre ce questionnaire.');
        }

        // Valider les réponses
        $answers = $request->validate([
            'answers' => 'required|array',
            'answers.*' => 'nullable|string',
        ])['answers'];

        // Créer ou mettre à jour la réponse
        $response = PatientQuestionnaireResponse::updateOrCreate(
            [
                'patient_id' => $patientId,
                'questionnaire_id' => $questionnaireId,
            ],
            [
                'answers' => $answers,
                'filled_on' => now(),
                'answered_by' => auth()->id(),
            ]
        );

        // Analyser les risques critiques si applicable
        $riskAnalysis = $this->analyzeRisks($questionnaire, $answers);
        if (!empty($riskAnalysis['critical_risks'])) {
            $response->update([
                'has_critical_risk' => true,
                'critical_notes' => implode('; ', $riskAnalysis['critical_risks']),
                'risk_tags' => $riskAnalysis['risk_tags'] ?? [],
            ]);

            // Ajouter une alerte au patient si nécessaire
            $this->notifyAboutCriticalRisks($patient, $questionnaire, $riskAnalysis);
        }

        return redirect()
            ->back()
            ->with('success', "Questionnaire « {$questionnaire->name} » enregistré avec succès.");
    }

    /**
     * Afficher l'historique des réponses d'un patient
     */
    public function history(int $patientId, ?int $questionnaireId = null): View
    {
        $patient = Patient::findOrFail($patientId);

        $responses = PatientQuestionnaireResponse::where('patient_id', $patientId)
            ->when($questionnaireId, fn ($q) => $q->where('questionnaire_id', $questionnaireId))
            ->with(['questionnaire', 'answeredBy', 'validator'])
            ->orderByDesc('filled_on')
            ->paginate(20);

        return view('clinical_record::questionnaire-response.history', compact('patient', 'responses'));
    }

    /**
     * Valider une réponse au questionnaire (pour praticien)
     */
    public function validateResponse(int $patientId, int $responseId): RedirectResponse
    {
        $patient = Patient::findOrFail($patientId);
        $response = PatientQuestionnaireResponse::findOrFail($responseId);

        if (!auth()->user()->isPractitioner() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $response->update([
            'validated_by' => auth()->id(),
            'validated_at' => now(),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Questionnaire validé avec succès.');
    }

    /**
     * Exporter les réponses en PDF
     */
    public function exportPdf(int $patientId, int $responseId): \Symfony\Component\HttpFoundation\Response
    {
        $patient = Patient::findOrFail($patientId);
        $response = PatientQuestionnaireResponse::findOrFail($responseId);

        // TODO: Implémenter l'export PDF avec DomPDF ou Spatie PDF
        return view('clinical_record::questionnaire-response.pdf', compact('patient', 'response'));
    }

    /**
     * Analyser les réponses pour les risques critiques
     */
    private function analyzeRisks(Questionnaire $questionnaire, array $answers): array
    {
        $analysis = [
            'risk_tags' => [],
            'critical_risks' => [],
        ];

        // Analyser les réponses selon les champs du questionnaire
        foreach ($questionnaire->field_schema ?? [] as $field) {
            $fieldId = $field['id'] ?? null;
            $answer = $answers[$fieldId] ?? null;

            // Vérifier les réponses "oui" à des questions sur les allergies
            if (strpos(strtolower($field['label']), 'allergi') !== false && strtolower($answer) === 'oui') {
                $analysis['critical_risks'][] = "Allergies déclarées";
                $analysis['risk_tags'][] = 'allergies';
            }

            // Vérifier d'autres patterns critiques
            if (strpos(strtolower($field['label']), 'symptôme') !== false && !empty($answer)) {
                $analysis['risk_tags'][] = 'symptoms';
            }

            if (strpos(strtolower($field['label']), 'medicament') !== false && !empty($answer)) {
                $analysis['risk_tags'][] = 'medications';
            }
        }

        return $analysis;
    }

    /**
     * Notifier l'équipe médicale des risques critiques
     */
    private function notifyAboutCriticalRisks(Patient $patient, Questionnaire $questionnaire, array $analysis): void
    {
        // TODO: Ajouter une alerte au dossier patient
        // TODO: Envoyer une notification par email au responsable
    }

    /**
     * Supprimer une réponse au questionnaire
     */
    public function destroy(int $patientId, int $responseId): RedirectResponse
    {
        $patient = Patient::findOrFail($patientId);
        $response = PatientQuestionnaireResponse::findOrFail($responseId);

        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $questionnaireName = $response->questionnaire?->name ?? 'Questionnaire';
        $response->delete();

        return redirect()
            ->back()
            ->with('success', "Réponse au questionnaire « {$questionnaireName} » supprimée.");
    }
}
