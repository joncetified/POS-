@php
    $homeRoute = route('profile.edit');

    if (auth()->check() && auth()->user()->hasPermission('page.pos')) {
        $homeRoute = route('pos.index');
    } elseif (auth()->check() && auth()->user()->hasPermission('page.dashboard')) {
        $homeRoute = route('dashboard.index');
    } elseif (auth()->check() && auth()->user()->hasPermission('page.customer_menu')) {
        $homeRoute = route('customer.menu');
    }
@endphp

<div class="staff-brand-stack">
    <a class="staff-brand" href="{{ $homeRoute }}">
        <span class="staff-brand-mark">
            @php($storeInitials = collect(explode(' ', $store['name']))->map(fn ($word) => mb_substr($word, 0, 1))->take(2)->implode(''))
            @if ($store['logo_url'])
                <img src="{{ $store['logo_url'] }}" alt="{{ $store['name'] }} logo" onerror="this.hidden=true; this.nextElementSibling.hidden=false">
                <span class="image-fallback" hidden>{{ $storeInitials }}</span>
            @else
                <span class="image-fallback">{{ $storeInitials }}</span>
            @endif
        </span>
        <span>
            <strong>{{ $store['name'] }}</strong>
            <small>Cafe POS</small>
        </span>
    </a>

    @auth
        <a class="staff-user-profile" href="{{ route('profile.edit') }}">
            <span class="staff-user-avatar">
                @php($userInitials = collect(explode(' ', auth()->user()->name))->map(fn ($word) => mb_substr($word, 0, 1))->take(2)->implode(''))
                @if (auth()->user()->avatarUrl())
                    <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}" onerror="this.hidden=true; this.nextElementSibling.hidden=false">
                    <span class="image-fallback" hidden>{{ $userInitials }}</span>
                @else
                    <span class="image-fallback">{{ $userInitials }}</span>
                @endif
            </span>
            <span>
                <strong>{{ auth()->user()->name }}</strong>
                <small>{{ auth()->user()->roleLabel() }} - Profil Saya</small>
            </span>
        </a>
    @endauth
</div>
