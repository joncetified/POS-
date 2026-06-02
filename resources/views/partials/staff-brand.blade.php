<a class="staff-brand" href="{{ route('pos.index') }}">
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
