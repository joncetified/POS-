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
            @if ($store['logo_url'])
                <img src="{{ $store['logo_url'] }}" alt="{{ $store['name'] }} logo">
            @else
                {{ collect(explode(' ', $store['name']))->map(fn ($word) => mb_substr($word, 0, 1))->take(2)->implode('') }}
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
                @if (auth()->user()->avatar_path)
                    <img src="{{ asset('storage/' . auth()->user()->avatar_path) }}" alt="{{ auth()->user()->name }}">
                @else
                    {{ collect(explode(' ', auth()->user()->name))->map(fn ($word) => mb_substr($word, 0, 1))->take(2)->implode('') }}
                @endif
            </span>
            <span>
                <strong>{{ auth()->user()->name }}</strong>
                <small>{{ auth()->user()->roleLabel() }} - Profil Saya</small>
            </span>
        </a>
    @endauth
</div>
