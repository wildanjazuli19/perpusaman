<header class="topbar">
    <a class="brand" href="{{ route('dashboard') }}">
        <span class="brand-mark">P</span>
        <span>Perpus<span class="brand-light">Aman</span></span>
    </a>

    <nav class="main-nav" aria-label="Navigasi utama">
        <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Ringkasan</a>
        <a class="{{ request()->routeIs('members.*') ? 'active' : '' }}" href="{{ route('members.index') }}">Data anggota</a>
        <a class="{{ request()->routeIs('experiments.*') ? 'active' : '' }}" href="{{ route('experiments.index') }}">Eksperimen</a>
    </nav>

    <div class="account-nav">
        <span class="account-name">{{ Auth::user()->name }}</span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="link-button" type="submit">Keluar</button>
        </form>
    </div>
</header>
