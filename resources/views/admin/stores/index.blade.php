@extends('admin.layouts.app')
@section('title', 'UMKM List — TALASAJI')

@section('content')

<header class="topbar">
  <span class="topbar-title">Merchant Console</span>
  <div class="search-bar">
    <i data-lucide="search"></i>
    <input type="text" id="search-input" placeholder="Search partners..." />
  </div>
  <div class="topbar-right">
    <a href="{{ route('admin.settings') }}" style="display:block;text-decoration:none;">
      <div class="avatar" style="cursor:pointer;">
        <img src="https://i.pravatar.cc/100?img=12" alt="Admin" />
      </div>
    </a>
  </div>
</header>

<main class="content umkm-content">

  <div class="umkm-header">
    <div>
      <h1 class="page-title">Curated Partners</h1>
      <p class="page-subtitle">Oversee your collection of culinary artisans. Manage profiles, track status, and expand the TALASAJI ecosystem with precision.</p>
    </div>
  </div>

  <div class="umkm-toolbar">
    <div class="tab-group">
      <button class="tab-btn active" data-filter="all">All Partners</button>
      <button class="tab-btn" data-filter="active">Active</button>
      <button class="tab-btn" data-filter="pending">Pending</button>
    </div>
    <div class="umkm-meta">
      <span class="meta-badge">
        <i data-lucide="trending-up"></i>
        <span id="new-this-month">{{ $pending->count() }} New this month</span>
      </span>
      <span class="meta-badge">
        <i data-lucide="calendar"></i>
        <span id="total-count">{{ $totalAll }} Total</span>
      </span>
    </div>
  </div>

  <div class="umkm-body" id="umkm-body">
    <div class="umkm-left" id="umkm-left">

      <div id="table-view">
        <div class="partners-table-wrap">
          <table class="partners-table">
            <thead>
              <tr>
                <th>BRAND &amp; IDENTITY</th>
                <th>CATEGORY</th>
                <th>PRIMARY LOCATION</th>
                <th>STATUS</th>
                <th>ACTIONS</th>
              </tr>
            </thead>
            <tbody id="partners-tbody"></tbody>
          </table>
        </div>
      </div>

      <div id="pending-view" style="display:none;">
        <div id="pending-list"></div>
      </div>

      <div class="pagination-row" id="pagination-row">
        <span class="showing-label" id="showing-label"></span>
        <div class="pagination" id="pagination">
          <button class="page-btn prev" id="prev-btn"><i data-lucide="chevron-left"></i></button>
          <div id="page-buttons"></div>
          <button class="page-btn next" id="next-btn"><i data-lucide="chevron-right"></i></button>
        </div>
      </div>
    </div>

    <div class="queue-panel" id="queue-panel" style="display:none;">
      <h3 class="queue-title">Queue Overview</h3>
      <p class="queue-sub">TOTAL PENDING</p>
      <div class="queue-number" id="queue-total">{{ $totalPending }}</div>
      <div class="queue-stat">
        <span class="queue-stat-label">Avg. Time in Queue</span>
        <span class="queue-stat-value">{{ round($avgDaysInQueue, 1) }} Days</span>
      </div>
      <div class="queue-stat">
        <span class="queue-stat-label">Requires Action</span>
        <span class="queue-stat-value orange" id="queue-action">{{ $requiresAction }} Apps</span>
      </div>
    </div>
  </div>
</main>

