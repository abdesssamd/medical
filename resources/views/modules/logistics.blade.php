@extends('layouts.admin')

@section('title', 'Module 4 - Logistique')
@section('page_pretitle', 'Module 4')
@section('page_title', 'Centre de pilotage logistique')

@section('content')
@php
    $activeTab = $active_tab ?? request('tab', 'dashboard');
    $traceFilterDate = $trace_filters['date'] ?? null;
    $traceFilterPractitionerId = $trace_filters['practitioner_id'] ?? null;

    $tabs = ['dashboard', 'sterilization', 'stocks-lab'];
    $tabIndex = array_search($activeTab, $tabs, true);
    $tabIndex = $tabIndex === false ? 0 : $tabIndex;

    $labSteps = [
        'impression_taken' => 'Empreinte prise',
        'sent_to_lab' => 'Envoye au labo',
        'received_from_lab' => 'Recu',
        'fitted_on_patient' => 'Pose sur patient',
        'cancelled' => 'Annule',
    ];

    $batchStatusLabels = $batch_status_labels ?? [
        'in_progress' => 'En cours',
        'validated' => 'Valide',
        'expired' => 'Expire',
    ];

    $stockTone = static function ($item): string {
        $qty = (float) $item->current_quantity;
        $min = (float) $item->minimum_quantity;
        if ($qty <= 0) {
            return 'danger';
        }
        if ($qty <= $min) {
            return 'warning';
        }
        return 'success';
    };

    $stockLabel = static function ($item): string {
        $qty = (float) $item->current_quantity;
        $min = (float) $item->minimum_quantity;
        if ($qty <= 0) {
            return 'Rupture';
        }
        if ($qty <= $min) {
            return 'Bas';
        }
        return 'OK';
    };

    $previewCards = match ($activeTab) {
        'sterilization' => [
            [
                'title' => 'Sterilisation du jour',
                'subtitle' => 'Lots et traces rattaches a l onglet actif',
                'rows' => [
                    ['label' => 'Lots recents', 'value' => (string) $recent_batches->count()],
                    ['label' => 'Traces recentes', 'value' => (string) $recent_traces->count()],
                    ['label' => 'Sachets expires', 'value' => (string) $expired_available_pouches->count()],
                ],
            ],
            [
                'title' => 'Point de vigilance',
                'subtitle' => 'Element prioritaire a traiter maintenant',
                'rows' => [
                    [
                        'label' => 'Dernier lot',
                        'value' => optional($recent_batches->first())->batch_code ?: 'Aucun lot',
                    ],
                    [
                        'label' => 'Derniere trace',
                        'value' => optional($recent_traces->first()?->patient)->full_name ?: 'Aucune trace',
                    ],
                    [
                        'label' => 'Validation en attente',
                        'value' => (string) $recent_batches->where('status', 'in_progress')->count(),
                    ],
                ],
            ],
        ],
        'stocks-lab' => [
            [
                'title' => 'Stocks sensibles',
                'subtitle' => 'Synthese immediate des seuils et mouvements',
                'rows' => [
                    ['label' => 'Sous seuil', 'value' => (string) $low_stock_items->count()],
                    ['label' => 'Commandes labo', 'value' => (string) $lab_orders->count()],
                    ['label' => 'Alertes critiques', 'value' => (string) $critical_alert_count],
                ],
            ],
            [
                'title' => 'Priorite operationnelle',
                'subtitle' => 'Ce qui merite une action rapide',
                'rows' => [
                    [
                        'label' => 'Article le plus critique',
                        'value' => $low_stock_items->first()?->name ?: 'Aucun article critique',
                    ],
                    [
                        'label' => 'Commande la plus recente',
                        'value' => $lab_orders->first()?->order_number ?: 'Aucune commande',
                    ],
                    [
                        'label' => 'Labo / destinataire',
                        'value' => $lab_orders->first()?->lab_name ?: 'Aucun labo',
                    ],
                ],
            ],
        ],
        default => [
            [
                'title' => 'Vue d ensemble',
                'subtitle' => 'Resume du pilotage logistique',
                'rows' => [
                    ['label' => 'Alertes intelligentes', 'value' => (string) count($smart_alerts)],
                    ['label' => 'Stocks sensibles', 'value' => (string) $low_stock_items->count()],
                    ['label' => 'Commandes en cours', 'value' => (string) $lab_orders->count()],
                ],
            ],
            [
                'title' => 'A surveiller',
                'subtitle' => 'Signal le plus important de l instant',
                'rows' => [
                    [
                        'label' => 'Alerte principale',
                        'value' => $smart_alerts[0]['title'] ?? 'Aucune alerte active',
                    ],
                    [
                        'label' => 'Lot recent',
                        'value' => optional($recent_batches->first())->batch_code ?: 'Aucun lot',
                    ],
                    [
                        'label' => 'Commande labo',
                        'value' => $lab_orders->first()?->order_number ?: 'Aucune commande',
                    ],
                ],
            ],
        ],
    };
@endphp

