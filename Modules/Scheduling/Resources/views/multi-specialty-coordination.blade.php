@extends('layouts.app')

@section('title', 'Coordination Multi-Spécialités')
@section('page-title', 'Coordination Multi-Spécialités')

@section('content')
<div class="page-stack">
    <section class="card toolbar">
        <div>
            <h2>🔄 Coordination Multi-Spécialités</h2>
            <p class="muted">Trouver le jour optimal pour un patient nécessitant plusieurs spécialistes</p>
        </div>
        <div class="split-actions">
            <a class="btn btn-soft" href="{{ route('scheduling.dashboard') }}">← Planning</a>
        </div>
    </section>

    <div class="coordination-container">
        {{-- Formulaire de recherche --}}
        <section class="card">
            <h3 class="card-title">🔍 Configurer la recherche</h3>
            <form id="coordinationForm">
                <div class="form-row">
                    <div>
                        <label class="label">Patient</label>
                        <select class="select" name="patient_id" id="patientId" required>
                            <option value="">-- Sélectionner un patient --</option>
                        </select>
                    </div>
                </div>

                <div class="specialties-section">
                    <div class="section-header">
                        <h4>Spécialités requises</h4>
                        <button type="button" class="btn btn-sm btn-primary" onclick="addSpecialtyRow()">
                            ➕ Ajouter
                        </button>
                    </div>
                    <div id="specialtiesContainer">
                        <div class="specialty-row">
                            <div class="form-group">
                                <label class="label">Spécialité</label>
                                <select class="select specialty-select" name="specialties[0][specialty_id]" required>
                                    <option value="">-- Choisir --</option>
                                    @foreach($specialties as $spec)
                                        <option value="{{ $spec->id }}">{{ $spec->name }} ({{ $spec->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="label">Priorité</label>
                                <input type="number" class="input priority-input" name="specialties[0][priority]" value="1" min="1" max="10">
                            </div>
                            <div class="form-group form-group-remove">
                                <button type="button" class="btn btn-sm btn-danger remove-row" onclick="removeSpecialtyRow(this)" disabled>
                                    🗑️
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-row mt-2">
                    <div>
                        <label class="label">Date début</label>
                        <input type="date" class="input" name="from_date" id="fromDate" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div>
                        <label class="label">Date fin</label>
                        <input type="date" class="input" name="to_date" id="toDate" value="{{ now()->addWeeks(2)->format('Y-m-d') }}" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="align-self: flex-end;">
                        🔍 Trouver le jour optimal
                    </button>
                </div>
            </form>
        </section>

        {{-- Résultats --}}
        <section class="card" id="resultsCard" style="display: none;">
            <h3 class="card-title">📊 Résultats</h3>
            <div id="resultsContent"></div>
        </section>

        {{-- Praticiens par spécialité --}}
        <section class="card">
            <h3 class="card-title">👨‍⚕️ Praticiens disponibles</h3>
            <div class="practitioners-grid">
                @foreach($practitioners->groupBy(fn($p) => $p->specialties->pluck('name')->join(', ')) as $specName => $pracList)
                <div class="practitioner-group">
                    <h4 class="group-title">{{ $specName ?: 'Sans spécialité' }}</h4>
                    @foreach($pracList as $practitioner)
                    <div class="practitioner-item">
                        <div class="prac-avatar">{{ substr($practitioner->name, 0, 1) }}</div>
                        <div class="prac-info">
                            <div class="prac-name">{{ $practitioner->name }}</div>
                            <div class="prac-meta">
                                @if($practitioner->primaryRoom)
                                    <span>🏥 {{ $practitioner->primaryRoom->name }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endforeach
            </div>
        </section>
    </div>
</div>

@push('head')
<style>
    .specialties-section {
        margin-top: var(--spacing-lg);
        padding: var(--spacing-md);
        background: var(--color-gray-50);
        border-radius: var(--radius-md);
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: var(--spacing-md);
    }

    .section-header h4 {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 600;
    }

    .specialty-row {
        display: flex;
        gap: var(--spacing-md);
        align-items: flex-end;
        margin-bottom: var(--spacing-sm);
        padding: var(--spacing-sm);
        background: var(--color-white);
        border-radius: var(--radius-md);
    }

    .specialty-row .form-group {
        flex: 1;
    }

    .specialty-row .form-group-remove {
        flex: 0 0 50px;
    }

    .priority-input {
        width: 70px;
    }

    .practitioners-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: var(--spacing-lg);
    }

    .practitioner-group {
        background: var(--color-white);
        border: 1px solid var(--color-gray-200);
        border-radius: var(--radius-md);
        overflow: hidden;
    }

    .group-title {
        padding: var(--spacing-sm) var(--spacing-md);
        background: var(--color-primary-light);
        color: #1e40af;
        font-size: 0.85rem;
        font-weight: 600;
        margin: 0;
    }

    .practitioner-item {
        display: flex;
        align-items: center;
        gap: var(--spacing-sm);
        padding: var(--spacing-sm) var(--spacing-md);
        border-bottom: 1px solid var(--color-gray-100);
    }

    .practitioner-item:last-child {
        border-bottom: none;
    }

    .prac-avatar {
        width: 40px;
        height: 40px;
        border-radius: var(--radius-full);
        background: linear-gradient(135deg, var(--color-primary), #8b5cf6);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
    }

    .prac-info { flex: 1; }
    .prac-name { font-weight: 500; font-size: 0.9rem; }
    .prac-meta { font-size: 0.75rem; color: var(--color-gray-500); }

    .result-card {
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        border: 2px solid #10b981;
        border-radius: var(--radius-lg);
        padding: var(--spacing-xl);
        text-align: center;
    }

    .result-date {
        font-size: 1.5rem;
        font-weight: 700;
        color: #065f46;
        margin-bottom: var(--spacing-sm);
    }

    .result-slots {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-sm);
        margin-top: var(--spacing-md);
    }

    .result-slot {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--spacing-sm) var(--spacing-md);
        background: white;
        border-radius: var(--radius-md);
    }

    @media (max-width: 768px) {
        .specialty-row { flex-direction: column; }
        .practitioners-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@push('scripts')
<script>
let specialtyIndex = 1;

function addSpecialtyRow() {
    const container = document.getElementById('specialtiesContainer');
    const newRow = document.createElement('div');
    newRow.className = 'specialty-row';
    newRow.innerHTML = `
        <div class="form-group">
            <label class="label">Spécialité</label>
            <select class="select specialty-select" name="specialties[${specialtyIndex}][specialty_id]" required>
                <option value="">-- Choisir --</option>
                @foreach($specialties as $spec)
                    <option value="{{ $spec->id }}">{{ $spec->name }} ({{ $spec->code }})</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="label">Priorité</label>
            <input type="number" class="input priority-input" name="specialties[${specialtyIndex}][priority]" value="${specialtyIndex + 1}" min="1" max="10">
        </div>
        <div class="form-group form-group-remove">
            <button type="button" class="btn btn-sm btn-danger remove-row" onclick="removeSpecialtyRow(this)">🗑️</button>
        </div>
    `;
    container.appendChild(newRow);
    specialtyIndex++;
}

function removeSpecialtyRow(btn) {
    btn.closest('.specialty-row').remove();
    // Disable remove button if only one row remains
    const rows = document.querySelectorAll('.specialty-row');
    rows.forEach(row => {
        const removeBtn = row.querySelector('.remove-row');
        if (removeBtn) removeBtn.disabled = rows.length <= 1;
    });
}

document.getElementById('coordinationForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const specialties = [];
    
    document.querySelectorAll('.specialty-row').forEach(row => {
        const select = row.querySelector('.specialty-select');
        const priority = row.querySelector('.priority-input');
        if (select && select.value) {
            specialties.push({
                specialty_id: parseInt(select.value),
                priority: parseInt(priority?.value || 99)
            });
        }
    });

    const data = {
        patient_id: parseInt(document.getElementById('patientId').value),
        specialties: specialties,
        from_date: document.getElementById('fromDate').value,
        to_date: document.getElementById('toDate').value
    };

    fetch('{{ route('scheduling.multi-specialty.find-optimal') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(response => {
        const resultsCard = document.getElementById('resultsCard');
        const resultsContent = document.getElementById('resultsContent');
        resultsCard.style.display = 'block';
        
        if (response.success && response.optimal_day) {
            const day = response.optimal_day;
            resultsContent.innerHTML = `
                <div class="result-card">
                    <div class="result-date">📅 ${day.day_name} ${day.date}</div>
                    <div>Temps d'attente total: ${day.total_wait_minutes} minutes</div>
                    <div>${day.practitioner_count} praticien(s) disponible(s)</div>
                    <div class="result-slots">
                        ${day.slots.map(slot => `
                            <div class="result-slot">
                                <div>
                                    <strong>${slot.practitioner_name}</strong><br>
                                    <small>${slot.start_time} - ${slot.end_time}</small>
                                </div>
                                <span class="badge badge-primary">Salle ${slot.room_id || '-'}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        } else {
            resultsContent.innerHTML = '<div class="empty-state"><p>❌ Aucun jour optimal trouvé pour cette période.</p></div>';
        }
    });
});
</script>
@endpush
@endsection
