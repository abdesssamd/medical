@props([
    'name' => 'sparkles',
    'class' => 'h-5 w-5',
])

@switch($name)
    @case('shield-check')
    @case('scan')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="{{ $class }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="m12 2 7 3v6c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V5l7-3Z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="m9.5 12 1.8 1.8L15 10"/>
        </svg>
        @break

    @case('box')
    @case('cube')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="{{ $class }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 12 3l9 4.5-9 4.5-9-4.5Z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5V16.5L12 21l9-4.5V7.5"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 12v9"/>
        </svg>
        @break

    @case('flask-conical')
    @case('beaker')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="{{ $class }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 3v4l-5 9a2 2 0 0 0 1.8 3h10.4A2 2 0 0 0 19 16l-5-9V3"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 13h8"/>
        </svg>
        @break

    @case('alert-triangle')
    @case('sparkles')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="{{ $class }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.3 4.7 2.8 17.3A1.5 1.5 0 0 0 4.1 19.5h15.8a1.5 1.5 0 0 0 1.3-2.2L13.7 4.7a1.5 1.5 0 0 0-2.6 0Z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5h.01"/>
        </svg>
        @break

    @case('file-text')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="{{ $class }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="16" y1="13" x2="8" y2="13"/>
            <line x1="16" y1="17" x2="8" y2="17"/>
            <line x1="10" y1="9" x2="8" y2="9"/>
        </svg>
        @break

    @case('qr-code')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="{{ $class }}">
            <rect x="3" y="3" width="7" height="7" rx="1"/>
            <rect x="14" y="3" width="7" height="7" rx="1"/>
            <rect x="14" y="14" width="7" height="7" rx="1"/>
            <rect x="3" y="14" width="7" height="7" rx="1"/>
            <rect x="5" y="5" width="3" height="3" rx="0.5"/>
            <rect x="16" y="5" width="3" height="3" rx="0.5"/>
            <rect x="16" y="16" width="3" height="3" rx="0.5"/>
            <rect x="5" y="16" width="3" height="3" rx="0.5"/>
        </svg>
        @break

    @case('mail')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="{{ $class }}">
            <rect width="20" height="16" x="2" y="4" rx="2"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
        </svg>
        @break

    @case('trash-2')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="{{ $class }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
            <line x1="10" x2="10" y1="11" y2="17"/>
            <line x1="14" x2="14" y1="11" y2="17"/>
        </svg>
        @break

    @case('check-circle')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="{{ $class }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="m9 11 3 3L22 4"/>
        </svg>
        @break

    @case('x-circle')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="{{ $class }}">
            <circle cx="12" cy="12" r="10"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="m15 9-6 6"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="m9 9 6 6"/>
        </svg>
        @break

    @case('search')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="{{ $class }}">
            <circle cx="11" cy="11" r="8"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3"/>
        </svg>
        @break

    @default
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="{{ $class }}">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75 10.5 18l9-12"/>
        </svg>
@endswitch