<div class="module4-premium">
    @if(session('batch_labels_url'))
        <div class="mb-6 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="font-medium">Lot cree. Les etiquettes QR sont pretes a imprimer.</p>
                <a href="{{ session('batch_labels_url') }}" target="_blank" class="m4-btn-primary text-xs">Imprimer etiquettes</a>
            </div>
        </div>
    @endif

    <section class="m4-kpi-grid">
        <x-module4.stat-tile class="m4-kpi-hover" title="Scans sterilisation" :value="$trace_today" subtitle="Aujourd hui" tone="sterile" icon="shield-check" />
        <x-module4.stat-tile class="m4-kpi-hover" title="Stocks sensibles" :value="$low_stock_count" subtitle="Seuil mini atteint" tone="danger" icon="box" />
        <x-module4.stat-tile class="m4-kpi-hover" title="Commandes labo" :value="$lab_pending_count" subtitle="En progression" tone="primary" icon="flask-conical" />
        <x-module4.stat-tile class="m4-kpi-hover" title="Alertes critiques" :value="$critical_alert_count" subtitle="Action requise" tone="danger" icon="alert-triangle" />
    </section>

    <section class="m4-tabs-shell">
        <div class="m4-tabs-grid">
            <span class="m4-tab-indicator" style="transform: translateX(calc({{ $tabIndex }} * 100%));"></span>
            <a data-tab-link href="{{ route('care.module4.index', ['tab' => 'dashboard']) }}" class="m4-tab-link {{ $activeTab === 'dashboard' ? 'is-active' : '' }}">Tableau de Bord</a>
            <a data-tab-link href="{{ route('care.module4.index', ['tab' => 'sterilization']) }}" class="m4-tab-link {{ $activeTab === 'sterilization' ? 'is-active' : '' }}">Sterilisation</a>
            <a data-tab-link href="{{ route('care.module4.index', ['tab' => 'stocks-lab']) }}" class="m4-tab-link {{ $activeTab === 'stocks-lab' ? 'is-active' : '' }}">Stocks & Labo</a>
        </div>
    </section>

    <section class="m4-preview-grid">
        @foreach($previewCards as $card)
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4">
                    <p class="text-sm font-semibold text-slate-900">{{ $card['title'] }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $card['subtitle'] }}</p>
                </div>
                <div class="space-y-3">
                    @foreach($card['rows'] as $row)
                        <div class="flex items-center justify-between gap-3 rounded-2xl bg-slate-50 px-3 py-3">
                            <span class="text-sm text-slate-500">{{ $row['label'] }}</span>
                            <span class="text-sm font-semibold text-slate-900">{{ $row['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            </article>
        @endforeach
    </section>

    <div id="module4TabContent" class="m4-tab-enter mt-6">
        @if($activeTab === 'dashboard')
            <section class="grid grid-cols-1 gap-5 xl:grid-cols-12">
                <div class="space-y-5 xl:col-span-8">
                    <x-module4.panel-card title="Alertes intelligentes" subtitle="Priorisation automatique: sterilisation, stock, labo">
                        <div class="space-y-3">
                            @forelse($smart_alerts as $alert)
                                @php
                                    $sev = $alert['severity'] ?? 'warning';
                                    $tone = $sev === 'critical' ? 'danger' : ($sev === 'warning' ? 'warning' : 'primary');
                                    $rail = $sev === 'critical' ? 'bg-rose-500' : ($sev === 'warning' ? 'bg-amber-500' : 'bg-emerald-500');
                                @endphp
                                <article class="flex gap-3 rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
                                    <span class="mt-0.5 h-10 w-1.5 shrink-0 rounded-full {{ $rail }}"></span>
                                    <div class="min-w-0 flex-1 space-y-1">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <p class="text-sm font-semibold text-slate-900">{{ $alert['title'] }}</p>
                                            <x-module4.status-badge :tone="$tone">{{ $sev }}</x-module4.status-badge>
                                        </div>
                                        <p class="text-xs font-medium uppercase tracking-[0.08em] text-slate-500">{{ $alert['type'] }}</p>
                                        <p class="text-sm text-slate-600">{{ $alert['message'] }}</p>
                                    </div>
                                </article>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-6 py-8 text-center">
                                    <div class="mx-auto mb-3 inline-flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                                        <x-module4.icon name="shield-check" class="h-5 w-5" />
                                    </div>
                                    <p class="text-sm font-semibold text-slate-700">Tout est sous controle. Aucune alerte pour le moment.</p>
                                </div>
                            @endforelse
                        </div>
                    </x-module4.panel-card>

                    <x-module4.panel-card title="File operationnelle" subtitle="Lots, traces et commandes en attente">
                        <div class="space-y-2">
                            @forelse($recent_batches->take(4) as $batch)
                                <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 px-3 py-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">{{ $batch->batch_code }}</p>
                                        <p class="text-xs text-slate-500">Expire {{ optional($batch->expires_at)->format('d/m/Y H:i') ?: '-' }}</p>
                                    </div>
                                    <x-module4.status-badge :tone="$batch->status === 'validated' ? 'success' : ($batch->status === 'expired' ? 'danger' : 'warning')">{{ $batchStatusLabels[$batch->status] ?? $batch->status }}</x-module4.status-badge>
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-6 py-8 text-center">
                                    <p class="text-sm font-semibold text-slate-700">Tout est sous controle. Aucune alerte pour le moment.</p>
                                </div>
                            @endforelse
                        </div>
                    </x-module4.panel-card>
                </div>

                <div class="space-y-5 xl:col-span-4">
                    <x-module4.panel-card title="Actions rapides" subtitle="Acces direct aux formulaires metier">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-1">
                            <button data-modal-open="batchModal" class="m4-btn-primary w-full justify-start">Generer un lot sterile</button>
                            <button data-modal-open="traceModal" class="m4-btn-ghost w-full justify-start">Tracer un sachet patient</button>
                            <a href="{{ route('care.module4.index', ['tab' => 'stocks-lab']) }}" class="m4-btn-ghost w-full justify-start">Acceder aux stocks & labo</a>
                        </div>
                    </x-module4.panel-card>

                    <x-module4.panel-card title="Stocks sous seuil" subtitle="Items remontees automatiquement en tete">
                        <div class="space-y-2">
                            @forelse($low_stock_items as $item)
                                <div class="rounded-xl border border-slate-100 bg-white px-3 py-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-sm font-semibold text-slate-900">{{ $item->name }}</p>
                                        <x-module4.status-badge :tone="$stockTone($item)">{{ $stockLabel($item) }}</x-module4.status-badge>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500">{{ $item->code }} | Min {{ $item->minimum_quantity }}</p>
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-6 py-8 text-center">
                                    <p class="text-sm font-semibold text-slate-700">Tout est sous controle. Aucune alerte pour le moment.</p>
                                </div>
                            @endforelse
                        </div>
                    </x-module4.panel-card>

                    <x-module4.panel-card title="Commandes labo en cours" subtitle="Vue rapide des etapes">
                        <div class="space-y-2">
                            @forelse($lab_orders->take(6) as $order)
                                <div class="rounded-xl border border-slate-100 bg-white px-3 py-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-sm font-semibold text-slate-900">{{ $order->order_number }}</p>
                                        <x-module4.status-badge tone="primary">{{ $labSteps[$order->status] ?? $order->status }}</x-module4.status-badge>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500">{{ $order->patient?->full_name ?: '-' }} | {{ $order->lab_name }}</p>
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-6 py-8 text-center">
                                    <p class="text-sm font-semibold text-slate-700">Tout est sous controle. Aucune alerte pour le moment.</p>
                                </div>
                            @endforelse
                        </div>
                    </x-module4.panel-card>
                </div>
            </section>
        @endif

        @if($activeTab === 'sterilization')
            <section class="space-y-5">
                <div class="flex flex-wrap items-center gap-3">
                    <button data-modal-open="batchModal" class="m4-btn-primary">+ Nouveau lot</button>
                    <button data-modal-open="traceModal" class="m4-btn-ghost">Tracer sachet QR</button>
                </div>

                <div class="grid grid-cols-1 gap-5 xl:grid-cols-12">
                    <div class="space-y-5 xl:col-span-7">
                        <x-module4.panel-card title="Cycles de sterilisation" subtitle="Etat de lot: En cours, Valide, Expire">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-100 text-sm">
                                    <thead>
                                        <tr class="text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                                            <th class="px-3 py-2">Lot</th>
                                            <th class="px-3 py-2">Statut</th>
                                            <th class="px-3 py-2">Sterilise le</th>
                                            <th class="px-3 py-2">Expire le</th>
                                            <th class="px-3 py-2">Sachets</th>
                                            <th class="px-3 py-2 text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @forelse($recent_batches as $batch)
                                            @php
                                                $tone = $batch->status === 'validated' ? 'success' : ($batch->status === 'expired' ? 'danger' : 'warning');
                                            @endphp
                                            <tr>
                                                <td class="px-3 py-3">
                                                    <p class="font-semibold text-slate-900">{{ $batch->batch_code }}</p>
                                                    <p class="text-xs text-slate-500">Cycle {{ $batch->sterilizer_cycle ?: '-' }}</p>
                                                </td>
                                                <td class="px-3 py-3"><x-module4.status-badge :tone="$tone">{{ $batchStatusLabels[$batch->status] ?? $batch->status }}</x-module4.status-badge></td>
                                                <td class="px-3 py-3 text-slate-600">{{ optional($batch->sterilized_at)->format('d/m/Y H:i') ?: '-' }}</td>
                                                <td class="px-3 py-3 text-slate-600">{{ optional($batch->expires_at)->format('d/m/Y H:i') ?: '-' }}</td>
                                                <td class="px-3 py-3 text-slate-700">{{ $batch->pouches_count }}</td>
                                                <td class="px-3 py-3 text-right">
                                                    <a href="{{ route('care.module4.batch.labels', ['batch' => $batch->id]) }}" target="_blank" class="inline-flex rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 transition-all duration-300 hover:border-slate-300 hover:bg-slate-50">Etiquettes QR</a>
                                                </td>
                                            </tr>
                                            @if($batch->status === 'in_progress')
                                                <tr>
                                                    <td colspan="6" class="px-3 py-3">
                                                        <form method="POST" action="{{ route('care.module4.batch.validate', ['batch' => $batch->id]) }}" class="flex flex-wrap items-center gap-4 rounded-2xl bg-amber-50 px-3 py-2">
                                                            @csrf
                                                            <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700"><input type="checkbox" class="rounded border-slate-300 text-emerald-600" name="bowie_dick_passed" value="1" required> Bowie-Dick OK</label>
                                                            <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700"><input type="checkbox" class="rounded border-slate-300 text-emerald-600" name="helix_passed" value="1" required> Helix OK</label>
                                                            <button class="rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition-all duration-300 hover:bg-emerald-700">Valider lot</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endif
                                        @empty
                                            <tr><td colspan="6" class="px-3 py-8 text-center text-slate-400">Aucun lot de sterilisation.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </x-module4.panel-card>

                        <x-module4.panel-card title="Dernieres traces steriles" subtitle="Filtrable par praticien et date">
                            <x-slot:actions>
                                <form method="GET" action="{{ route('care.module4.index') }}" class="flex flex-wrap items-center gap-2">
                                    <input type="hidden" name="tab" value="sterilization">
                                    <input type="date" name="trace_date" value="{{ $traceFilterDate }}" class="m4-input">
                                    <select name="trace_practitioner_id" class="m4-input">
                                        <option value="">Praticien</option>
                                        @foreach($practitioners as $pro)
                                            <option value="{{ $pro->id }}" @selected((int) $traceFilterPractitionerId === (int) $pro->id)>{{ $pro->name }}</option>
                                        @endforeach
                                    </select>
                                    <button class="m4-btn-ghost">Filtrer</button>
                                </form>
                            </x-slot:actions>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-100 text-sm">
                                    <thead>
                                        <tr class="text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                                            <th class="px-3 py-2">Date</th>
                                            <th class="px-3 py-2">Patient</th>
                                            <th class="px-3 py-2">Sachet</th>
                                            <th class="px-3 py-2">Lot</th>
                                            <th class="px-3 py-2">Conformite</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @forelse($recent_traces as $trace)
                                            <tr>
                                                <td class="px-3 py-3 text-slate-600">{{ optional($trace->scanned_at)->format('d/m/Y H:i') ?: '-' }}</td>
                                                <td class="px-3 py-3 text-slate-700">{{ $trace->patient?->full_name ?: '-' }}</td>
                                                <td class="px-3 py-3 text-slate-700">{{ $trace->pouch?->pouch_code ?: '-' }}</td>
                                                <td class="px-3 py-3 text-slate-700">{{ $trace->pouch?->batch?->batch_code ?: '-' }}</td>
                                                <td class="px-3 py-3">
                                                    @if($trace->is_conformity_ok)
                                                        <x-module4.status-badge tone="success">OK</x-module4.status-badge>
                                                    @else
                                                        <x-module4.status-badge tone="danger">{{ $trace->conformity_issue ?: 'non conforme' }}</x-module4.status-badge>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="px-3 py-8 text-center text-slate-400">Aucune trace sterile.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </x-module4.panel-card>
                    </div>

                    <div class="space-y-5 xl:col-span-5">
                        <x-module4.panel-card title="Scan QR instantane" subtitle="Traçabilite en AJAX">
                            <form id="instantTraceForm" class="space-y-3">
                                <div>
                                    <label class="mb-1 block text-xs font-medium tracking-wide text-slate-600">Code sachet</label>
                                    <input name="pouch_code" required class="m4-input" />
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium tracking-wide text-slate-600">Patient</label>
                                    <select name="patient_id" required class="m4-input">
                                        @foreach($patients as $patient)
                                            <option value="{{ $patient->id }}">{{ $patient->last_name }} {{ $patient->first_name }} - {{ $patient->medical_record_number }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="mb-1 block text-xs font-medium tracking-wide text-slate-600">RDV</label>
                                        <select name="appointment_id" class="m4-input">
                                            <option value="">-</option>
                                            @foreach($appointments_today as $apt)
                                                <option value="{{ $apt->id }}">#{{ $apt->id }} - {{ \Illuminate\Support\Str::of($apt->start_time)->substr(0,5) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium tracking-wide text-slate-600">Procedure ID</label>
                                        <input type="number" name="clinical_procedure_id" class="m4-input" />
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium tracking-wide text-slate-600">Notes</label>
                                    <input name="notes" class="m4-input" />
                                </div>
                                <div class="flex items-center justify-between">
                                    <button type="submit" class="rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition-all duration-300 hover:bg-emerald-700">Tracer instantanement</button>
                                    <p id="instantTraceFeedback" class="text-xs text-slate-500"></p>
                                </div>
                            </form>
                        </x-module4.panel-card>

                        <x-module4.panel-card title="Alertes expiration" subtitle="Sachets non utilises au dela de la validite">
                            <div class="space-y-2">
                                @forelse($expired_available_pouches as $pouch)
                                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                                        <p class="font-semibold">{{ $pouch->pouch_code }}</p>
                                        <p class="text-xs">Lot {{ $pouch->batch?->batch_code }}</p>
                                    </div>
                                @empty
                                    <p class="text-sm text-slate-400">Aucune alerte expiration.</p>
                                @endforelse
                            </div>
                        </x-module4.panel-card>
                    </div>
                </div>
            </section>
        @endif

        @if($activeTab === 'stocks-lab')
            <section class="space-y-5">
                <div class="flex flex-wrap items-center gap-3">
                    <button data-modal-open="stockItemModal" class="m4-btn-primary">+ Nouvel article</button>
                    <button data-modal-open="stockMoveModal" class="m4-btn-ghost">+ Mouvement</button>
                    <button data-modal-open="labOrderModal" class="m4-btn-ghost">+ Commande labo</button>
                </div>

                <div class="grid grid-cols-1 gap-5 xl:grid-cols-12">
                    <div class="space-y-5 xl:col-span-5">
                        <x-module4.panel-card title="Scan-to-Out" subtitle="Commande rapide style barre de commande">
                            <form id="scanOutForm" class="space-y-3">
                                <div id="scanOutShell" class="rounded-2xl border border-slate-200 bg-white p-2 shadow-sm transition-all duration-300">
                                    <div class="flex items-center gap-2 rounded-xl bg-slate-50 px-3 py-2">
                                        <x-module4.icon name="scan" class="h-4 w-4 text-emerald-600" />
                                        <input name="barcode" placeholder="Scanner un code-barres..." required class="w-full border-0 bg-transparent text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none" />
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="mb-1 block text-xs font-medium tracking-wide text-slate-600">Quantite</label>
                                        <input type="number" step="0.01" min="0.01" name="quantity" value="1" class="m4-input" />
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium tracking-wide text-slate-600">Notes</label>
                                        <input name="notes" placeholder="Fauteuil 2" class="m4-input" />
                                    </div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <button type="submit" class="m4-btn-primary">Scanner et sortir</button>
                                    <p id="scanOutFeedback" class="text-xs text-slate-500"></p>
                                </div>
                            </form>
                        </x-module4.panel-card>

                        <x-module4.panel-card title="Derniers mouvements" subtitle="Flux de sorties et ajustements">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-100 text-sm">
                                    <thead>
                                        <tr class="text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                                            <th class="px-3 py-2">Date</th>
                                            <th class="px-3 py-2">Article</th>
                                            <th class="px-3 py-2">Type</th>
                                            <th class="px-3 py-2 text-right">Qte</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @forelse($recent_stock_movements as $movement)
                                            <tr>
                                                <td class="px-3 py-3 text-slate-600">{{ optional($movement->moved_at)->format('d/m H:i') ?: '-' }}</td>
                                                <td class="px-3 py-3 text-slate-700">{{ $movement->item?->name ?: '-' }}</td>
                                                <td class="px-3 py-3"><x-module4.status-badge tone="primary">{{ $movement->type }}</x-module4.status-badge></td>
                                                <td class="px-3 py-3 text-right text-slate-700">{{ $movement->quantity }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="px-3 py-8 text-center text-slate-400">Aucun mouvement.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </x-module4.panel-card>
                    </div>

                    <div class="space-y-5 xl:col-span-7">
                        <x-module4.panel-card title="Inventaire" subtitle="Badges de statut selon seuil minimum">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-100 text-sm">
                                    <thead>
                                        <tr class="text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                                            <th class="px-3 py-2">Article</th>
                                            <th class="px-3 py-2">Categorie</th>
                                            <th class="px-3 py-2 text-right">Qte</th>
                                            <th class="px-3 py-2 text-right">Min</th>
                                            <th class="px-3 py-2">Etat</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @forelse($stock_items as $item)
                                            <tr>
                                                <td class="px-3 py-3 text-slate-800">{{ $item->name }} ({{ $item->code }})</td>
                                                <td class="px-3 py-3 text-slate-600">{{ $item->category }}</td>
                                                <td class="px-3 py-3 text-right text-slate-700">{{ $item->current_quantity }}</td>
                                                <td class="px-3 py-3 text-right text-slate-700">{{ $item->minimum_quantity }}</td>
                                                <td class="px-3 py-3"><x-module4.status-badge :tone="$stockTone($item)">{{ $stockLabel($item) }}</x-module4.status-badge></td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="px-3 py-8 text-center text-slate-400">Aucun article.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </x-module4.panel-card>

                        <x-module4.panel-card title="Commandes labo" subtitle="Flux Empreinte -> Labo -> Recu -> Pose">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-100 text-sm">
                                    <thead>
                                        <tr class="text-left text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                                            <th class="px-3 py-2">No</th>
                                            <th class="px-3 py-2">Patient</th>
                                            <th class="px-3 py-2">Labo</th>
                                            <th class="px-3 py-2">Fichiers</th>
                                            <th class="px-3 py-2">Etape</th>
                                            <th class="px-3 py-2 text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @forelse($lab_orders as $order)
                                            @php
                                                $files = collect($order->external_file_paths ?? []);
                                                $hasStl = $files->contains(fn ($path) => str_ends_with(strtolower((string) $path), '.stl'));
                                                $hasDicom = $files->contains(fn ($path) => str_ends_with(strtolower((string) $path), '.dcm') || str_contains(strtolower((string) $path), 'dicom') || str_contains(strtolower((string) $path), 'cbct'));
                                            @endphp
                                            <tr>
                                                <td class="px-3 py-3 text-slate-800">{{ $order->order_number }}</td>
                                                <td class="px-3 py-3 text-slate-700">{{ $order->patient?->full_name ?: '-' }}</td>
                                                <td class="px-3 py-3 text-slate-700">{{ $order->lab_name }}</td>
                                                <td class="px-3 py-3">
                                                    <div class="flex flex-wrap items-center gap-1">
                                                        @if($hasStl)
                                                            <x-module4.status-badge tone="primary" title="Fichier STL">STL</x-module4.status-badge>
                                                        @endif
                                                        @if($hasDicom)
                                                            <x-module4.status-badge tone="sterile" title="Fichier DICOM/CBCT">DICOM</x-module4.status-badge>
                                                        @endif
                                                        @if(! $hasStl && ! $hasDicom)
                                                            <span class="text-xs text-slate-400">-</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="px-3 py-3"><x-module4.status-badge tone="primary">{{ $labSteps[$order->status] ?? $order->status }}</x-module4.status-badge></td>
                                                <td class="px-3 py-3 text-right">
                                                    <form method="POST" action="{{ route('care.module4.lab-order.status', ['labOrder' => $order->id]) }}" class="inline-flex items-center gap-1">
                                                        @csrf
                                                        <select name="status" class="rounded-xl border border-slate-200 bg-slate-50 px-2 py-1 text-xs text-slate-700 shadow-sm transition-all duration-300 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/25">
                                                            @foreach($labSteps as $code => $label)
                                                                <option value="{{ $code }}" @selected($order->status === $code)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                        <button class="rounded-xl border border-slate-200 px-2 py-1 text-xs font-semibold text-slate-700 transition-all duration-300 hover:bg-slate-50">OK</button>
                                                    </form>
                                                    <button class="ml-1 rounded-xl border border-emerald-200 px-2 py-1 text-xs font-semibold text-emerald-700 transition-all duration-300 hover:bg-emerald-50 btn-load-events" data-order-id="{{ $order->id }}">Timeline</button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="6" class="px-3 py-8 text-center text-slate-400">Aucune commande labo.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4 rounded-2xl bg-slate-50 px-4 py-3">
                                <p class="mb-2 text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Timeline labo selectionnee</p>
                                <div id="labEventsWrap" class="text-sm text-slate-500">Cliquer Timeline pour charger les etapes.</div>
                            </div>
                        </x-module4.panel-card>
                    </div>
                </div>
            </section>
        @endif
    </div>
</div>

@include('modules.partials.logistics-modals-tailwind')
@endsection

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

.module4-premium {
    font-family: 'Inter', sans-serif;
    width: 100%;
    max-width: 1280px;
    margin: 0 auto;
    padding: 1.5rem 1rem;
    color: #0f172a;
}

.module4-premium a {
    text-decoration: none;
}

.m4-kpi-grid,
.m4-preview-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
    width: 100%;
}

.m4-preview-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    margin-top: 1.5rem;
}

.m4-stat-tile {
    min-width: 0;
    min-height: 150px;
    border: 1px solid #e2e8f0;
    border-radius: 0.875rem;
    background: #fff;
    padding: 1.35rem;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
}

.m4-stat-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
}

.m4-stat-copy {
    min-width: 0;
}

.m4-stat-title {
    margin: 0;
    color: #64748b;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    line-height: 1.25;
    text-transform: uppercase;
}

.m4-stat-value {
    margin: 0.75rem 0 0;
    color: #0f172a;
    font-size: 2rem;
    font-weight: 800;
    line-height: 1;
}

.m4-stat-subtitle {
    margin: 0.55rem 0 0;
    color: #64748b;
    font-size: 0.82rem;
    line-height: 1.35;
}

.m4-stat-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.6rem;
    height: 2.6rem;
    flex: 0 0 2.6rem;
    border-radius: 1rem;
}

.m4-stat-icon svg {
    width: 1.25rem;
    height: 1.25rem;
}

.m4-tabs-shell {
    width: 100%;
    margin-top: 1.5rem;
    border: 1px solid #e2e8f0;
    border-radius: 1.25rem;
    background: #f8fafc;
    padding: 0.5rem;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
}

.m4-tabs-grid {
    position: relative;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.25rem;
}

.m4-tab-indicator {
    position: absolute;
    top: 0.25rem;
    bottom: 0.25rem;
    left: 0.25rem;
    width: calc((100% - 0.5rem) / 3);
    border-radius: 1rem;
    background: #fff;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.10);
    pointer-events: none;
    transition: transform .25s ease;
}

.m4-tab-link {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 44px;
    border-radius: 1rem;
    color: #64748b;
    font-size: 0.88rem;
    font-weight: 700;
    text-align: center;
    transition: color .2s ease;
}

.m4-tab-link:hover,
.m4-tab-link.is-active {
    color: #047857;
}

.m4-card {
    min-width: 0;
    border: 1px solid #e2e8f0;
    border-radius: 0.875rem;
    background: #fff;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
}

.m4-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    border-bottom: 1px solid #eef2f7;
    padding: 1rem 1.25rem;
}

