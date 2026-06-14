@extends('layouts.admin')

@section('title', 'Modalités RIS')
@section('page-title', 'Gestion des modalités')

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
    .param-form input,.param-form select { width:100%; border:1px solid #cbd5e1; border-radius:12px; padding:10px 12px; font: inherit; background:#fff; }
    .param-grid-form { display:grid; gap:12px; }
    .param-empty { padding:18px; border:1px dashed #cbd5e1; border-radius:14px; color:#64748b; }
    .param-warning { padding: 10px 12px; border-radius: 10px; background: #fff7ed; color:#9a3412; border:1px solid #fdba74; font-weight:700; }
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
            <h2>Gestion des modalités</h2>
            <p>Types logiques d'imagerie (DICOM). Chaque type peut avoir plusieurs équipements physiques.</p>
        </div>
        <a href="{{ route('ris.exams.index') }}" class="param-link">Retour aux examens</a>
    </section>

    <div class="param-grid">
        <section class="param-card">
            <div class="param-card-inner">
                <div class="param-toolbar">
                    <div>
                        <div class="param-title">Modalités enregistrées</div>
                        <div class="param-muted">Liste des types d'imagerie disponibles.</div>
                    </div>
                </div>

                <div class="param-list">
                    @forelse($modalities as $modality)
                        <article class="param-item">
                            <div class="param-item-head">
                                <div>
                                    <div style="font-weight:800; font-size:16px;">{{ $modality->name }}</div>
                                    <div class="param-muted" style="margin-top:4px;">
                                        Code DICOM: <span class="param-badge">{{ $modality->type }}</span>
                                        &middot; {{ $modality->orders()->count() }} examen(s)
                                        &middot; {{ $modality->equipments_count }} équipement(s)
                                    </div>
                                    @if($modality->description)
                                        <div class="param-muted" style="margin-top:6px;font-size:0.84rem;">{{ $modality->description }}</div>
                                    @endif
                                </div>
                                <div class="param-actions">
                                    <a href="{{ route('ris.parametrage.modalities.edit', $modality) }}" class="param-btn"><i class="ti ti-edit"></i></a>
                                    <form method="POST" action="{{ route('ris.parametrage.modalities.destroy', $modality) }}" onsubmit="return confirm('Supprimer cette modalité ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="param-btn param-btn-danger"><i class="ti ti-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="param-empty">Aucune modalité pour le moment. Créez-en une via le formulaire.</div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="param-card">
            <div class="param-card-inner">
                <div class="param-title">
                    @if($editingModality)
                        Modifier la modalité
                    @else
                        Nouvelle modalité
                    @endif
                </div>
                <div class="param-muted" style="margin-bottom:14px;">
                    @if($editingModality)
                        Modification de <strong>{{ $editingModality->name }}</strong>
                    @else
                        Remplissez le formulaire pour ajouter une modalité.
                    @endif
                </div>

                <form method="POST" action="{{ $editingModality ? route('ris.parametrage.modalities.update', $editingModality) : route('ris.parametrage.modalities.store') }}" class="param-form">
                    @csrf
                    @if($editingModality)
                        @method('PUT')
                    @endif

                    <div class="param-grid-form">
                        <div>
                            <label>Nom *</label>
                            <input type="text" name="name" value="{{ old('name', $editingModality?->name) }}" placeholder="Radiographie Numérique" required maxlength="191">
                        </div>
                        <div>
                            <label>Code DICOM *</label>
                            <select name="type" required>
                                <option value="">Choisir</option>
                                @foreach(\Modules\RIS\Models\RisModality::TYPES as $code => $label)
                                    <option value="{{ $code }}" @selected(old('type', $editingModality?->type) === $code)>{{ $code }} — {{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label>Description</label>
                            <input type="text" name="description" value="{{ old('description', $editingModality?->description) }}" placeholder="Capteur plan numérique" maxlength="255">
                        </div>
                    </div>

                    <div class="param-actions" style="margin-top:14px;">
                        <button type="submit" class="param-btn param-btn-primary">
                            {{ $editingModality ? 'Enregistrer' : 'Créer la modalité' }}
                        </button>
                        @if($editingModality)
                            <a href="{{ route('ris.parametrage.modalities.index') }}" class="param-btn">Annuler</a>
                        @endif
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>
@endsection
