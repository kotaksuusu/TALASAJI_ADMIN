<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>@yield('title', 'TALASAJI Admin')</title>

  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet" />
  <script src="https://unpkg.com/lucide@0.383.0/dist/umd/lucide.js"></script>

  <link rel="icon" type="image/png" href="{{ asset('images/logo_mangkok.png') }}" />
  <link rel="stylesheet" href="{{ asset('css/talasaji.css') }}" />
  @yield('styles')
</head>
<body>
<div class="layout">

  <aside class="sidebar">
    <div class="sidebar-top">
      <a href="{{ route('admin.dashboard') }}" style="display:block;text-decoration:none;">
        <img src="{{ asset('images/Logo_Talasaji_1.png') }}" alt="TALASAJI" class="sidebar-logo"
             style="cursor:pointer;" />
      </a>
      <p class="brand-sub">Kurator Kuliner</p>
    </div>

    <nav class="sidebar-nav">
      <a href="{{ route('admin.dashboard') }}"
         class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i>
        Dasbor
      </a>
      <a href="{{ route('admin.umkm.index') }}"
         class="nav-item {{ request()->routeIs('admin.umkm.*') ? 'active' : '' }}">
        <i data-lucide="clipboard-list"></i>
        Daftar UMKM
      </a>
      <a href="{{ route('admin.settings') }}"
         class="nav-item {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
        <i data-lucide="settings"></i>
        Pengaturan
      </a>
    </nav>

    <div class="sidebar-bottom">
      <a href="https://wa.me/62859159871537" target="_blank" rel="noopener noreferrer" class="nav-item">
        <i data-lucide="help-circle"></i>
        Bantuan
      </a>
      <form method="POST" action="{{ route('admin.logout') }}" style="width:100%">
        @csrf
        <button type="submit" class="nav-item" style="width:100%;text-align:left;cursor:pointer;">
          <i data-lucide="log-out"></i>
          Keluar
        </button>
      </form>
    </div>
  </aside>

  <div class="main-area">
    @yield('content')
  </div>

</div>

<script>
  window.CSRF_TOKEN = "{{ csrf_token() }}";
  window.ADMIN_BASE = "{{ url('/admin') }}";
</script>

@yield('scripts')
<script>
  if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
<script>
  async function loadNotifCount() {
    try {
      const res = await fetch("{{ route('admin.notifications.count') }}");
      const data = await res.json();
      const badge = document.getElementById('notif-count');
      if (badge) {
        if (data.count > 0) {
          badge.textContent = data.count > 99 ? '99+' : data.count;
          badge.style.display = 'flex';
        } else {
          badge.style.display = 'none';
        }
      }
    } catch(e) {}
  }
  loadNotifCount();
</script>
</body>
</html>
