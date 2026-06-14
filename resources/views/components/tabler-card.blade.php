@props([
    'title' => '',
    'variant' => 'default', // default, modern, flat
])

@php
    $classes = match($variant) {
        'modern' => 'content-card',
        'flat' => 'card card-flat',
        default => 'card',
    };
@endphp

<div {{ $attributes->class([$classes]) }}>
    @if($title !== '' || isset($options))
        <div @class(['card-header-custom' => $variant === 'modern', 'card-header' => $variant !== 'modern'])>
            <h3 @class(['card-title' => $variant !== 'modern', 'fw-bold' => true])>{{ $title }}</h3>
            @if(isset($options))
                <div @class(['card-actions' => $variant !== 'modern'])>
                    {{ $options }}
                </div>
            @endif
        </div>
    @endif
    <div class="card-body">
        {{ $slot }}
    </div>
</div>
