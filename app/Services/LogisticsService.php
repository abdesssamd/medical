<?php

namespace App\Services;

use App\Models\CareAlert;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Logistics\Models\LabOrder;
use Modules\Logistics\Models\LabOrderEvent;
use Modules\Logistics\Models\PatientSterilizationTrace;
use Modules\Logistics\Models\SterilizationBatch;
use Modules\Logistics\Models\SterilizationPouch;
use Modules\Logistics\Models\StockItem;
use Modules\Logistics\Models\StockMovement;

class LogisticsService
{
    public function dashboardData(array $filters = []): array
    {
        $validatedStatusMap = [
            'in_progress' => 'En cours',
            'validated' => 'Valide',
            'expired' => 'Expire',
        ];
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        SterilizationBatch::query()
            ->where('status', '!=', 'expired')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        $traceDateFilter = ! empty($filters['trace_date']) ? Carbon::parse((string) $filters['trace_date'])->toDateString() : null;
        $tracePractitionerFilter = ! empty($filters['trace_practitioner_id']) ? (int) $filters['trace_practitioner_id'] : null;

        $traceToday = PatientSterilizationTrace::whereDate('scanned_at', $today)->count();
        $traceYesterday = PatientSterilizationTrace::whereDate('scanned_at', $yesterday)->count();
        $lowStockCount = StockItem::where('is_active', true)
            ->whereColumn('current_quantity', '<=', 'minimum_quantity')
            ->count();
        $labPending = LabOrder::whereIn('status', [
            'impression_taken',
            'sent_to_lab',
            'received_from_lab',
            'created',
            'sent',
            'in_progress',
        ])->count();
        $labCreatedToday = LabOrder::whereDate('created_at', $today)->count();
        $stockMovementsToday = StockMovement::whereDate('moved_at', $today)->count();
        $smartAlerts = $this->smartAlerts();
        $criticalAlerts = collect($smartAlerts)->where('severity', 'critical')->count();

        $recentTracesQuery = PatientSterilizationTrace::with(['patient', 'pouch.batch', 'appointment', 'clinicalProcedure'])
            ->when($traceDateFilter, fn (Builder $query) => $query->whereDate('scanned_at', $traceDateFilter))
            ->when($tracePractitionerFilter, function (Builder $query) use ($tracePractitionerFilter): void {
                $query->where(function (Builder $sub) use ($tracePractitionerFilter): void {
                    $sub->where('scanned_by', $tracePractitionerFilter)
                        ->orWhereHas('appointment', fn (Builder $apt) => $apt->where('professional_id', $tracePractitionerFilter))
                        ->orWhereHas('clinicalProcedure', fn (Builder $proc) => $proc->where('practitioner_id', $tracePractitionerFilter));
                });
            })
            ->latest('scanned_at');

        $availablePouches = SterilizationPouch::with('batch')
            ->where('status', 'available')
            ->latest('id')
            ->limit(60)
            ->get();

        $expiredPouches = $availablePouches->filter(function (SterilizationPouch $pouch): bool {
            $batch = $pouch->batch;
            if (! $batch) {
                return false;
            }

            if ($batch->expires_at && $batch->expires_at->isPast()) {
                return true;
            }

            $validityDays = (int) ($batch->sterility_validity_days ?: 7);
            if (! $batch->sterilized_at) {
                return false;
            }

            return $batch->sterilized_at->copy()->addDays($validityDays)->isPast();
        })->values();

        return [
            'trace_today' => $traceToday,
            'trace_trend' => $traceToday - $traceYesterday,
            'low_stock_count' => $lowStockCount,
            'lab_pending_count' => $labPending,
            'lab_created_today' => $labCreatedToday,
            'stock_movements_today' => $stockMovementsToday,
            'critical_alert_count' => $criticalAlerts,
            'recent_traces' => $recentTracesQuery->limit(50)->get(),
            'recent_batches' => SterilizationBatch::withCount('pouches')
                ->latest('sterilized_at')
                ->latest('id')
                ->limit(20)
                ->get(),
            'low_stock_items' => StockItem::where('is_active', true)
                ->whereColumn('current_quantity', '<=', 'minimum_quantity')
                ->orderByRaw('(current_quantity - minimum_quantity) asc')
                ->orderBy('current_quantity')
                ->limit(20)
                ->get(),
            'lab_orders' => LabOrder::with(['patient', 'practitioner'])
                ->latest('created_at')
                ->limit(20)
                ->get(),
            'smart_alerts' => $smartAlerts,
            'stock_items' => StockItem::where('is_active', true)
                ->orderByRaw('CASE WHEN current_quantity <= minimum_quantity THEN 0 ELSE 1 END asc')
                ->orderBy('name')
                ->limit(500)
                ->get(),
            'recent_stock_movements' => StockMovement::with(['item', 'performer'])->latest('moved_at')->limit(40)->get(),
            'patients' => \App\Models\Patient::active()->orderBy('last_name')->limit(300)->get(['id', 'first_name', 'last_name', 'medical_record_number']),
            'appointments_today' => \Modules\Appointment\Models\Appointment::whereDate('appointment_date', $today)
                ->orderBy('start_time')->limit(300)->get(['id', 'patient_id', 'appointment_date', 'start_time']),
            'practitioners' => \App\Models\User::whereIn('role', ['professional', 'doctor', 'super_admin'])
                ->orderBy('name')->get(['id', 'name']),
            'trace_filters' => [
                'date' => $traceDateFilter,
                'practitioner_id' => $tracePractitionerFilter,
            ],
            'available_pouches' => $availablePouches,
            'expired_available_pouches' => $expiredPouches,
            'batch_status_labels' => $validatedStatusMap,
        ];
    }

