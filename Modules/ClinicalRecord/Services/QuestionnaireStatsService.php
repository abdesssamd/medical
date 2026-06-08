<?php

namespace Modules\ClinicalRecord\Services;

use Carbon\Carbon;
use Modules\ClinicalRecord\Models\PatientQuestionnaireResponse;
use Modules\ClinicalRecord\Models\Questionnaire;

class QuestionnaireStatsService
{
    /**
     * Analyse les réponses pour un questionnaire et une question spécifique.
     *
     * @param int $questionnaireId
     * @param string $questionKey
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array
     */
    public function analyzeQuestion(
        int $questionnaireId,
        string $questionKey,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $questionnaire = Questionnaire::findOrFail($questionnaireId);
        $fieldSchema = $questionnaire->field_schema;

        $question = collect($fieldSchema)->firstWhere('key', $questionKey);

        if (! $question) {
            throw new \InvalidArgumentException("Question '{$questionKey}' introuvable dans ce questionnaire.");
        }

        $query = PatientQuestionnaireResponse::where('questionnaire_id', $questionnaireId);

        if ($startDate) {
            $query->where('answered_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('answered_at', '<=', $endDate);
        }

        $responses = $query->get();

        $questionType = $question['type'] ?? 'text';

        return match ($questionType) {
            'select', 'radio', 'checkbox' => $this->analyzeChoiceQuestion($responses, $questionKey, $question),
            'number', 'range' => $this->analyzeNumericQuestion($responses, $questionKey, $question),
            'date' => $this->analyzeDateQuestion($responses, $questionKey, $question),
            default => $this->analyzeTextQuestion($responses, $questionKey, $question),
        };
    }

    /**
     * Analyse les questions à choix multiples (select, radio, checkbox).
     */
    private function analyzeChoiceQuestion($responses, string $key, array $question): array
    {
        $options = $question['options'] ?? [];
        $counts = array_fill_keys($options, 0);
        $nullCount = 0;
        $totalCount = 0;

        foreach ($responses as $response) {
            $answers = $response->answers ?? [];
            $value = $answers[$key] ?? null;

            $totalCount++;

            if ($value === null || $value === '') {
                $nullCount++;
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $v) {
                    if (isset($counts[$v])) {
                        $counts[$v]++;
                    }
                }
            } else {
                if (isset($counts[$value])) {
                    $counts[$value]++;
                }
            }
        }

        $validCount = $totalCount - $nullCount;
        $percentages = [];

        foreach ($counts as $option => $count) {
            $percentages[$option] = $validCount > 0 ? round(($count / $validCount) * 100, 2) : 0;
        }

        return [
            'type' => 'choice',
            'question_key' => $key,
            'question_label' => $question['label'] ?? $key,
            'total_responses' => $totalCount,
            'valid_responses' => $validCount,
            'null_responses' => $nullCount,
            'counts' => $counts,
            'percentages' => $percentages,
            'chart_data' => [
                'labels' => array_keys($counts),
                'values' => array_values($counts),
                'percentages' => array_values($percentages),
            ],
        ];
    }

    /**
     * Analyse les questions numériques (number, range).
     */
    private function analyzeNumericQuestion($responses, string $key, array $question): array
    {
        $values = [];
        $nullCount = 0;
        $totalCount = 0;

        foreach ($responses as $response) {
            $answers = $response->answers ?? [];
            $value = $answers[$key] ?? null;

            $totalCount++;

            if ($value === null || $value === '') {
                $nullCount++;
                continue;
            }

            $numericValue = is_numeric($value) ? (float) $value : null;

            if ($numericValue !== null) {
                $values[] = $numericValue;
            } else {
                $nullCount++;
            }
        }

        if (empty($values)) {
            return [
                'type' => 'numeric',
                'question_key' => $key,
                'question_label' => $question['label'] ?? $key,
                'total_responses' => $totalCount,
                'valid_responses' => 0,
                'null_responses' => $nullCount,
                'statistics' => null,
                'chart_data' => null,
            ];
        }

        sort($values);

        $count = count($values);
        $sum = array_sum($values);
        $mean = $sum / $count;
        $min = $values[0];
        $max = $values[$count - 1];
        $median = $count % 2 === 0
            ? ($values[$count / 2 - 1] + $values[$count / 2]) / 2
            : $values[intval($count / 2)];

        $variance = 0;
        foreach ($values as $v) {
            $variance += pow($v - $mean, 2);
        }
        $variance /= $count;
        $stdDev = sqrt($variance);

        $ranges = $this->calculateRanges($values);

        return [
            'type' => 'numeric',
            'question_key' => $key,
            'question_label' => $question['label'] ?? $key,
            'total_responses' => $totalCount,
            'valid_responses' => count($values),
            'null_responses' => $nullCount,
            'statistics' => [
                'count' => $count,
                'sum' => round($sum, 2),
                'mean' => round($mean, 2),
                'median' => round($median, 2),
                'min' => round($min, 2),
                'max' => round($max, 2),
                'std_dev' => round($stdDev, 2),
            ],
            'chart_data' => [
                'labels' => array_keys($ranges),
                'values' => array_values($ranges),
            ],
        ];
    }

