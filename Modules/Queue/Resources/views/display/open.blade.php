@extends('layouts.admin')

@section('title', __('queue.public_display'))

@section('content')
<div class="card" style="max-width:760px;display:grid;gap:1rem;">
    <h1 class="page-title">{{ __('queue.public_display') }}</h1>

    <form method="GET" action="{{ route('display.open') }}" style="display:flex;gap:.6rem;flex-wrap:wrap;">
        <input class="input" name="code" placeholder="{{ __('queue.tv_code') }}: TV-HOSP-01" style="max-width:320px;">
        <button class="btn btn-primary" type="submit">{{ __('queue.open_by_code') }}</button>
    </form>

    <div>
        <label class="label">{{ __('queue.select_tv') }}</label>
        <div style="display:grid;gap:.5rem;max-height:300px;overflow:auto;">
            @foreach($screens as $screen)
                <a class="btn btn-soft" style="justify-content:space-between;" href="{{ route('display.public.code', $screen->code) }}" target="_blank">
                    <span>{{ $screen->name }} ({{ $screen->code }})</span>
                    <span>{{ $screen->organization?->name }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endsection



