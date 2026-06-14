<?php

namespace Modules\RIS\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\RIS\Models\RisProcedure;
use PhpOffice\PhpSpreadsheet\IOFactory;

class RisProcedureController extends Controller
{
    public function index(): View
    {
        $procedures = RisProcedure::query()
            ->orderBy('label')
            ->get();

        $modalities = \Modules\RIS\Models\RisModality::query()->orderBy('type')->get();

        return view('ris::parametrage.procedures.index', [
            'procedures' => $procedures,
            'modalities' => $modalities,
            'editingProcedure' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:60', 'unique:ris_procedures,code'],
            'label' => ['required', 'string', 'max:191'],
            'price' => ['required', 'numeric', 'min:0'],
            'modality_type' => ['nullable', 'string', 'max:50', 'exists:ris_modalities,type'],
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

        $modalities = \Modules\RIS\Models\RisModality::query()->orderBy('type')->get();

        return view('ris::parametrage.procedures.index', [
            'procedures' => $procedures,
            'modalities' => $modalities,
            'editingProcedure' => $procedure,
        ]);
    }

    public function update(Request $request, RisProcedure $procedure): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:60', 'unique:ris_procedures,code,'.$procedure->id],
            'label' => ['required', 'string', 'max:191'],
            'price' => ['required', 'numeric', 'min:0'],
            'modality_type' => ['nullable', 'string', 'max:50', 'exists:ris_modalities,type'],
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

    public function importExcel(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        if (count($rows) < 2) {
            return back()->with('error', 'Le fichier est vide ou ne contient que l\'en-tête.');
        }

        $header = array_map('strtolower', (array) ($rows[0] ?? []));
        $codeIdx = array_search('code', $header);
        $labelIdx = array_search('libelle', $header) ?? array_search('label', $header);
        $priceIdx = array_search('prix', $header) ?? array_search('price', $header);
        $typeIdx = array_search('type', $header) ?? array_search('modalite', $header) ?? array_search('modality_type', $header);

        if ($codeIdx === false || $labelIdx === false) {
            return back()->with('error', 'Le fichier Excel doit contenir au moins les colonnes "Code" et "Libellé".');
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach (array_slice($rows, 1) as $index => $row) {
            $code = trim((string) ($row[$codeIdx] ?? ''));
            $label = trim((string) ($row[$labelIdx] ?? ''));
            $price = trim((string) ($row[$priceIdx] ?? ''));
            $type = $typeIdx !== false ? trim((string) ($row[$typeIdx] ?? '')) : '';

            if ($code === '' || $label === '') {
                $skipped++;
                continue;
            }

            if (RisProcedure::query()->where('code', $code)->exists()) {
                $skipped++;
                continue;
            }

            $price = str_replace([' ', ','], ['', '.'], $price);
            $price = is_numeric($price) ? (float) $price : 0;

            $modalityType = \Modules\RIS\Models\RisModality::query()->where('type', strtoupper($type))->exists() ? strtoupper($type) : null;

            try {
                RisProcedure::query()->create([
                    'code' => $code,
                    'label' => $label,
                    'price' => max(0, $price),
                    'modality_type' => $modalityType,
                ]);
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "Ligne " . ($index + 2) . " : " . $e->getMessage();
            }
        }

        $message = "{$imported} acte(s) importé(s) avec succès.";
        if ($skipped > 0) {
            $message .= " {$skipped} ligne(s) ignorée(s) (doublons ou vides).";
        }

        if (! empty($errors)) {
            return back()
                ->with('warning', $message)
                ->with('import_errors', $errors);
        }

        return back()->with('success', $message);
    }
}
