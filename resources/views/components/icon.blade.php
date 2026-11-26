@props(['name', 'size' => 18])
<svg {{ $attributes->merge(['class' => 'i-'.$name]) }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="square" stroke-linejoin="miter" aria-hidden="true">
    @switch($name)
        @case('play')
            <path d="M8 5.5v13l10.5-6.5z" fill="currentColor" stroke="none"/>
            @break
        @case('pause')
            <path d="M7 5h3.5v14H7zM13.5 5H17v14h-3.5z" fill="currentColor" stroke="none"/>
            @break
        @case('cart')
            <path d="M3 4h2.5l2.2 11h10.6L20.5 7H7"/><circle cx="9.5" cy="19.5" r="1.3"/><circle cx="17" cy="19.5" r="1.3"/>
            @break
        @case('heart')
            <path d="M12 20s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7.3a4.3 4.3 0 0 1 7.5 2.5C19.5 15.4 12 20 12 20z"/>
            @break
        @case('search')
            <circle cx="11" cy="11" r="6.5"/><path d="M16 16l4.5 4.5"/>
            @break
        @case('user')
            <circle cx="12" cy="8.5" r="3.8"/><path d="M4.5 20.5c1.2-4 4-5.8 7.5-5.8s6.3 1.8 7.5 5.8"/>
            @break
        @case('download')
            <path d="M12 4v11M7 10.5l5 5 5-5M4.5 20h15"/>
            @break
        @case('upload')
            <path d="M12 20V9M7 13.5l5-5 5 5M4.5 4h15"/>
            @break
        @case('grid')
            <path d="M4 4h6.5v6.5H4zM13.5 4H20v6.5h-6.5zM4 13.5h6.5V20H4zM13.5 13.5H20V20h-6.5z"/>
            @break
        @case('wave')
            <path d="M3 12h2M7 8v8M11 5v14M15 9v6M19 7v10M21 12h0"/>
            @break
        @case('disc')
            <circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="2.5"/>
            @break
        @case('chart')
            <path d="M4 20V4M4 20h16M8 16v-4M12 16V8M16 16v-6"/>
            @break
        @case('coins')
            <ellipse cx="12" cy="6.5" rx="7" ry="2.8"/><path d="M5 6.5v5c0 1.5 3.1 2.8 7 2.8s7-1.3 7-2.8v-5M5 11.5v5c0 1.5 3.1 2.8 7 2.8s7-1.3 7-2.8v-5"/>
            @break
        @case('settings')
            <circle cx="12" cy="12" r="3"/><path d="M12 2.5v3M12 18.5v3M2.5 12h3M18.5 12h3M5.3 5.3l2.1 2.1M16.6 16.6l2.1 2.1M5.3 18.7l2.1-2.1M16.6 7.4l2.1-2.1"/>
            @break
        @case('receipt')
            <path d="M6 3h12v18l-3-2-3 2-3-2-3 2zM9 8h6M9 12h6M9 16h3"/>
            @break
        @case('users')
            <circle cx="9" cy="8.5" r="3.3"/><path d="M3 19.5c.9-3.4 3.2-5 6-5s5.1 1.6 6 5M15.5 5.5a3.3 3.3 0 0 1 0 6.3M17.5 14.7c1.8.6 3 2.2 3.5 4.8"/>
            @break
        @case('tag')
            <path d="M3.5 12.5V4h8.5l8.5 8.5-8.5 8.5z"/><circle cx="8" cy="8.5" r="1.3"/>
            @break
        @case('folder')
            <path d="M3 6h6.5l2 2.5H21V19H3z"/>
            @break
        @case('shield')
            <path d="M12 3l7.5 3v6c0 4.5-3.2 7.6-7.5 9-4.3-1.4-7.5-4.5-7.5-9V6z"/>
            @break
        @case('logout')
            <path d="M14 4H5v16h9M10 12h10.5M17 8.5l3.5 3.5-3.5 3.5"/>
            @break
        @case('x')
            <path d="M6 6l12 12M18 6L6 18"/>
            @break
        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16"/>
            @break
        @case('check')
            <path d="M4.5 12.5l5 5L20 7"/>
            @break
        @case('plus')
            <path d="M12 4.5v15M4.5 12h15"/>
            @break
        @case('trash')
            <path d="M4.5 7h15M9.5 7V4.5h5V7M6.5 7l1 13h9l1-13"/>
            @break
        @case('edit')
            <path d="M4 20h4L19 9l-4-4L4 16z"/>
            @break
        @case('key')
            <circle cx="8" cy="15" r="4"/><path d="M11 12l9-9M17 6l3 3M15 8l2 2"/>
            @break
        @case('arrow')
            <path d="M4 12h15M14 7l5 5-5 5"/>
            @break
        @default
            <circle cx="12" cy="12" r="8"/>
    @endswitch
</svg>
