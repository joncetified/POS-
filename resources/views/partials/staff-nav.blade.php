<div class="actions staff-nav">
    @if (auth()->user()->hasPermission('page.dashboard'))
        <a class="btn @class(['active' => request()->routeIs('dashboard.*')])" href="{{ route('dashboard.index') }}">Dashboard</a>
    @endif
    @if (auth()->user()->hasPermission('page.pos'))
        <a class="btn @class(['active' => request()->routeIs('pos.*')])" href="{{ route('pos.index') }}">Kasir</a>
    @endif
    @if (auth()->user()->hasPermission('page.products'))
        <a class="btn @class(['active' => request()->routeIs('products.*')])" href="{{ route('products.index') }}">Produk</a>
    @endif
    @if (auth()->user()->hasPermission('page.qr_tables'))
        <a class="btn @class(['active' => request()->routeIs('customer.qr.*')])" href="{{ route('customer.qr.index') }}">QR Meja</a>
    @endif
    @if (auth()->user()->hasPermission('page.sales'))
        <a class="btn @class(['active' => request()->routeIs('sales.*')])" href="{{ route('sales.index') }}">Transaksi</a>
    @endif
    @if (auth()->user()->hasPermission('page.reports'))
        <a class="btn @class(['active' => request()->routeIs('reports.*')])" href="{{ route('reports.index') }}">Laporan</a>
    @endif
    @if (auth()->user()->hasPermission('page.operations'))
        <a class="btn @class(['active' => request()->routeIs('operations.*')])" href="{{ route('operations.index') }}">Operasional</a>
    @endif
    @if (auth()->user()->hasPermission('page.settings'))
        <a class="btn @class(['active' => request()->routeIs('settings.*')])" href="{{ route('settings.index') }}">Settings</a>
    @endif
    @if (auth()->user()->role === \App\Enums\UserRole::SuperAdmin)
        <a class="btn @class(['active' => request()->routeIs('access-control.*')])" href="{{ route('access-control.index') }}">Akses User</a>
    @endif
    <a class="btn @class(['active' => request()->routeIs('profile.*')])" href="{{ route('profile.edit') }}">Profil Saya</a>
    <form class="logout-form" method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn" type="submit">Logout</button>
    </form>
</div>
