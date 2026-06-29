let allPartners   = window.ALL_PARTNERS || [];
let filtered      = [];
let currentPage   = 1;
const PER_PAGE    = 3;
let currentFilter = 'all';

let pendingPartners  = allPartners.filter(p => p.regStatus === 'pending');
let allPendingPartners = allPartners.filter(p => p.regStatus === 'pending');
let currentPendingPage = 1;
const PENDING_PER_PAGE = 3;
let rejectTargetId  = null;
let reviewTargetId  = null;

async function patchAdmin(path, body) {
  try {
    const res = await fetch(window.ADMIN_BASE + path, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': window.CSRF_TOKEN,
      },
      body: JSON.stringify(body),
    });
    return await res.json();
  } catch (e) {
    console.error('Error:', e);
    return null;
  }
}

function applyFilter(filter) {
  currentFilter = filter;
  currentPage   = 1;
  if (filter === 'all')         filtered = [...allPartners];
  else if (filter === 'active') filtered = allPartners.filter(p => p.regStatus === 'active');
  renderTable();
  renderPagination();
}

document.getElementById('search-input').addEventListener('input', function () {
  const q = this.value.toLowerCase().trim();

  if (currentFilter === 'pending') {
    if (!q) {
      pendingPartners    = [...allPendingPartners];
      currentPendingPage = 1;
      renderPendingList();
      return;
    }
    pendingPartners = allPendingPartners.filter(p =>
      p.name.toLowerCase().includes(q) ||
      (p.category || '').toLowerCase().includes(q) ||
      (p.location || '').toLowerCase().includes(q)
    );
    currentPendingPage = 1;
    renderPendingList();
    return;
  }

  if (!q) {
    applyFilter(currentFilter);
    return;
  }

  let base = allPartners;
  if (currentFilter === 'active') {
    base = allPartners.filter(p => p.regStatus === 'active');
  }

  filtered = base.filter(p =>
    p.name.toLowerCase().includes(q) ||
    (p.category || '').toLowerCase().includes(q) ||
    (p.location || '').toLowerCase().includes(q)
  );
  currentPage = 1;
  renderTable();
  renderPagination();
});

function renderTable() {
  const tbody = document.getElementById('partners-tbody');
  const start = (currentPage - 1) * PER_PAGE;
  const pageItems = filtered.slice(start, start + PER_PAGE);
  tbody.innerHTML = '';

  if (!pageItems.length) {
    tbody.innerHTML = `<tr><td colspan="5" class="empty-state">Tidak ada mitra ditemukan.</td></tr>`;
    document.getElementById('showing-label').textContent = 'Tidak ada hasil';
    return;
  }

  const statusLabels = { Active:'Aktif', Pending:'Menunggu', Inactive:'Tidak Aktif' };
  pageItems.forEach(p => {
    const tr = document.createElement('tr');
    tr.className = 'partner-row';
    tr.innerHTML = `
      <td class="col-brand">
        <div class="brand-cell">
          <div class="partner-avatar">
            ${p.image
            ? `<img src="${p.image}" alt="${p.name}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" /><span class="avatar-initials" style="display:none;">${p.name.charAt(0).toUpperCase()}</span>`
            : `<span class="avatar-initials">${p.name.charAt(0).toUpperCase()}</span>`
          }
          </div>
          <div class="brand-info">
            <span class="brand-name">${p.name}</span>
            <span class="brand-id">ID: ${p.umkId}</span>
          </div>
        </div>
      </td>
      <td class="col-category"><span class="category-badge">${p.category || '-'}</span></td>
      <td class="col-location">
        <span class="location-cell"><i data-lucide="map-pin"></i> ${p.location}</span>
      </td>
      <td class="col-status">
        <span class="status-dot ${p.status === 'Active' ? 'dot-active' : 'dot-inactive'}"></span>
        <span class="status-label">${statusLabels[p.status] || p.status}</span>
      </td>
      <td class="col-actions">
        <button class="action-eye" data-id="${p.id}" title="View Details">
          <i data-lucide="eye"></i>
        </button>
      </td>
    `;
    tbody.appendChild(tr);
  });

  lucide.createIcons();

  document.querySelectorAll('.action-eye').forEach(btn => {
    btn.addEventListener('click', () => {
      const partner = allPartners.find(p => p.id == btn.dataset.id);
      if (partner) openPartnerModal(partner);
    });
  });

  const end = Math.min(start + PER_PAGE, filtered.length);
  document.getElementById('showing-label').textContent =
    `Menampilkan ${start + 1}–${end} dari ${filtered.length} Mitra`;
}