    public function createSterilizationBatch(array $payload): SterilizationBatch
    {
        return DB::transaction(function () use ($payload): SterilizationBatch {
            $sterilizedAt = ! empty($payload['sterilized_at'])
                ? Carbon::parse((string) $payload['sterilized_at'])
                : now();

            $validityDays = (int) ($payload['sterility_validity_days'] ?? 7);
            $batchStatus = (string) ($payload['batch_status'] ?? 'in_progress');
            $bowieDickPassed = (bool) ($payload['bowie_dick_passed'] ?? false);
            $helixPassed = (bool) ($payload['helix_passed'] ?? false);

            $isValidated = $batchStatus === 'validated' && $bowieDickPassed && $helixPassed;
            $expiresAt = ! empty($payload['expires_at'])
                ? Carbon::parse((string) $payload['expires_at'])
                : $sterilizedAt->copy()->addDays(max(1, $validityDays));

            $batch = SterilizationBatch::create([
                'organization_id' => $payload['organization_id'] ?? null,
                'batch_code' => $payload['batch_code'],
                'sterilized_at' => $sterilizedAt,
                'expires_at' => $expiresAt,
                'sterilizer_cycle' => $payload['sterilizer_cycle'] ?? null,
                'operator_user_id' => auth()->id(),
                'status' => $isValidated ? 'validated' : 'in_progress',
                'sterility_validity_days' => $validityDays,
                'bowie_dick_passed' => $bowieDickPassed,
                'helix_passed' => $helixPassed,
                'validated_at' => $isValidated ? now() : null,
                'notes' => $payload['notes'] ?? null,
            ]);

            $count = max(1, (int) ($payload['pouch_count'] ?? 1));
            for ($i = 1; $i <= $count; $i++) {
                SterilizationPouch::create([
                    'batch_id' => $batch->id,
                    'pouch_code' => sprintf('%s-%03d', $batch->batch_code, $i),
                    'instrument_set_name' => $payload['instrument_set_name'] ?? null,
                    'status' => 'available',
                ]);
            }

            return $batch->load('pouches');
        });
    }

