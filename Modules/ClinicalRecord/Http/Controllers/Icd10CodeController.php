<?php

namespace Modules\ClinicalRecord\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ClinicalRecord\Models\Icd10Code;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class Icd10CodeController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $codes = Icd10Code::when($search, fn ($q, $s) => $q->where('code', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%"))
            ->orderBy('code')
            ->paginate(20);

        if ($request->ajax()) {
            return response()->json(['html' => view('clinicalrecord::icd10-codes._table', compact('codes'))->render()]);
        }

        return view('clinicalrecord::icd10-codes.index', compact('codes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:icd10_codes,code',
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
        ]);

        Icd10Code::create($validated);

        return response()->json(['message' => 'Code CIM-10 créé avec succès.']);
    }

    public function update(Request $request, Icd10Code $icd10Code)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:icd10_codes,code,' . $icd10Code->id,
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $icd10Code->update($validated);

        return response()->json(['message' => 'Code CIM-10 modifié avec succès.']);
    }

    public function destroy(Icd10Code $icd10Code)
    {
        $icd10Code->delete();

        return response()->json(['message' => 'Code CIM-10 supprimé avec succès.']);
    }

    public function exportExcel()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [__('Code'), __('Nom'), __('Catégorie'), __('Actif')];
        $sheet->fromArray($headers, null, 'A1');

        $headerStyle = $sheet->getStyle('A1:D1');
        $headerStyle->getFont()->setBold(true);
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
        $headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = 2;
        foreach (Icd10Code::orderBy('code')->cursor() as $code) {
            $sheet->setCellValue("A{$row}", $code->code);
            $sheet->setCellValue("B{$row}", $code->name);
            $sheet->setCellValue("C{$row}", $code->category);
            $sheet->setCellValue("D{$row}", $code->is_active ? 'Oui' : 'Non');
            $row++;
        }

        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'codes-cim10-' . now()->format('Y-m-d') . '.xlsx';

        return response()->streamDownload(fn () => $writer->save('php://output'), $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exampleExcel()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['Code', 'Nom', 'Catégorie'];
        $sheet->fromArray($headers, null, 'A1');

        $headerStyle = $sheet->getStyle('A1:C1');
        $headerStyle->getFont()->setBold(true);
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');

        $data = [
            ['E10', 'Diabète sucré de type 1', 'Endocrinologie'],
            ['I10', 'Hypertension essentielle (primitive)', 'Cardiologie'],
        ];
        $sheet->fromArray($data, null, 'A2');

        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'exemple-codes-cim10.xlsx';

        return response()->streamDownload(fn () => $writer->save('php://output'), $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function importExcel(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls']);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $rows = $spreadsheet->getActiveSheet()->toArray();

        $imported = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            if ($index === 0) continue;

            $code = trim($row[0] ?? '');
            $name = trim($row[1] ?? '');
            $category = trim($row[2] ?? '');

            if (empty($code) || empty($name)) {
                $errors[] = "Ligne " . ($index + 1) . " : Code ou nom manquant.";
                continue;
            }

            try {
                Icd10Code::updateOrCreate(
                    ['code' => $code],
                    ['name' => $name, 'category' => $category, 'is_active' => true]
                );
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Ligne " . ($index + 1) . " : " . $e->getMessage();
            }
        }

        return response()->json([
            'message' => "{$imported} codes importés avec succès." . (count($errors) ? ' ' . implode(' ', $errors) : ''),
        ]);
    }
}