function renderPagination() {
  const total = Math.ceil(filtered.length / PER_PAGE);
  const container = document.getElementById('page-buttons');
  container.innerHTML = '';
  for (let i = 1; i <= Math.min(total, 5); i++) {
    const btn = document.createElement('button');
    btn.className = `page-btn num-btn ${i === currentPage ? 'active' : ''}`;
    btn.textContent = i;
    btn.addEventListener('click', () => { currentPage = i; renderTable(); renderPagination(); });
    container.appendChild(btn);
  }
  document.getElementById('prev-btn').disabled = currentPage <= 1;
  document.getElementById('next-btn').disabled = currentPage >= total;
}

document.getElementById('prev-btn').addEventListener('click', () => {
  if (currentFilter === 'pending') {
    if (currentPendingPage > 1) {
      currentPendingPage--;
      renderPendingList();
    }
  } else {
    if (currentPage > 1) {
      currentPage--;
      renderTable();
      renderPagination();
    }
  }
});

document.getElementById('next-btn').addEventListener('click', () => {
  if (currentFilter === 'pending') {
    const total = Math.ceil(pendingPartners.length / PENDING_PER_PAGE);
    if (currentPendingPage < total) {
      currentPendingPage++;
      renderPendingList();
    }
  } else {
    const total = Math.ceil(filtered.length / PER_PAGE);
    if (currentPage < total) {
      currentPage++;
      renderTable();
      renderPagination();
    }
  }
});

function openPartnerModal(p) {
  document.getElementById('modal-name').value     = p.name;
  document.getElementById('modal-location').value = p.location;
  document.getElementById('modal-category').value = p.category || '-';
  document.getElementById('modal-menu-count').value = p.menuCount ?? '-';
  document.getElementById('modal-rating-value').textContent = '-';
  document.getElementById('modal-reviews').textContent = '(belum ada ulasan)';
  document.getElementById('modal-stars').innerHTML = '';

  const photoEl = document.getElementById('modal-photo');
  const placeholderEl = document.getElementById('modal-photo-placeholder');
  if (p.image) {
    photoEl.src = p.image;
    photoEl.style.display = 'block';
    placeholderEl.style.display = 'none';
  } else {
    photoEl.style.display = 'none';
    placeholderEl.style.display = 'flex';
  }

  document.getElementById('modal-overlay').classList.add('open');
  document.body.style.overflow = 'hidden';
  lucide.createIcons();
}

document.getElementById('modal-close').addEventListener('click', () => {
  document.getElementById('modal-overlay').classList.remove('open');
  document.body.style.overflow = '';
});
document.getElementById('modal-back').addEventListener('click', () => {
  document.getElementById('modal-overlay').classList.remove('open');
  document.body.style.overflow = '';
});
document.getElementById('modal-overlay').addEventListener('click', function(e) {
  if (e.target === this) { this.classList.remove('open'); document.body.style.overflow = ''; }
});