.m4-card-title {
    margin: 0;
    color: #0f172a;
    font-size: 0.9rem;
    font-weight: 800;
    letter-spacing: 0.02em;
}

.m4-card-subtitle {
    margin: 0.25rem 0 0;
    color: #64748b;
    font-size: 0.76rem;
    line-height: 1.35;
}

.m4-card-body {
    padding: 1.25rem;
}

@media (max-width: 1199px) {
    .m4-kpi-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 767px) {
    .module4-premium {
        padding: 1rem 0;
    }

    .m4-kpi-grid,
    .m4-preview-grid {
        grid-template-columns: 1fr;
    }

    .m4-tabs-grid {
        grid-template-columns: 1fr;
    }

    .m4-tab-indicator {
        display: none;
    }

    .m4-tab-link.is-active {
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.10);
    }
}

/* Pure CSS utility fallback for Module 4 (without Tailwind runtime) */
.module4-premium *,
[data-modal] * {
    box-sizing: border-box;
}

.mx-auto { margin-left: auto; margin-right: auto; }
.mt-0\.5 { margin-top: 0.125rem; }
.mt-1 { margin-top: 0.25rem; }
.mt-2 { margin-top: 0.5rem; }
.mt-4 { margin-top: 1rem; }
.mt-6 { margin-top: 1.5rem; }
.mt-10 { margin-top: 2.5rem; }
.mb-1 { margin-bottom: 0.25rem; }
.mb-2 { margin-bottom: 0.5rem; }
.mb-3 { margin-bottom: 0.75rem; }
.mb-4 { margin-bottom: 1rem; }
.mb-6 { margin-bottom: 1.5rem; }
.ml-1 { margin-left: 0.25rem; }
.mr-2 { margin-right: 0.5rem; }

