/**
 * DX Plastic Group - Master Client JavaScript
 */

function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebarBackdrop');
  if (!sidebar) return;

  if (window.innerWidth <= 991.98) {
    const isOpen = sidebar.classList.toggle('mobile-open');
    if (backdrop) backdrop.classList.toggle('active', isOpen);
  } else {
    sidebar.classList.toggle('collapsed');
  }
}

function closeSidebar() {
  const sidebar = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebarBackdrop');
  if (sidebar) sidebar.classList.remove('mobile-open');
  if (backdrop) backdrop.classList.remove('active');
}

function toggleSubmenu(element) {
  if (!element) return;
  const parent = element.closest('.has-submenu');
  if (parent) {
    parent.classList.toggle('open');
  }
}

document.addEventListener("DOMContentLoaded", function () {
  // Khởi tạo trạng thái giao diện Sáng / Tối
  initThemeUI();

  // Tự động đóng sidebar mobile khi resize lên màn hình lớn
  window.addEventListener('resize', function () {
    if (window.innerWidth > 991.98) {
      closeSidebar();
    }
  });

  // Kiểm tra trạng thái xác thực người dùng
  checkAuthentication();
});

// Kiểm tra xác thực session qua API
async function checkAuthentication() {
  try {
    const response = await fetch('api/check_auth.php', {
      method: 'GET',
      credentials: 'same-origin'
    });

    if (!response.ok) return;
    const data = await response.json();

    if (!data.authenticated) {
      // Nếu chưa đăng nhập và không ở trang login
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('mainpage') !== 'authentication') {
        window.location.href = 'index.php?mainpage=authentication&subpage=login';
      }
      return;
    }

    // Cập nhật thông tin hiển thị nếu có
    displayUserInfo(data.user);
  } catch (error) {
    console.warn('Lưu ý khi kiểm tra phiên đăng nhập:', error);
  }
}

function displayUserInfo(user) {
  if (!user) return;
  const avatarEl = document.getElementById('headerUserAvatar');
  if (avatarEl) {
    const displayName = user.fullname || user.username || 'U';
    avatarEl.textContent = displayName.charAt(0).toUpperCase();
  }

  const nameEl = document.getElementById('headerUserName');
  if (nameEl) {
    nameEl.textContent = user.fullname || user.username || 'Người dùng';
  }
}

// Xử lý Đăng xuất
async function logout() {
  if (confirm('Bạn có chắc chắn muốn đăng xuất khỏi hệ thống?')) {
    try {
      const response = await fetch('api/logout.php', {
        method: 'POST',
        credentials: 'same-origin'
      });

      const data = await response.json().catch(() => ({ success: true }));
      if (data.success || response.ok) {
        localStorage.removeItem('loggedInUser');
        window.location.href = 'index.php?mainpage=authentication&subpage=login';
      } else {
        alert('Đã xảy ra lỗi khi đăng xuất: ' + (data.message || 'Thử lại sau'));
      }
    } catch (error) {
      console.error('Lỗi đăng xuất:', error);
      window.location.href = 'index.php?mainpage=authentication&subpage=login';
    }
  }
}