function renderPendingList() {
  const container = document.getElementById('pending-list');
  const start = (currentPendingPage - 1) * PENDING_PER_PAGE;
  const pageItems = pendingPartners.slice(start, start + PENDING_PER_PAGE);
  container.innerHTML = '';

  if (!pageItems.length) {
    container.innerHTML = `<div class="empty-pending"><i data-lucide="check-circle-2"></i><p>Tidak ada pendaftaran yang menunggu persetujuan.</p></div>`;
    lucide.createIcons();
    document.getElementById('showing-label').textContent = 'Tidak ada data';
    return;
  }

  pageItems.forEach(p => {
    const appliedDate = new Date(p.appliedAt).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    const card = document.createElement('div');
    card.className = 'pending-card';
    card.dataset.id = p.id;
    card.innerHTML = `
      <div class="pending-card-inner">
        <div class="pending-photo">
          ${p.image
            ? `<img src="${p.image}" alt="${p.name}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" /><span class="avatar-initials" style="display:none;">${p.name.charAt(0).toUpperCase()}</span>`
            : `<span class="avatar-initials">${p.name.charAt(0).toUpperCase()}</span>`
          }
        </div>
        <div class="pending-info">
          <div class="pending-name-row">
            <span class="pending-name">${p.name}</span>
            ${p.isNew ? `<span class="badge-new">NEW</span>` : ''}
          </div>
          <p class="pending-meta">Pemilik: ${p.owner} · Mendaftar: ${appliedDate}</p>
          <div class="pending-tags">
            <span class="pending-tag"><i data-lucide="store"></i> ${p.category || '-'}</span>
            <span class="pending-tag"><i data-lucide="utensils"></i> ${p.layanan || '-'}</span>
          </div>
        </div>
        <div class="pending-actions">
          <button class="btn-approve" data-id="${p.id}">Setujui</button>
          <button class="btn-reject-card" data-id="${p.id}">Tolak</button>
          <button class="btn-review" data-id="${p.id}">Detail</button>
        </div>
      </div>
    `;
    container.appendChild(card);
  });

  lucide.createIcons();

  document.querySelectorAll('.btn-approve').forEach(btn => btn.addEventListener('click', () => handleApprove(btn.dataset.id)));
  document.querySelectorAll('.btn-reject-card').forEach(btn => btn.addEventListener('click', () => openRejectModal(btn.dataset.id)));
  document.querySelectorAll('.btn-review').forEach(btn => btn.addEventListener('click', () => openReviewModal(btn.dataset.id)));

  const end = Math.min(start + PENDING_PER_PAGE, pendingPartners.length);
  document.getElementById('showing-label').textContent = `Menampilkan ${start + 1}–${end} dari ${pendingPartners.length} Mitra`;
  renderPendingPagination();
}

function renderPendingPagination() {
  const total = Math.ceil(pendingPartners.length / PENDING_PER_PAGE);
  const container = document.getElementById('page-buttons');
  container.innerHTML = '';
  for (let i = 1; i <= Math.min(total, 5); i++) {
    const btn = document.createElement('button');
    btn.className = `page-btn num-btn ${i === currentPendingPage ? 'active' : ''}`;
    btn.textContent = i;
    btn.addEventListener('click', () => { currentPendingPage = i; renderPendingList(); });
    container.appendChild(btn);
  }
  document.getElementById('prev-btn').disabled = currentPendingPage <= 1;
  document.getElementById('next-btn').disabled = currentPendingPage >= total;
}

function updateQueueStats() {
  const totalEl = document.getElementById('queue-total');
  const actionEl = document.getElementById('queue-action');
  if (totalEl) totalEl.textContent = pendingPartners.length;
  if (actionEl) actionEl.textContent = `${pendingPartners.filter(p => p.isAging).length} UMKM`;
}

async function handleApprove(id) {
  const card = document.querySelector(`.pending-card[data-id="${id}"]`);
  if (card) card.querySelector('.pending-actions').innerHTML = `<span class="approve-success">✓ Approved!</span>`;

  await patchAdmin(`/umkm/${id}/approve`, {});

  setTimeout(() => {
    pendingPartners    = pendingPartners.filter(p => p.id != id);
    allPartners        = allPartners.map(p => p.id == id ? { ...p, regStatus: 'active', status: 'Active' } : p);
    allPendingPartners = allPendingPartners.filter(p => p.id != id);
    updateQueueStats();
    renderPendingList();
    showToast('UMKM berhasil disetujui dan sekarang Active ✓');
  }, 800);
}

