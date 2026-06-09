<?php

namespace Modules\RIS\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\RIS\Models\RisProcedure;

class RisProcedureController extends Controller
{
    public function index(): View
    {
        $procedures = RisProcedure::query()
            ->orderBy('label')
            ->get();

        return view('ris::parametrage.procedures.index', [
            'procedures' => $procedures,
            'editingProcedure' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:60', 'unique:ris_procedures,code'],
            'label' => ['required', 'string', 'max:191'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        RisProcedure::query()->create($validated);

        return redirect()
            ->route('ris.parametrage.procedures.index')
            ->with('success', 'Acte RIS créé avec succès.');
    }

    public function edit(RisProcedure $procedure): View
    {
        $procedures = RisProcedure::query()
            ->orderBy('label')
            ->get();

        return view('ris::parametrage.procedures.index', [
            'procedures' => $procedures,
            'editingProcedure' => $procedure,
        ]);
    }

    public function update(Request $request, RisProcedure $procedure): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:60', 'unique:ris_procedures,code,'.$procedure->id],
            'label' => ['required', 'string', 'max:191'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        $procedure->forceFill($validated)->save();

        return redirect()
            ->route('ris.parametrage.procedures.index')
            ->with('success', 'Acte RIS mis à jour.');
    }

    public function destroy(RisProcedure $procedure): RedirectResponse
    {
        if ($procedure->orders()->exists()) {
            return redirect()
                ->route('ris.parametrage.procedures.index')
                ->with('error', 'Impossible de supprimer cet acte : des examens y sont liés.');
        }

        $procedure->delete();

        return redirect()
            ->route('ris.parametrage.procedures.index')
            ->with('success', 'Acte RIS supprimé.');
    }
}
