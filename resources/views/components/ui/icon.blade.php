@props([
    'name',
    'class' => 'size-5',
])

@switch($name)
    @case('shield')
        <svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'aria-hidden' => 'true']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l7 3.5V12c0 4.4-2.4 7.9-7 9-4.6-1.1-7-4.6-7-9V6.5L12 3Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="m9.5 12 1.7 1.7L15 9.9" />
        </svg>
        @break

    @case('play')
        <svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'aria-hidden' => 'true']) }}>
            <circle cx="12" cy="12" r="9" />
            <path stroke-linecap="round" stroke-linejoin="round" d="m10 9 5 3-5 3V9Z" fill="currentColor" stroke="none" />
        </svg>
        @break

    @case('document')
        <svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'aria-hidden' => 'true']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 3.5h6l4 4V19a1.5 1.5 0 0 1-1.5 1.5h-8A1.5 1.5 0 0 1 7 19V5a1.5 1.5 0 0 1 1-1.4Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M14 3.5V8h4" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 12.5h4M10 15.5h4" />
        </svg>
        @break

    @case('stack')
        <svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'aria-hidden' => 'true']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="m12 4 8 4-8 4-8-4 8-4Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="m4 12 8 4 8-4" />
            <path stroke-linecap="round" stroke-linejoin="round" d="m4 16 8 4 8-4" />
        </svg>
        @break

    @case('quiz')
        <svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'aria-hidden' => 'true']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 5.5h9A1.5 1.5 0 0 1 18 7v10a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 17V7a1.5 1.5 0 0 1 1.5-1.5Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.5 10.5h5M9.5 13.5h5M9.5 16.5h3" />
        </svg>
        @break

    @case('book')
        <svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'aria-hidden' => 'true']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 6.5A2.5 2.5 0 0 1 7.5 4H19v14.5H7.5A2.5 2.5 0 0 0 5 21V6.5Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 6.5A2.5 2.5 0 0 1 7.5 4H19" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 8.5h6M9 11.5h6" />
        </svg>
        @break

    @case('users')
        <svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'aria-hidden' => 'true']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.5 19a3.5 3.5 0 0 0-7 0" />
            <circle cx="12" cy="10" r="3" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M18.5 18.5a3 3 0 0 0-2.2-2.9M17 7.5a2.5 2.5 0 0 1 0 5" />
        </svg>
        @break

    @case('spark')
        <svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'aria-hidden' => 'true']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="m12 3 1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Z" />
        </svg>
        @break

    @case('chart')
        <svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'aria-hidden' => 'true']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 19.5h14" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 16V10M12 16V6.5M16.5 16v-4" />
        </svg>
        @break

    @case('clock')
        <svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'aria-hidden' => 'true']) }}>
            <circle cx="12" cy="12" r="8.5" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.8v4.7l3 1.8" />
        </svg>
        @break

    @case('badge-check')
        <svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'aria-hidden' => 'true']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="m12 3 2.1 2.2 3-.3 1.1 2.8 2.7 1.2-.7 3 1.8 2.5-1.8 2.5.7 3-2.7 1.2-1.1 2.8-3-.3L12 21l-2.1-2.2-3 .3-1.1-2.8L3 14.9l.7-3L3 9.4l2.8-1.2L6.9 5.4l3 .3L12 3Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="m9.5 12.2 1.7 1.7 3.5-3.8" />
        </svg>
        @break

    @case('wallet')
        <svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'aria-hidden' => 'true']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="M5.5 7h11A2.5 2.5 0 0 1 19 9.5v7A2.5 2.5 0 0 1 16.5 19h-11A2.5 2.5 0 0 1 3 16.5v-7A2.5 2.5 0 0 1 5.5 7Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M16 12h3v3h-3a1.5 1.5 0 0 1 0-3Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 7V6.5A2.5 2.5 0 0 1 8.5 4H18" />
        </svg>
        @break

    @case('graduation')
        <svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'aria-hidden' => 'true']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="m3 9 9-4 9 4-9 4-9-4Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 11.5V15c0 1.4 2.2 2.5 5 2.5s5-1.1 5-2.5v-3.5" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 10v5" />
        </svg>
        @break

    @case('refresh')
        <svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'aria-hidden' => 'true']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" d="M18 8a7 7 0 1 0 1.5 7.5" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M18 4.5V8h-3.5" />
        </svg>
        @break

    @default
        <svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'aria-hidden' => 'true']) }}>
            <circle cx="12" cy="12" r="8.5" />
        </svg>
@endswitch