// ==========================================================================
// QUẢN LÝ CHẾ ĐỘ GIAO DIỆN SÁNG / TỐI (LIGHT / DARK THEME)
// ==========================================================================
function initThemeUI() {
  const currentTheme = document.documentElement.getAttribute('data-theme') || 
    localStorage.getItem('dx-theme') || 
    (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
  
  applyTheme(currentTheme, false);
}

function applyTheme(theme, save = true) {
  const isDark = (theme === 'dark');
  document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
  
  if (save) {
    localStorage.setItem('dx-theme', isDark ? 'dark' : 'light');
  }

  // Cập nhật trạng thái hiển thị của công tắc trong User Dropdown
  const switchIcon = document.getElementById('themeSwitchIcon');
  const switchLabel = document.getElementById('themeSwitchLabel');
  const quickIcon = document.getElementById('themeQuickIcon');
  const quickBtn = document.getElementById('themeQuickToggle');

  if (switchIcon) {
    switchIcon.textContent = isDark ? 'light_mode' : 'dark_mode';
    switchIcon.style.color = isDark ? '#f59e0b' : 'var(--dx-primary)';
  }
  if (switchLabel) {
    switchLabel.textContent = isDark ? 'Chế độ sáng' : 'Chế độ tối';
  }
  if (quickIcon) {
    quickIcon.textContent = isDark ? 'light_mode' : 'dark_mode';
    quickIcon.style.color = isDark ? '#f59e0b' : 'var(--dx-text-muted)';
  }
  if (quickBtn) {
    quickBtn.title = isDark ? 'Chuyển sang giao diện Sáng' : 'Chuyển sang giao diện Tối';
  }

  // Phát sự kiện toàn cục để các biểu đồ hay thành phần động cập nhật
  window.dispatchEvent(new CustomEvent('dxThemeChanged', { detail: { theme: isDark ? 'dark' : 'light' } }));
}

function toggleTheme(event) {
  if (event) {
    event.stopPropagation();
  }
  const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
  const newTheme = (currentTheme === 'dark') ? 'light' : 'dark';
  applyTheme(newTheme, true);
}

// ==========================================================================
// HỆ THỐNG PHÂN TRANG CHUẨN TOÀN DIỆN (STANDARDIZED PAGINATION SYSTEM)
// ==========================================================================

/**
 * Chuẩn hóa render giao diện phân trang toàn hệ thống DX Plastic
 * @param {HTMLElement|string} container - Container hoặc ID container
 * @param {Object} opts - { currentPage, totalPages, totalRecords, pageSize, pageSizeOptions, onPageChange, onPageSizeChange }
 */
function renderStandardPagination(container, opts) {
  const el = typeof container === 'string' ? document.getElementById(container) : container;
  if (!el) return;

  const current = Math.max(1, parseInt(opts.currentPage || 1));
  const totalRec = Math.max(0, parseInt(opts.totalRecords || 0));
  const size = Math.max(1, parseInt(opts.pageSize || 25));
  const totalPages = Math.max(1, parseInt(opts.totalPages || Math.ceil(totalRec / size) || 1));
  const sizeOpts = opts.pageSizeOptions || [10, 25, 50, 100];

  const startRec = totalRec === 0 ? 0 : (current - 1) * size + 1;
  const endRec = Math.min(current * size, totalRec);

  let html = `<div class="app-pagination-wrapper">`;

  // Bên trái: Thông tin bản ghi & Chọn kích thước trang
  html += `
    <div class="app-pagination-left">
      <div class="app-pagination-info">
        Hiển thị <strong>${startRec.toLocaleString()} - ${endRec.toLocaleString()}</strong> trên tổng số <strong>${totalRec.toLocaleString()}</strong> bản ghi
      </div>
  `;

  if (opts.onPageSizeChange) {
    html += `
      <div class="app-pagination-size">
        <span>Hiển thị:</span>
        <select class="app-pagination-select" data-role="page-size-select">
          ${sizeOpts.map(s => `<option value="${s}" ${s === size ? 'selected' : ''}>${s} / trang</option>`).join('')}
        </select>
      </div>
    `;
  }

  html += `</div>`;

  // Bên phải: Cụm nút chuyển trang
  html += `<div class="app-pagination-controls">`;

  // Nút Đầu «
  html += `<button type="button" class="app-page-btn" data-page="1" ${current === 1 ? 'disabled' : ''} title="Trang đầu">
    <span class="material-icons" style="font-size: 16px;">first_page</span>
  </button>`;

  // Nút Trước ‹
  html += `<button type="button" class="app-page-btn" data-page="${current - 1}" ${current === 1 ? 'disabled' : ''} title="Trang trước">
    <span class="material-icons" style="font-size: 16px;">chevron_left</span>
  </button>`;

  // Các số trang (tối đa 5 nút trang hiển thị)
  const maxButtons = 5;
  let startPage = Math.max(1, current - 2);
  let endPage = Math.min(totalPages, startPage + maxButtons - 1);
  if (endPage - startPage < maxButtons - 1) {
    startPage = Math.max(1, endPage - maxButtons + 1);
  }

  if (startPage > 1) {
    html += `<button type="button" class="app-page-btn" data-page="1">1</button>`;
    if (startPage > 2) {
      html += `<span class="app-page-ellipsis">&hellip;</span>`;
    }
  }

  for (let p = startPage; p <= endPage; p++) {
    const isActive = (p === current);
    html += `<button type="button" class="app-page-btn ${isActive ? 'active' : ''}" data-page="${p}">${p}</button>`;
  }

  if (endPage < totalPages) {
    if (endPage < totalPages - 1) {
      html += `<span class="app-page-ellipsis">&hellip;</span>`;
    }
    html += `<button type="button" class="app-page-btn" data-page="${totalPages}">${totalPages}</button>`;
  }

  // Nút Sau ›
  html += `<button type="button" class="app-page-btn" data-page="${current + 1}" ${current === totalPages ? 'disabled' : ''} title="Trang sau">
    <span class="material-icons" style="font-size: 16px;">chevron_right</span>
  </button>`;

  // Nút Cuối »
  html += `<button type="button" class="app-page-btn" data-page="${totalPages}" ${current === totalPages ? 'disabled' : ''} title="Trang cuối">
    <span class="material-icons" style="font-size: 16px;">last_page</span>
  </button>`;

  html += `</div></div>`;

  el.innerHTML = html;

  // Gắn sự kiện click
  el.querySelectorAll('.app-page-btn[data-page]').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      if (this.disabled || this.classList.contains('active')) return;
      const targetPage = parseInt(this.getAttribute('data-page'));
      if (typeof opts.onPageChange === 'function' && targetPage >= 1 && targetPage <= totalPages) {
        opts.onPageChange(targetPage);
      }
    });
  });

  const sizeSelect = el.querySelector('[data-role="page-size-select"]');
  if (sizeSelect && typeof opts.onPageSizeChange === 'function') {
    sizeSelect.addEventListener('change', function() {
      const newSize = parseInt(this.value);
      opts.onPageSizeChange(newSize);
    });
  }
}