    public function scanPouchToPatient(array $payload): PatientSterilizationTrace
    {
        return DB::transaction(function () use ($payload): PatientSterilizationTrace {
            $pouch = SterilizationPouch::where('pouch_code', $payload['pouch_code'])->lockForUpdate()->firstOrFail();

            if ($pouch->status !== 'available') {
                abort(422, 'Ce sachet n est pas disponible.');
            }

            $batch = $pouch->batch()->first();
            if (! $batch) {
                abort(422, 'Ce sachet n est rattache a aucun lot valide.');
            }

            if ((string) $batch->status !== 'validated' || ! $batch->bowie_dick_passed || ! $batch->helix_passed) {
                abort(422, 'Impossible de tracer ce sachet: le lot n a pas ete valide (Bowie-Dick/Helix).');
            }

            $isExpired = false;
            if ($batch->expires_at && $batch->expires_at->isPast()) {
                $isExpired = true;
            } elseif ($batch->sterilized_at) {
                $validityDays = (int) ($batch->sterility_validity_days ?: 7);
                $isExpired = $batch->sterilized_at->copy()->addDays(max(1, $validityDays))->isPast();
            }

            if ($isExpired) {
                $batch->update(['status' => 'expired']);
                $pouch->update(['status' => 'expired']);
                abort(422, 'Sachet expire: impossible de tracer l utilisation.');
            }

            $isConformityOk = true;
            $conformityIssue = null;

            $trace = PatientSterilizationTrace::create([
                'patient_id' => $payload['patient_id'],
                'appointment_id' => $payload['appointment_id'] ?? null,
                'clinical_procedure_id' => $payload['clinical_procedure_id'] ?? null,
                'sterilization_pouch_id' => $pouch->id,
                'scanned_by' => auth()->id(),
                'scanned_at' => now(),
                'is_conformity_ok' => $isConformityOk,
                'conformity_issue' => $conformityIssue,
                'notes' => $payload['notes'] ?? null,
            ]);

            $pouch->update([
                'status' => 'used',
                'used_at' => now(),
            ]);

            return $trace->fresh(['patient', 'pouch.batch']);
        });
    }

    public function scanToOutByBarcode(string $barcode, float $quantity = 1, ?string $notes = null): array
    {
        return DB::transaction(function () use ($barcode, $quantity, $notes): array {
            $item = StockItem::query()
                ->where('is_active', true)
                ->where('code', trim($barcode))
                ->lockForUpdate()
                ->first();

            if (! $item) {
                abort(404, 'Article introuvable pour ce code-barres.');
            }

            if ($quantity <= 0) {
                abort(422, 'Quantite invalide.');
            }

            $current = (float) $item->current_quantity;
            $next = $current - $quantity;
            if ($next < 0) {
                abort(422, 'Stock insuffisant pour la sortie rapide.');
            }

            $item->update(['current_quantity' => $next]);

            $movement = StockMovement::create([
                'stock_item_id' => $item->id,
                'type' => 'out',
                'quantity' => $quantity,
                'performed_by' => auth()->id(),
                'reference_type' => 'scan_to_out',
                'reference_id' => null,
                'notes' => $notes ?: 'Sortie rapide par scan code-barres.',
                'moved_at' => now(),
            ]);

            if ($next <= (float) $item->minimum_quantity) {
                $this->createAlertOnce(
                    type: 'stock',
                    severity: $next <= 0 ? 'critical' : 'warning',
                    title: $next <= 0 ? 'Rupture stock' : 'Stock bas',
                    message: sprintf(
                        'Article %s (%s): %.2f restant (seuil %.2f).',
                        $item->name,
                        $item->code,
                        $next,
                        (float) $item->minimum_quantity
                    ),
                    referenceType: 'stock_items',
                    referenceId: $item->id
                );
            }

            return [
                'item' => $item->fresh(),
                'movement' => $movement,
            ];
        });
    }

