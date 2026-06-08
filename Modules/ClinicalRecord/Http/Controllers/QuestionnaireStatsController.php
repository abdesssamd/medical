<?php

namespace Modules\ClinicalRecord\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ClinicalRecord\Models\Questionnaire;
use Modules\ClinicalRecord\Services\QuestionnaireStatsService;

class QuestionnaireStatsController extends Controller
{
    public function __construct(
        private readonly QuestionnaireStatsService $statsService
    ) {}

    /**
     * Liste les questionnaires actifs disponibles pour les statistiques.
     */
    public function index(): JsonResponse
    {
        $questionnaires = Questionnaire::active()
            ->select('id', 'name', 'description', 'group_name')
            ->withCount('responses')
            ->orderByDesc('responses_count')
            ->get()
            ->map(fn ($q) => [
                'id' => $q->id,
                'name' => $q->name,
                'description' => $q->description,
                'group' => $q->group_name ?? 'Autres',
                'responses_count' => $q->responses_count,
            ]);

        return response()->json([
            'success' => true,
            'questionnaires' => $questionnaires,
        ]);
    }

    /**
     * Liste les questions d'un questionnaire spécifique.
     */
    public function questions(int $questionnaireId): JsonResponse
    {
        try {
            $questions = $this->statsService->getQuestionnaireQuestions($questionnaireId);

            return response()->json([
                'success' => true,
                'questions' => $questions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Analyse les réponses pour une question spécifique.
     */
    public function analyze(Request $request, int $questionnaireId, string $questionKey): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : null;

        try {
            $stats = $this->statsService->analyzeQuestion(
                $questionnaireId,
                $questionKey,
                $startDate,
                $endDate
            );

            return response()->json([
                'success' => true,
                'stats' => $stats,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'analyse: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Vue d'ensemble des statistiques d'un questionnaire.
     */
    public function overview(Request $request, int $questionnaireId): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : null;

        try {
            $overview = $this->statsService->getQuestionnaireOverview(
                $questionnaireId,
                $startDate,
                $endDate
            );

            return response()->json([
                'success' => true,
                'overview' => $overview,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Affiche la page du dashboard de statistiques.
     */
    public function dashboard(Request $request)
    {
        $questionnaires = Questionnaire::active()
            ->select('id', 'name', 'description', 'group_name')
            ->withCount('responses')
            ->orderByDesc('responses_count')
            ->get();

        return view('clinical_record::questionnaire-stats.dashboard', compact('questionnaires'));
    }
}
