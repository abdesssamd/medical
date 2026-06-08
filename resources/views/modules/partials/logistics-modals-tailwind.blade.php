<div id="batchModal" data-modal class="fixed inset-0 z-50 hidden bg-slate-900/40 p-4 backdrop-blur-sm">
    <div class="mx-auto mt-10 w-full max-w-3xl rounded-3xl border border-slate-200 bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h3 class="text-base font-semibold tracking-wide text-slate-900">Nouveau lot de sterilisation</h3>
            <button type="button" data-modal-close class="rounded-xl border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-600 transition-all duration-300 hover:bg-slate-50">Fermer</button>
        </div>
        <div class="max-h-[75vh] overflow-y-auto px-6 py-5">
            <form id="batchForm" method="POST" action="{{ route('care.module4.batch.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-2">
                @csrf
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Code lot</label><input class="m4-input" name="batch_code" placeholder="ST-2026-0001" required></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Cycle</label><input class="m4-input" name="sterilizer_cycle" placeholder="AUTO-121C"></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Sterilise le</label><input type="datetime-local" class="m4-input" name="sterilized_at"></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Validite (jours)</label><input type="number" class="m4-input" name="sterility_validity_days" min="1" max="90" value="7"></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Set instruments</label><input class="m4-input" name="instrument_set_name" placeholder="Implant set"></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Nombre sachets</label><input class="m4-input" type="number" min="1" max="300" name="pouch_count" value="10" required></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Statut initial</label><select class="m4-input" name="batch_status"><option value="in_progress">En cours</option><option value="validated">Valide</option></select></div>
                <div class="flex items-center gap-4 pt-7">
                    <label class="inline-flex items-center gap-2 text-xs text-slate-700"><input class="rounded border-slate-300" type="checkbox" name="bowie_dick_passed" value="1"> Bowie-Dick OK</label>
                    <label class="inline-flex items-center gap-2 text-xs text-slate-700"><input class="rounded border-slate-300" type="checkbox" name="helix_passed" value="1"> Helix OK</label>
                </div>
                <div class="md:col-span-2"><label class="mb-1 block text-xs font-medium text-slate-600">Notes</label><textarea class="m4-input" name="notes" rows="3"></textarea></div>
            </form>
        </div>
        <div class="flex justify-end gap-2 border-t border-slate-100 px-6 py-4">
            <button type="button" data-modal-close class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition-all duration-300 hover:bg-slate-50">Annuler</button>
            <button form="batchForm" class="m4-btn-primary">Creer lot</button>
        </div>
    </div>
</div>

<div id="traceModal" data-modal class="fixed inset-0 z-50 hidden bg-slate-900/40 p-4 backdrop-blur-sm">
    <div class="mx-auto mt-10 w-full max-w-3xl rounded-3xl border border-slate-200 bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h3 class="text-base font-semibold tracking-wide text-slate-900">Tracer un sachet vers dossier patient</h3>
            <button type="button" data-modal-close class="rounded-xl border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-600 transition-all duration-300 hover:bg-slate-50">Fermer</button>
        </div>
        <div class="max-h-[75vh] overflow-y-auto px-6 py-5">
            <form id="traceForm" method="POST" action="{{ route('care.module4.trace.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-2">
                @csrf
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Code sachet</label><input class="m4-input" name="pouch_code" placeholder="ST-2026-0001-001" required></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Patient</label><select class="m4-input" name="patient_id" required>@foreach($patients as $patient)<option value="{{ $patient->id }}">{{ $patient->last_name }} {{ $patient->first_name }} - {{ $patient->medical_record_number }}</option>@endforeach</select></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-600">RDV (optionnel)</label><select class="m4-input" name="appointment_id"><option value="">-</option>@foreach($appointments_today as $apt)<option value="{{ $apt->id }}">#{{ $apt->id }} - {{ \Illuminate\Support\Str::of($apt->start_time)->substr(0,5) }}</option>@endforeach</select></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Procedure ID</label><input class="m4-input" type="number" name="clinical_procedure_id"></div>
                <div class="md:col-span-2"><label class="mb-1 block text-xs font-medium text-slate-600">Notes</label><textarea class="m4-input" name="notes" rows="3"></textarea></div>
            </form>
        </div>
        <div class="flex justify-end gap-2 border-t border-slate-100 px-6 py-4">
            <button type="button" data-modal-close class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition-all duration-300 hover:bg-slate-50">Annuler</button>
            <button form="traceForm" class="m4-btn-primary">Tracer</button>
        </div>
    </div>
</div>

<div id="stockItemModal" data-modal class="fixed inset-0 z-50 hidden bg-slate-900/40 p-4 backdrop-blur-sm">
    <div class="mx-auto mt-10 w-full max-w-3xl rounded-3xl border border-slate-200 bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h3 class="text-base font-semibold tracking-wide text-slate-900">Nouvel article de stock</h3>
            <button type="button" data-modal-close class="rounded-xl border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-600 transition-all duration-300 hover:bg-slate-50">Fermer</button>
        </div>
        <div class="max-h-[75vh] overflow-y-auto px-6 py-5">
            <form id="stockItemForm" method="POST" action="{{ route('care.module4.stock-item.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-2">
                @csrf
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Code</label><input class="m4-input" name="code" required></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Nom</label><input class="m4-input" name="name" required></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Categorie</label><select class="m4-input" name="category" required><option value="consumable">Consommable</option><option value="high_value">Haute valeur</option></select></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Unite</label><input class="m4-input" name="unit" value="unit" required></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Qte actuelle</label><input class="m4-input" type="number" step="0.01" name="current_quantity" value="0" required></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Stock minimum</label><input class="m4-input" type="number" step="0.01" name="minimum_quantity" value="0" required></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Qte reappro</label><input class="m4-input" type="number" step="0.01" name="reorder_quantity"></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Cout unitaire</label><input class="m4-input" type="number" step="0.01" name="unit_cost"></div>
            </form>
        </div>
        <div class="flex justify-end gap-2 border-t border-slate-100 px-6 py-4">
            <button type="button" data-modal-close class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition-all duration-300 hover:bg-slate-50">Annuler</button>
            <button form="stockItemForm" class="m4-btn-primary">Creer article</button>
        </div>
    </div>
