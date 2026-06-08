<?php

namespace Modules\RIS\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\RIS\Models\RisReportTemplate;

class RisReportTemplateController extends Controller
{
    public function index(Request $request): View
    {
        $templates = RisReportTemplate::query()
            ->orderBy('category')
            ->orderBy('title')
            ->get();

        $categories = RisReportTemplate::query()
            ->select('category')
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('ris::templates.index', [
            'templates' => $templates,
            'categories' => $categories,
            'editingTemplate' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateTemplate($request);

        RisReportTemplate::query()->create($validated);

        return redirect()
            ->route('ris.templates.index')
            ->with('success', 'Template cree avec succes.');
    }

    public function edit(RisReportTemplate $template): View
    {
        $templates = RisReportTemplate::query()
            ->orderBy('category')
            ->orderBy('title')
            ->get();

        $categories = RisReportTemplate::query()
            ->select('category')
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('ris::templates.index', [
            'templates' => $templates,
            'categories' => $categories,
            'editingTemplate' => $template,
        ]);
    }

    public function update(Request $request, RisReportTemplate $template): RedirectResponse
    {
        $validated = $this->validateTemplate($request);

        $template->forceFill($validated)->save();

        return redirect()
            ->route('ris.templates.index')
            ->with('success', 'Template mis a jour.');
    }

    public function destroy(RisReportTemplate $template): RedirectResponse
    {
        $template->delete();

        return redirect()
            ->route('ris.templates.index')
            ->with('success', 'Template supprime.');
    }

    private function validateTemplate(Request $request): array
    {
        $validated = $request->validate([
            'category' => ['nullable', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:160'],
            'content' => ['required', 'string'],
        ]);

        return [
            'category' => trim((string) ($validated['category'] ?? '')) ?: 'Général',
            'title' => trim((string) $validated['title']),
            'content' => $this->normalizeTemplateContent((string) $validated['content']),
        ];
    }

    private function normalizeTemplateContent(string $content): string
    {
        $content = trim($content);

        if ($content === '') {
            return '';
        }

        return strip_tags($content, '<p><br><strong><b><em><i><ul><ol><li><table><thead><tbody><tr><th><td><div><span>');
    }
}