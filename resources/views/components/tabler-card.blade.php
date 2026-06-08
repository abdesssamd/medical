@props([
    'title' => '',
])

<div {{ $attributes->class(['card']) }}>
    @if($title !== '' || isset($options))
        <div class="card-header">
            <h3 class="card-title">{{ $title }}</h3>
            @if(isset($options))
                <div class="card-actions">
                    {{ $options }}
                </div>
            @endif
        </div>
    @endif
    <div class="card-body">
        {{ $slot }}
    </div>
</div>
