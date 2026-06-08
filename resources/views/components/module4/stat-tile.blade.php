@props([
    'title',
    'value',
    'subtitle' => null,
    'tone' => 'primary',
    'icon' => 'chart',
])

@php
    $toneMap = [
        'primary' => 'from-indigo-500/15 to-indigo-500/5 text-indigo-700 bg-indigo-500/10',
        'success' => 'from-emerald-500/15 to-emerald-500/5 text-emerald-700 bg-emerald-500/10',
        'danger' => 'from-rose-500/15 to-rose-500/5 text-rose-700 bg-rose-500/10',
        'sterile' => 'from-cyan-500/15 to-cyan-500/5 text-cyan-700 bg-cyan-500/10',
        'neutral' => 'from-slate-500/15 to-slate-500/5 text-slate-700 bg-slate-500/10',
    ];

    $toneClass = $toneMap[$tone] ?? $toneMap['neutral'];
@endphp

<article {{ $attributes->merge(['class' => 'm4-stat-tile']) }}>
    <div class="m4-stat-head">
        <div class="m4-stat-copy">
            <p class="m4-stat-title">{{ $title }}</p>
            <p class="m4-stat-value">{{ $value }}</p>
            @if($subtitle)
                <p class="m4-stat-subtitle">{{ $subtitle }}</p>
            @endif
        </div>
        <div class="m4-stat-icon {{ $toneClass }}">
            <x-module4.icon :name="$icon" class="h-5 w-5" />
        </div>
    </div>
</article>