/**
 * Tiện ích tạo phân trang Client-side nhanh cho bất kỳ bảng HTML nào
 * @param {Object} config - { tbodyId, paginationId, data, renderRow, defaultPageSize, pageSizeOptions, colSpan, emptyMessage, filterFn }
 */
function createClientTablePagination(config) {
  let currentPage = 1;
  let pageSize = config.defaultPageSize || 25;
  let allData = config.data || [];
  let filteredData = allData;

  function refresh() {
    if (typeof config.filterFn === 'function') {
      filteredData = allData.filter(config.filterFn);
    } else {
      filteredData = allData;
    }
    const totalRecords = filteredData.length;
    const totalPages = Math.max(1, Math.ceil(totalRecords / pageSize));
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    const startIdx = totalRecords === 0 ? 0 : (currentPage - 1) * pageSize;
    const pageItems = filteredData.slice(startIdx, startIdx + pageSize);

    const tbody = typeof config.tbodyId === 'string' ? document.getElementById(config.tbodyId) : config.tbodyId;
    if (tbody) {
      if (pageItems.length === 0) {
        tbody.innerHTML = `<tr><td colspan="${config.colSpan || 10}" class="text-center py-4 text-muted">${config.emptyMessage || 'Không tìm thấy dữ liệu'}</td></tr>`;
      } else {
        tbody.innerHTML = pageItems.map((item, index) => config.renderRow(item, startIdx + index + 1)).join('');
      }
    }

    renderStandardPagination(config.paginationId, {
      currentPage,
      totalPages,
      totalRecords,
      pageSize,
      pageSizeOptions: config.pageSizeOptions || [10, 25, 50, 100],
      onPageChange: (newPage) => {
        currentPage = newPage;
        refresh();
      },
      onPageSizeChange: (newSize) => {
        pageSize = newSize;
        currentPage = 1;
        refresh();
      }
    });
  }

  refresh();

  return {
    setData: (newData) => {
      allData = newData || [];
      currentPage = 1;
      refresh();
    },
    setPage: (p) => {
      currentPage = p;
      refresh();
    },
    setFilter: (fn) => {
      config.filterFn = fn;
      currentPage = 1;
      refresh();
    },
    refresh: () => {
      refresh();
    },
    getCurrentData: () => filteredData
  };
}
