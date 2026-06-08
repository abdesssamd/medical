@props(['tone' => 'neutral'])

@php
    $toneClasses = [
        'primary' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'warning' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'danger' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'sterile' => 'bg-cyan-50 text-cyan-700 ring-cyan-200',
        'neutral' => 'bg-slate-100 text-slate-700 ring-slate-200',
    ];

    $classes = $toneClasses[$tone] ?? $toneClasses['neutral'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold tracking-wide ring-1 ring-inset {$classes}"]) }}>
    {{ $slot }}
</span>
