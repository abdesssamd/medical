@props(['title' => null, 'subtitle' => null, 'actions' => null])

<section {{ $attributes->merge(['class' => 'm4-card']) }}>
    @if($title || $actions)
        <header class="m4-card-header">
            <div>
                @if($title)
                    <h3 class="m4-card-title">{{ $title }}</h3>
                @endif
                @if($subtitle)
                    <p class="m4-card-subtitle">{{ $subtitle }}</p>
                @endif
            </div>
            @if($actions)
                <div>{{ $actions }}</div>
            @endif
        </header>
    @endif
    <div class="m4-card-body">
        {{ $slot }}
    </div>
</section>
