<table class="table table-hover">
    <thead><tr><th>Code</th><th>Nom</th><th>Catégorie</th><th>Actif</th><th>Actions</th></tr></thead>
    <tbody>
    @forelse($codes as $code)
        <tr>
            <td><code>{{ $code->code }}</code></td>
            <td>{{ $code->name }}</td>
            <td>{{ $code->category }}</td>
            <td>{!! $code->is_active ? '<span class="badge bg-success">Oui</span>' : '<span class="badge bg-secondary">Non</span>' !!}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary edit-code-btn" data-id="{{ $code->id }}" data-code="{{ $code->code }}" data-name="{{ $code->name }}" data-category="{{ $code->category }}" data-active="{{ $code->is_active }}">Modifier</button>
                <button class="btn btn-sm btn-outline-danger delete-code-btn" data-id="{{ $code->id }}">Supprimer</button>
            </td>
        </tr>
    @empty
        <tr><td colspan="5" class="text-secondary">Aucun code CIM-10.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $codes->links() }}
