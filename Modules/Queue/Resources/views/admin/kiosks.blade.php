@extends('layouts.admin')

@section('title', __('queue.kiosk_management'))

@section('content')
<div class="page-stack">
    <section class="card">
        <h1 class="page-title">{{ __('queue.kiosk_management') }}</h1>
        <form method="POST" action="{{ route('admin.kiosks.store') }}" class="grid-two">
            @csrf
            <div>
                <label class="label">{{ __('queue.select_organization') }}</label>
                <select class="select" name="organization_id" required>
                    @foreach($organizations as $org)
                        <option value="{{ $org->id }}">{{ $org->name }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="label">{{ __('queue.name') }}</label><input class="input" name="name" required></div>
            <div><label class="label">Code</label><input class="input" name="code" required></div>
            <div><label class="label">{{ __('queue.location') }}</label><input class="input" name="location"></div>
            <div style="grid-column:1/-1;"><button class="btn btn-primary" type="submit">{{ __('queue.create_kiosk') }}</button></div>
        </form>
    </section>

    <section class="card">
        <div class="table-wrap">
            <table>
                <thead><tr><th>{{ __('queue.name') }}</th><th>Code</th><th>{{ __('queue.select_organization') }}</th><th>{{ __('queue.location') }}</th><th>{{ __('queue.actions') }}</th></tr></thead>
                <tbody>
                @foreach($kiosks as $kiosk)
                    <tr>
                        <td>{{ $kiosk->name }}</td>
                        <td>{{ $kiosk->code }}</td>
                        <td>{{ $kiosk->organization?->name }}</td>
                        <td>{{ $kiosk->location }}</td>
                        <td>
                            <details>
                                <summary>{{ __('queue.edit') }}</summary>
                                <form method="POST" action="{{ route('admin.kiosks.update', $kiosk) }}" style="display:grid;gap:.4rem;min-width:280px;">
                                    @csrf
                                    @method('PUT')
                                    <select class="select" name="organization_id" required>
                                        @foreach($organizations as $org)
                                            <option value="{{ $org->id }}" @selected($kiosk->organization_id === $org->id)>{{ $org->name }}</option>
                                        @endforeach
                                    </select>
                                    <input class="input" name="name" value="{{ $kiosk->name }}" required>
                                    <input class="input" name="code" value="{{ $kiosk->code }}" required>
                                    <input class="input" name="location" value="{{ $kiosk->location }}">
                                    <label style="display:flex;gap:.4rem;align-items:center;"><input type="checkbox" name="is_active" value="1" @checked($kiosk->is_active)> {{ __('queue.active') }}</label>
                                    <button class="btn btn-primary" type="submit">{{ __('queue.save') }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.kiosks.destroy', $kiosk) }}" onsubmit="return confirm('{{ __('queue.confirm_delete') }}')" style="margin-top:.35rem;">
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
    </section>
</div>
@endsection



