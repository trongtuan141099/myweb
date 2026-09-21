<!-- MAIN WRAPPER START -->
<div class="main-wrapper">
  <header class="top-header">
    <!-- Nút mở menu trên thiết bị di động -->
    <button class="mobile-menu-btn d-lg-none" type="button" aria-label="Mở menu" onclick="toggleSidebar()">
      <span class="material-icons">menu</span>
    </button>

    <div class="header-actions">
      <!-- Menu Thông báo 5S real-time -->
      <div class="notification-menu">
        <button class="header-icon-btn" type="button" id="notificationToggle" aria-label="Thông báo" aria-expanded="false">
          <span class="material-icons">notifications_none</span>
          <span class="notification-badge d-none" id="notificationCount">0</span>
        </button>
        <div class="notification-dropdown" id="notificationDropdown" hidden>
          <div class="notification-header">
            <span>Thông báo công việc</span>
            <span class="small text-muted font-normal" id="notificationDate"></span>
          </div>
          <div class="notification-list" id="notificationList">
            <div class="notification-empty">Đang tải thông báo...</div>
          </div>
          <a class="notification-footer" href="index.php?mainpage=five_s&subpage=mobile_audit">
            Xem lịch kiểm tra 5S hôm nay
          </a>
        </div>
      </div>

      <!-- Nút đổi Theme 1 chạm nhanh trên headbar -->
      <button class="header-icon-btn" type="button" id="themeQuickToggle" onclick="toggleTheme(event)" title="Chuyển chế độ Sáng / Tối" aria-label="Đổi giao diện">
        <span class="material-icons" id="themeQuickIcon">dark_mode</span>
      </button>

      <!-- Menu Người dùng & Đăng xuất -->
      <div class="user-menu">
        <button class="user-menu-toggle" type="button" id="userMenuToggle" aria-expanded="false">
          <span class="header-avatar" id="headerUserAvatar">T</span>
          <span class="header-username" id="headerUserName"><?= htmlspecialchars($_SESSION['user']['username'] ?? 'User') ?></span>
          <span class="material-icons user-menu-arrow">expand_more</span>
        </button>
        <div class="user-dropdown" id="userDropdown" hidden>
          <div class="px-3 py-2 border-bottom">
            <div class="fw-bold small"><?= htmlspecialchars($_SESSION['user']['fullname'] ?? $_SESSION['user']['username'] ?? 'Tài khoản') ?></div>
            <div class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($_SESSION['user']['role'] ?? 'viewer') ?></div>
          </div>
          <a href="index.php?mainpage=system&subpage=roles" class="user-dropdown-item">
            <span class="material-icons">manage_accounts</span>
            <span>Phân quyền</span>
          </a>

          <!-- Nút Tùy Chỉnh Giao Diện Sáng / Tối -->
          <div class="user-dropdown-divider"></div>
          <div class="theme-switch-row" onclick="toggleTheme(event)" title="Chuyển đổi giao diện Sáng / Tối">
            <div class="theme-switch-info">
              <span class="material-icons" id="themeSwitchIcon">dark_mode</span>
              <span id="themeSwitchLabel">Chế độ tối</span>
            </div>
            <div class="theme-switch-toggle" id="themeSwitchToggle">
              <span class="theme-switch-knob"></span>
            </div>
          </div>
          <div class="user-dropdown-divider"></div>

          <button type="button" class="user-dropdown-item text-danger" onclick="logout()">
            <span class="material-icons">logout</span>
            <span>Đăng xuất</span>
          </button>
        </div>
      </div>
    </div>
  </header>

  <!-- CONTENT AREA START (Chứa nội dung của module) -->
  <main class="content-area">

  <script>
    (function () {
      const notificationToggle = document.getElementById('notificationToggle');
      const notificationDropdown = document.getElementById('notificationDropdown');
      const userMenuToggle = document.getElementById('userMenuToggle');
      const userDropdown = document.getElementById('userDropdown');

      function closeHeaderMenus(except) {
        if (except !== notificationDropdown && notificationDropdown) {
          notificationDropdown.hidden = true;
          notificationToggle.setAttribute('aria-expanded', 'false');
        }
        if (except !== userDropdown && userDropdown) {
          userDropdown.hidden = true;
          userMenuToggle.setAttribute('aria-expanded', 'false');
        }
      }

      if (notificationToggle && notificationDropdown) {
        notificationToggle.addEventListener('click', function (event) {
          event.stopPropagation();
          const willOpen = notificationDropdown.hidden;
          closeHeaderMenus(willOpen ? notificationDropdown : null);
          notificationDropdown.hidden = !willOpen;
          notificationToggle.setAttribute('aria-expanded', String(willOpen));
        });
      }

      if (userMenuToggle && userDropdown) {
        userMenuToggle.addEventListener('click', function (event) {
          event.stopPropagation();
          const willOpen = userDropdown.hidden;
          closeHeaderMenus(willOpen ? userDropdown : null);
          userDropdown.hidden = !willOpen;
          userMenuToggle.setAttribute('aria-expanded', String(willOpen));
        });
      }

      document.addEventListener('click', function () { closeHeaderMenus(null); });

      function renderFiveSNotifications(data) {
        const schedules = data.schedules || [];
        const notifications = data.notifications || [];
        const pendingSchedules = schedules.filter(item => Number(item.can_audit) === 1);
        const total = pendingSchedules.length + notifications.length;
        const count = document.getElementById('notificationCount');
        const list = document.getElementById('notificationList');
        if (!count || !list) return;

        count.textContent = total > 99 ? '99+' : String(total);
        count.classList.toggle('d-none', total === 0);
        const dateEl = document.getElementById('notificationDate');
        if (dateEl) dateEl.textContent = new Date().toLocaleDateString('vi-VN');
        list.innerHTML = '';

        if (!total) {
          list.innerHTML = '<div class="notification-empty"><span class="material-icons text-success d-block mb-1">done_all</span>Không có thông báo mới</div>';
          return;
        }

        notifications.forEach(item => {
          const row = document.createElement('a');
          row.className = 'notification-item';
          row.href = item.link || 'index.php?mainpage=five_s&subpage=mobile_audit';
          row.innerHTML = '<span><strong>' + escapeHeaderText(item.title) + '</strong><small>' + escapeHeaderText(item.message) + '</small></span>';
          list.appendChild(row);
        });

        pendingSchedules.forEach(item => {
          const row = document.createElement('a');
          row.className = 'notification-item';
          row.href = 'index.php?mainpage=five_s&subpage=mobile_audit';
          row.innerHTML = '<span><strong class="text-primary">Việc 5S hôm nay</strong><small>' + escapeHeaderText(item.zone_name) + ' (' + escapeHeaderText(item.zone_code) + ')</small></span>';
          list.appendChild(row);
        });
      }

      function escapeHeaderText(value) {
        const element = document.createElement('span');
        element.textContent = value || '';
        return element.innerHTML;
      }

      fetch('api/five_s_get_schedules.php', { credentials: 'same-origin' })
        .then(response => response.json())
        .then(renderFiveSNotifications)
        .catch(() => {
          const list = document.getElementById('notificationList');
          if (list) list.innerHTML = '<div class="notification-empty">Không thể tải thông báo</div>';
        });
    }());
  </script>
