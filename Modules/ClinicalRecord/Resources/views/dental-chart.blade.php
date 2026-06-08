@extends('layouts.app')

@section('title', 'Odontogramme - ' . $patient->full_name)
@section('page-title', 'Odontogramme : ' . $patient->full_name)

@section('content')
<div class="page-stack">
    <section class="card toolbar">
        <div>
            <h2>🦷 Odontogramme</h2>
            <p class="muted">
                {{ $patient->full_name }} ({{ $patient->medical_record_number }})
            </p>
        </div>
        <div class="split-actions">
            <a class="btn btn-soft" href="{{ route('clinical.patient.show', ['patientId' => $patient->id]) }}">← Dossier</a>
        </div>
    </section>

    <div class="dental-chart-container">
        {{-- Dent Status Legend --}}
        <div class="chart-legend">
            <div class="legend-item"><span class="legend-color legend-present"></span> Présent</div>
            <div class="legend-item"><span class="legend-color legend-crown"></span> Couronne</div>
            <div class="legend-item"><span class="legend-color legend-filling"></span> Plombage</div>
            <div class="legend-item"><span class="legend-color legend-extracted"></span> Extrait</div>
            <div class="legend-item"><span class="legend-color legend-implant"></span> Implant</div>
            <div class="legend-item"><span class="legend-color legend-decay"></span> Carie</div>
        </div>

        {{-- Upper Jaw --}}
        <div class="jaw-section">
            <h3 class="jaw-title">Mâchoire supérieure</h3>
            <div class="teeth-row">
                @foreach([18,17,16,15,14,13,12,11,21,22,23,24,25,26,27,28] as $tooth)
                    @php
                        $toothData = $chart->getLatestToothStatus($tooth);
                        $status = $toothData['status'] ?? 'present';
                    @endphp
                    <div class="tooth-item tooth-{{ $status }}" data-tooth="{{ $tooth }}" onclick="openToothModal({{ $tooth }}, '{{ $status }}')">
                        <div class="tooth-number">{{ $tooth }}</div>
                        <div class="tooth-status">{{ $status }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Lower Jaw --}}
        <div class="jaw-section">
            <h3 class="jaw-title">Mâchoire inférieure</h3>
            <div class="teeth-row">
                @foreach([48,47,46,45,44,43,42,41,31,32,33,34,35,36,37,38] as $tooth)
                    @php
                        $toothData = $chart->getLatestToothStatus($tooth);
                        $status = $toothData['status'] ?? 'present';
                    @endphp
                    <div class="tooth-item tooth-{{ $status }}" data-tooth="{{ $tooth }}" onclick="openToothModal({{ $tooth }}, '{{ $status }}')">
                        <div class="tooth-number">{{ $tooth }}</div>
                        <div class="tooth-status">{{ $status }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- Tooth Edit Modal --}}
<div class="modal-overlay" id="toothModal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Dent #<span id="modalToothNumber"></span></h3>
            <button class="modal-close" onclick="closeToothModal()">×</button>
        </div>
        <form id="toothForm" method="POST" action="">
            @csrf
            <div class="form-group">
                <label class="label">Statut</label>
                <select class="select" name="status" id="toothStatus" required>
                    @foreach(\Modules\ClinicalRecord\Models\DentalChart::STATUSES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="label">Type d'acte</label>
                <input type="text" class="input" name="procedure_type" placeholder="ex: extraction, couronne...">
            </div>
            <div class="form-group">
                <label class="label">Notes</label>
                <textarea class="textarea" name="details[notes]" rows="3"></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-soft" onclick="closeToothModal()">Annuler</button>
                <button type="submit" class="btn btn-primary">💾 Sauvegarder</button>
            </div>
        </form>
    </div>
</div>

@push('head')
<style>
    .dental-chart-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .chart-legend {
        display: flex;
        flex-wrap: wrap;
        gap: var(--spacing-md);
        padding: var(--spacing-md);
        background: var(--color-white);
        border-radius: var(--radius-md);
        margin-bottom: var(--spacing-xl);
        border: 1px solid var(--color-gray-200);
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: var(--spacing-xs);
        font-size: 0.85rem;
    }

    .legend-color {
        width: 20px;
        height: 20px;
        border-radius: var(--radius-sm);
    }

    .legend-present { background: #10b981; }
    .legend-crown { background: #8b5cf6; }
    .legend-filling { background: #3b82f6; }
    .legend-extracted { background: #ef4444; }
    .legend-implant { background: #f59e0b; }
    .legend-decay { background: #dc2626; }

    .jaw-section {
        margin-bottom: var(--spacing-2xl);
    }

    .jaw-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: var(--spacing-md);
        color: var(--color-gray-700);
    }

    .teeth-row {
        display: flex;
        gap: var(--spacing-sm);
        flex-wrap: wrap;
        justify-content: center;
    }

    .tooth-item {
        width: 60px;
        height: 80px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-md);
        cursor: pointer;
        transition: all var(--transition-fast);
        border: 2px solid var(--color-gray-200);
        background: var(--color-white);
    }

    .tooth-item:hover {
        transform: scale(1.1);
        box-shadow: var(--shadow-md);
    }

    .tooth-number {
        font-weight: 700;
        font-size: 1rem;
        font-family: var(--font-mono);
    }

    .tooth-status {
        font-size: 0.65rem;
        text-transform: uppercase;
        margin-top: var(--spacing-xs);
    }

    .tooth-present { border-color: #10b981; background: #d1fae5; }
    .tooth-crown { border-color: #8b5cf6; background: #ede9fe; }
    .tooth-filling { border-color: #3b82f6; background: #dbeafe; }
    .tooth-extracted { border-color: #ef4444; background: #fee2e2; }
    .tooth-implant { border-color: #f59e0b; background: #fef3c7; }
    .tooth-decay { border-color: #dc2626; background: #fee2e2; }
    .tooth-absent { border-color: #6b7280; background: #f3f4f6; }
    .tooth-root_canal { border-color: #06b6d4; background: #cffafe; }
    .tooth-bridge_abutment { border-color: #ec4899; background: #fce7f3; }
    .tooth-fractured { border-color: #dc2626; background: #fee2e2; border-style: dashed; }

    /* Modal */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: var(--z-modal);
    }

    .modal-content {
        background: var(--color-white);
        border-radius: var(--radius-lg);
        padding: var(--spacing-xl);
        max-width: 500px;
        width: 90%;
        box-shadow: var(--shadow-xl);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: var(--spacing-lg);
    }

    .modal-header h3 { margin: 0; }

    .modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--color-gray-500);
    }

    .modal-actions {
        display: flex;
        gap: var(--spacing-sm);
        justify-content: flex-end;
        margin-top: var(--spacing-lg);
    }

    .form-group { margin-bottom: var(--spacing-md); }
</style>
@endpush

@push('scripts')
<script>
function openToothModal(toothNumber, currentStatus) {
    document.getElementById('modalToothNumber').textContent = toothNumber;
    document.getElementById('toothStatus').value = currentStatus;
    document.getElementById('toothForm').action = `/clinical/patients/{{ $patient->id }}/teeth/${toothNumber}/status`;
    document.getElementById('toothModal').style.display = 'flex';
}

function closeToothModal() {
    document.getElementById('toothModal').style.display = 'none';
}

document.getElementById('toothForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch(this.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
});
</script>
@endpush
@endsection