    public function createStockMovement(array $payload): StockMovement
    {
        return DB::transaction(function () use ($payload): StockMovement {
            $item = StockItem::lockForUpdate()->findOrFail($payload['stock_item_id']);
            $type = $payload['type'];
            $qty = (float) $payload['quantity'];

            if ($qty <= 0) {
                abort(422, 'Quantite invalide.');
            }

            $current = (float) $item->current_quantity;
            $next = $current;

            if (in_array($type, ['in', 'release'], true)) {
                $next = $current + $qty;
            } elseif (in_array($type, ['out', 'reserve'], true)) {
                $next = $current - $qty;
            } elseif ($type === 'adjustment') {
                $next = $qty;
            }

            if ($next < 0) {
                abort(422, 'Stock insuffisant.');
            }

            $item->update(['current_quantity' => $next]);

            if ($next <= (float) $item->minimum_quantity) {
                $this->createAlertOnce(
                    type: 'stock',
                    severity: 'warning',
                    title: 'Rupture stock imminente',
                    message: sprintf(
                        'Article %s (%s) sous le seuil: %.2f <= %.2f.',
                        $item->name,
                        $item->code,
                        $next,
                        (float) $item->minimum_quantity
                    ),
                    referenceType: 'stock_items',
                    referenceId: $item->id
                );
            }

            return StockMovement::create([
                'stock_item_id' => $item->id,
                'type' => $type,
                'quantity' => $qty,
                'performed_by' => auth()->id(),
                'reference_type' => $payload['reference_type'] ?? null,
                'reference_id' => $payload['reference_id'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'moved_at' => $payload['moved_at'] ?? now(),
            ]);
        });
    }

    public function createLabOrder(array $payload): LabOrder
    {
        $order = LabOrder::create([
            'patient_id' => $payload['patient_id'],
            'appointment_id' => $payload['appointment_id'] ?? null,
            'clinical_procedure_id' => $payload['clinical_procedure_id'] ?? null,
            'practitioner_id' => $payload['practitioner_id'] ?? auth()->id(),
            'lab_name' => $payload['lab_name'],
            'lab_contact' => $payload['lab_contact'] ?? null,
            'type' => $payload['type'] ?? 'prosthesis',
            'status' => $payload['status'] ?? 'impression_taken',
            'due_date' => $payload['due_date'] ?? null,
            'external_file_paths' => $payload['external_file_paths'] ?? null,
            'notes' => $payload['notes'] ?? null,
        ]);

        $this->addLabOrderEvent($order, [
            'event_type' => 'created',
            'status' => $order->status,
            'message' => 'Commande labo creee (empreinte prise).',
            'meta' => [
                'external_file_paths' => $order->external_file_paths,
                'lab_name' => $order->lab_name,
            ],
        ]);

        if (! empty($order->external_file_paths)) {
            $this->addLabOrderEvent($order, [
                'event_type' => 'file_sent',
                'status' => $order->status,
                'message' => 'Fichiers STL/CBCT/DICOM transmis au laboratoire.',
                'meta' => ['file_count' => count($order->external_file_paths)],
            ]);
        }

        return $order;
    }

    public function updateLabOrderStatus(LabOrder $order, string $status): LabOrder
    {
        $allowed = ['impression_taken', 'sent_to_lab', 'received_from_lab', 'fitted_on_patient', 'cancelled'];
        if (! in_array($status, $allowed, true)) {
            abort(422, 'Statut labo invalide.');
        }

        $payload = ['status' => $status];
        if ($status === 'received_from_lab') {
            $payload['delivered_at'] = now();
        }

        $order->update($payload);

        $this->addLabOrderEvent($order, [
            'event_type' => 'status_update',
            'status' => $status,
            'message' => 'Statut commande mis a jour.',
            'meta' => ['delivered_at' => $payload['delivered_at'] ?? null],
        ]);

        return $order->fresh();
    }

    public function addLabOrderEvent(LabOrder $order, array $payload): LabOrderEvent
    {
        return LabOrderEvent::create([
            'lab_order_id' => $order->id,
            'event_type' => $payload['event_type'],
            'status' => $payload['status'] ?? null,
            'message' => $payload['message'] ?? null,
            'meta' => $payload['meta'] ?? null,
            'created_by' => auth()->id(),
            'event_at' => now(),
        ]);
    }

    public function smartAlerts(): array
    {
        $alerts = collect();

        $lowStock = StockItem::where('is_active', true)
            ->whereColumn('current_quantity', '<=', 'minimum_quantity')
            ->limit(20)
            ->get();

        foreach ($lowStock as $item) {
            $alerts->push([
                'type' => 'stock',
                'severity' => (float) $item->current_quantity <= 0 ? 'critical' : 'warning',
                'title' => (float) $item->current_quantity <= 0 ? 'Rupture stock' : 'Rupture stock imminente',
                'message' => sprintf(
                    '%s (%s) sous seuil: %.2f <= %.2f.',
                    $item->name,
                    $item->code,
                    (float) $item->current_quantity,
                    (float) $item->minimum_quantity
                ),
            ]);
        }

        $availablePouches = SterilizationPouch::query()
            ->with('batch')
            ->where('status', 'available')
            ->limit(150)
            ->get();

        foreach ($availablePouches as $pouch) {
            $batch = $pouch->batch;
            if (! $batch) {
                continue;
            }

            $validityDays = (int) ($batch->sterility_validity_days ?: 7);
            $isExpiredByDate = $batch->expires_at && $batch->expires_at->isPast();
            $isExpiredByAge = $batch->sterilized_at
                ? $batch->sterilized_at->copy()->addDays(max(1, $validityDays))->isPast()
                : false;

            if (! $isExpiredByDate && ! $isExpiredByAge) {
                continue;
            }

            $alerts->push([
                'type' => 'sterilization',
                'severity' => 'critical',
                'title' => 'Sachet expire non utilise',
                'message' => sprintf(
                    'Sachet %s (lot %s) depasse la validite (%d jours).',
                    $pouch->pouch_code,
                    $batch->batch_code,
                    max(1, $validityDays)
                ),
            ]);
        }

        $steriIssues = PatientSterilizationTrace::with(['patient', 'pouch'])
            ->where('is_conformity_ok', false)
            ->latest('scanned_at')
            ->limit(20)
            ->get();

        foreach ($steriIssues as $trace) {
            $alerts->push([
                'type' => 'sterilization',
                'severity' => 'critical',
                'title' => 'Sterilisation non conforme',
                'message' => sprintf(
                    'Patient %s, sachet %s, issue: %s.',
                    $trace->patient?->full_name ?? '#'.$trace->patient_id,
                    $trace->pouch?->pouch_code ?? '-',
                    $trace->conformity_issue ?? 'unknown'
                ),
            ]);
        }

        $procedures = \Modules\ClinicalRecord\Models\ClinicalProcedure::with('patient')
            ->whereIn('status', ['completed', 'in_progress'])
            ->latest('performed_at')
            ->limit(150)
            ->get();

        foreach ($procedures as $procedure) {
            $patient = $procedure->patient;
            $allergies = collect($patient?->allergies ?? [])->map(fn ($v) => mb_strtolower((string) $v));
            if ($allergies->isEmpty()) {
                continue;
            }

            $materials = collect($procedure->materials_used ?? [])->map(fn ($v) => mb_strtolower((string) $v));
            $conflicts = $materials->filter(function (string $material) use ($allergies): bool {
                foreach ($allergies as $allergy) {
                    if ($allergy !== '' && str_contains($material, $allergy)) {
                        return true;
                    }
                }
                return false;
            });

            if ($conflicts->isNotEmpty()) {
                $alerts->push([
                    'type' => 'allergy',
                    'severity' => 'critical',
                    'title' => 'Allergie potentielle detectee',
                    'message' => sprintf(
                        'Patient %s: materiau potentiellement allergene (%s).',
                        $patient?->full_name ?? '#'.$procedure->patient_id,
                        $conflicts->implode(', ')
                    ),
                ]);
            }
        }

        $recallWindowStart = now()->subMonths(13)->startOfDay();
        $recallWindowEnd = now()->subMonths(11)->endOfDay();
        $implants = \Modules\ClinicalRecord\Models\ClinicalProcedure::with('patient')
            ->where('status', 'completed')
            ->whereBetween('performed_at', [$recallWindowStart, $recallWindowEnd])
            ->where(function ($q): void {
                $q->where('name', 'like', '%implant%')
                    ->orWhere('procedure_code', 'like', '%IMP%');
            })
            ->limit(100)
            ->get();

        foreach ($implants as $implant) {
            $alerts->push([
                'type' => 'implant_recall',
                'severity' => 'info',
                'title' => 'Rappel implant garantie',
                'message' => sprintf(
                    'Controle recommande pour %s (acte %s).',
                    $implant->patient?->full_name ?? '#'.$implant->patient_id,
                    $implant->procedure_code
                ),
            ]);
        }

        $persisted = CareAlert::whereNull('resolved_at')
            ->latest('alerted_at')
            ->limit(20)
            ->get(['type', 'severity', 'title', 'message'])
            ->map(fn (CareAlert $alert) => $alert->toArray());

        return $alerts->merge($persisted)->take(50)->values()->all();
    }

    private function createAlertOnce(
        string $type,
        string $severity,
        string $title,
        string $message,
        ?int $patientId = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): void {
        $exists = CareAlert::query()
            ->where('type', $type)
            ->where('title', $title)
            ->where('message', $message)
            ->whereNull('resolved_at')
            ->when($referenceType, fn ($q) => $q->where('reference_type', $referenceType))
            ->when($referenceId, fn ($q) => $q->where('reference_id', $referenceId))
            ->exists();

        if ($exists) {
            return;
        }

        CareAlert::create([
            'type' => $type,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'patient_id' => $patientId,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'alerted_at' => now(),
        ]);
    }
}