.p-2 { padding: 0.5rem; }
.p-4 { padding: 1rem; }
.p-5 { padding: 1.25rem; }
.p-6 { padding: 1.5rem; }
.px-2 { padding-left: 0.5rem; padding-right: 0.5rem; }
.px-3 { padding-left: 0.75rem; padding-right: 0.75rem; }
.px-4 { padding-left: 1rem; padding-right: 1rem; }
.px-5 { padding-left: 1.25rem; padding-right: 1.25rem; }
.px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
.py-0\.5 { padding-top: 0.125rem; padding-bottom: 0.125rem; }
.py-1 { padding-top: 0.25rem; padding-bottom: 0.25rem; }
.py-1\.5 { padding-top: 0.375rem; padding-bottom: 0.375rem; }
.py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
.py-3 { padding-top: 0.75rem; padding-bottom: 0.75rem; }
.py-4 { padding-top: 1rem; padding-bottom: 1rem; }
.py-5 { padding-top: 1.25rem; padding-bottom: 1.25rem; }
.py-6 { padding-top: 1.5rem; padding-bottom: 1.5rem; }
.py-8 { padding-top: 2rem; padding-bottom: 2rem; }
.pt-7 { padding-top: 1.75rem; }

.grid { display: grid; }
.flex { display: flex; }
.inline-flex { display: inline-flex; }
.block { display: block; }
.hidden { display: none; }

.grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
.grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }

.gap-1 { gap: 0.25rem; }
.gap-2 { gap: 0.5rem; }
.gap-3 { gap: 0.75rem; }
.gap-4 { gap: 1rem; }
.gap-5 { gap: 1.25rem; }

.space-y-1 > :not([hidden]) ~ :not([hidden]) { margin-top: 0.25rem; }
.space-y-2 > :not([hidden]) ~ :not([hidden]) { margin-top: 0.5rem; }
.space-y-3 > :not([hidden]) ~ :not([hidden]) { margin-top: 0.75rem; }
.space-y-5 > :not([hidden]) ~ :not([hidden]) { margin-top: 1.25rem; }

.items-center { align-items: center; }
.items-start { align-items: flex-start; }
.justify-between { justify-content: space-between; }
.justify-center { justify-content: center; }
.justify-end { justify-content: flex-end; }
.justify-start { justify-content: flex-start; }
.flex-wrap { flex-wrap: wrap; }
.flex-col { flex-direction: column; }
.flex-1 { flex: 1 1 0%; }
.shrink-0 { flex-shrink: 0; }
.min-w-0 { min-width: 0; }
.min-w-full { min-width: 100%; }

.relative { position: relative; }
.absolute { position: absolute; }
.fixed { position: fixed; }
.inset-0 { inset: 0; }
.inset-y-1 { top: 0.25rem; bottom: 0.25rem; }
.left-1 { left: 0.25rem; }
.z-10 { z-index: 10; }
.z-50 { z-index: 50; }
.pointer-events-none { pointer-events: none; }

