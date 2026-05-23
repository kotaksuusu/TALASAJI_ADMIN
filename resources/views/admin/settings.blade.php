@extends('admin.layouts.app')
@section('title', 'Settings — TALASAJI')

@section('styles')
<style>
.settings-body {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 24px;
  align-items: start;
}
.settings-card {
  background: #ffffff;
  border-radius: 16px;
  padding: 28px 32px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
  margin-bottom: 24px;
}
.settings-card:last-child { margin-bottom: 0; }
.settings-card-title {
  font-family: 'Playfair Display', serif;
  font-size: 18px;
  font-weight: 700;
  color: #9A1E22;
  margin-bottom: 4px;
}
.settings-card-sub {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 13px;
  color: #888;
  margin-bottom: 24px;
}
.avatar-section {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 28px;
  padding-bottom: 24px;
  border-bottom: 1px solid #f0ebe6;
}
.avatar-preview {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  overflow: hidden;
  background: #FFE6D2;
  flex-shrink: 0;
  border: 3px solid #FFD4B8;
}
.avatar-preview img { width: 100%; height: 100%; object-fit: cover; }
.avatar-name {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 15px;
  font-weight: 700;
  color: #1A1A1A;
  display: block;
  margin-bottom: 4px;
}
.avatar-role {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 12px;
  color: #888;
}
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}
.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 16px;
}
.form-group:last-of-type { margin-bottom: 0; }
.form-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid #f0ebe6;
}
.right-col { display: flex; flex-direction: column; gap: 24px; }
.info-card {
  background: #ffffff;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.info-card-logo {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
  padding-bottom: 20px;
  border-bottom: 1px solid #f0ebe6;
}
.info-card-logo img { width: 40px; height: 40px; object-fit: contain; }
.info-card-logo-name {
  font-family: 'Playfair Display', serif;
  font-size: 16px;
  font-weight: 700;
  color: #9A1E22;
}
.info-card-logo-sub {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 11px;
  color: #888;
}
.info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid #f5f0ec;
}
.info-item:last-child { border-bottom: none; }
.info-label {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 12px;
  color: #888;
}
.info-value {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 13px;
  font-weight: 600;
  color: #1A1A1A;
}
.info-value.active { color: #2BA67B; }
.info-value.active::before {
  content: '';
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #2BA67B;
  display: inline-block;
  margin-right: 5px;
}
.strength-bar-wrap {
  height: 4px;
  border-radius: 2px;
  background: #f0ebe6;
  margin-top: 8px;
  overflow: hidden;
}
.strength-bar {
  height: 100%;
  border-radius: 2px;
  width: 0%;
  transition: width 0.3s, background 0.3s;
}
.strength-hint {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 11px;
  color: #888;
  margin-top: 5px;
}
.alert-success {
  background: #e6f7e6;
  border: 1px solid #b3e6b3;
  border-radius: 10px;
  padding: 12px 16px;
  margin-bottom: 20px;
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 13px;
  color: #2e7d32;
  display: flex;
  align-items: center;
  gap: 8px;
}
.alert-error {
  background: #fff0f0;
  border: 1px solid #f5c0c0;
  border-radius: 10px;
  padding: 12px 16px;
  margin-bottom: 20px;
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 13px;
  color: #9A1E22;
}
.divider { border: none; border-top: 1px solid #f0ebe6; margin: 20px 0; }

body,
.layout,
.main-area {
  overflow: visible !important;
  height: auto !important;
}
.sidebar {
  height: 100vh !important;
  position: sticky !important;
  top: 0 !important;
  overflow-y: auto;
}
</style>
@endsection

@section('content')

<header class="topbar">
  <div class="topbar-spacer"></div>
  <div class="topbar-actions">
    <a href="{{ route('admin.settings') }}" style="display:block;text-decoration:none;">
      <div class="avatar" style="cursor:pointer;">
        <img src="https://i.pravatar.cc/100?img=12" alt="Admin" />
      </div>
    </a>
  </div>
</header>

<div class="content-area">

  <div class="content-header">
    <div>
      <h1>Configuration & Preferences</h1>
      <p class="subtitle">Manage your profile, application settings, and account security.</p>
    </div>
  </div>

  @if(session('success'))
    <div class="alert-success">
      <i data-lucide="check-circle" style="width:16px;height:16px;flex-shrink:0;"></i>
      {{ session('success') }}
    </div>
  @endif
  @if(session('error'))
    <div class="alert-error">{{ session('error') }}</div>
  @endif
  @if($errors->any())
    <div class="alert-error">
      @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
  @endif

  <div class="settings-body">

    <div>

      <div class="settings-card">
        <div class="settings-card-title">Profile Settings</div>
        <div class="settings-card-sub">Update your application name, logo, and admin identity.</div>

        <div class="avatar-section">
          <div class="avatar-preview">
            <img src="https://i.pravatar.cc/100?img=12" alt="Admin" />
          </div>
          <div>
            <span class="avatar-name">{{ Auth::user()->name }}</span>
            <span class="avatar-role">Administrator · TALASAJI Culinary Curator</span>
          </div>
        </div>

        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
          @csrf
          @method('PUT')
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="app_name">App Name</label>
              <input type="text" id="app_name" name="app_name" class="form-input"
                     value="{{ old('app_name', $settings->app_name ?? 'TALASAJI') }}" />
            </div>
            <div class="form-group">
              <label class="form-label">Admin Email</label>
              <input type="text" class="form-input" value="{{ Auth::user()->email }}" readonly
                     style="background:#f9f6f3;color:#aaa;cursor:default;" />
            </div>
          </div>
          <div class="form-group">
            <label class="form-label" for="logo">App Logo</label>
            <input type="file" id="logo" name="logo" class="form-input" accept="image/*" style="padding:10px;" />
            @if(!empty($settings->logo))
              <div style="margin-top:10px;display:flex;align-items:center;gap:10px;">
                <img src="{{ asset('uploads/' . $settings->logo) }}" alt="Logo"
                     style="height:36px;border-radius:8px;border:1px solid #f0ebe6;" />
                <span style="font-family:'Plus Jakarta Sans',sans-serif;font-size:12px;color:#888;">Logo saat ini</span>
              </div>
            @endif
          </div>
          <div class="form-actions">
            <button type="submit" class="btn-back">Save Changes</button>
          </div>
        </form>
      </div>

      <div class="settings-card">
        <div class="settings-card-title">Security</div>
        <div class="settings-card-sub">Change your admin account password. Use a strong password to keep your account safe.</div>

        <form method="POST" action="{{ route('admin.settings.password') }}">
          @csrf
          @method('PUT')
          <div class="form-group">
            <label class="form-label" for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password"
                   class="form-input" placeholder="Enter your current password" autocomplete="current-password" />
          </div>
          <hr class="divider">
          <div class="form-group">
            <label class="form-label" for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password"
                   class="form-input" placeholder="Minimum 8 characters"
                   autocomplete="new-password" oninput="checkStrength(this.value)" />
            <div class="strength-bar-wrap">
              <div class="strength-bar" id="strength-bar"></div>
            </div>
            <span class="strength-hint" id="strength-label">Enter a new password</span>
          </div>
          <div class="form-group">
            <label class="form-label" for="new_password_confirmation">Confirm New Password</label>
            <input type="password" id="new_password_confirmation" name="new_password_confirmation"
                   class="form-input" placeholder="Re-enter new password" autocomplete="new-password" />
          </div>
          <div class="form-actions">
            <button type="submit" class="btn-back">Update Password</button>
          </div>
        </form>
      </div>

    </div>

    <div class="right-col">

      <div class="info-card">
        <div class="info-card-logo">
          <img src="{{ asset('images/logo_talasaji.png') }}" alt="TALASAJI" />
          <div>
            <div class="info-card-logo-name">{{ $settings->app_name ?? 'TALASAJI' }}</div>
            <div class="info-card-logo-sub">Culinary Curator Platform</div>
          </div>
        </div>
        <div class="info-item">
          <span class="info-label">Status Aplikasi</span>
          <span class="info-value active">Active</span>
        </div>
        <div class="info-item">
          <span class="info-label">Total UMKM Partner</span>
          <span class="info-value">{{ $totalStores }}</span>
        </div>
        <div class="info-item">
          <span class="info-label">Total Transaksi</span>
          <span class="info-value">{{ number_format($totalOrders) }}</span>
        </div>
        <div class="info-item">
          <span class="info-label">Pending Approval</span>
          <span class="info-value" style="{{ $totalPending > 0 ? 'color:#FF7901;' : '' }}">
            {{ $totalPending }}
          </span>
        </div>
        <div class="info-item">
          <span class="info-label">Active Regions</span>
          <span class="info-value">{{ $activeRegions }}</span>
        </div>
      </div>

      <div class="info-card">
        <div class="settings-card-title" style="margin-bottom:4px;">Platform Overview</div>
        <div class="settings-card-sub" style="margin-bottom:16px;">Ecosystem summary this month.</div>
        <div class="info-item">
          <span class="info-label">Transaksi Bulan Ini</span>
          <span class="info-value">{{ $ordersThisMonth }}</span>
        </div>
        <div class="info-item">
          <span class="info-label">Revenue Bulan Ini</span>
          <span class="info-value">Rp {{ number_format($revenueMtd, 0, ',', '.') }}</span>
        </div>
        <div class="info-item">
          <span class="info-label">UMKM Baru Bulan Ini</span>
          <span class="info-value">{{ $storesNewThisMonth }}</span>
        </div>
      </div>

    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
function checkStrength(val) {
  const bar   = document.getElementById('strength-bar');
  const label = document.getElementById('strength-label');
  if (!val.length) {
    bar.style.width = '0%'; bar.style.background = '#e0e0e0';
    label.textContent = 'Enter a new password'; label.style.color = '#888';
    return;
  }
  let s = 0;
  if (val.length >= 8)           s++;
  if (/[A-Z]/.test(val))         s++;
  if (/[0-9]/.test(val))         s++;
  if (/[^A-Za-z0-9]/.test(val))  s++;
  const cfg = [
    { w:'25%',  c:'#e53935', t:'Weak' },
    { w:'50%',  c:'#FF7901', t:'Fair' },
    { w:'75%',  c:'#FFC107', t:'Good' },
    { w:'100%', c:'#2BA67B', t:'Strong' },
  ];
  const r = cfg[Math.min(s - 1, 3)];
  bar.style.width = r.w; bar.style.background = r.c;
  label.textContent = r.t; label.style.color = r.c;
}
</script>
@endsection