</div>

<div id="stockMoveModal" data-modal class="fixed inset-0 z-50 hidden bg-slate-900/40 p-4 backdrop-blur-sm">
    <div class="mx-auto mt-10 w-full max-w-3xl rounded-3xl border border-slate-200 bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h3 class="text-base font-semibold tracking-wide text-slate-900">Nouveau mouvement de stock</h3>
            <button type="button" data-modal-close class="rounded-xl border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-600 transition-all duration-300 hover:bg-slate-50">Fermer</button>
        </div>
        <div class="max-h-[75vh] overflow-y-auto px-6 py-5">
            <form id="stockMoveForm" method="POST" action="{{ route('care.module4.stock-movement.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-2">
                @csrf
                <div class="md:col-span-2"><label class="mb-1 block text-xs font-medium text-slate-600">Article</label><select class="m4-input" name="stock_item_id" required>@foreach($stock_items as $item)<option value="{{ $item->id }}">{{ $item->name }} ({{ $item->code }}) - {{ $item->current_quantity }}</option>@endforeach</select></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Type</label><select class="m4-input" name="type" required><option value="in">Entree</option><option value="out">Sortie</option><option value="adjustment">Ajustement</option><option value="reserve">Reservation</option><option value="release">Liberation</option></select></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Quantite</label><input class="m4-input" type="number" step="0.01" name="quantity" required></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Ref type</label><input class="m4-input" name="reference_type" placeholder="procedure, lab_order"></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Ref ID</label><input class="m4-input" type="number" name="reference_id"></div>
                <div class="md:col-span-2"><label class="mb-1 block text-xs font-medium text-slate-600">Notes</label><textarea class="m4-input" name="notes" rows="3"></textarea></div>
            </form>
        </div>
        <div class="flex justify-end gap-2 border-t border-slate-100 px-6 py-4">
            <button type="button" data-modal-close class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition-all duration-300 hover:bg-slate-50">Annuler</button>
            <button form="stockMoveForm" class="m4-btn-primary">Enregistrer</button>
        </div>
    </div>
</div>

<div id="labOrderModal" data-modal class="fixed inset-0 z-50 hidden bg-slate-900/40 p-4 backdrop-blur-sm">
    <div class="mx-auto mt-10 w-full max-w-3xl rounded-3xl border border-slate-200 bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <h3 class="text-base font-semibold tracking-wide text-slate-900">Nouvelle commande laboratoire</h3>
            <button type="button" data-modal-close class="rounded-xl border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-600 transition-all duration-300 hover:bg-slate-50">Fermer</button>
        </div>
        <div class="max-h-[75vh] overflow-y-auto px-6 py-5">
            <form id="labOrderForm" method="POST" action="{{ route('care.module4.lab-order.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-2">
                @csrf
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Patient</label><select class="m4-input" name="patient_id" required>@foreach($patients as $patient)<option value="{{ $patient->id }}">{{ $patient->last_name }} {{ $patient->first_name }}</option>@endforeach</select></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Praticien</label><select class="m4-input" name="practitioner_id"><option value="">-</option>@foreach($practitioners as $pro)<option value="{{ $pro->id }}">{{ $pro->name }}</option>@endforeach</select></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Laboratoire</label><input class="m4-input" name="lab_name" required></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Contact labo</label><input class="m4-input" name="lab_contact"></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Type</label><select class="m4-input" name="type" required><option value="prosthesis">Prothese</option><option value="crown">Couronne</option><option value="implant">Implant</option><option value="ortho">Ortho</option><option value="other">Autre</option></select></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Etape initiale</label><select class="m4-input" name="status"><option value="impression_taken">Empreinte prise</option><option value="sent_to_lab">Envoye au labo</option><option value="received_from_lab">Recu</option><option value="fitted_on_patient">Pose sur patient</option><option value="cancelled">Annule</option></select></div>
                <div class="md:col-span-2"><label class="mb-1 block text-xs font-medium text-slate-600">Date prevue</label><input type="date" class="m4-input" name="due_date"></div>
                <div class="md:col-span-2"><label class="mb-1 block text-xs font-medium text-slate-600">Fichiers externes (1 lien/ligne)</label><textarea class="m4-input" name="external_file_paths" rows="3"></textarea></div>
                <div class="md:col-span-2"><label class="mb-1 block text-xs font-medium text-slate-600">Notes</label><textarea class="m4-input" name="notes" rows="3"></textarea></div>
            </form>
        </div>
        <div class="flex justify-end gap-2 border-t border-slate-100 px-6 py-4">
            <button type="button" data-modal-close class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition-all duration-300 hover:bg-slate-50">Annuler</button>
            <button form="labOrderForm" class="m4-btn-primary">Creer commande</button>
        </div>
    </div>
</div>

