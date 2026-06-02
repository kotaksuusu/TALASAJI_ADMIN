@extends('admin.layouts.app')
@section('title', 'Notifikasi — TALASAJI')

@section('content')

<header class="topbar">
  <div class="topbar-spacer"></div>
  <div class="topbar-actions">
    <a href="{{ route('admin.notifications.index') }}" class="notif-btn" id="notif-btn">
      <i data-lucide="bell"></i>
      <span class="notif-badge" id="notif-count" style="display:none;"></span>
    </a>
    <a href="{{ route('admin.settings') }}" style="display:block;text-decoration:none;">
      <div class="avatar" style="cursor:pointer;">
        <img src="https://i.pravatar.cc/100?img=12" alt="Admin" />
      </div>
    </a>
  </div>
</header>

<div class="content-area">
  <div class="notif-page-header">
    <h1>Notifikasi</h1>
    <p>Peringatan otomatis berdasarkan kondisi data platform.</p>
  </div>

  @if(count($notifications) > 0)
    <div class="notif-list">
      @foreach($notifications as $notif)
        <div class="notif-item {{ $notif['type'] }}">
          <div class="notif-icon {{ $notif['type'] }}">
            <i data-lucide="{{ $notif['icon'] }}"></i>
          </div>
          <div class="notif-body">
            <div class="notif-title">{{ $notif['title'] }}</div>
            <div class="notif-message">{{ $notif['message'] }}</div>
            <a href="{{ $notif['action_url'] }}" class="notif-action">
              <i data-lucide="arrow-right"></i>
              {{ $notif['action_label'] }}
            </a>
          </div>
        </div>
      @endforeach
    </div>
  @else
    <div class="notif-empty">
      <div><i data-lucide="bell-off"></i></div>
      <h3>Tidak Ada Notifikasi</h3>
      <p>Semua toko dalam kondisi baik. Tidak ada peringatan saat ini.</p>
    </div>
  @endif
</div>

@endsection
