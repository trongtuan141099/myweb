<?php
/**
 * DX Plastic Group - Sidebar Navigation
 * Quản lý menu 2 cấp đồng bộ theo mô hình mainpage/subpage và phân quyền người dùng
 */
$current_main = $mainpage ?? ($_GET['mainpage'] ?? 'dashboard');
$current_sub  = $subpage ?? ($_GET['subpage'] ?? 'overview');
?>

<aside class="sidebar" id="sidebar">
  <!-- Sidebar Header / Brand Logo -->
  <div class="sidebar-header">
    <a href="index.php?mainpage=dashboard&subpage=overview" class="sidebar-logo">
      <div class="logo-badge">DX</div>
      <span class="logo-text">Plastic Group</span>
    </a>
    <button class="sidebar-toggle-btn d-none d-lg-flex" type="button" aria-label="Thu gọn menu" onclick="toggleSidebar()">
      <span class="material-icons">menu_open</span>
    </button>
  </div>

  <!-- Sidebar Navigation Menu -->
  <nav class="sidebar-menu">
    <!-- 1. Tổng quan (Dashboard) -->
    <?php if (hasPermission('dashboard.view')): ?>
    <div class="has-submenu <?= ($current_main === 'dashboard') ? 'open' : '' ?>">
      <a href="index.php?mainpage=dashboard&subpage=overview" class="menu-item menu-parent <?= ($current_main === 'dashboard') ? 'active' : '' ?>">
        <span class="material-icons">dashboard</span>
        <span class="label">Tổng quan</span>
      </a>
    </div>
    <?php endif; ?>

    <!-- 2. Quản lý sản xuất -->
    <?php 
    $canProdPlan = hasPermission('production.plan');
    $canProdData = hasPermission('production.data');
    if ($canProdPlan || $canProdData): 
    ?>
    <div class="has-submenu <?= ($current_main === 'production') ? 'open' : '' ?>">
      <div class="menu-item menu-parent <?= ($current_main === 'production') ? 'active' : '' ?>" onclick="toggleSubmenu(this)">
        <span class="material-icons">precision_manufacturing</span>
        <span class="label">Quản lý sản xuất</span>
        <span class="material-icons arrow-icon">expand_more</span>
      </div>
      <div class="submenu">
        <?php if ($canProdPlan): ?>
        <a href="index.php?mainpage=production&subpage=production_planing" class="submenu-item <?= ($current_main === 'production' && $current_sub === 'production_planing') ? 'active' : '' ?>">
          <span class="material-icons">analytics</span>
          <span class="label">Kế hoạch sản xuất</span>
        </a>
        <?php endif; ?>
        <?php if ($canProdData): ?>
        <a href="index.php?mainpage=production&subpage=production_data" class="submenu-item <?= ($current_main === 'production' && $current_sub === 'production_data') ? 'active' : '' ?>">
          <span class="material-icons">tune</span>
          <span class="label">Dữ liệu thực tích</span>
        </a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- 3. Quản lý nhân sự -->
    <?php 
    $canHrmView = hasPermission('hrm.view');
    $canHrmManage = hasPermission('hrm.manage');
    if ($canHrmView || $canHrmManage): 
    ?>
    <div class="has-submenu <?= ($current_main === 'hrm') ? 'open' : '' ?>">
      <div class="menu-item menu-parent <?= ($current_main === 'hrm') ? 'active' : '' ?>" onclick="toggleSubmenu(this)">
        <span class="material-icons">people</span>
        <span class="label">Quản lý nhân sự</span>
        <span class="material-icons arrow-icon">expand_more</span>
      </div>
      <div class="submenu">
        <?php if ($canHrmView): ?>
        <a href="index.php?mainpage=hrm&subpage=list" class="submenu-item <?= ($current_main === 'hrm' && $current_sub === 'list') ? 'active' : '' ?>">
          <span class="material-icons">view_list</span>
          <span class="label">Danh sách nhân viên</span>
        </a>
        <?php endif; ?>
        <?php if ($canHrmManage): ?>
        <a href="index.php?mainpage=hrm&subpage=add_employee" class="submenu-item <?= ($current_main === 'hrm' && $current_sub === 'add_employee') ? 'active' : '' ?>">
          <span class="material-icons">person_add</span>
          <span class="label">Thêm nhân viên</span>
        </a>
        <?php endif; ?>
        <?php if ($canHrmView): ?>
        <a href="index.php?mainpage=hrm&subpage=inventory_org_chart" class="submenu-item <?= ($current_main === 'hrm' && $current_sub === 'inventory_org_chart') ? 'active' : '' ?>">
          <span class="material-icons">account_tree</span>
          <span class="label">Sơ đồ nhân sự kiểm kê</span>
        </a>
        <a href="index.php?mainpage=hrm&subpage=leave_management" class="submenu-item <?= ($current_main === 'hrm' && $current_sub === 'leave_management') ? 'active' : '' ?>">
          <span class="material-icons">event_busy</span>
          <span class="label">Quản lý phép nghỉ</span>
        </a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- 3.1. Quản lý tăng ca -->
    <?php if (hasPermission(['overtime.view', 'overtime.import', 'overtime.reconcile', 'overtime.explain', 'overtime.yearly', 'overtime.export'])): ?>
    <div class="has-submenu <?= ($current_main === 'overtime') ? 'open' : '' ?>">
      <div class="menu-item menu-parent <?= ($current_main === 'overtime') ? 'active' : '' ?>" onclick="toggleSubmenu(this)">
        <span class="material-icons">more_time</span>
        <span class="label">Quản lý tăng ca</span>
        <span class="material-icons arrow-icon">expand_more</span>
      </div>
      <div class="submenu">
        <?php if (hasPermission('overtime.view')): ?>
        <a href="index.php?mainpage=overtime&subpage=dashboard" class="submenu-item <?= ($current_main === 'overtime' && $current_sub === 'dashboard') ? 'active' : '' ?>">
          <span class="material-icons">dashboard</span>
          <span class="label">Tổng quan tăng ca</span>
        </a>
        <?php endif; ?>
        <?php if (hasPermission('overtime.import')): ?>
        <a href="index.php?mainpage=overtime&subpage=import" class="submenu-item <?= ($current_main === 'overtime' && $current_sub === 'import') ? 'active' : '' ?>">
          <span class="material-icons">upload_file</span>
          <span class="label">Import dữ liệu Excel</span>
        </a>
        <?php endif; ?>
        <?php if (hasPermission('overtime.reconcile')): ?>
        <a href="index.php?mainpage=overtime&subpage=reconciliation" class="submenu-item <?= ($current_main === 'overtime' && $current_sub === 'reconciliation') ? 'active' : '' ?>">
          <span class="material-icons">fact_check</span>
          <span class="label">Đối soát tăng ca</span>
        </a>
        <?php endif; ?>
        <?php if (hasPermission('overtime.explain')): ?>
        <a href="index.php?mainpage=overtime&subpage=explanations" class="submenu-item <?= ($current_main === 'overtime' && $current_sub === 'explanations') ? 'active' : '' ?>">
          <span class="material-icons">rate_review</span>
          <span class="label">Quản lý giải trình</span>
        </a>
        <?php endif; ?>
        <?php if (hasPermission('overtime.yearly')): ?>
        <a href="index.php?mainpage=overtime&subpage=yearly_control" class="submenu-item <?= ($current_main === 'overtime' && $current_sub === 'yearly_control') ? 'active' : '' ?>">
          <span class="material-icons">alarm_on</span>
          <span class="label">Kiểm soát giới hạn 200h</span>
        </a>
        <?php endif; ?>
        <?php if (hasPermission('overtime.view')): ?>
        <a href="index.php?mainpage=overtime&subpage=records" class="submenu-item <?= ($current_main === 'overtime' && $current_sub === 'records') ? 'active' : '' ?>">
          <span class="material-icons">list_alt</span>
          <span class="label">Tra cứu chi tiết OT</span>
        </a>
        <?php endif; ?>
        <?php if (hasPermission('overtime.export')): ?>
        <a href="index.php?mainpage=overtime&subpage=export" class="submenu-item <?= ($current_main === 'overtime' && $current_sub === 'export') ? 'active' : '' ?>">
          <span class="material-icons">file_download</span>
          <span class="label">Xuất báo cáo Excel</span>
        </a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- 4. Quản lý tài liệu -->
    <?php if (hasPermission('document.view')): ?>
    <div class="has-submenu <?= ($current_main === 'document') ? 'open' : '' ?>">
      <a href="index.php?mainpage=document&subpage=viewer" class="menu-item menu-parent <?= ($current_main === 'document') ? 'active' : '' ?>">
        <span class="material-icons">description</span>
        <span class="label">Quản lý tài liệu</span>
      </a>
    </div>
    <?php endif; ?>

    <!-- 5. Quản lý thiết bị IoT -->
    <?php 
    $canIotView = hasPermission('device.view');
    $canIotHistory = hasPermission('device.history');
    if ($canIotView || $canIotHistory): 
    ?>
    <div class="has-submenu <?= ($current_main === 'iot') ? 'open' : '' ?>">
      <div class="menu-item menu-parent <?= ($current_main === 'iot') ? 'active' : '' ?>" onclick="toggleSubmenu(this)">
        <span class="material-icons">sensors</span>
        <span class="label">Quản lý thiết bị</span>
        <span class="material-icons arrow-icon">expand_more</span>
      </div>
      <div class="submenu">
        <?php if ($canIotView): ?>
        <a href="index.php?mainpage=iot&subpage=device_status" class="submenu-item <?= ($current_main === 'iot' && $current_sub === 'device_status') ? 'active' : '' ?>">
          <span class="material-icons">monitor_heart</span>
          <span class="label">Trạng thái thiết bị</span>
        </a>
        <?php endif; ?>
        <?php if ($canIotHistory): ?>
        <a href="index.php?mainpage=iot&subpage=device_history" class="submenu-item <?= ($current_main === 'iot' && $current_sub === 'device_history') ? 'active' : '' ?>">
          <span class="material-icons">history</span>
          <span class="label">Lịch sử thiết bị</span>
        </a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- 6. Quản lý vật liệu -->
    <?php if (hasPermission('materials.view')): ?>
    <div class="has-submenu <?= ($current_main === 'materials') ? 'open' : '' ?>">
      <a href="index.php?mainpage=materials&subpage=viscoscity" class="menu-item menu-parent <?= ($current_main === 'materials') ? 'active' : '' ?>">
        <span class="material-icons">science</span>
        <span class="label">Độ nhớt vật liệu</span>
      </a>
    </div>
    <?php endif; ?>

    <!-- 7. Quản lý 5S -->
    <?php 
    $can5sView = hasPermission('five_s.view');
    $can5sAudit = hasPermission('five_s.audit');
    $can5sProps = hasPermission('five_s.proposals');
    $can5sSet = hasPermission('five_s.settings');
    if ($can5sView || $can5sAudit || $can5sProps || $can5sSet): 
    ?>
    <div class="has-submenu <?= ($current_main === 'five_s') ? 'open' : '' ?>">
      <div class="menu-item menu-parent <?= ($current_main === 'five_s') ? 'active' : '' ?>" onclick="toggleSubmenu(this)">
        <span class="material-icons">verified</span>
        <span class="label">Quản lý 5S</span>
        <span class="material-icons arrow-icon">expand_more</span>
      </div>
      <div class="submenu">
        <?php if ($can5sView): ?>
        <a href="index.php?mainpage=five_s&subpage=overview" class="submenu-item <?= ($current_main === 'five_s' && $current_sub === 'overview') ? 'active' : '' ?>">
          <span class="material-icons">dashboard</span>
          <span class="label">Tổng quan 5S</span>
        </a>
        <a href="index.php?mainpage=five_s&subpage=list" class="submenu-item <?= ($current_main === 'five_s' && $current_sub === 'list') ? 'active' : '' ?>">
          <span class="material-icons">rule</span>
          <span class="label">Danh sách vi phạm</span>
        </a>
        <?php endif; ?>
        <?php if ($can5sProps): ?>
        <a href="index.php?mainpage=five_s&subpage=proposals" class="submenu-item <?= ($current_main === 'five_s' && $current_sub === 'proposals') ? 'active' : '' ?>">
          <span class="material-icons">lightbulb</span>
          <span class="label">Đề xuất cải tiến</span>
        </a>
        <?php endif; ?>
        <?php if ($can5sAudit): ?>
        <a href="index.php?mainpage=five_s&subpage=mobile_audit" class="submenu-item <?= ($current_main === 'five_s' && $current_sub === 'mobile_audit') ? 'active' : '' ?>">
          <span class="material-icons">fact_check</span>
          <span class="label">Công việc kiểm tra</span>
        </a>
        <?php endif; ?>
        <?php if ($can5sSet): ?>
        <a href="index.php?mainpage=five_s&subpage=settings" class="submenu-item <?= ($current_main === 'five_s' && $current_sub === 'settings') ? 'active' : '' ?>">
          <span class="material-icons">tune</span>
          <span class="label">Thiết lập 5S</span>
        </a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- 8. Tiện ích -->
    <?php if (hasPermission('mixer.view')): ?>
    <div class="has-submenu <?= ($current_main === 'utilities') ? 'open' : '' ?>">
      <div class="menu-item menu-parent <?= ($current_main === 'utilities') ? 'active' : '' ?>" onclick="toggleSubmenu(this)">
        <span class="material-icons">handyman</span>
        <span class="label">Tiện ích</span>
        <span class="material-icons arrow-icon">expand_more</span>
      </div>
      <div class="submenu">
        <a href="index.php?mainpage=utilities&subpage=color_mixer" class="submenu-item <?= ($current_main === 'utilities' && $current_sub === 'color_mixer') ? 'active' : '' ?>">
          <span class="material-icons">palette</span>
          <span class="label">Tra cứu trộn màu</span>
        </a>
        <a href="index.php?mainpage=utilities&subpage=color_mixer_summary" class="submenu-item <?= ($current_main === 'utilities' && $current_sub === 'color_mixer_summary') ? 'active' : '' ?>">
          <span class="material-icons">grid_view</span>
          <span class="label">Bảng tổng quan trộn</span>
        </a>
      </div>
    </div>
    <?php endif; ?>

    <!-- 9. Cài đặt hệ thống -->
    <?php if (hasPermission('role.manage')): ?>
    <div class="has-submenu <?= ($current_main === 'system') ? 'open' : '' ?>">
      <a href="index.php?mainpage=system&subpage=roles" class="menu-item menu-parent <?= ($current_main === 'system') ? 'active' : '' ?>">
        <span class="material-icons">admin_panel_settings</span>
        <span class="label">Vai trò & Quyền</span>
      </a>
    </div>
    <?php endif; ?>
  </nav>
</aside>

<!-- Nền mờ cho Mobile Sidebar -->
<button class="sidebar-backdrop" id="sidebarBackdrop" type="button" aria-label="Đóng menu" onclick="closeSidebar()"></button>