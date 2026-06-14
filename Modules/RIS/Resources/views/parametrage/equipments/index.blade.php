@extends('layouts.admin')

@section('title', 'Équipements RIS')
@section('page-title', 'Gestion des équipements')

@section('content')
<style>
    .param-page { display: grid; gap: 18px; color: #0f172a; }
    .param-hero { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; padding: 18px; border: 1px solid #dbe8f3; border-radius: 18px; background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%); box-shadow: 0 18px 40px rgba(15,23,42,0.06); }
    .param-hero h2 { margin: 0 0 8px; font-size: 1.25rem; font-weight: 900; }
    .param-hero p { margin: 0; color: #64748b; }
    .param-grid { display: grid; grid-template-columns: minmax(0, 1.08fr) minmax(380px, 0.92fr); gap: 18px; align-items: start; }
    .param-card { background: #fff; border: 1px solid #dbe8f3; border-radius: 18px; box-shadow: 0 18px 40px rgba(15,23,42,0.06); }
    .param-card-inner { padding: 18px; }
    .param-toolbar { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:12px; flex-wrap:wrap; }
    .param-title { font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 8px; }
    .param-muted { color: #64748b; }
    .param-link { color:#2563eb; text-decoration:none; font-weight:700; }
    .param-list { display:flex; flex-direction:column; gap:12px; }
    .param-item { border:1px solid #e2e8f0; border-radius:16px; padding:16px; background: #fff; }
    .param-item-head { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; }
    .param-badge { display:inline-flex; padding:4px 10px; border-radius:999px; background:#e0f2fe; color:#075985; font-size:12px; font-weight:700; }
    .param-actions { display:flex; gap:8px; flex-wrap:wrap; }
    .param-btn { display:inline-flex; align-items:center; justify-content:center; min-height: 38px; border:1px solid #cbd5e1; background:#fff; color:#0f172a; text-decoration:none; padding:8px 12px; border-radius:10px; font-weight:700; cursor:pointer; }
    .param-btn:hover { border-color:#93c5fd; background:#eff6ff; color:#1d4ed8; }
    .param-btn-danger { border-color:#fca5a5; background:#fee2e2; color:#b91c1c; }
    .param-btn-primary { background:#2563eb; color:#fff; border-color:#2563eb; }
    .param-btn-primary:hover { background:#1d4ed8; color:#fff; border-color:#1d4ed8; }
    .param-form label { display:block; margin-bottom:6px; font-size:12px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:.04em; }
    .param-form input,.param-form select,.param-form textarea { width:100%; border:1px solid #cbd5e1; border-radius:12px; padding:10px 12px; font: inherit; background:#fff; }
    .param-grid-form { display:grid; gap:12px; }
    .param-empty { padding:18px; border:1px dashed #cbd5e1; border-radius:14px; color:#64748b; }
    .param-warning { padding: 10px 12px; border-radius: 10px; background: #fff7ed; color:#9a3412; border:1px solid #fdba74; font-weight:700; }
    .param-toggle { position:relative; display:inline-flex; align-items:center; gap:8px; cursor:pointer; }
    .param-toggle input { width:auto; }
    @media (max-width: 1100px) { .param-grid { grid-template-columns: 1fr; } .param-hero { flex-direction:column; } }
</style>

<div class="param-page">
    @if(session('success'))
        <div class="param-warning" style="background:#dcfce7; border-color:#86efac; color:#166534;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="param-warning" style="background:#fee2e2; border-color:#fecaca; color:#991b1b;">{{ session('error') }}</div>
    @endif

    <section class="param-hero">
        <div>
            <h2>Gestion des équipements</h2>
            <p>Créer, modifier ou supprimer les équipements physiques (machines) utilisés dans le module RIS.</p>
        </div>
        <a href="{{ route('ris.exams.index') }}" class="param-link">Retour aux examens</a>
    </section>

    <div class="param-grid">
        <section class="param-card">
            <div class="param-card-inner">
                <div class="param-toolbar">
                    <div>
                        <div class="param-title">Équipements enregistrés</div>
                        <div class="param-muted">Liste des machines et appareils d'imagerie.</div>
                    </div>
                </div>

                <div class="param-list">
                    @forelse($equipments as $equipment)
                        <article class="param-item" style="{{ $equipment->is_active ? '' : 'opacity:0.6;' }}">
                            <div class="param-item-head">
                                <div>
                                    <div style="font-weight:800; font-size:16px;">
                                        {{ $equipment->name }}
                                        @if(!$equipment->is_active)
                                            <span class="param-badge" style="background:#fee2e2;color:#991b1b;">Inactif</span>
                                        @endif
                                    </div>
                                    <div class="param-muted" style="margin-top:4px;">
                                        @if($equipment->modality)
                                            Type: <span class="param-badge">{{ $equipment->modality->type }}</span>
                                            &middot; Modalité: <strong>{{ $equipment->modality->name }}</strong>
                                        @else
                                            Type: <span class="param-badge">—</span>
                                        @endif
                                        @if($equipment->ae_title)
                                            &middot; AE: <strong>{{ $equipment->ae_title }}</strong>
                                        @endif
                                        @if($equipment->ip_address)
                                            &middot; IP: {{ $equipment->ip_address }}
                                        @endif
                                        @if($equipment->location)
                                            &middot; Emplacement: {{ $equipment->location }}
                                        @endif
                                    </div>
                                    @if($equipment->description)
                                        <div class="param-muted" style="margin-top:6px;font-size:0.84rem;">{{ $equipment->description }}</div>
                                    @endif
                                </div>
                                <div class="param-actions">
                                    <a href="{{ route('ris.parametrage.equipments.edit', $equipment) }}" class="param-btn"><i class="ti ti-edit"></i></a>
                                    <form method="POST" action="{{ route('ris.parametrage.equipments.destroy', $equipment) }}" onsubmit="return confirm('Supprimer cet équipement ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="param-btn param-btn-danger"><i class="ti ti-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="param-empty">Aucun équipement pour le moment. Créez-en un via le formulaire.</div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="param-card">
            <div class="param-card-inner">
                <div class="param-title">
                    @if($editingEquipment)
                        Modifier l'équipement
                    @else
                        Nouvel équipement
                    @endif
                </div>
                <div class="param-muted" style="margin-bottom:14px;">
                    @if($editingEquipment)
                        Modification de <strong>{{ $editingEquipment->name }}</strong>
                    @else
                        Remplissez le formulaire pour ajouter un équipement.
                    @endif
                </div>

                <form method="POST" action="{{ $editingEquipment ? route('ris.parametrage.equipments.update', $editingEquipment) : route('ris.parametrage.equipments.store') }}" class="param-form">
                    @csrf
                    @if($editingEquipment)
                        @method('PUT')
                    @endif

                    <div class="param-grid-form">
                        <div>
                            <label>Nom *</label>
                            <input type="text" name="name" value="{{ old('name', $editingEquipment?->name) }}" placeholder="Rayence X9" required maxlength="191">
                        </div>
                        <div>
                            <label>Modalité *</label>
                            <select name="modality_id" required>
                                <option value="">— Choisir une modalité —</option>
                                @foreach($modalities as $modality)
                                    <option value="{{ $modality->id }}" @selected(old('modality_id', $editingEquipment?->modality_id) == $modality->id)>
                                        {{ $modality->name }} ({{ $modality->type }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label>AE Title</label>
                            <input type="text" name="ae_title" value="{{ old('ae_title', $editingEquipment?->ae_title) }}" placeholder="RAYENCE_X9_AE" maxlength="64">
                        </div>
                        <div>
                            <label>Adresse IP</label>
                            <input type="text" name="ip_address" value="{{ old('ip_address', $editingEquipment?->ip_address) }}" placeholder="192.168.1.100" maxlength="45">
                        </div>
                        <div>
                            <label>Emplacement</label>
                            <input type="text" name="location" value="{{ old('location', $editingEquipment?->location) }}" placeholder="Salle 1" maxlength="191">
                        </div>
                        <div>
                            <label>Description</label>
                            <textarea name="description" rows="2" placeholder="Informations complémentaires..." maxlength="1000">{{ old('description', $editingEquipment?->description) }}</textarea>
                        </div>
                        <div>
                            <label class="param-toggle">
                                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $editingEquipment?->is_active ?? true))>
                                Équipement actif
                            </label>
                        </div>
                    </div>

                    <div class="param-actions" style="margin-top:14px;">
                        <button type="submit" class="param-btn param-btn-primary">
                            {{ $editingEquipment ? 'Enregistrer' : "Créer l'équipement" }}
                        </button>
                        @if($editingEquipment)
                            <a href="{{ route('ris.parametrage.equipments.index') }}" class="param-btn">Annuler</a>
                        @endif
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>
@endsection