    /**
     * Analyse les questions de type date.
     */
    private function analyzeDateQuestion($responses, string $key, array $question): array
    {
        $dates = [];
        $nullCount = 0;
        $totalCount = 0;

        foreach ($responses as $response) {
            $answers = $response->answers ?? [];
            $value = $answers[$key] ?? null;

            $totalCount++;

            if ($value === null || $value === '') {
                $nullCount++;
                continue;
            }

            try {
                $date = Carbon::parse($value);
                $dates[] = $date->format('Y-m');
            } catch (\Exception $e) {
                $nullCount++;
            }
        }

        $counts = array_count_values($dates);
        ksort($counts);

        return [
            'type' => 'date',
            'question_key' => $key,
            'question_label' => $question['label'] ?? $key,
            'total_responses' => $totalCount,
            'valid_responses' => count($dates),
            'null_responses' => $nullCount,
            'chart_data' => [
                'labels' => array_keys($counts),
                'values' => array_values($counts),
            ],
        ];
    }

    /**
     * Analyse les questions textuelles.
     */
    private function analyzeTextQuestion($responses, string $key, array $question): array
    {
        $nullCount = 0;
        $filledCount = 0;
        $totalCount = 0;

        foreach ($responses as $response) {
            $answers = $response->answers ?? [];
            $value = $answers[$key] ?? null;

            $totalCount++;

            if ($value === null || trim($value) === '') {
                $nullCount++;
            } else {
                $filledCount++;
            }
        }

        return [
            'type' => 'text',
            'question_key' => $key,
            'question_label' => $question['label'] ?? $key,
            'total_responses' => $totalCount,
            'valid_responses' => $filledCount,
            'null_responses' => $nullCount,
            'chart_data' => [
                'labels' => ['Rempli', 'Vide'],
                'values' => [$filledCount, $nullCount],
            ],
        ];
    }

    /**
     * Calcule les plages de valeurs pour les histogrammes.
     */
    private function calculateRanges(array $values): array
    {
        if (empty($values)) {
            return [];
        }

        $min = min($values);
        $max = max($values);
        $range = $max - $min;

        if ($range === 0) {
            return [(string) $min => count($values)];
        }

        $bucketCount = min(10, max(3, intval(sqrt(count($values)))));
        $bucketSize = $range / $bucketCount;

        $ranges = [];
        for ($i = 0; $i < $bucketCount; $i++) {
            $lower = $min + ($i * $bucketSize);
            $upper = $min + (($i + 1) * $bucketSize);
            $label = round($lower, 1) . '-' . round($upper, 1);
            $ranges[$label] = 0;
        }

        foreach ($values as $value) {
            $bucketIndex = min($bucketCount - 1, intval(($value - $min) / $bucketSize));
            $keys = array_keys($ranges);
            $ranges[$keys[$bucketIndex]]++;
        }

        return $ranges;
    }

    /**
     * Récupère la liste des questions d'un questionnaire.
     */
    public function getQuestionnaireQuestions(int $questionnaireId): array
    {
        $questionnaire = Questionnaire::findOrFail($questionnaireId);
        $fieldSchema = $questionnaire->field_schema ?? [];

        return collect($fieldSchema)->map(function ($field) {
            return [
                'key' => $field['key'],
                'label' => $field['label'] ?? $field['key'],
                'type' => $field['type'] ?? 'text',
            ];
        })->values()->all();
    }

    /**
     * Récupère les statistiques globales d'un questionnaire.
     */
    public function getQuestionnaireOverview(
        int $questionnaireId,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $questionnaire = Questionnaire::findOrFail($questionnaireId);

        $query = PatientQuestionnaireResponse::where('questionnaire_id', $questionnaireId);

        if ($startDate) {
            $query->where('answered_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('answered_at', '<=', $endDate);
        }

        $totalResponses = $query->count();

        $responsesByMonth = $query
            ->selectRaw('DATE_FORMAT(answered_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();

        return [
            'questionnaire_id' => $questionnaireId,
            'questionnaire_name' => $questionnaire->name,
            'total_responses' => $totalResponses,
            'responses_by_month' => $responsesByMonth,
        ];
    }
}
