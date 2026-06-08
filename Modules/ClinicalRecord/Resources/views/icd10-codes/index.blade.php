@php
    $title = 'Codes CIM-10';
@endphp

@extends('layouts.admin')

@section('content')
<div class="container-xl">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">{{ $title }}</h3>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.icd10-codes.export') }}" class="btn btn-sm btn-outline-primary">Exporter Excel</a>
                <a href="{{ route('admin.icd10-codes.example') }}" class="btn btn-sm btn-outline-info">Exemple Excel</a>
                <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#importModal">Importer Excel</button>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#codeModal">Nouveau code</button>
            </div>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <input type="text" id="searchCodes" class="form-control" placeholder="Rechercher par code ou nom...">
            </div>
            <div id="codesTable">@include('clinicalrecord::icd10-codes._table')</div>
        </div>
    </div>
</div>

<div class="modal fade" id="codeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="codeForm">
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="id" id="codeId">
                <div class="modal-header">
                    <h5 class="modal-title" id="codeModalTitle">Nouveau code CIM-10</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Code *</label>
                        <input type="text" name="code" id="codeInput" class="form-control" required maxlength="20">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nom *</label>
                        <input type="text" name="name" id="nameInput" class="form-control" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catégorie</label>
                        <input type="text" name="category" id="categoryInput" class="form-control" maxlength="100">
                    </div>
                    <div class="mb-3 form-check" id="activeField" style="display:none">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" id="activeInput" checked>
                        <label class="form-check-label">Actif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="importForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Importer codes CIM-10 (Excel)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Fichier Excel (.xlsx ou .xls)</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls" required>
                    </div>
                    <p class="text-secondary small mb-0">Colonnes attendues : Code, Nom, Catégorie</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">Importer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchCodes');
    const codesTable = document.getElementById('codesTable');
    let searchTimer;

    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            fetch(`{{ route('admin.icd10-codes.index') }}?search=${encodeURIComponent(searchInput.value)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(d => { if (d.html) codesTable.innerHTML = d.html; });
        }, 300);
    });

    const codeForm = document.getElementById('codeForm');
    codeForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(codeForm);
        const id = document.getElementById('codeId').value;
        const method = id ? 'PUT' : 'POST';
        const url = id ? `{{ url('admin/icd10-codes') }}/${id}` : '{{ route('admin.icd10-codes.store') }}';

        fetch(url, { method, headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }, body: formData })
        .then(r => r.json())
        .then(d => { showToast(d.message); bootstrap.Modal.getInstance(document.getElementById('codeModal')).hide(); location.reload(); })
        .catch(e => showToast(e.message, 'error'));
    });

    document.querySelectorAll('.edit-code-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('codeModalTitle').textContent = 'Modifier code CIM-10';
            document.getElementById('codeId').value = btn.dataset.id;
            document.getElementById('codeInput').value = btn.dataset.code;
            document.getElementById('nameInput').value = btn.dataset.name;
            document.getElementById('categoryInput').value = btn.dataset.category;
            document.getElementById('activeInput').checked = btn.dataset.active === '1';
            document.getElementById('activeField').style.display = 'block';
            new bootstrap.Modal(document.getElementById('codeModal')).show();
        });
    });

    document.querySelectorAll('.delete-code-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!confirm('Confirmer la suppression ?')) return;
            fetch(`{{ url('admin/icd10-codes') }}/${btn.dataset.id}`, {
                method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(d => { showToast(d.message); location.reload(); })
            .catch(e => showToast(e.message, 'error'));
        });
    });

    document.getElementById('importForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        fetch('{{ route('admin.icd10-codes.import') }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: formData })
        .then(r => r.json())
        .then(d => { showToast(d.message); bootstrap.Modal.getInstance(document.getElementById('importModal')).hide(); location.reload(); })
        .catch(e => showToast(e.message, 'error'));
    });
});
</script>
@endpush
