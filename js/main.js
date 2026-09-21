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
