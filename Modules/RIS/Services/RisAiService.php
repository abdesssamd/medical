<?php

namespace Modules\RIS\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\RIS\Models\RisOrder;

class RisAiService
{
    public function analyze(RisOrder $order, string $content, string $mode = 'correction'): array
    {
        $sanitizedContent = $this->normalizeContent($content);

        if (! $this->isEnabled()) {
            return $this->fallbackAnalysis($order, $sanitizedContent, $mode);
        }

        $response = Http::withToken((string) config('ris.ai.api_key'))
            ->acceptJson()
            ->timeout((int) config('ris.ai.timeout', 12))
            ->post(rtrim((string) config('ris.ai.base_url'), '/').(string) config('ris.ai.chat_path', '/chat/completions'), [
                'model' => (string) config('ris.ai.model', 'gpt-4o-mini'),
                'temperature' => 0.2,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu assistes un radiologue dentaire. Réponds uniquement en JSON valide avec les clés: corrected_html, pre_report_html, summary, conclusion, suggestions.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->buildPrompt($order, $sanitizedContent, $mode),
                    ],
                ],
            ]);

        if ($response->successful()) {
            $contentJson = data_get($response->json(), 'choices.0.message.content');
            $decoded = is_string($contentJson) ? json_decode($contentJson, true) : null;

            if (is_array($decoded)) {
                return $this->normalizeAiPayload($decoded, $sanitizedContent, $mode);
            }
        }

        return $this->fallbackAnalysis($order, $sanitizedContent, $mode);
    }

    private function isEnabled(): bool
    {
        return (bool) config('ris.ai.enabled', false) && filled(config('ris.ai.api_key'));
    }

    private function buildPrompt(RisOrder $order, string $content, string $mode): string
    {
        return implode("\n\n", array_filter([
            'Mode: '.$mode,
            'Patient: '.($order->patient?->full_name ?? 'Patient inconnu'),
            'Age: '.($order->patient?->date_of_birth ? $order->patient->age.' ans' : 'non renseigne'),
            'Examen: '.($order->procedure?->label ?? 'Examen RIS'),
            'Modalite: '.($order->modality?->name ?? 'non definie'),
            'Indication clinique: '.($order->clinical_indication ?: 'non renseignee'),
            'Texte a analyser: '.$content,
            'Produis une proposition medicale prudente, concise, structurée, sans inventer de données non présentes.',
        ]));
    }

    private function fallbackAnalysis(RisOrder $order, string $content, string $mode): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/u', trim(strip_tags($content))) ?: [];
        $summary = trim(implode(' ', array_slice($sentences, 0, 2)));
        $summary = $summary !== '' ? $summary : 'Aucune conclusion automatique disponible.';

        $preReportHtml = '<p><strong>Contexte:</strong> '.e($order->patient?->full_name ?? 'Patient inconnu').'</p>'
            .'<p><strong>Examen:</strong> '.e($order->procedure?->label ?? 'Examen RIS').'</p>'
            .'<p><strong>Indication:</strong> '.e($order->clinical_indication ?: 'Non renseignee').'</p>';

        $correctedHtml = $this->formatAsHtml($content);

        return [
            'mode' => $mode,
            'summary' => $summary,
            'conclusion' => $this->buildFallbackConclusion($content),
            'suggestions' => [
                'Vérifier la cohérence entre indication et conclusion.',
                'Remplacer les abréviations ambigües par des termes explicites.',
            ],
            'pre_report_html' => $preReportHtml,
            'corrected_html' => $correctedHtml,
            'source' => 'fallback',
        ];
    }

    private function normalizeAiPayload(array $payload, string $content, string $mode): array
    {
        $correctedHtml = (string) ($payload['corrected_html'] ?? $payload['corrected_text'] ?? '');
        $preReportHtml = (string) ($payload['pre_report_html'] ?? $payload['pre_report'] ?? '');
        $summary = trim((string) ($payload['summary'] ?? ''));
        $conclusion = trim((string) ($payload['conclusion'] ?? ''));
        $suggestions = $payload['suggestions'] ?? [];

        if (! is_array($suggestions)) {
            $suggestions = [$suggestions];
        }

        return [
            'mode' => $mode,
            'summary' => $summary !== '' ? $summary : 'Analyse IA disponible.',
            'conclusion' => $conclusion !== '' ? $conclusion : $this->buildFallbackConclusion($content),
            'suggestions' => array_values(array_filter(array_map('strval', $suggestions))),
            'pre_report_html' => $preReportHtml !== '' ? $preReportHtml : $this->formatAsHtml($content),
            'corrected_html' => $correctedHtml !== '' ? $correctedHtml : $this->formatAsHtml($content),
            'source' => 'ai',
        ];
    }

    private function normalizeContent(string $content): string
    {
        $content = trim($content);

        if ($content === '') {
            return '';
        }

        $content = preg_replace('/<\s*br\s*\/?>/i', "\n", $content) ?? $content;
        $content = strip_tags($content);

        return trim(html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function formatAsHtml(string $content): string
    {
        $plain = $this->normalizeContent($content);

        if ($plain === '') {
            return '<p>Compte rendu à compléter.</p>';
        }

        $paragraphs = array_filter(array_map('trim', preg_split('/\n{2,}/', $plain) ?: []));

        return implode('', array_map(static function (string $paragraph): string {
            return '<p>'.e($paragraph).'</p>';
        }, $paragraphs ?: [$plain]));
    }

    private function buildFallbackConclusion(string $content): string
    {
        $plain = mb_strtolower($this->normalizeContent($content));

        if (Str::contains($plain, ['anomalie', 'normal', 'rien a signaler', 'sans anomalie'])) {
            return 'Aspect sans anomalie radiologique evidente.';
        }

        return 'Conclusion à préciser par le radiologue.';
    }
}