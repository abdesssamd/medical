@props([
    'status' => 'En attente',
    'modern' => false,
])

@php
    $normalized = mb_strtolower(trim($status));
    if ($modern) {
        $color = match ($normalized) {
            'termine', 'terminé', 'consulted', 'servi', 'servi(e)', 'payee', 'payé', 'paid' => 'badge-green',
            'en attente', 'waiting', 'pending' => 'badge-yellow',
            'annule', 'annulé', 'cancelled', 'absent', 'no_show' => 'badge-red',
            default => 'badge-gray',
        };
        $class = "badge-modern $color";
    } else {
        $color = match ($normalized) {
            'termine', 'terminé', 'consulted', 'servi', 'servi(e)', 'payee', 'payé', 'paid' => 'success',
            'en attente', 'waiting', 'pending' => 'warning',
            'annule', 'annulé', 'cancelled', 'absent', 'no_show' => 'danger',
            default => 'secondary',
        };
        $class = "badge bg-$color-lt text-$color";
    }
@endphp

<span {{ $attributes->class([$class]) }}>
    {{ $status }}
</span>
