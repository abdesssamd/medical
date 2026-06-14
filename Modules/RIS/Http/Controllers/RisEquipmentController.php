<?php

namespace Modules\RIS\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\RIS\Models\RisEquipment;
use Modules\RIS\Models\RisModality;

class RisEquipmentController extends Controller
{
    public function index(): View
    {
        $equipments = RisEquipment::query()
            ->with('modality')
            ->orderBy('name')
            ->get();

        $modalities = RisModality::query()->orderBy('name')->get();

        return view('ris::parametrage.equipments.index', [
            'equipments' => $equipments,
            'modalities' => $modalities,
            'editingEquipment' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'modality_id' => ['nullable', 'integer', 'exists:ris_modalities,id'],
            'ae_title' => ['nullable', 'string', 'max:64'],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'location' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        RisEquipment::query()->create($validated);

        return redirect()
            ->route('ris.parametrage.equipments.index')
            ->with('success', 'Équipement créé avec succès.');
    }

    public function edit(RisEquipment $equipment): View
    {
        $equipments = RisEquipment::query()
            ->with('modality')
            ->orderBy('name')
            ->get();

        $modalities = RisModality::query()->orderBy('name')->get();

        return view('ris::parametrage.equipments.index', [
            'equipments' => $equipments,
            'modalities' => $modalities,
            'editingEquipment' => $equipment,
        ]);
    }

    public function update(Request $request, RisEquipment $equipment): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'modality_id' => ['nullable', 'integer', 'exists:ris_modalities,id'],
            'ae_title' => ['nullable', 'string', 'max:64'],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'location' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $equipment->forceFill($validated)->save();

        return redirect()
            ->route('ris.parametrage.equipments.index')
            ->with('success', 'Équipement mis à jour.');
    }

    public function destroy(RisEquipment $equipment): RedirectResponse
    {
        $equipment->delete();

        return redirect()
            ->route('ris.parametrage.equipments.index')
            ->with('success', 'Équipement supprimé.');
    }
}
