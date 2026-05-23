@extends('admin.layouts.app')
@section('title', 'Dashboard — TALASAJI')

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
      <h1>Curator's Overview</h1>
      <p class="subtitle">Here is the state of the TALASAJI ecosystem today.</p>
    </div>
    <span class="badge-update">Last updated: Just now</span>
  </div>

  <div class="stat-cards">
    <div class="stat-card">
      <div class="stat-card-header">
        <div class="icon-box"><i data-lucide="store"></i></div>
        @if($storeGrowth > 0)
            <span class="change positive">+{{ $storeGrowth }}% ↑</span>
        @elseif($storeGrowth < 0)
            <span class="change negative">{{ $storeGrowth }}% ↓</span>
        @else
            <span class="change neutral">Stable —</span>
        @endif
      </div>
      <p class="stat-label">Total UMKM Partners</p>
      <p class="stat-value">{{ $totalStores }}</p>
    </div>
    <div class="stat-card">
      <div class="stat-card-header">
        <div class="icon-box"><i data-lucide="receipt-text"></i></div>
        @if($orderGrowth > 0)
            <span class="change positive">+{{ $orderGrowth }}% ↑</span>
        @elseif($orderGrowth < 0)
            <span class="change negative">{{ $orderGrowth }}% ↓</span>
        @else
            <span class="change neutral">Stable —</span>
        @endif
      </div>
      <p class="stat-label">Total Transactions</p>
      <p class="stat-value">{{ number_format($totalOrders) }}</p>
    </div>
    <div class="stat-card">
      <div class="stat-card-header">
        <div class="icon-box"><i data-lucide="map"></i></div>
        @if($regionDiff > 0)
            <span class="change positive">+{{ $regionDiff }} region ↑</span>
        @elseif($regionDiff < 0)
            <span class="change negative">{{ $regionDiff }} region ↓</span>
        @else
            <span class="change neutral">Stable —</span>
        @endif
      </div>
      <p class="stat-label">Active Regions</p>
      <p class="stat-value">{{ $activeRegions }}</p>
    </div>
    <div class="stat-card">
      <div class="stat-card-header">
        <div class="icon-box warning"><i data-lucide="calendar-check"></i></div>
        <span class="change negative">{{ $totalPending > 0 ? '+' . $totalPending : '0' }}</span>
      </div>
      <p class="stat-label">Pending Approvals</p>
      <p class="stat-value">{{ $totalPending }}</p>
    </div>
  </div>

  <div class="bottom-section">
    <div class="chart-panel">
      <div class="chart-header">
        <div>
          <h2>Ecosystem Growth</h2>
          <p class="chart-subtitle">Total transaksi & revenue per bulan (6 bulan terakhir).</p>
        </div>
        <span class="badge-monthly" style="cursor:default;pointer-events:none;">Monthly</span>
      </div>
      <div class="chart-container">
        <canvas id="ecosystemChart"></canvas>
      </div>
    </div>

    <div class="activity-panel">
      <h2>Recent Activity</h2>
      <div class="activity-list">
        @forelse($recentOrders as $order)
          <div class="activity-item">
            <div class="activity-icon-wrap" style="background:#FFE6D2;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i data-lucide="shopping-bag" style="width:16px;height:16px;color:#FF7901;"></i>
            </div>
            <div class="activity-text">
              <p><b>{{ $order->store->name ?? 'Unknown' }}</b> — pesanan baru masuk.</p>
              <span class="activity-time">
                {{ $order->created_at->diffForHumans() }}
              </span>
            </div>
          </div>
        @empty
          <p style="font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;color:#aaa;text-align:center;padding:20px 0;">
            Belum ada aktivitas.
          </p>
        @endforelse
      </div>
      <a href="{{ route('admin.umkm.index') }}" class="view-all">View All Activity</a>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
const chartLabels   = @json($chartLabels);
const chartOrders   = @json($chartOrders);
const chartRevenue  = @json($chartRevenue);

const ctx = document.getElementById('ecosystemChart')?.getContext('2d');
if (ctx) {
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: chartLabels,
      datasets: [
        { label: 'Total Transaksi', data: chartOrders,   backgroundColor: '#9A1E22', borderRadius: 6 },
        { label: 'Revenue (jt)',    data: chartRevenue,  backgroundColor: '#FF7901', borderRadius: 6 },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, grid: { color: '#f0f0f0' } },
        x: { grid: { display: false } },
      },
    },
  });
}
</script>
@endsection
