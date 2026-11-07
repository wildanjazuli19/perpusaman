<div class="sidebar-layout">
    <input type="checkbox" id="sidebar-toggle" class="sidebar-toggle">

    {{-- Tombol hamburger untuk mobile --}}
    <label for="sidebar-toggle" class="sidebar-open-button" aria-label="Buka navigasi">
        ☰
    </label>

    {{-- Sidebar --}}
    <aside class="app-sidebar">

        <div class="sidebar-header">
            <a class="brand" href="{{ route('dashboard') }}">
                <span class="brand-mark">P</span>
                <span>Perpus<span class="brand-light">Aman</span></span>
            </a>

            <label for="sidebar-toggle" class="sidebar-close-button" aria-label="Tutup navigasi">
                ×
            </label>
        </div>

        <div class="sidebar-section-label">MENU UTAMA</div>

        <nav class="sidebar-nav" aria-label="Navigasi utama">

            <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"
               href="{{ route('dashboard') }}">
                <span class="sidebar-icon">⌂</span>
                <span>Ringkasan</span>
            </a>

            <a class="{{ request()->routeIs('members.*') ? 'active' : '' }}"
               href="{{ route('members.index') }}">
                <span class="sidebar-icon">♙</span>
                <span>Data Anggota</span>
            </a>

            <a class="{{ request()->routeIs('books.*') ? 'active' : '' }}"
               href="{{ route('books.index') }}">
                <span class="sidebar-icon">▤</span>
                <span>Katalog Buku</span>
            </a>

            <a class="{{ request()->routeIs('categories.*') ? 'active' : '' }}"
               href="{{ route('categories.index') }}">
                <span class="sidebar-icon">▦</span>
                <span>Kategori</span>
            </a>

            <a class="{{ request()->routeIs('loans.*') ? 'active' : '' }}"
               href="{{ route('loans.index') }}">
                <span class="sidebar-icon">⇧</span>
                <span>Peminjaman</span>
            </a>

            <a class="{{ request()->routeIs('returns.*') ? 'active' : '' }}"
               href="{{ route('returns.index') }}">
                <span class="sidebar-icon">⇩</span>
                <span>Pengembalian</span>
            </a>
            <a class="{{ request()->routeIs('reports.*') ? 'active' : '' }}"
   href="{{ route('reports.index') }}">
    <span class="sidebar-icon">▥</span>
    <span>Laporan</span>
</a>

            <a class="{{ request()->routeIs('experiments.*') ? 'active' : '' }}"
               href="{{ route('experiments.index') }}">
                <span class="sidebar-icon">⚗</span>
                <span>Eksperimen</span>
            </a>

        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-avatar">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>

                <div class="sidebar-user-info">
                    <span class="sidebar-user-name">{{ Auth::user()->name }}</span>
                    <span class="sidebar-user-role">Administrator</span>
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="sidebar-logout" type="submit">
                    <span>↪</span>
                    Keluar
                </button>
            </form>
        </div>

    </aside>

    {{-- Background gelap ketika sidebar dibuka di mobile --}}
    <label for="sidebar-toggle" class="sidebar-overlay"></label>
</div>