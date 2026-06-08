@extends('layouts.admin')

@section('title', 'Liste des médicaments')

@section('content')
<div class="page-stack">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <section class="card">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;">
            <h1 class="page-title" style="margin:0;">Liste des médicaments</h1>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                <form method="GET" action="{{ route('admin.medications.index') }}" style="display:flex;gap:.25rem;">
                    <input class="input" name="search" placeholder="Rechercher..." value="{{ request('search') }}" style="width:200px;">
                    <button class="btn btn-primary" type="submit">Chercher</button>
                    @if(request('search'))
                        <a href="{{ route('admin.medications.index') }}" class="btn btn-secondary">Effacer</a>
                    @endif
                </form>
                <a href="{{ route('admin.medications.export') }}" class="btn btn-success">Exporter Excel</a>
                <a href="{{ route('admin.medications.example') }}" class="btn btn-info">Exemple Excel</a>
            </div>
        </div>
    </section>

    <section class="card" style="margin-top:1rem;">
        <details>
            <summary style="cursor:pointer;font-weight:600;padding:.5rem 0;color:var(--accent);">+ Ajouter un médicament</summary>
            <form method="POST" action="{{ route('admin.medications.store') }}" class="grid-two" style="margin-top:.75rem;">
                @csrf
                <div>
                    <label class="label">Nom *</label>
                    <input class="input" name="name" required maxlength="255">
                </div>
                <div>
                    <label class="label">Catégorie</label>
                    <input class="input" name="category" maxlength="80" placeholder="Antibiotique, Antalgique...">
                </div>
                <div>
                    <label class="label">Dosage / Force</label>
                    <input class="input" name="strength" placeholder="1 g, 500 mg...">
                </div>
                <div>
                    <label class="label">Formes (séparées par virgule)</label>
                    <input class="input" name="forms" placeholder="comprimé, gélule, sirop...">
                </div>
                <div>
                    <label class="label">Unité par défaut</label>
                    <input class="input" name="default_unit" maxlength="50" placeholder="comprimé, ml...">
                </div>
                <div>
                    <label class="label">Fréquence par défaut</label>
                    <input class="input" name="default_frequency" maxlength="80" placeholder="Matin/Midi/Soir">
                </div>
                <div>
                    <label class="label">Durée par défaut (jours)</label>
                    <input class="input" type="number" name="default_duration_days" min="1" max="365" placeholder="7">
                </div>
                <div>
                    <label class="label">Actif</label>
                    <select class="select" name="is_active">
                        <option value="1">Oui</option>
                        <option value="0">Non</option>
                    </select>
                </div>
                <div style="grid-column:1/-1;">
                    <button class="btn btn-primary" type="submit">Créer le médicament</button>
                </div>
            </form>
        </details>
    </section>

    <section class="card" style="margin-top:1rem;">
        <details>
            <summary style="cursor:pointer;font-weight:600;padding:.5rem 0;color:var(--accent);">Importer depuis Excel</summary>
            <form method="POST" action="{{ route('admin.medications.import') }}" enctype="multipart/form-data" style="margin-top:.75rem;display:flex;gap:.5rem;align-items:end;">
                @csrf
                <div style="flex:1;">
                    <label class="label">Fichier Excel (.xlsx ou .xls)</label>
                    <input class="input" type="file" name="file" accept=".xlsx,.xls" required>
                </div>
                <button class="btn btn-primary" type="submit">Importer</button>
                <a href="{{ route('admin.medications.example') }}" class="btn btn-info" style="font-size:13px;">Télécharger un exemple</a>
            </form>
        </details>
    </section>

    <section class="card" style="margin-top:1rem;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Catégorie</th>
                        <th>Dosage</th>
                        <th>Formes</th>
                        <th>Unité</th>
                        <th>Fréquence</th>
                        <th>Durée (j)</th>
                        <th>Actif</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($medications as $med)
                    <tr>
                        <td>{{ $med->id }}</td>
                        <td>{{ $med->name }}</td>
                        <td>{{ $med->category ?? '-' }}</td>
                        <td>{{ $med->strength ?? '-' }}</td>
                        <td>{{ $med->forms ? implode(', ', $med->forms) : '-' }}</td>
                        <td>{{ $med->default_unit ?? '-' }}</td>
                        <td>{{ $med->default_frequency ?? '-' }}</td>
                        <td>{{ $med->default_duration_days ?? '-' }}</td>
                        <td>{!! $med->is_active ? '<span class="badge bg-success">Oui</span>' : '<span class="badge bg-secondary">Non</span>' !!}</td>
                        <td>
                            <details>
                                <summary style="cursor:pointer;color:var(--accent);">Modifier</summary>
                                <form method="POST" action="{{ route('admin.medications.update', $med) }}" style="display:grid;gap:.4rem;margin-top:.4rem;min-width:300px;">
                                    @csrf
                                    @method('PUT')
                                    <input class="input" name="name" value="{{ $med->name }}" required maxlength="255">
                                    <input class="input" name="category" value="{{ $med->category }}" maxlength="80" placeholder="Catégorie">
                                    <input class="input" name="strength" value="{{ $med->strength }}" placeholder="Dosage">
                                    <input class="input" name="forms" value="{{ $med->forms ? implode(', ', $med->forms) : '' }}" placeholder="Formes (séparées par virgule)">
                                    <input class="input" name="default_unit" value="{{ $med->default_unit }}" maxlength="50" placeholder="Unité">
                                    <input class="input" name="default_frequency" value="{{ $med->default_frequency }}" maxlength="80" placeholder="Fréquence">
                                    <input class="input" type="number" name="default_duration_days" value="{{ $med->default_duration_days }}" min="1" max="365" placeholder="Durée (jours)">
                                    <select class="select" name="is_active">
                                        <option value="1" @selected($med->is_active)>Oui</option>
                                        <option value="0" @selected(!$med->is_active)>Non</option>
                                    </select>
                                    <div style="display:flex;gap:.5rem;">
                                        <button class="btn btn-primary" type="submit">Enregistrer</button>
                                    </div>
                                </form>
                                <form method="POST" action="{{ route('admin.medications.destroy', $med) }}" onsubmit="return confirm('Supprimer ce médicament ?')" style="margin-top:.35rem;">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger" type="submit">Supprimer</button>
                                </form>
                            </details>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" style="text-align:center;padding:2rem;color:#94a3b8;">Aucun médicament trouvé.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap" style="margin-top:1rem;">{{ $medications->links() }}</div>
    </section>
</div>
@endsection