function openRejectModal(id) {
  rejectTargetId = id;
  const p = pendingPartners.find(p => p.id == id);
  if (!p) return;
  document.getElementById('reject-store-name').value = p.name;
  document.getElementById('reject-reason').value = '';
  document.getElementById('reject-category').value = '';
  document.getElementById('reject-reason').style.borderColor = '';
  document.getElementById('reject-category').style.borderColor = '';
  document.getElementById('reject-overlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeRejectModal() {
  document.getElementById('reject-overlay').classList.remove('open');
  document.body.style.overflow = '';
  rejectTargetId = null;
}
document.getElementById('reject-close').addEventListener('click', closeRejectModal);
document.getElementById('reject-cancel').addEventListener('click', closeRejectModal);
document.getElementById('reject-overlay').addEventListener('click', function(e) { if (e.target === this) closeRejectModal(); });
document.getElementById('reject-submit').addEventListener('click', async () => {
  const reason   = document.getElementById('reject-reason').value.trim();
  const category = document.getElementById('reject-category').value;
  let valid = true;
  if (reason.length < 20) { document.getElementById('reject-reason').style.borderColor = '#9A1E22'; valid = false; }
  else document.getElementById('reject-reason').style.borderColor = '';
  if (!category) { document.getElementById('reject-category').style.borderColor = '#9A1E22'; valid = false; }
  else document.getElementById('reject-category').style.borderColor = '';
  if (!valid) return;

  await patchAdmin(`/umkm/${rejectTargetId}/reject`, { rejection_reason: reason, rejection_category: category });
  pendingPartners    = pendingPartners.filter(p => p.id != rejectTargetId);
  allPartners        = allPartners.filter(p => p.id != rejectTargetId);
  allPendingPartners = allPendingPartners.filter(p => p.id != rejectTargetId);
  updateQueueStats();
  renderPendingList();
  closeRejectModal();
  showToast('Penolakan berhasil dikirim ke pendaftar');
});

function openReviewModal(id) {
  reviewTargetId = id;
  const p = pendingPartners.find(p => p.id == id);
  if (!p) return;
  document.getElementById('rv-nama').value      = p.name;
  document.getElementById('rv-deskripsi').value = p.description || '-';
  document.getElementById('rv-alamat').value    = p.location;
  document.getElementById('rv-latitude').value  = p.latitude || '-';
  document.getElementById('rv-longitude').value = p.longitude || '-';
  document.getElementById('rv-telepon').value   = p.telepon;
  document.getElementById('rv-jam-buka').value  = p.jamBuka;
  document.getElementById('rv-jam-tutup').value = p.jamTutup;
  document.getElementById('rv-pemilik').value   = p.owner;
  document.getElementById('rv-status').value    = 'Menunggu Persetujuan';
  const photoEl = document.getElementById('review-photo');
  const placeholderEl = document.getElementById('review-photo-placeholder');
  if (p.image) { photoEl.src = p.image; photoEl.style.display = 'block'; placeholderEl.style.display = 'none'; }
  else { photoEl.style.display = 'none'; placeholderEl.style.display = 'flex'; }
  document.getElementById('review-overlay').classList.add('open');
  document.body.style.overflow = 'hidden';
  lucide.createIcons();
}
function closeReviewModal() {
  document.getElementById('review-overlay').classList.remove('open');
  document.body.style.overflow = '';
  reviewTargetId = null;
}
document.getElementById('review-close').addEventListener('click', closeReviewModal);
document.getElementById('review-back').addEventListener('click', closeReviewModal);
document.getElementById('review-overlay').addEventListener('click', function(e) { if (e.target === this) closeReviewModal(); });
document.getElementById('review-approve-btn').addEventListener('click', () => { closeReviewModal(); handleApprove(reviewTargetId); });
document.getElementById('review-reject-btn').addEventListener('click', () => { closeReviewModal(); openRejectModal(reviewTargetId); });

function showToast(message) {
  const existing = document.getElementById('toast-notif');
  if (existing) existing.remove();
  const toast = document.createElement('div');
  toast.id = 'toast-notif';
  toast.className = 'toast-notif';
  toast.textContent = message;
  document.body.appendChild(toast);
  setTimeout(() => toast.classList.add('toast-show'), 10);
  setTimeout(() => { toast.classList.remove('toast-show'); setTimeout(() => toast.remove(), 300); }, 3000);
}

document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const filter = btn.dataset.filter;
    if (filter === 'pending') {
      currentFilter = 'pending';
      document.getElementById('table-view').style.display  = 'none';
      document.getElementById('pending-view').style.display = 'block';
      document.getElementById('queue-panel').style.display  = 'block';
      document.getElementById('umkm-body').classList.add('two-col');
      currentPendingPage = 1;
      renderPendingList();
    } else {
      document.getElementById('table-view').style.display  = 'block';
      document.getElementById('pending-view').style.display = 'none';
      document.getElementById('queue-panel').style.display  = 'none';
      document.getElementById('umkm-body').classList.remove('two-col');
      applyFilter(filter);
    }
  });
});

applyFilter('all');
