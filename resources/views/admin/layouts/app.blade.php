<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>@yield('title', 'TALASAJI Admin')</title>

  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet" />
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <link rel="stylesheet" href="{{ asset('css/talasaji.css') }}" />
  @yield('styles')
</head>
<body>
<div class="layout">

  <aside class="sidebar">
    <div class="sidebar-top">
      <a href="{{ route('admin.dashboard') }}" style="display:block;text-decoration:none;">
        <img src="{{ asset('images/logo_talasaji.png') }}" alt="TALASAJI" class="sidebar-logo"
             style="cursor:pointer;" />
      </a>
      <p class="brand-sub">Culinary Curator</p>
    </div>

    <nav class="sidebar-nav">
      <a href="{{ route('admin.dashboard') }}"
         class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <i data-lucide="layout-dashboard"></i>
        Dashboard
      </a>
      <a href="{{ route('admin.umkm.index') }}"
         class="nav-item {{ request()->routeIs('admin.umkm.*') ? 'active' : '' }}">
        <i data-lucide="clipboard-list"></i>
        UMKM List
      </a>
      <a href="{{ route('admin.settings') }}"
         class="nav-item {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
        <i data-lucide="settings"></i>
        Settings
      </a>
    </nav>

    <div class="sidebar-bottom">
      <a href="https://wa.me/62859159871537" target="_blank" rel="noopener noreferrer" class="nav-item">
        <i data-lucide="help-circle"></i>
        Support
      </a>
      <form method="POST" action="{{ route('admin.logout') }}" style="width:100%">
        @csrf
        <button type="submit" class="nav-item" style="width:100%;text-align:left;cursor:pointer;">
          <i data-lucide="log-out"></i>
          Logout
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
</body>
</html>
