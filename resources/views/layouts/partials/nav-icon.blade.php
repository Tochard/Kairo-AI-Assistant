@php
    $class = $class ?? 'size-5';
@endphp

<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="{{ $class }} shrink-0">
    @switch($icon)
        @case('home')
            <path d="M3 11.5 12 4l9 7.5" />
            <path d="M5.5 10v9a1 1 0 0 0 1 1H9a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h2.5a1 1 0 0 0 1-1v-9" />
            @break

        @case('chat')
            <path d="M4 5h16v11H8l-4 4V5Z" />
            @break

        @case('goals')
            <path d="M5 21V4" />
            <path d="M5 4h13l-3 4 3 4H5" />
            @break

        @case('tasks')
            <circle cx="12" cy="12" r="9" />
            <path d="M8.5 12.5l2.5 2.5 4.5-5" />
            @break

        @case('trackables')
            <path d="M5 20V13" />
            <path d="M12 20V8" />
            <path d="M19 20v-5" />
            @break
    @endswitch
</svg>
