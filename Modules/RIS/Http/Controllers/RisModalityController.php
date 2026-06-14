<?php

namespace Modules\RIS\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\RIS\Models\RisModality;

class RisModalityController extends Controller
{
    public function index(): View
    {
        $modalities = RisModality::query()
            ->withCount('equipments')
            ->orderBy('name')
            ->get();

        return view('ris::parametrage.modalities.index', [
            'modalities' => $modalities,
            'editingModality' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'type' => ['required', 'string', 'in:' . implode(',', array_keys(RisModality::TYPES))],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        RisModality::query()->create($validated);

        return redirect()
            ->route('ris.parametrage.modalities.index')
            ->with('success', 'Modalité créée avec succès.');
    }

    public function edit(RisModality $modality): View
    {
        $modalities = RisModality::query()
            ->withCount('equipments')
            ->orderBy('name')
            ->get();

        return view('ris::parametrage.modalities.index', [
            'modalities' => $modalities,
            'editingModality' => $modality,
        ]);
    }

    public function update(Request $request, RisModality $modality): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'type' => ['required', 'string', 'in:' . implode(',', array_keys(RisModality::TYPES))],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $modality->forceFill($validated)->save();

        return redirect()
            ->route('ris.parametrage.modalities.index')
            ->with('success', 'Modalité mise à jour.');
    }

    public function destroy(RisModality $modality): RedirectResponse
    {
        if ($modality->orders()->exists()) {
            return redirect()
                ->route('ris.parametrage.modalities.index')
                ->with('error', 'Impossible de supprimer cette modalité : des examens y sont liés.');
        }

        $modality->delete();

        return redirect()
            ->route('ris.parametrage.modalities.index')
            ->with('success', 'Modalité supprimée.');
    }
}
