@extends('layouts.admin')

@section('title', __('queue.user_management'))

@section('content')
<div class="page-stack">
    <section class="card">
        <h1 class="page-title">{{ __('queue.user_management') }}</h1>
        <form method="POST" action="{{ route('admin.users.store') }}" class="grid-two">
            @csrf
            <div><label class="label">{{ __('queue.name') }}</label><input class="input" name="name" required></div>
            <div><label class="label">Email</label><input class="input" type="email" name="email" required></div>
            <div>
                <label class="label">Role</label>
                <select class="select" name="role" required>
                    <option value="professional">Professionnel (Médecin/Dentiste)</option>
                    <option value="admin">Administrateur</option>
                    <option value="secretary">Secrétaire</option>
                    <option value="assistant">Assistant</option>
                    <option value="super_admin">Super Admin</option>
                    <option value="agent">Agent (File d'attente)</option>
                </select>
            </div>
            <div>
                <label class="label">Spécialité</label>
                <select class="select" name="specialty_id">
                    <option value="">-- Aucune --</option>
                    @foreach($specialties as $spec)
                        <option value="{{ $spec->id }}">{{ $spec->name }} ({{ $spec->code }})</option>
                    @endforeach
                </select>
            </div>
            <div><label class="label">Titre professionnel</label><input class="input" name="professional_title" placeholder="Dr, Pr, Mme..."></div>
            <div><label class="label">Téléphone</label><input class="input" name="phone" placeholder="+212..."></div>
            <div>
                <label class="label">{{ __('queue.select_organization') }}</label>
                <select class="select" name="organization_id">
                    <option value="">--</option>
                    @foreach($organizations as $org)
                        <option value="{{ $org->id }}">{{ $org->name }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="label">{{ __('queue.password') }}</label><input class="input" type="password" name="password" required></div>
            <div><label class="label">{{ __('queue.phone') }} (Agent)</label><input class="input" name="agent_phone" placeholder="Pour les agents de file d'attente"></div>
            <div style="grid-column:1/-1;"><button class="btn btn-primary" type="submit">{{ __('queue.create_user') }}</button></div>
        </form>
    </section>

    <section class="card">
        <div class="table-wrap">
            <table>
                <thead><tr><th>ID</th><th>{{ __('queue.name') }}</th><th>Email</th><th>Role</th><th>Spécialité</th><th>{{ __('queue.select_organization') }}</th><th>{{ __('queue.actions') }}</th></tr></thead>
                <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->role }}</td>
                        <td>{{ $user->specialty?->name ?? '-' }}</td>
                        <td>{{ $user->organization?->name ?? '-' }}</td>
                        <td>
                            <details>
                                <summary>{{ __('queue.edit') }}</summary>
                                <form method="POST" action="{{ route('admin.users.update', $user) }}" style="display:grid;gap:.4rem;margin-top:.4rem;min-width:280px;">
                                    @csrf
                                    @method('PUT')
                                    <input class="input" name="name" value="{{ $user->name }}" required>
                                    <input class="input" type="email" name="email" value="{{ $user->email }}" required>
                                    <select class="select" name="role">
                                        <option value="professional" @selected($user->role === 'professional')>Professionnel</option>
                                        <option value="admin" @selected($user->role === 'admin')>Administrateur</option>
                                        <option value="secretary" @selected($user->role === 'secretary')>Secrétaire</option>
                                        <option value="assistant" @selected($user->role === 'assistant')>Assistant</option>
                                        <option value="super_admin" @selected($user->role === 'super_admin')>Super Admin</option>
                                        <option value="agent" @selected($user->role === 'agent')>Agent</option>
                                    </select>
                                    <select class="select" name="specialty_id">
                                        <option value="">-- Aucune --</option>
                                        @foreach($specialties as $spec)
                                            <option value="{{ $spec->id }}" @selected($user->specialty_id === $spec->id)>{{ $spec->name }} ({{ $spec->code }})</option>
                                        @endforeach
                                    </select>
                                    <input class="input" name="professional_title" value="{{ $user->professional_title }}" placeholder="Titre professionnel">
                                    <input class="input" name="phone" value="{{ $user->phone }}" placeholder="Téléphone">
                                    <select class="select" name="organization_id">
                                        <option value="">--</option>
                                        @foreach($organizations as $org)
                                            <option value="{{ $org->id }}" @selected($user->organization_id === $org->id)>{{ $org->name }}</option>
                                        @endforeach
                                    </select>
                                    <input class="input" type="password" name="password" placeholder="{{ __('queue.password_optional') }}">
                                    <input class="input" name="agent_phone" value="{{ $user->agent?->phone }}" placeholder="{{ __('queue.phone') }} (Agent)">
                                    <button class="btn btn-primary" type="submit">{{ __('queue.save') }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('{{ __('queue.confirm_delete') }}')" style="margin-top:.35rem;">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger" type="submit">{{ __('queue.delete') }}</button>
                                </form>
                            </details>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $users->links() }}</div>
    </section>
</div>
@endsection



