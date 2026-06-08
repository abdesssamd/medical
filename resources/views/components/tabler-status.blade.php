@props([
    'status' => 'En attente',
])

@php
    $normalized = mb_strtolower(trim($status));
    $color = match ($normalized) {
        'termine', 'terminé', 'consulted', 'servi', 'servi(e)', 'payee', 'payé', 'paid' => 'success',
        'en attente', 'waiting', 'pending' => 'warning',
        'annule', 'annulé', 'cancelled', 'absent', 'no_show' => 'danger',
        default => 'secondary',
    };
@endphp

<span {{ $attributes->class(["badge bg-$color-lt text-$color"]) }}>
    {{ $status }}
</span>
