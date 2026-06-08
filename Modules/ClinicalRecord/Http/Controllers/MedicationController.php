<?php

namespace Modules\ClinicalRecord\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\ClinicalRecord\Models\Medication;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MedicationController extends Controller
{
    private const HEADERS = [
        'Nom',
        'Catégorie',
        'Dosage',
        'Formes',
        'Unité',
        'Fréquence',
        'Durée (jours)',
        'Mots-clés allergènes',
        'Tags contre-indications',
        'Mots-clés interactions',
        'Actif (Oui/Non)',
    ];

    private const HEADER_KEYS = [
        'name', 'category', 'strength', 'forms', 'default_unit',
        'default_frequency', 'default_duration_days', 'allergen_keywords',
        'contraindication_tags', 'interaction_keywords', 'is_active',
    ];

    public function index(Request $request): View
    {
        $query = Medication::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('strength', 'like', "%{$search}%");
            });
        }

        $medications = $query->orderBy('name')->paginate(20);

        return view('clinical_record::medications.index', compact('medications'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:80',
            'strength' => 'nullable|string|max:255',
            'forms' => 'nullable|string',
            'default_unit' => 'nullable|string|max:50',
            'default_frequency' => 'nullable|string|max:80',
            'default_duration_days' => 'nullable|integer|min:1|max:365',
        ]);

        if (!empty($validated['forms'])) {
            $validated['forms'] = array_map('trim', explode(',', $validated['forms']));
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        Medication::create($validated);

        return redirect()->route('admin.medications.index')->with('success', 'Médicament créé avec succès.');
    }

    public function update(Request $request, Medication $medication): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:80',
            'strength' => 'nullable|string|max:255',
            'forms' => 'nullable|string',
            'default_unit' => 'nullable|string|max:50',
            'default_frequency' => 'nullable|string|max:80',
            'default_duration_days' => 'nullable|integer|min:1|max:365',
        ]);

        if (!empty($validated['forms'])) {
            $validated['forms'] = array_map('trim', explode(',', $validated['forms']));
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $medication->update($validated);

        return redirect()->route('admin.medications.index')->with('success', 'Médicament mis à jour avec succès.');
    }

    public function destroy(Medication $medication): RedirectResponse
    {
        $medication->delete();

        return redirect()->route('admin.medications.index')->with('success', 'Médicament supprimé avec succès.');
    }

    public function exportExcel(): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Médicaments');

        $this->writeHeaderRow($sheet);

        $medications = Medication::orderBy('name')->get();
        $row = 2;

        foreach ($medications as $med) {
            $sheet->setCellValue("A{$row}", $med->name);
            $sheet->setCellValue("B{$row}", $med->category);
            $sheet->setCellValue("C{$row}", $med->strength);
            $sheet->setCellValue("D{$row}", $med->forms ? implode(', ', $med->forms) : null);
            $sheet->setCellValue("E{$row}", $med->default_unit);
            $sheet->setCellValue("F{$row}", $med->default_frequency);
            $sheet->setCellValue("G{$row}", $med->default_duration_days);
            $sheet->setCellValue("H{$row}", $med->allergen_keywords ? implode(', ', $med->allergen_keywords) : null);
            $sheet->setCellValue("I{$row}", $med->contraindication_tags ? implode(', ', $med->contraindication_tags) : null);
            $sheet->setCellValue("J{$row}", $med->interaction_keywords ? implode(', ', $med->interaction_keywords) : null);
            $sheet->setCellValue("K{$row}", $med->is_active ? 'Oui' : 'Non');
            $row++;
        }

        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->streamSpreadsheet($spreadsheet, 'medicaments.xlsx');
    }

    public function exampleExcel(): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Exemple');

        $this->writeHeaderRow($sheet);

        $sheet->setCellValue('A2', 'Paracétamol');
        $sheet->setCellValue('B2', 'Antalgique');
        $sheet->setCellValue('C2', '1 g');
        $sheet->setCellValue('D2', 'comprimé, sachet');
        $sheet->setCellValue('E2', 'comprimé');
        $sheet->setCellValue('F2', 'Matin/Midi/Soir');
        $sheet->setCellValue('G2', 3);
        $sheet->setCellValue('H2', '');
        $sheet->setCellValue('I2', 'insuffisance_hepatique');
        $sheet->setCellValue('J2', '');
        $sheet->setCellValue('K2', 'Oui');

        $sheet->setCellValue('A3', 'Amoxicilline');
        $sheet->setCellValue('B3', 'Antibiotique');
        $sheet->setCellValue('C3', '1 g');
        $sheet->setCellValue('D3', 'gélule, comprimé');
        $sheet->setCellValue('E3', 'gélule');
        $sheet->setCellValue('F3', 'Matin/Midi/Soir');
        $sheet->setCellValue('G3', 7);
        $sheet->setCellValue('H3', 'pénicilline, amoxicilline');
        $sheet->setCellValue('I3', 'allergie_penicilline');
        $sheet->setCellValue('J3', 'methotrexate');
        $sheet->setCellValue('K3', 'Oui');

        $sheet->getStyle('A2:K3')->getFont()->setSize(11);

        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->streamSpreadsheet($spreadsheet, 'exemple_medicaments.xlsx');
    }

    public function importExcel(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $file = $request->file('file');
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        if (count($rows) < 2) {
            return back()->with('error', 'Le fichier est vide ou ne contient que l\'en-tête.');
        }

        $header = $rows[0];
        $colMap = $this->mapHeaders($header);

        if (empty($colMap['name'])) {
            return back()->with('error', 'La colonne "Nom" est obligatoire dans le fichier.');
        }

        $imported = 0;
        $errors = [];

        foreach (array_slice($rows, 1) as $index => $row) {
            $rowNum = $index + 2;
            $data = $this->extractRowData($row, $colMap);

            if (empty($data['name'])) {
                continue;
            }

            $data['forms'] = $this->parseArrayField($data['forms'] ?? null);
            $data['allergen_keywords'] = $this->parseArrayField($data['allergen_keywords'] ?? null);
            $data['contraindication_tags'] = $this->parseArrayField($data['contraindication_tags'] ?? null);
            $data['interaction_keywords'] = $this->parseArrayField($data['interaction_keywords'] ?? null);

            if (isset($data['default_duration_days'])) {
                $data['default_duration_days'] = is_numeric($data['default_duration_days'])
                    ? (int) $data['default_duration_days'] : null;
            }

            if (isset($data['is_active'])) {
                $data['is_active'] = in_array(strtolower($data['is_active']), ['oui', 'yes', '1', 'true'], true);
            } else {
                $data['is_active'] = true;
            }

            try {
                Medication::create($data);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Ligne {$rowNum} : {$e->getMessage()}";
            }
        }

        $message = "{$imported} médicament(s) importé(s) avec succès.";
        if (!empty($errors)) {
            $message .= ' Erreurs : ' . implode(' | ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $message .= ' (+' . (count($errors) - 5) . ' autres)';
            }
        }

        return redirect()->route('admin.medications.index')->with(
            empty($errors) ? 'success' : 'warning',
            $message
        );
    }

    private function writeHeaderRow($sheet): void
    {
        $headers = self::HEADERS;
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue("{$col}1", $header);
            $col++;
        }

        $style = $sheet->getStyle('A1:K1');
        $style->getFont()->setBold(true)->setSize(12)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $style->getFill()->setFillType(Fill::FILL_SOLID)
            ->setStartColor(new \PhpOffice\PhpSpreadsheet\Style\Color('2563EB'));
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    private function streamSpreadsheet(Spreadsheet $spreadsheet, string $filename): Response
    {
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    private function mapHeaders(array $header): array
    {
        $map = [];
        foreach ($header as $i => $label) {
            $normalized = trim(mb_strtolower((string) $label));
            $normalized = str_replace(['-', '_', ' '], '', $normalized);

            foreach (self::HEADERS as $key => $expectedLabel) {
                $expectedNormalized = str_replace(['-', '_', ' '], '', mb_strtolower($expectedLabel));
                if ($normalized === $expectedNormalized) {
                    $map[self::HEADER_KEYS[$key]] = $i;
                    break;
                }
            }
        }
        return $map;
    }

    private function extractRowData(array $row, array $colMap): array
    {
        $data = [];
        foreach ($colMap as $key => $colIndex) {
            $data[$key] = $row[$colIndex] ?? null;
            if (is_string($data[$key])) {
                $data[$key] = trim($data[$key]);
            }
            if ($data[$key] === '' || $data[$key] === null) {
                $data[$key] = null;
            }
        }
        return $data;
    }

    private function parseArrayField(?string $value): ?array
    {
        if (empty($value)) {
            return null;
        }
        return array_map('trim', explode(',', $value));
    }
}
