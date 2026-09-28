<?php
// Tự động điều hướng về index.php nếu truy cập trực tiếp file này
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'login.php' && strpos($_SERVER['REQUEST_URI'] ?? '', 'modules/authentication') !== false) {
    header("Location: ../../index.php?mainpage=authentication&subpage=login");
    exit;
}
$baseAppUrl = (strpos($_SERVER['REQUEST_URI'] ?? '', 'modules/authentication') !== false) ? '../../index.php' : 'index.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0" />
  <title>Đăng nhập & Tiện ích nhanh - DX Plastic Group</title>
  
  <!-- Icon Libraries -->
  <link rel="stylesheet" href="resources/icon.css">
  <link rel="stylesheet" href="css/material-icons.css">

  <script>
    (function() {
      try {
        const saved = localStorage.getItem('dx-theme');
        if (saved === 'dark' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
          document.documentElement.setAttribute('data-theme', 'dark');
        } else {
          document.documentElement.setAttribute('data-theme', 'light');
        }
      } catch (e) {}
    })();
  </script>

  <style>
    :root {
      --bg1: #f8fafc;
      --bg2: #edf2f7;
      --panel: #ffffff;
      --panel-glass: rgba(255, 255, 255, 0.94);
      --panel-border: #e2e8f0;
      --primary: #0284c7;
      --primary-hover: #0369a1;
      --primary-subtle: #e0f2fe;
      --success: #10b981;
      --success-subtle: #d1fae5;
      --warning: #f59e0b;
      --warning-subtle: #fef3c7;
      --danger: #ef4444;
      --danger-subtle: #fee2e2;
      --purple: #8b5cf6;
      --purple-subtle: #ede9fe;
      --text: #0f172a;
      --muted: #64748b;
      --input-bg: #ffffff;
      --input-border: #cbd5e1;
      --shadow-sm: 0 2px 4px rgba(0,0,0,0.03);
      --shadow-md: 0 10px 25px -5px rgba(0,0,0,0.06), 0 8px 10px -6px rgba(0,0,0,0.04);
      --shadow-lg: 0 20px 35px -8px rgba(15, 23, 42, 0.12), 0 10px 15px -6px rgba(15, 23, 42, 0.05);
      --card-highlight: #f1f5f9;
    }

    [data-theme="dark"] {
      --bg1: #090d16;
      --bg2: #0f172a;
      --panel: #131c2e;
      --panel-glass: rgba(19, 28, 46, 0.95);
      --panel-border: #22314a;
      --primary: #38bdf8;
      --primary-hover: #0284c7;
      --primary-subtle: rgba(56, 189, 248, 0.12);
      --success: #34d399;
      --success-subtle: rgba(52, 211, 153, 0.12);
      --warning: #fbbf24;
      --warning-subtle: rgba(251, 191, 36, 0.12);
      --danger: #f87171;
      --danger-subtle: rgba(248, 113, 113, 0.12);
      --purple: #a78bfa;
      --purple-subtle: rgba(167, 139, 250, 0.12);
      --text: #f8fafc;
      --muted: #94a3b8;
      --input-bg: #0b1120;
      --input-border: #283953;
      --shadow-sm: 0 2px 4px rgba(0,0,0,0.25);
      --shadow-md: 0 10px 25px -5px rgba(0,0,0,0.4);
      --shadow-lg: 0 25px 45px -8px rgba(0, 0, 0, 0.6);
      --card-highlight: #1b263b;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      background: linear-gradient(135deg, var(--bg1) 0%, var(--bg2) 100%);
      color: var(--text);
      min-height: 100vh;
      min-height: 100dvh;
      display: flex;
      flex-direction: column;
      overflow-x: hidden;
      -webkit-font-smoothing: antialiased;
    }

    /* TOP BAR / NAVIGATION HEADER */
    .login-topbar {
      width: 100%;
      height: 64px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 clamp(16px, 3vw, 32px);
      background: var(--panel-glass);
      border-bottom: 1px solid var(--panel-border);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      position: sticky;
      top: 0;
      z-index: 50;
      box-shadow: var(--shadow-sm);
    }

    .topbar-brand {
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
      color: var(--text);
      flex-shrink: 0;
    }

    .topbar-badge {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      background: linear-gradient(135deg, #0284c7, #2563eb);
      color: white;
      font-weight: 800;
      font-size: 17px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 10px rgba(2, 132, 199, 0.35);
    }

    .topbar-title-wrap {
      display: flex;
      flex-direction: column;
    }

    .topbar-brand-name {
      font-weight: 700;
      font-size: 1.05rem;
      letter-spacing: -0.01em;
      line-height: 1.2;
    }

    .topbar-tagline {
      font-size: 0.72rem;
      color: var(--muted);
      font-weight: 500;
    }

    .topbar-actions {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    /* QUICK PILLS (TOPBAR SHORTCUTS) */
    .quick-pills {
      display: flex;
      align-items: center;
      gap: 8px;
      overflow-x: auto;
      scrollbar-width: none;
      -ms-overflow-style: none;
      padding: 2px 0;
    }

    .quick-pills::-webkit-scrollbar {
      display: none;
    }

    .quick-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.2s ease;
      white-space: nowrap;
      border: 1px solid var(--panel-border);
      background: var(--card-highlight);
      color: var(--text);
    }

    .quick-pill:hover {
      border-color: var(--primary);
      background: var(--primary-subtle);
      color: var(--primary);
      transform: translateY(-1px);
    }

    .quick-pill .material-icons {
      font-size: 16px;
    }

    .quick-pill.pill-blue .material-icons { color: #0284c7; }
    .quick-pill.pill-green .material-icons { color: #10b981; }
    .quick-pill.pill-purple .material-icons { color: #8b5cf6; }

    /* THEME TOGGLE BUTTON */
    .theme-toggle-btn {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      border: 1px solid var(--panel-border);
      background: var(--card-highlight);
      color: var(--text);
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.2s ease;
      flex-shrink: 0;
    }

    .theme-toggle-btn:hover {
      background: var(--primary-subtle);
      color: var(--primary);
      border-color: var(--primary);
    }

    .theme-toggle-btn .material-icons {
      font-size: 20px;
    }

    /* MAIN CONTAINER WRAPPER */
    .login-viewport {
      flex: 1;
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: clamp(16px, 3vw, 36px) 16px;
    }

    .login-container-grid {
      width: 100%;
      max-width: 1040px;
      background: var(--panel);
      border: 1px solid var(--panel-border);
      border-radius: 24px;
      box-shadow: var(--shadow-lg);
      display: grid;
      grid-template-columns: 1.15fr 1fr;
      overflow: hidden;
      position: relative;
    }

    /* LEFT PANEL: QUICK UTILITIES */
    .utilities-panel {
      padding: clamp(28px, 4vw, 40px);
      background: linear-gradient(145deg, var(--card-highlight) 0%, var(--panel) 100%);
      border-right: 1px solid var(--panel-border);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      gap: 24px;
    }

    .util-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 5px 12px;
      border-radius: 20px;
      background: var(--primary-subtle);
      color: var(--primary);
      font-size: 0.74rem;
      font-weight: 700;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      margin-bottom: 12px;
      border: 1px solid rgba(2, 132, 199, 0.2);
    }

    .util-badge .material-icons {
      font-size: 14px;
    }

    .util-heading {
      font-size: clamp(1.4rem, 2.5vw, 1.75rem);
      font-weight: 800;
      line-height: 1.25;
      letter-spacing: -0.02em;
      color: var(--text);
      margin-bottom: 8px;
    }

    .util-desc {
      color: var(--muted);
      font-size: 0.88rem;
      line-height: 1.5;
    }

    /* UTILITY CARDS LIST */
    .util-cards-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-top: 4px;
    }

    .util-card-item {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 14px 16px;
      background: var(--panel);
      border: 1px solid var(--panel-border);
      border-radius: 16px;
      text-decoration: none;
      color: var(--text);
      transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
      position: relative;
      box-shadow: var(--shadow-sm);
    }

    .util-card-item:hover {
      border-color: var(--primary);
      background: var(--card-highlight);
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }

    .util-card-item:active {
      transform: scale(0.98);
    }

    .util-icon-box {
      width: 48px;
      height: 48px;
      border-radius: 13px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      transition: transform 0.2s ease;
    }

    .util-card-item:hover .util-icon-box {
      transform: scale(1.06);
    }

    .util-icon-blue {
      background: var(--primary-subtle);
      color: var(--primary);
    }

    .util-icon-green {
      background: var(--success-subtle);
      color: var(--success);
    }

    .util-icon-purple {
      background: var(--purple-subtle);
      color: var(--purple);
    }

    .util-icon-box .material-icons {
      font-size: 26px;
    }

    .util-content {
      flex: 1;
      min-width: 0;
    }

    .util-top-meta {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      margin-bottom: 2px;
    }

    .util-item-title {
      font-weight: 700;
      font-size: 0.95rem;
      color: var(--text);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .util-item-tag {
      font-size: 0.68rem;
      font-weight: 700;
      padding: 2px 7px;
      border-radius: 6px;
      background: var(--card-highlight);
      color: var(--muted);
      border: 1px solid var(--panel-border);
      text-transform: uppercase;
      flex-shrink: 0;
    }

    .util-item-desc {
      font-size: 0.78rem;
      color: var(--muted);
      line-height: 1.35;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .util-arrow {
      color: var(--muted);
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s ease;
      flex-shrink: 0;
    }

    .util-card-item:hover .util-arrow {
      color: var(--primary);
      transform: translateX(3px);
    }

    .util-footer-note {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.76rem;
      color: var(--muted);
      line-height: 1.4;
      padding: 10px 14px;
      background: var(--panel);
      border-radius: 12px;
      border: 1px solid var(--panel-border);
    }

    .util-footer-note .material-icons {
      font-size: 16px;
      color: var(--success);
      flex-shrink: 0;
    }

    /* RIGHT PANEL: LOGIN FORM */
    .login-panel {
      padding: clamp(28px, 4vw, 44px);
      display: flex;
      flex-direction: column;
      justify-content: center;
      background: var(--panel);
    }

    .login-header-group {
      margin-bottom: 26px;
      text-align: left;
    }

    .login-brand-icon {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      background: linear-gradient(135deg, var(--primary), #1d4ed8);
      color: white;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 14px;
      box-shadow: 0 4px 14px rgba(2, 132, 199, 0.35);
    }

    .login-brand-icon .material-icons {
      font-size: 24px;
    }

    .login-heading {
      font-size: clamp(1.4rem, 2.5vw, 1.75rem);
      font-weight: 800;
      letter-spacing: -0.02em;
      color: var(--text);
      line-height: 1.2;
    }

    .login-subheading {
      color: var(--muted);
      font-size: 0.88rem;
      margin-top: 6px;
    }

    /* FORM STYLING */
    .form-group {
      margin-bottom: 18px;
    }

    .form-label {
      display: block;
      margin-bottom: 7px;
      font-size: 0.84rem;
      font-weight: 600;
      color: var(--text);
    }

    .input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }

    .input-icon {
      position: absolute;
      left: 14px;
      color: var(--muted);
      font-size: 20px;
      pointer-events: none;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .form-input {
      width: 100%;
      height: 48px;
      border: 1px solid var(--input-border);
      border-radius: 12px;
      background: var(--input-bg);
      color: var(--text);
      font-size: 0.95rem;
      padding: 0 44px 0 44px;
      outline: none;
      transition: all 0.2s ease;
    }

    .form-input:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
    }

    .password-toggle-btn {
      position: absolute;
      right: 10px;
      width: 32px;
      height: 32px;
      border: 0;
      background: transparent;
      color: var(--muted);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 8px;
      transition: color 0.15s ease;
    }

    .password-toggle-btn:hover {
      color: var(--text);
      background: var(--card-highlight);
    }

    .password-toggle-btn .material-icons {
      font-size: 20px;
    }

    .form-options-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;
      font-size: 0.84rem;
      color: var(--muted);
    }

    .remember-label {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      cursor: pointer;
      user-select: none;
    }

    .remember-label input {
      accent-color: var(--primary);
      width: 16px;
      height: 16px;
    }

    .forgot-link {
      color: var(--primary);
      text-decoration: none;
      font-weight: 600;
      transition: opacity 0.2s ease;
    }

    .forgot-link:hover {
      text-decoration: underline;
    }

    .btn-submit-login {
      width: 100%;
      height: 48px;
      border: none;
      border-radius: 12px;
      background: linear-gradient(135deg, var(--primary) 0%, #0369a1 100%);
      color: #ffffff;
      font-size: 0.98rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      cursor: pointer;
      box-shadow: 0 8px 18px rgba(2, 132, 199, 0.35);
      transition: all 0.2s ease;
    }

    .btn-submit-login:hover {
      transform: translateY(-1px);
      box-shadow: 0 10px 22px rgba(2, 132, 199, 0.45);
    }

    .btn-submit-login:active {
      transform: scale(0.98);
    }

    .btn-submit-login:disabled {
      opacity: 0.7;
      cursor: not-allowed;
      transform: none;
    }

    /* STATUS MESSAGE */
    .status-box {
      margin-top: 14px;
      padding: 10px 14px;
      border-radius: 10px;
      font-size: 0.85rem;
      font-weight: 600;
      text-align: center;
      display: none;
      animation: fadeIn 0.2s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-4px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .status-box.success {
      display: block;
      background: var(--success-subtle);
      color: var(--success);
      border: 1px solid rgba(16, 185, 129, 0.25);
    }

    .status-box.error {
      display: block;
      background: var(--danger-subtle);
      color: var(--danger);
      border: 1px solid rgba(239, 68, 68, 0.25);
    }

    .register-footer {
      margin-top: 20px;
      text-align: center;
      font-size: 0.84rem;
      color: var(--muted);
    }

    .register-footer a {
      color: var(--primary);
      text-decoration: none;
      font-weight: 600;
    }

    /* MOBILE DEDICATED QUICK SHORTCUTS SECTION */
    .mobile-quick-section {
      display: none;
    }

    /* RESPONSIVE BREAKPOINTS */
    @media (max-width: 991px) {
      .login-container-grid {
        max-width: 720px;
        grid-template-columns: 1fr;
      }

      .utilities-panel {
        border-right: none;
        border-top: 1px solid var(--panel-border);
        order: 2;
        padding: 28px 24px;
      }

      .login-panel {
        order: 1;
        padding: 32px 24px;
      }

      .util-cards-list {
        display: grid;
        grid-template-columns: 1fr;
        gap: 12px;
      }
    }

    @media (max-width: 640px) {
      .login-topbar {
        height: 56px;
        padding: 0 14px;
      }

      .topbar-brand-name {
        font-size: 0.95rem;
      }

      .topbar-tagline {
        display: none;
      }

      .quick-pills {
        display: none; /* Ẩn trên topbar để chuyển xuống dưới dạng danh sách lớn */
      }

      .login-viewport {
        padding: 12px 10px;
        align-items: flex-start;
      }

      .login-container-grid {
        border-radius: 18px;
        box-shadow: var(--shadow-md);
      }

      .login-panel {
        padding: 24px 16px;
      }

      .form-input, .btn-submit-login {
        height: 48px;
      }

      .utilities-panel {
        padding: 20px 14px 24px;
      }

      .util-card-item {
        padding: 12px 14px;
        border-radius: 14px;
      }

      .util-icon-box {
        width: 42px;
        height: 42px;
        border-radius: 10px;
      }

      .util-icon-box .material-icons {
        font-size: 22px;
      }
    }

    /* MOBILE FAST SHORTCUT BANNER AT TOP OF VIEWPORT */
    .mobile-fast-bar {
      display: none;
      width: 100%;
      background: var(--card-highlight);
      border-bottom: 1px solid var(--panel-border);
      padding: 8px 12px;
      overflow-x: auto;
      scrollbar-width: none;
      white-space: nowrap;
    }

    .mobile-fast-bar::-webkit-scrollbar {
      display: none;
    }

    @media (max-width: 640px) {
      .mobile-fast-bar {
        display: flex;
        align-items: center;
        gap: 8px;
      }
    }

    .mobile-fast-title {
      font-size: 0.72rem;
      font-weight: 700;
      color: var(--primary);
      text-transform: uppercase;
      display: flex;
      align-items: center;
      gap: 4px;
      padding-right: 4px;
      flex-shrink: 0;
    }
  </style>
</head>
<body>

  <!-- TOP BAR -->
  <header class="login-topbar">
    <a href="<?= $baseAppUrl ?>?mainpage=authentication&subpage=login" class="topbar-brand">
      <div class="topbar-badge">DX</div>
      <div class="topbar-title-wrap">
        <span class="topbar-brand-name">DX Plastic Group</span>
        <span class="topbar-tagline">Hệ Thống Quản Lý Sản Xuất</span>
      </div>
    </a>

    <div class="topbar-actions">
      <!-- Desktop & Tablet Quick Nav Pills -->
      <nav class="quick-pills" aria-label="Truy cập nhanh">
        <a href="<?= $baseAppUrl ?>?mainpage=materials&subpage=viscoscity" class="quick-pill pill-blue" title="Vào tra cứu độ nhớt vật liệu">
          <span class="material-icons">science</span>
          <span>Độ nhớt vật liệu</span>
        </a>
        <a href="<?= $baseAppUrl ?>?mainpage=utilities&subpage=color_mixer" class="quick-pill pill-green" title="Vào tra cứu thông số bộ trộn màu">
          <span class="material-icons">palette</span>
          <span>Tra cứu trộn màu</span>
        </a>
        <a href="<?= $baseAppUrl ?>?mainpage=utilities&subpage=color_mixer_summary" class="quick-pill pill-purple" title="Vào xem bảng tổng quan ma trận trộn màu">
          <span class="material-icons">grid_view</span>
          <span>Bảng tổng quan</span>
        </a>
      </nav>

      <!-- Theme Switch Button -->
      <button type="button" class="theme-toggle-btn" id="themeToggleBtn" onclick="toggleThemeManual()" aria-label="Đổi giao diện sáng/tối" title="Đổi giao diện Sáng / Tối">
        <span class="material-icons" id="themeIcon">dark_mode</span>
      </button>
    </div>
  </header>

  <!-- MOBILE QUICK ACCESS SCROLL STRIP -->
  <div class="mobile-fast-bar">
    <span class="mobile-fast-title">
      <span class="material-icons" style="font-size: 15px;">bolt</span> Vào nhanh:
    </span>
    <a href="<?= $baseAppUrl ?>?mainpage=materials&subpage=viscoscity" class="quick-pill pill-blue">
      <span class="material-icons">science</span> Độ nhớt
    </a>
    <a href="<?= $baseAppUrl ?>?mainpage=utilities&subpage=color_mixer" class="quick-pill pill-green">
      <span class="material-icons">palette</span> Trộn màu
    </a>
    <a href="<?= $baseAppUrl ?>?mainpage=utilities&subpage=color_mixer_summary" class="quick-pill pill-purple">
      <span class="material-icons">grid_view</span> Tổng quan
    </a>
  </div>

  <!-- MAIN LOGIN & UTILITIES VIEWPORT -->
  <main class="login-viewport">
    <div class="login-container-grid">
      
      <!-- LEFT PANEL: KHU VỰC TRUY CẬP NHANH TIỆN ÍCH (KHÔNG CẦN ĐĂNG NHẬP) -->
      <section class="utilities-panel" aria-label="Tiện ích tra cứu công khai">
        <div>
          <div class="util-badge">
            <span class="material-icons">bolt</span>
            Truy Cập Nhanh • Không Cần Đăng Nhập
          </div>
          <h2 class="util-heading">Tiện Ích Xưởng Sản Xuất</h2>
          <p class="util-desc">
            Dành cho nhân viên vận hành và kỹ thuật kiểm tra nhanh thông số kéo ống, ma trận trộn màu và chỉ số vật liệu trực tiếp tại xưởng.
          </p>
        </div>

        <div class="util-cards-list">
          <!-- TIỆN ÍCH 1: ĐỘ NHỚT VẬT LIỆU -->
          <a href="<?= $baseAppUrl ?>?mainpage=materials&subpage=viscoscity" class="util-card-item" title="Mở trang Độ nhớt vật liệu">
            <div class="util-icon-box util-icon-blue">
              <span class="material-icons">science</span>
            </div>
            <div class="util-content">
              <div class="util-top-meta">
                <span class="util-item-title">Độ nhớt vật liệu</span>
                <span class="util-item-tag">Chỉ số & YI</span>
              </div>
              <p class="util-item-desc">Tra cứu chỉ số độ nhớt, YI và số lượng vật liệu nhập kho theo Lot No</p>
            </div>
            <span class="material-icons util-arrow">arrow_forward</span>
          </a>

          <!-- TIỆN ÍCH 2: TRA CỨU TRỘN MÀU -->
          <a href="<?= $baseAppUrl ?>?mainpage=utilities&subpage=color_mixer" class="util-card-item" title="Mở trang Tra cứu trộn màu">
            <div class="util-icon-box util-icon-green">
              <span class="material-icons">palette</span>
            </div>
            <div class="util-content">
              <div class="util-top-meta">
                <span class="util-item-title">Tra cứu trộn màu</span>
                <span class="util-item-tag">Trục lớn / nhỏ</span>
              </div>
              <p class="util-item-desc">Tra cứu tốc độ kéo ống (m/p) và vòng quay bộ trộn (RPM) theo từng mã màu</p>
            </div>
            <span class="material-icons util-arrow">arrow_forward</span>
          </a>

          <!-- TIỆN ÍCH 3: BẢNG TỔNG QUAN TRỘN MÀU -->
          <a href="<?= $baseAppUrl ?>?mainpage=utilities&subpage=color_mixer_summary" class="util-card-item" title="Mở trang Bảng tổng quan trộn màu">
            <div class="util-icon-box util-icon-purple">
              <span class="material-icons">grid_view</span>
            </div>
            <div class="util-content">
              <div class="util-top-meta">
                <span class="util-item-title">Bảng tổng quan trộn màu</span>
                <span class="util-item-tag">Ma trận toàn diện</span>
              </div>
              <p class="util-item-desc">Ma trận đối chiếu tốc độ tổng hợp theo từng size ống, mã màu và tốc độ kéo</p>
            </div>
            <span class="material-icons util-arrow">arrow_forward</span>
          </a>
        </div>

        <div class="util-footer-note">
          <span class="material-icons">verified_user</span>
          <span>Bảo mật hệ thống: Các chức năng thêm mới, chỉnh sửa và quản trị yêu cầu đăng nhập tài khoản.</span>
        </div>
      </section>

      <!-- RIGHT PANEL: FORM ĐĂNG NHẬP HỆ THỐNG -->
      <section class="login-panel" aria-label="Khu vực đăng nhập">
        <div class="login-header-group">
          <div class="login-brand-icon">
            <span class="material-icons">lock</span>
          </div>
          <h1 class="login-heading">Đăng nhập</h1>
          <p class="login-subheading">Chào mừng bạn quay trở lại với hệ thống</p>
        </div>

        <form id="loginForm" novalidate>
          <div class="form-group">
            <label class="form-label" for="username">Tên đăng nhập hoặc Email</label>
            <div class="input-wrapper">
              <span class="material-icons input-icon">person</span>
              <input 
                id="username" 
                name="username" 
                type="text" 
                class="form-input" 
                placeholder="Nhập tên đăng nhập hoặc email" 
                autocomplete="username" 
                autocorrect="off" 
                autocapitalize="off" 
                required 
              />
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="password">Mật khẩu</label>
            <div class="input-wrapper">
              <span class="material-icons input-icon">lock</span>
              <input 
                id="password" 
                name="password" 
                type="password" 
                class="form-input" 
                placeholder="Nhập mật khẩu của bạn" 
                autocomplete="current-password" 
                required 
              />
              <button 
                type="button" 
                class="password-toggle-btn" 
                id="togglePassword" 
                aria-label="Hiển thị hoặc ẩn mật khẩu" 
                title="Hiển thị mật khẩu"
              >
                <span class="material-icons" id="togglePasswordIcon">visibility</span>
              </button>
            </div>
          </div>

          <div class="form-options-row">
            <label class="remember-label">
              <input type="checkbox" name="remember" id="rememberMe" />
              <span>Ghi nhớ đăng nhập</span>
            </label>
            <a class="forgot-link" href="#" onclick="alert('Vui lòng liên hệ Quản trị viên hệ thống để khôi phục mật khẩu!'); return false;">Quên mật khẩu?</a>
          </div>

          <button class="btn-submit-login" type="submit" id="btnSubmitLogin">
            <span class="material-icons" style="font-size: 20px;">login</span>
            <span id="btnSubmitText">Đăng nhập hệ thống</span>
          </button>

          <div id="loginStatus" class="status-box" aria-live="polite"></div>
        </form>

        <div class="register-footer">
          Chưa có tài khoản? <a href="#" onclick="alert('Vui lòng liên hệ Trưởng bộ phận hoặc Quản trị viên để được cấp tài khoản!'); return false;">Liên hệ Quản trị viên</a>
        </div>
      </section>

    </div>
  </main>

  <script>
    // XỬ LÝ GIAO DIỆN SÁNG / TỐI ĐỒNG BỘ TOÀN HỆ THỐNG
    function updateThemeIcon() {
      const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
      const icon = document.getElementById('themeIcon');
      if (icon) {
        icon.textContent = isDark ? 'light_mode' : 'dark_mode';
      }
    }

    function toggleThemeManual() {
      const current = document.documentElement.getAttribute('data-theme');
      const target = current === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', target);
      localStorage.setItem('dx-theme', target);
      updateThemeIcon();
    }

    document.addEventListener('DOMContentLoaded', () => {
      updateThemeIcon();
    });

    // ẨN / HIỆN MẬT KHẨU
    const passwordInput = document.getElementById('password');
    const togglePasswordBtn = document.getElementById('togglePassword');
    const togglePasswordIcon = document.getElementById('togglePasswordIcon');

    if (togglePasswordBtn && passwordInput) {
      togglePasswordBtn.addEventListener('click', function() {
        const isPassword = passwordInput.type === 'password';
        passwordInput.type = isPassword ? 'text' : 'password';
        togglePasswordIcon.textContent = isPassword ? 'visibility_off' : 'visibility';
        togglePasswordBtn.setAttribute('title', isPassword ? 'Ẩn mật khẩu' : 'Hiển thị mật khẩu');
      });
    }

    // XỬ LÝ SUBMIT ĐĂNG NHẬP
    const form = document.getElementById('loginForm');
    const usernameInput = document.getElementById('username');
    const statusEl = document.getElementById('loginStatus');
    const loginButton = document.getElementById('btnSubmitLogin');
    const btnSubmitText = document.getElementById('btnSubmitText');

    function showStatus(message, type) {
      statusEl.textContent = message;
      statusEl.className = 'status-box ' + type;
    }

    if (form) {
      form.addEventListener('submit', async function(event) {
        event.preventDefault();

        const username = usernameInput.value.trim();
        const password = passwordInput.value.trim();

        if (!username || !password) {
          showStatus('Vui lòng nhập đầy đủ tên đăng nhập/email và mật khẩu.', 'error');
          return;
        }

        loginButton.disabled = true;
        btnSubmitText.textContent = 'Đang xử lý...';

        try {
          let basePath = window.location.pathname;
          if (basePath.includes('/modules/authentication')) {
            basePath = basePath.substring(0, basePath.indexOf('/modules/authentication'));
          } else if (basePath.endsWith('/login.php')) {
            basePath = basePath.substring(0, basePath.lastIndexOf('/login.php'));
          } else if (basePath.endsWith('/index.php')) {
            basePath = basePath.substring(0, basePath.lastIndexOf('/index.php'));
          }
          if (!basePath.endsWith('/')) basePath += '/';

          const apiEndpoint = basePath + 'api/login_process.php';
          const redirectUrl = basePath + 'index.php?mainpage=dashboard&subpage=overview';

          const response = await fetch(apiEndpoint, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              username: username,
              password: password
            })
          });

          const data = await response.json();

          if (data.success) {
            localStorage.setItem('loggedInUser', JSON.stringify(data.user));
            showStatus('✓ Đăng nhập thành công! Đang chuyển hướng...', 'success');
            setTimeout(() => {
              window.location.href = redirectUrl;
            }, 400);
          } else {
            showStatus('✗ ' + data.message, 'error');
          }
        } catch (error) {
          showStatus('✗ Lỗi kết nối máy chủ: ' + error.message, 'error');
        } finally {
          loginButton.disabled = false;
          btnSubmitText.textContent = 'Đăng nhập hệ thống';
        }
      });
    }
  </script>
</body>
</html>