.w-full { width: 100%; }
.w-1\.5 { width: 0.375rem; }
.w-4 { width: 1rem; }
.w-5 { width: 1.25rem; }
.w-10 { width: 2.5rem; }
.h-4 { height: 1rem; }
.h-5 { height: 1.25rem; }
.h-10 { height: 2.5rem; }
.max-w-3xl { max-width: 48rem; }
.max-w-7xl { max-width: 80rem; }
.max-h-\[75vh\] { max-height: 75vh; }

.overflow-x-auto { overflow-x: auto; }
.overflow-y-auto { overflow-y: auto; }

.rounded { border-radius: 0.25rem; }
.rounded-lg { border-radius: 0.5rem; }
.rounded-xl { border-radius: 0.75rem; }
.rounded-2xl { border-radius: 1rem; }
.rounded-3xl { border-radius: 1.5rem; }
.rounded-full { border-radius: 9999px; }

.border { border: 1px solid #e2e8f0; }
.border-0 { border: 0; }
.border-b { border-bottom: 1px solid #e2e8f0; }
.border-t { border-top: 1px solid #e2e8f0; }
.border-dashed { border-style: dashed; }
.border-slate-100 { border-color: #f1f5f9; }
.border-slate-200 { border-color: #e2e8f0; }
.border-slate-300 { border-color: #cbd5e1; }
.border-emerald-100 { border-color: #d1fae5; }
.border-emerald-200 { border-color: #a7f3d0; }
.border-rose-200 { border-color: #fecdd3; }

.divide-y > :not([hidden]) ~ :not([hidden]) { border-top: 1px solid #e2e8f0; }
.divide-slate-100 > :not([hidden]) ~ :not([hidden]) { border-color: #f1f5f9; }

.bg-white { background-color: #fff; }
.bg-transparent { background-color: transparent; }
.bg-slate-50 { background-color: #f8fafc; }
.bg-amber-50 { background-color: #fffbeb; }
.bg-rose-50 { background-color: #fff1f2; }
.bg-emerald-50 { background-color: #ecfdf5; }
.bg-emerald-100 { background-color: #d1fae5; }
.bg-emerald-600 { background-color: #059669; }
.bg-emerald-500 { background-color: #10b981; }
.bg-rose-500 { background-color: #f43f5e; }
.bg-amber-500 { background-color: #f59e0b; }
.bg-indigo-50 { background-color: #eef2ff; }
.bg-indigo-500\/10 { background-color: rgba(99, 102, 241, 0.1); }
.bg-emerald-500\/10 { background-color: rgba(16, 185, 129, 0.1); }
.bg-rose-500\/10 { background-color: rgba(244, 63, 94, 0.1); }
.bg-cyan-500\/10 { background-color: rgba(6, 182, 212, 0.1); }
.bg-slate-500\/10 { background-color: rgba(100, 116, 139, 0.1); }
.bg-slate-900\/40 { background-color: rgba(15, 23, 42, 0.4); }

.bg-gradient-to-br { background-image: linear-gradient(to bottom right, var(--tw-gradient-from), var(--tw-gradient-to)); }
.from-indigo-500\/15 { --tw-gradient-from: rgba(99, 102, 241, 0.15); }
.to-indigo-500\/5 { --tw-gradient-to: rgba(99, 102, 241, 0.05); }
.from-emerald-500\/15 { --tw-gradient-from: rgba(16, 185, 129, 0.15); }
.to-emerald-500\/5 { --tw-gradient-to: rgba(16, 185, 129, 0.05); }
.from-rose-500\/15 { --tw-gradient-from: rgba(244, 63, 94, 0.15); }
.to-rose-500\/5 { --tw-gradient-to: rgba(244, 63, 94, 0.05); }
.from-cyan-500\/15 { --tw-gradient-from: rgba(6, 182, 212, 0.15); }
.to-cyan-500\/5 { --tw-gradient-to: rgba(6, 182, 212, 0.05); }
.from-slate-500\/15 { --tw-gradient-from: rgba(100, 116, 139, 0.15); }
.to-slate-500\/5 { --tw-gradient-to: rgba(100, 116, 139, 0.05); }

.shadow-sm { box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08); }
.shadow-xl { box-shadow: 0 20px 45px -18px rgba(15, 23, 42, 0.3); }

.text-left { text-align: left; }
.text-center { text-align: center; }
.text-right { text-align: right; }

.text-xs { font-size: 0.75rem; line-height: 1.15rem; }
.text-sm { font-size: 0.875rem; line-height: 1.25rem; }
.text-base { font-size: 1rem; line-height: 1.4rem; }
.text-3xl { font-size: 1.85rem; line-height: 2.2rem; }

.font-medium { font-weight: 500; }
.font-semibold { font-weight: 600; }
.font-bold { font-weight: 700; }

.uppercase { text-transform: uppercase; }
.tracking-tight { letter-spacing: -0.01em; }
.tracking-wide { letter-spacing: 0.03em; }

.text-white { color: #fff; }
.text-slate-400 { color: #94a3b8; }
.text-slate-500 { color: #64748b; }
.text-slate-600 { color: #475569; }
.text-slate-700 { color: #334155; }
.text-slate-800 { color: #1e293b; }
.text-slate-900 { color: #0f172a; }
.text-emerald-600 { color: #059669; }
.text-emerald-700 { color: #047857; }
.text-emerald-800 { color: #065f46; }
.text-rose-700 { color: #be123c; }
.text-indigo-700 { color: #4338ca; }
.text-cyan-700 { color: #0e7490; }
.text-amber-700 { color: #b45309; }

.transition-all { transition: all .2s ease; }
.duration-300 { transition-duration: .3s; }

.hover\:bg-slate-50:hover { background-color: #f8fafc; }
.hover\:bg-emerald-50:hover { background-color: #ecfdf5; }
.hover\:bg-emerald-700:hover { background-color: #047857; }
.hover\:border-slate-300:hover { border-color: #cbd5e1; }
.hover\:text-slate-700:hover { color: #334155; }
.hover\:-translate-y-0\.5:hover { transform: translateY(-2px); }

.focus\:outline-none:focus { outline: none; }
.focus\:border-emerald-400:focus { border-color: #34d399; }
.focus\:ring-2:focus { box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2); }

.ring-1 { box-shadow: 0 0 0 1px rgba(148, 163, 184, 0.45) inset; }
.ring-inset { box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.45); }
.ring-indigo-200 { box-shadow: inset 0 0 0 1px #c7d2fe; }
.ring-emerald-200 { box-shadow: inset 0 0 0 1px #a7f3d0; }
.ring-amber-200 { box-shadow: inset 0 0 0 1px #fde68a; }
.ring-rose-200 { box-shadow: inset 0 0 0 1px #fecdd3; }
.ring-cyan-200 { box-shadow: inset 0 0 0 1px #a5f3fc; }
.ring-slate-200 { box-shadow: inset 0 0 0 1px #e2e8f0; }

.backdrop-blur-sm { backdrop-filter: blur(6px); }

.module4-premium .m4-tab-indicator {
    width: calc((100% - 0.5rem) / 3);
}

@media (min-width: 640px) {
    .sm\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .sm\:flex-row { flex-direction: row; }
    .sm\:items-center { align-items: center; }
    .sm\:justify-between { justify-content: space-between; }
}

@media (min-width: 768px) {
    .md\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .md\:col-span-2 { grid-column: span 2 / span 2; }
}

@media (min-width: 1024px) {
    .lg\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
}

@media (min-width: 1280px) {
    .xl\:grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
    .xl\:grid-cols-12 { grid-template-columns: repeat(12, minmax(0, 1fr)); }
    .xl\:col-span-4 { grid-column: span 4 / span 4; }
    .xl\:col-span-5 { grid-column: span 5 / span 5; }
    .xl\:col-span-7 { grid-column: span 7 / span 7; }
    .xl\:col-span-8 { grid-column: span 8 / span 8; }
}

/* Existing semantic Module 4 classes */
.m4-panel {
    border-radius: 1.5rem;
    border: 1px solid #e2e8f0;
    background: #fff;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
}

.m4-btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 1rem;
    background: #059669;
    color: #fff;
    border: 1px solid #059669;
    padding: 0.55rem 0.95rem;
    font-size: 0.86rem;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: all .2s ease;
}

.m4-btn-primary:hover { background: #047857; border-color: #047857; color: #fff; }

.m4-btn-ghost {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 1rem;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #334155;
    padding: 0.55rem 0.95rem;
    font-size: 0.86rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all .2s ease;
}

.m4-btn-ghost:hover { background: #f8fafc; }

.m4-input {
    width: 100%;
    border-radius: 1rem;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #0f172a;
    padding: 0.58rem 0.8rem;
    font-size: 0.86rem;
    transition: all .2s ease;
}

.m4-input:focus {
    outline: none;
    border-color: #34d399;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
}

.m4-kpi-hover {
    transition: transform .2s ease, box-shadow .2s ease;
}

.m4-kpi-hover:hover {
    transform: translateY(-2px);
    box-shadow: 0 20px 38px -20px rgba(15, 23, 42, 0.35);
}

.m4-tab-enter {
    opacity: 0;
    transform: translateY(4px);
}

.m4-tab-ready {
    opacity: 1;
    transform: translateY(0);
    transition: all .25s ease;
}

.m4-tab-leave {
    opacity: 0;
    transform: translateY(8px);
    transition: all .2s ease;
}

.overflow-hidden { overflow: hidden; }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const traceForm = document.getElementById('instantTraceForm');
    const traceFeedback = document.getElementById('instantTraceFeedback');
    const scanOutForm = document.getElementById('scanOutForm');
    const scanOutFeedback = document.getElementById('scanOutFeedback');
    const scanOutShell = document.getElementById('scanOutShell');
    const wrap = document.getElementById('labEventsWrap');

    const modalOpeners = document.querySelectorAll('[data-modal-open]');
    const modalClosers = document.querySelectorAll('[data-modal-close]');
    const tabLinks = document.querySelectorAll('[data-tab-link]');
    const tabContent = document.getElementById('module4TabContent');

    if (tabContent) {
        requestAnimationFrame(() => {
            tabContent.classList.remove('m4-tab-enter');
            tabContent.classList.add('m4-tab-ready');
        });
    }

    tabLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            const href = link.getAttribute('href');
            if (!href || !tabContent) {
                return;
            }

            event.preventDefault();
            tabContent.classList.remove('m4-tab-ready');
            tabContent.classList.add('m4-tab-leave');
            setTimeout(() => {
                window.location.href = href;
            }, 180);
        });
    });

    function openModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    modalOpeners.forEach((button) => {
        button.addEventListener('click', () => {
            openModal(button.getAttribute('data-modal-open'));
        });
    });

    modalClosers.forEach((button) => {
        button.addEventListener('click', () => {
            closeModal(button.closest('[data-modal]'));
        });
    });

    document.querySelectorAll('[data-modal]').forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });

    async function parseJsonSafe(response) {
        return response.json().catch(() => ({}));
    }

    traceForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        traceFeedback.textContent = 'Traitement...';
        const payload = Object.fromEntries(new FormData(traceForm).entries());

        const response = await fetch('{{ route('care.module4.trace.ajax') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify(payload),
        });

        const data = await parseJsonSafe(response);
        if (!response.ok) {
            traceFeedback.className = 'text-xs text-rose-600';
            traceFeedback.textContent = data.message || 'Erreur de tracabilite.';
            return;
        }

        traceFeedback.className = 'text-xs text-emerald-600';
        traceFeedback.textContent = data.message || 'Trace enregistree.';
        traceForm.reset();
        setTimeout(() => window.location.reload(), 800);
    });

    scanOutForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        scanOutFeedback.textContent = 'Traitement...';
        const payload = Object.fromEntries(new FormData(scanOutForm).entries());

        const response = await fetch('{{ route('care.module4.stock-movement.scan-out') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify(payload),
        });

        const data = await parseJsonSafe(response);
        if (!response.ok) {
            scanOutFeedback.className = 'text-xs text-rose-600';
            scanOutFeedback.textContent = data.message || 'Erreur scan-to-out.';
            if (scanOutShell) {
                scanOutShell.classList.remove('border-emerald-300', 'bg-emerald-50');
                scanOutShell.classList.add('border-rose-300', 'bg-rose-50');
                setTimeout(() => {
                    scanOutShell.classList.remove('border-rose-300', 'bg-rose-50');
                    scanOutShell.classList.add('border-slate-200', 'bg-white');
                }, 650);
            }
            return;
        }

        scanOutFeedback.className = 'text-xs text-emerald-600';
        scanOutFeedback.textContent = `${data.item?.name || 'Article'} -> ${data.item?.current_quantity ?? '-'} restant.`;
        scanOutForm.reset();

        if (scanOutShell) {
            scanOutShell.classList.remove('border-slate-200', 'bg-white', 'border-rose-300', 'bg-rose-50');
            scanOutShell.classList.add('border-emerald-300', 'bg-emerald-50');
            setTimeout(() => {
                scanOutShell.classList.remove('border-emerald-300', 'bg-emerald-50');
                scanOutShell.classList.add('border-slate-200', 'bg-white');
            }, 700);
        }

        setTimeout(() => window.location.reload(), 900);
    });

    async function loadEvents(orderId) {
        const response = await fetch(`/care/module-4/lab-order/${orderId}/feed`, { headers: { Accept: 'application/json' } });
        if (!response.ok || !wrap) return;
        const data = await parseJsonSafe(response);
        const rows = (data.events || []).map((evt) => {
            const when = evt.event_at || '';
            return `<div class="mb-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"><span class="mr-2 inline-flex rounded-lg bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">${evt.event_type}</span><strong class="text-slate-700">${evt.status || '-'}</strong> - ${evt.message || ''} <span class="text-xs text-slate-400">(${when})</span></div>`;
        });
        wrap.innerHTML = `<div class="mb-2 text-sm font-semibold text-slate-700">${data.order.order_number} - ${data.order.status}</div>${rows.join('') || '<span class="text-sm text-slate-400">Aucun event.</span>'}`;
    }

    document.querySelectorAll('.btn-load-events').forEach((btn) => {
        btn.addEventListener('click', () => {
            const orderId = btn.getAttribute('data-order-id');
            if (orderId) loadEvents(orderId);
        });
    });
})();
</script>
@endpush