<div class="modal-overlay" id="modal-overlay">
  <div class="modal-box" id="partner-modal">
    <div class="modal-header">
      <h2 class="modal-title">Partner Details Form</h2>
      <button class="modal-close" id="modal-close"><i data-lucide="x"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Business Name</label>
        <input type="text" class="form-input" id="modal-name" readonly />
      </div>
      <div class="form-group">
        <label class="form-label">Location</label>
        <div class="form-input-icon">
          <i data-lucide="map-pin"></i>
          <input type="text" class="form-input icon-input" id="modal-location" readonly />
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Category</label>
          <input type="text" class="form-input" id="modal-category" readonly />
        </div>
        <div class="form-group">
          <label class="form-label">Number of Menu Items</label>
          <input type="text" class="form-input" id="modal-menu-count" readonly />
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Current Rating</label>
        <div class="rating-box">
          <div class="stars" id="modal-stars"></div>
          <span class="rating-value" id="modal-rating-value">-</span>
          <span class="rating-reviews" id="modal-reviews">-</span>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-back" id="modal-back">Back</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="reject-overlay">
  <div class="modal-box reject-modal">
    <div class="modal-header">
      <h2 class="modal-title">Reject Application</h2>
      <button class="modal-close" id="reject-close"><i data-lucide="x"></i></button>
    </div>
    <div class="modal-body">
      <p class="reject-intro">Berikan alasan penolakan yang jelas agar UMKM dapat memperbaiki pendaftarannya.</p>
      <div class="form-group">
        <label class="form-label">Nama Toko</label>
        <input type="text" class="form-input" id="reject-store-name" readonly />
      </div>
      <div class="form-group">
        <label class="form-label">Alasan Penolakan <span style="color:#9A1E22">*</span></label>
        <textarea class="form-input form-textarea" id="reject-reason" rows="4"
          placeholder="Contoh: Dokumen izin usaha tidak lengkap..."></textarea>
        <span class="field-hint">Min. 20 karakter.</span>
      </div>
      <div class="form-group">
        <label class="form-label">Kategori Masalah</label>
        <select class="form-input form-select" id="reject-category">
          <option value="">-- Pilih kategori --</option>
          <option value="dokumen">Dokumen tidak lengkap</option>
          <option value="foto">Foto tidak memenuhi standar</option>
          <option value="lokasi">Informasi lokasi tidak valid</option>
          <option value="kategori">Kategori usaha tidak sesuai</option>
          <option value="lainnya">Lainnya</option>
        </select>
      </div>
    </div>
    <div class="modal-footer" style="justify-content:space-between;">
      <button class="btn-cancel" id="reject-cancel">Batal</button>
      <button class="btn-reject-submit" id="reject-submit">Kirim Penolakan</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="review-overlay">
  <div class="modal-box review-modal">
    <div class="modal-header">
      <h2 class="modal-title">Review Details</h2>
      <button class="modal-close" id="review-close"><i data-lucide="x"></i></button>
    </div>
    <div class="modal-body review-modal-body">
      <div class="review-photo-wrap" id="review-photo-wrap">
        <img id="review-photo" src="" alt="Foto Toko" class="review-photo" style="display:none;" />
        <div class="review-photo-placeholder" id="review-photo-placeholder">
          <i data-lucide="image-off"></i><span>Tidak ada foto</span>
        </div>
      </div>
      <div class="review-grid">
        <div class="form-group">
          <label class="form-label">Nama Toko</label>
          <input type="text" class="form-input" id="rv-nama" readonly />
        </div>
        <div class="form-group">
          <label class="form-label">No. Telepon</label>
          <div class="form-input-icon">
            <i data-lucide="phone"></i>
            <input type="text" class="form-input icon-input" id="rv-telepon" readonly />
          </div>
        </div>
        <div class="form-group" style="grid-column:1/-1;">
          <label class="form-label">Deskripsi</label>
          <textarea class="form-input form-textarea" id="rv-deskripsi" readonly rows="3"></textarea>
        </div>
        <div class="form-group" style="grid-column:1/-1;">
          <label class="form-label">Alamat</label>
          <div class="form-input-icon">
            <i data-lucide="map-pin"></i>
            <input type="text" class="form-input icon-input" id="rv-alamat" readonly />
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Latitude</label>
          <input type="text" class="form-input" id="rv-latitude" readonly />
        </div>
        <div class="form-group">
          <label class="form-label">Longitude</label>
          <input type="text" class="form-input" id="rv-longitude" readonly />
        </div>
        <div class="form-group">
          <label class="form-label">Jam Buka</label>
          <div class="form-input-icon">
            <i data-lucide="clock"></i>
            <input type="text" class="form-input icon-input" id="rv-jam-buka" readonly />
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Jam Tutup</label>
          <div class="form-input-icon">
            <i data-lucide="clock"></i>
            <input type="text" class="form-input icon-input" id="rv-jam-tutup" readonly />
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Nama Pemilik</label>
          <div class="form-input-icon">
            <i data-lucide="user"></i>
            <input type="text" class="form-input icon-input" id="rv-pemilik" readonly />
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <input type="text" class="form-input" id="rv-status" readonly />
        </div>
      </div>
    </div>
    <div class="modal-footer" style="justify-content:space-between;gap:12px;">
      <button class="btn-cancel" id="review-back">Tutup</button>
      <div style="display:flex;gap:10px;">
        <button class="btn-reject-outline" id="review-reject-btn">Reject</button>
        <button class="btn-back" id="review-approve-btn">Approve</button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
window.ALL_PARTNERS  = @json($allPartnersJson);
window.TOTAL_PENDING = {{ $totalPending }};
window.QUEUE_ACTION  = {{ $requiresAction }};
window.CSRF_TOKEN    = "{{ csrf_token() }}";
window.ADMIN_BASE    = "{{ url('/admin') }}";
</script>
<script src="{{ asset('js/umkm.js') }}"></script>
@endsection
