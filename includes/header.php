<?php
$is_guest = !isset($_SESSION['user_id']) && !isset($_SESSION['user']);
?>
<!-- MAIN WRAPPER START -->
<div class="main-wrapper">
  <header class="top-header">
    <!-- Nút mở menu trên thiết bị di động -->
    <button class="mobile-menu-btn d-lg-none" type="button" aria-label="Mở menu" onclick="toggleSidebar()">
      <span class="material-icons">menu</span>
    </button>

    <div class="header-actions">
      <?php if (!$is_guest): ?>
      <!-- Menu Thông báo 5S real-time -->
      <div class="notification-menu">
        <button class="header-icon-btn" type="button" id="notificationToggle" aria-label="Thông báo" aria-expanded="false">
          <span class="material-icons">notifications_none</span>
          <span class="notification-badge d-none" id="notificationCount">0</span>
        </button>
        <div class="notification-dropdown" id="notificationDropdown" hidden>
          <div class="notification-header">
            <span data-i18n="header.notifications"><?= __('header.notifications', 'Thông báo công việc') ?></span>
            <span class="small text-muted font-normal" id="notificationDate"></span>
          </div>
          <div class="notification-list" id="notificationList">
            <div class="notification-empty" data-i18n="header.loading_notifications"><?= __('header.loading_notifications', 'Đang tải thông báo...') ?></div>
          </div>
          <a class="notification-footer" href="index.php?mainpage=five_s&subpage=mobile_audit" data-i18n="header.view_5s_today">
            <?= __('header.view_5s_today', 'Xem lịch kiểm tra 5S hôm nay') ?>
          </a>
        </div>
      </div>
      <?php endif; ?>

      <!-- Component Chuyển Đổi Ngôn Ngữ (Language Switcher) -->
      <?php
      $currLang = function_exists('getCurrentLanguage') ? getCurrentLanguage() : 'vi';
      $suppLangs = function_exists('getSupportedLanguages') ? getSupportedLanguages() : [];
      $currLangInfo = $suppLangs[$currLang] ?? ['flag' => '🇻🇳', 'short' => 'VI', 'name' => 'Tiếng Việt'];
      ?>
      <style>
      .lang-menu { position: relative; display: inline-flex; align-items: center; }
      .lang-toggle-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        height: 34px; padding: 0 10px 0 8px; border-radius: var(--dx-radius-sm, 6px);
        background: var(--dx-bg-card, #ffffff); border: 1px solid var(--dx-border, #e2e8f0);
        color: var(--dx-text-main, #1e293b); cursor: pointer; font-size: 12px; font-weight: 600;
        line-height: 1; transition: all 0.15s ease-in-out; user-select: none; box-sizing: border-box;
      }
      .lang-toggle-btn:hover {
        background: var(--dx-bg-hover, #f1f5f9); border-color: var(--dx-primary, #0ea5e9);
        color: var(--dx-primary, #0ea5e9);
      }
      .lang-toggle-btn[aria-expanded="true"], .lang-toggle-btn:focus-visible {
        outline: none; border-color: var(--dx-primary, #0ea5e9); background: var(--dx-bg-card, #ffffff);
        box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.2); color: var(--dx-primary, #0ea5e9);
      }
      .lang-btn-icon { font-size: 17px !important; color: var(--dx-primary, #0ea5e9); line-height: 1; flex-shrink: 0; }
      .lang-btn-flag { display: inline-flex; align-items: center; line-height: 1; flex-shrink: 0; }
      .lang-btn-code { font-size: 11.5px; font-weight: 700; letter-spacing: 0.5px; line-height: 1; color: inherit; }
      .lang-btn-arrow { font-size: 16px !important; color: var(--dx-text-muted, #64748b); line-height: 1; transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1); margin-left: -1px; }
      .lang-toggle-btn[aria-expanded="true"] .lang-btn-arrow { transform: rotate(180deg); color: var(--dx-primary, #0ea5e9); }
      .lang-dropdown {
        position: absolute; top: calc(100% + 8px); right: 0; z-index: 1050;
        width: 210px; min-width: 210px; background: var(--dx-bg-card, #ffffff);
        border: 1px solid var(--dx-border, #e2e8f0); border-radius: var(--dx-radius-md, 10px);
        box-shadow: 0 12px 28px -4px rgba(0, 0, 0, 0.12), 0 6px 12px -4px rgba(0, 0, 0, 0.08);
        padding: 6px; display: flex !important; flex-direction: column !important; gap: 2px;
        box-sizing: border-box; animation: dxLangFadeIn 0.16s cubic-bezier(0.16, 1, 0.3, 1); transform-origin: top right;
      }
      .lang-dropdown[hidden] { display: none !important; }
      @keyframes dxLangFadeIn {
        from { opacity: 0; transform: translateY(-6px) scale(0.97); }
        to { opacity: 1; transform: translateY(0) scale(1); }
      }
      .lang-dropdown-header {
        display: flex; align-items: center; gap: 6px; padding: 6px 8px 8px 8px;
        margin-bottom: 2px; border-bottom: 1px solid var(--dx-border, #e2e8f0);
        font-size: 11px; font-weight: 700; color: var(--dx-text-muted, #64748b);
        text-transform: uppercase; letter-spacing: 0.5px;
      }
      .lang-dropdown-header .material-icons { font-size: 15px; color: var(--dx-primary, #0ea5e9); }
      .lang-list { display: flex !important; flex-direction: column !important; gap: 2px; margin: 0; padding: 0; }
      .lang-item {
        width: 100% !important; display: flex !important; flex-direction: row !important;
        align-items: center !important; gap: 10px !important; padding: 8px 10px !important;
        border-radius: var(--dx-radius-sm, 6px) !important; border: 1px solid transparent !important;
        background: transparent !important; color: var(--dx-text-main, #1e293b) !important;
        font-size: 13px !important; font-weight: 500 !important; line-height: 1.4 !important;
        text-align: left !important; cursor: pointer !important; transition: all 0.15s ease !important;
        box-sizing: border-box !important; user-select: none !important;
      }
      .lang-item:hover { background: var(--dx-bg-hover, #f1f5f9) !important; color: var(--dx-text-main, #0f172a) !important; }
      .lang-item.active {
        background: rgba(14, 165, 233, 0.08) !important; border-color: rgba(14, 165, 233, 0.25) !important;
        color: var(--dx-primary, #0ea5e9) !important; font-weight: 600 !important;
      }
      .lang-item-flag { display: inline-flex; align-items: center; justify-content: center; line-height: 1; flex-shrink: 0; }
      .lang-item-name { flex: 1; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
      .lang-item-tag {
        font-size: 10.5px; font-weight: 700; padding: 2px 6px; border-radius: 4px;
        background: var(--dx-bg-subtle, #f1f5f9); color: var(--dx-text-muted, #64748b);
        flex-shrink: 0; letter-spacing: 0.3px; line-height: 1.2;
      }
      .lang-item.active .lang-item-tag { background: rgba(14, 165, 233, 0.18) !important; color: var(--dx-primary, #0ea5e9) !important; }
      .lang-check { font-size: 16px !important; color: var(--dx-primary, #0ea5e9) !important; flex-shrink: 0; line-height: 1; }
      [data-theme="dark"] .lang-toggle-btn { background: var(--dx-bg-card, #1e293b); border-color: var(--dx-border, #334155); color: var(--dx-text-main, #f8fafc); }
      [data-theme="dark"] .lang-toggle-btn:hover { background: var(--dx-bg-hover, #334155); border-color: var(--dx-primary, #38bdf8); color: var(--dx-primary, #38bdf8); }
      [data-theme="dark"] .lang-toggle-btn[aria-expanded="true"], [data-theme="dark"] .lang-toggle-btn:focus-visible { background: var(--dx-bg-card, #1e293b); border-color: var(--dx-primary, #38bdf8); box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.25); color: var(--dx-primary, #38bdf8); }
      [data-theme="dark"] .lang-dropdown { background: var(--dx-bg-card, #1e293b); border-color: var(--dx-border, #334155); box-shadow: 0 12px 28px -4px rgba(0, 0, 0, 0.6), 0 6px 12px -4px rgba(0, 0, 0, 0.4); }
      [data-theme="dark"] .lang-dropdown-header { border-bottom-color: var(--dx-border, #334155); color: var(--dx-text-muted, #94a3b8); }
      [data-theme="dark"] .lang-item { color: var(--dx-text-main, #f1f5f9) !important; }
      [data-theme="dark"] .lang-item:hover { background: var(--dx-bg-hover, #334155) !important; color: #38bdf8 !important; }
      [data-theme="dark"] .lang-item.active { background: rgba(56, 189, 248, 0.14) !important; border-color: rgba(56, 189, 248, 0.35) !important; color: #38bdf8 !important; }
      [data-theme="dark"] .lang-item-tag { background: rgba(255, 255, 255, 0.08); color: #94a3b8; }
      [data-theme="dark"] .lang-item.active .lang-item-tag { background: rgba(56, 189, 248, 0.25) !important; color: #38bdf8 !important; }
      </style>
      <div class="lang-menu" id="dxLangMenu">
        <button class="lang-toggle-btn" type="button" id="langMenuToggle" aria-label="Ngôn ngữ / Language" aria-expanded="false" title="<?= __('header.select_language', 'Chọn ngôn ngữ') ?>">
          <span class="material-icons lang-btn-icon">translate</span>
          <span class="lang-btn-flag" id="currentLangFlag"><?= function_exists('renderFlagSvg') ? renderFlagSvg($currLang, 18, 12) : $currLangInfo['flag'] ?></span>
          <span class="lang-btn-code" id="currentLangCode"><?= $currLangInfo['short'] ?></span>
          <span class="material-icons lang-btn-arrow">expand_more</span>
        </button>
        <div class="lang-dropdown" id="langDropdown" hidden>
          <div class="lang-dropdown-header">
            <span class="material-icons">translate</span>
            <span data-i18n="header.select_language"><?= __('header.select_language', 'Chọn ngôn ngữ') ?></span>
          </div>
          <div class="lang-list">
            <button type="button" class="lang-item <?= ($currLang === 'vi') ? 'active' : '' ?>" data-lang="vi" onclick="setAppLanguage('vi')">
              <span class="lang-item-flag"><?= function_exists('renderFlagSvg') ? renderFlagSvg('vi', 20, 14) : '🇻🇳' ?></span>
              <span class="lang-item-name">Tiếng Việt</span>
              <span class="lang-item-tag">VI</span>
              <span class="material-icons lang-check" style="<?= ($currLang === 'vi') ? 'display: inline-flex;' : 'display: none;' ?>">check</span>
            </button>
            <button type="button" class="lang-item <?= ($currLang === 'en') ? 'active' : '' ?>" data-lang="en" onclick="setAppLanguage('en')">
              <span class="lang-item-flag"><?= function_exists('renderFlagSvg') ? renderFlagSvg('en', 20, 14) : '🇬🇧' ?></span>
              <span class="lang-item-name">English</span>
              <span class="lang-item-tag">EN</span>
              <span class="material-icons lang-check" style="<?= ($currLang === 'en') ? 'display: inline-flex;' : 'display: none;' ?>">check</span>
            </button>
            <button type="button" class="lang-item <?= ($currLang === 'ja') ? 'active' : '' ?>" data-lang="ja" onclick="setAppLanguage('ja')">
              <span class="lang-item-flag"><?= function_exists('renderFlagSvg') ? renderFlagSvg('ja', 20, 14) : '🇯🇵' ?></span>
              <span class="lang-item-name">日本語</span>
              <span class="lang-item-tag">JA</span>
              <span class="material-icons lang-check" style="<?= ($currLang === 'ja') ? 'display: inline-flex;' : 'display: none;' ?>">check</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Nút đổi Theme 1 chạm nhanh trên headbar -->
      <button class="header-icon-btn" type="button" id="themeQuickToggle" onclick="toggleTheme(event)" title="<?= __('header.theme_toggle_title', 'Chuyển chế độ Sáng / Tối') ?>" aria-label="Đổi giao diện">
        <span class="material-icons" id="themeQuickIcon">dark_mode</span>
      </button>

      <?php if (!$is_guest): ?>
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
            <span data-i18n="header.roles_manage"><?= __('header.roles_manage', 'Phân quyền') ?></span>
          </a>

          <?php if (hasPermission(['role.manage', 'system.language', 'admin'])): ?>
          <a href="index.php?mainpage=system&subpage=language_settings" class="user-dropdown-item">
            <span class="material-icons">translate</span>
            <span data-i18n="nav.language_settings"><?= __('nav.language_settings', 'Thiết lập ngôn ngữ') ?></span>
          </a>
          <?php endif; ?>

          <!-- Nút Tùy Chỉnh Giao Diện Sáng / Tối -->
          <div class="user-dropdown-divider"></div>
          <div class="theme-switch-row" onclick="toggleTheme(event)" title="<?= __('header.theme_toggle_title', 'Chuyển đổi giao diện Sáng / Tối') ?>">
            <div class="theme-switch-info">
              <span class="material-icons" id="themeSwitchIcon">dark_mode</span>
              <span id="themeSwitchLabel" data-i18n="header.theme_dark"><?= __('header.theme_dark', 'Chế độ tối') ?></span>
            </div>
            <div class="theme-switch-toggle" id="themeSwitchToggle">
              <span class="theme-switch-knob"></span>
            </div>
          </div>
          <div class="user-dropdown-divider"></div>

          <button type="button" class="user-dropdown-item text-danger" onclick="logout()">
            <span class="material-icons">logout</span>
            <span data-i18n="header.logout"><?= __('header.logout', 'Đăng xuất') ?></span>
          </button>
        </div>
      </div>
      <?php else: ?>
      <!-- Nút Đăng nhập dành cho Khách truy cập tiện ích -->
      <div class="d-flex align-items-center gap-2">
        <span class="badge bg-primary-subtle text-primary d-none d-sm-inline-flex align-items-center gap-1" style="font-size: 12px; padding: 6px 10px; border-radius: 6px;" data-i18n="header.guest_utility">
          <span class="material-icons" style="font-size: 15px;">bolt</span> <?= __('header.guest_utility', 'Khách / Tiện ích nhanh') ?>
        </span>
        <a href="index.php?mainpage=authentication&subpage=login" class="app-btn app-btn-primary app-btn-sm" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none; padding: 6px 14px; font-weight: 600; border-radius: 6px;">
          <span class="material-icons" style="font-size: 16px;">login</span> <span data-i18n="header.sign_in"><?= __('header.sign_in', 'Đăng nhập') ?></span>
        </a>
      </div>
      <?php endif; ?>
    </div>
  </header>

  <!-- CONTENT AREA START (Chứa nội dung của module) -->
  <main class="content-area">

  <?php if (!$is_guest): ?>
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
        const langDropdown = document.getElementById('langDropdown');
        const langToggle = document.getElementById('langMenuToggle');
        if (except !== langDropdown && langDropdown) {
          langDropdown.hidden = true;
          if (langToggle) langToggle.setAttribute('aria-expanded', 'false');
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
  <?php endif; ?>
