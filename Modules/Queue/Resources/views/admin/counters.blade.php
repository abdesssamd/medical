@extends('layouts.admin')

@section('title', __('queue.counter_management'))

@section('content')
<div class="page-stack">
    <section class="card">
        <h1 class="page-title">{{ __('queue.counter_management') }}</h1>
        <form method="POST" action="{{ route('admin.counters.store') }}" class="grid-two">
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
            <div><label class="label">{{ __('queue.name') }} (AR)</label><input class="input" name="name_ar"></div>
            <div><label class="label">Code</label><input class="input" name="code" required></div>
            <div style="grid-column:1/-1;"><label class="label">{{ __('queue.location') }}</label><input class="input" name="location"></div>
            <div style="grid-column:1/-1;">
                <label class="label">{{ __('queue.services') }}</label>
                <select class="select" multiple size="5" name="service_ids[]">
                    @foreach($organizations as $org)
                        <optgroup label="{{ $org->name }}">
                            @foreach($org->services as $service)
                                <option value="{{ $service->id }}">{{ $service->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            <div style="grid-column:1/-1;"><button class="btn btn-primary" type="submit">{{ __('queue.create_counter') }}</button></div>
        </form>
    </section>

    <section class="card">
        <div class="table-wrap">
            <table>
                <thead><tr><th>{{ __('queue.name') }}</th><th>Code</th><th>{{ __('queue.select_organization') }}</th><th>{{ __('queue.services') }}</th><th>{{ __('queue.actions') }}</th></tr></thead>
                <tbody>
                @foreach($counters as $counter)
                    <tr>
                        <td>{{ $counter->name }}</td>
                        <td>{{ $counter->code }}</td>
                        <td>{{ $counter->organization?->name }}</td>
                        <td>{{ $counter->services->pluck('name')->join(', ') }}</td>
                        <td>
                            <details>
                                <summary>{{ __('queue.edit') }}</summary>
                                <form method="POST" action="{{ route('admin.counters.update', $counter) }}" style="display:grid;gap:.4rem;min-width:300px;">
                                    @csrf
                                    @method('PUT')
                                    <select class="select" name="organization_id" required>
                                        @foreach($organizations as $org)
                                            <option value="{{ $org->id }}" @selected($counter->organization_id === $org->id)>{{ $org->name }}</option>
                                        @endforeach
                                    </select>
                                    <input class="input" name="name" value="{{ $counter->name }}" required>
                                    <input class="input" name="name_ar" value="{{ $counter->name_ar }}">
                                    <input class="input" name="code" value="{{ $counter->code }}" required>
                                    <input class="input" name="location" value="{{ $counter->location }}">
                                    <label style="display:flex;gap:.4rem;align-items:center;"><input type="checkbox" name="is_active" value="1" @checked($counter->is_active)> {{ __('queue.active') }}</label>
                                    <select class="select" multiple size="5" name="service_ids[]">
                                        @foreach($counter->organization->services as $service)
                                            <option value="{{ $service->id }}" @selected($counter->services->contains('id', $service->id))>{{ $service->name }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-primary" type="submit">{{ __('queue.save') }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.counters.destroy', $counter) }}" onsubmit="return confirm('{{ __('queue.confirm_delete') }}')" style="margin-top:.35rem;">
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



