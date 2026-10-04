<?php
/**
 * DX Plastic Group - Sidebar Navigation
 * Quản lý menu 2 cấp đồng bộ theo mô hình mainpage/subpage và phân quyền người dùng
 */
$current_main = $mainpage ?? ($_GET['mainpage'] ?? 'dashboard');
$current_sub  = $subpage ?? ($_GET['subpage'] ?? 'overview');
$is_guest     = !isset($_SESSION['user_id']) && !isset($_SESSION['user']);
?>

<aside class="sidebar" id="sidebar">
  <!-- Sidebar Header / Brand Logo -->
  <div class="sidebar-header">
    <a href="<?= $is_guest ? 'index.php?mainpage=materials&subpage=viscoscity' : 'index.php?mainpage=dashboard&subpage=overview' ?>" class="sidebar-logo">
      <div class="logo-badge">DX</div>
      <span class="logo-text">Plastic Group</span>
    </a>
    <button class="sidebar-toggle-btn d-none d-lg-flex" type="button" aria-label="Thu gọn menu" onclick="toggleSidebar()">
      <span class="material-icons">menu_open</span>
    </button>
  </div>

  <!-- Sidebar Navigation Menu -->
  <nav class="sidebar-menu">
    <?php if ($is_guest): ?>
    <div style="padding: 10px 16px 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--dx-text-muted); letter-spacing: 0.5px;" data-i18n="nav.quick_utilities">
      <?= __('nav.quick_utilities', 'Tiện ích tra cứu nhanh') ?>
    </div>

    <!-- 1. Độ nhớt vật liệu -->
    <div class="has-submenu <?= ($current_main === 'materials') ? 'open' : '' ?>">
      <a href="index.php?mainpage=materials&subpage=viscoscity" class="menu-item menu-parent <?= ($current_main === 'materials' && $current_sub === 'viscoscity') ? 'active' : '' ?>">
        <span class="material-icons" style="color: var(--dx-primary, #0ea5e9);">science</span>
        <span class="label" data-i18n="nav.viscosity"><?= __('nav.viscosity', 'Độ nhớt vật liệu') ?></span>
      </a>
    </div>

    <!-- 2. Tiện ích trộn màu -->
    <div class="has-submenu <?= ($current_main === 'utilities') ? 'open' : '' ?>">
      <div class="menu-item menu-parent <?= ($current_main === 'utilities') ? 'active' : '' ?>" onclick="toggleSubmenu(this)">
        <span class="material-icons" style="color: var(--dx-success, #10b981);">palette</span>
        <span class="label" data-i18n="nav.color_mixer_group"><?= __('nav.color_mixer_group', 'Tiện ích trộn màu') ?></span>
        <span class="material-icons arrow-icon">expand_more</span>
      </div>
      <div class="submenu">
        <a href="index.php?mainpage=utilities&subpage=color_mixer" class="submenu-item <?= ($current_main === 'utilities' && $current_sub === 'color_mixer') ? 'active' : '' ?>">
          <span class="material-icons">manage_search</span>
          <span class="label" data-i18n="nav.color_mixer"><?= __('nav.color_mixer', 'Tra cứu trộn màu') ?></span>
        </a>
        <a href="index.php?mainpage=utilities&subpage=color_mixer_summary" class="submenu-item <?= ($current_main === 'utilities' && $current_sub === 'color_mixer_summary') ? 'active' : '' ?>">
          <span class="material-icons">grid_view</span>
          <span class="label" data-i18n="nav.color_mixer_summary"><?= __('nav.color_mixer_summary', 'Bảng tổng quan trộn') ?></span>
        </a>
      </div>
    </div>

    <div style="margin-top: 24px; padding: 12px 14px;">
      <a href="index.php?mainpage=authentication&subpage=login" class="app-btn app-btn-primary w-100" style="display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; border-radius: 8px; font-weight: 600; padding: 10px 14px;">
        <span class="material-icons" style="font-size: 18px;">lock</span>
        <span data-i18n="nav.login"><?= __('nav.login', 'Đăng nhập hệ thống') ?></span>
      </a>
    </div>
    <?php else: ?>
    <!-- 1. Tổng quan (Dashboard) -->
    <?php if (hasPermission('dashboard.view')): ?>
    <div class="has-submenu <?= ($current_main === 'dashboard') ? 'open' : '' ?>">
      <a href="index.php?mainpage=dashboard&subpage=overview" class="menu-item menu-parent <?= ($current_main === 'dashboard') ? 'active' : '' ?>">
        <span class="material-icons">dashboard</span>
        <span class="label" data-i18n="nav.dashboard"><?= __('nav.dashboard', 'Tổng quan') ?></span>
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
        <span class="label" data-i18n="nav.production"><?= __('nav.production', 'Quản lý sản xuất') ?></span>
        <span class="material-icons arrow-icon">expand_more</span>
      </div>
      <div class="submenu">
        <?php if ($canProdPlan): ?>
        <a href="index.php?mainpage=production&subpage=production_planing" class="submenu-item <?= ($current_main === 'production' && $current_sub === 'production_planing') ? 'active' : '' ?>">
          <span class="material-icons">analytics</span>
          <span class="label" data-i18n="nav.production_plan"><?= __('nav.production_plan', 'Kế hoạch sản xuất') ?></span>
        </a>
        <?php endif; ?>
        <?php if ($canProdData): ?>
        <a href="index.php?mainpage=production&subpage=production_data" class="submenu-item <?= ($current_main === 'production' && $current_sub === 'production_data') ? 'active' : '' ?>">
          <span class="material-icons">tune</span>
          <span class="label" data-i18n="nav.production_data"><?= __('nav.production_data', 'Dữ liệu thực tích') ?></span>
        </a>
        <a href="index.php?mainpage=production&subpage=extrusion_summary" class="submenu-item <?= ($current_main === 'production' && $current_sub === 'extrusion_summary') ? 'active' : '' ?>">
          <span class="material-icons">stacked_bar_chart</span>
          <span class="label" data-i18n="nav.extrusion_summary"><?= __('nav.extrusion_summary', 'Tổng hợp sản lượng đùn ép') ?></span>
        </a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- 2.0. Quản lý đơn hàng (Order Management) -->
    <?php if (hasPermission(['orders.view', 'orders.create', 'orders.manage', 'admin'])): ?>
    <div class="has-submenu <?= ($current_main === 'orders') ? 'open' : '' ?>">
      <div class="menu-item menu-parent <?= ($current_main === 'orders') ? 'active' : '' ?>" onclick="toggleSubmenu(this)">
        <span class="material-icons">receipt_long</span>
        <span class="label" data-i18n="nav.orders"><?= __('nav.orders', 'Quản lý đơn hàng') ?></span>
        <span class="material-icons arrow-icon">expand_more</span>
      </div>
      <div class="submenu">
        <a href="index.php?mainpage=orders&subpage=don_b" class="submenu-item <?= ($current_main === 'orders' && $current_sub === 'don_b') ? 'active' : '' ?>">
          <span class="material-icons">assignment</span>
          <span class="label" data-i18n="nav.don_b"><?= __('nav.don_b', 'Quản lý Đơn B') ?></span>
        </a>
      </div>
    </div>
    <?php endif; ?>

    <!-- 2.1. Quản lý chất lượng -->
    <?php if (hasPermission(['quality.view', 'quality.manage', 'quality.investigate', 'admin'])): ?>
    <div class="has-submenu <?= ($current_main === 'quality') ? 'open' : '' ?>">
      <div class="menu-item menu-parent <?= ($current_main === 'quality') ? 'active' : '' ?>" onclick="toggleSubmenu(this)">
        <span class="material-icons">verified_user</span>
        <span class="label" data-i18n="nav.quality"><?= __('nav.quality', 'Quản lý chất lượng') ?></span>
        <span class="material-icons arrow-icon">expand_more</span>
      </div>
      <div class="submenu">
        <a href="index.php?mainpage=quality&subpage=yield_tracking" class="submenu-item <?= ($current_main === 'quality' && $current_sub === 'yield_tracking') ? 'active' : '' ?>">
          <span class="material-icons">query_stats</span>
          <span class="label" data-i18n="nav.yield_tracking"><?= __('nav.yield_tracking', 'Theo dõi tỉ lệ thành phẩm') ?></span>
        </a>
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
        <span class="label" data-i18n="nav.hrm"><?= __('nav.hrm', 'Quản lý nhân sự') ?></span>
        <span class="material-icons arrow-icon">expand_more</span>
      </div>
      <div class="submenu">
        <?php if ($canHrmView): ?>
        <a href="index.php?mainpage=hrm&subpage=list" class="submenu-item <?= ($current_main === 'hrm' && $current_sub === 'list') ? 'active' : '' ?>">
          <span class="material-icons">view_list</span>
          <span class="label" data-i18n="nav.hrm_list"><?= __('nav.hrm_list', 'Danh sách nhân viên') ?></span>
        </a>
        <?php endif; ?>
        <?php if ($canHrmManage): ?>
        <a href="index.php?mainpage=hrm&subpage=add_employee" class="submenu-item <?= ($current_main === 'hrm' && $current_sub === 'add_employee') ? 'active' : '' ?>">
          <span class="material-icons">person_add</span>
          <span class="label" data-i18n="nav.hrm_add"><?= __('nav.hrm_add', 'Thêm nhân viên') ?></span>
        </a>
        <?php endif; ?>
        <?php if ($canHrmView): ?>
        <a href="index.php?mainpage=hrm&subpage=inventory_org_chart" class="submenu-item <?= ($current_main === 'hrm' && $current_sub === 'inventory_org_chart') ? 'active' : '' ?>">
          <span class="material-icons">account_tree</span>
          <span class="label" data-i18n="nav.hrm_org"><?= __('nav.hrm_org', 'Sơ đồ nhân sự kiểm kê') ?></span>
        </a>
        <a href="index.php?mainpage=hrm&subpage=leave_management" class="submenu-item <?= ($current_main === 'hrm' && $current_sub === 'leave_management') ? 'active' : '' ?>">
          <span class="material-icons">event_busy</span>
          <span class="label" data-i18n="nav.hrm_leave"><?= __('nav.hrm_leave', 'Quản lý phép nghỉ') ?></span>
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
        <span class="label" data-i18n="nav.overtime"><?= __('nav.overtime', 'Quản lý tăng ca') ?></span>
        <span class="material-icons arrow-icon">expand_more</span>
      </div>
      <div class="submenu">
        <?php if (hasPermission('overtime.view')): ?>
        <a href="index.php?mainpage=overtime&subpage=dashboard" class="submenu-item <?= ($current_main === 'overtime' && $current_sub === 'dashboard') ? 'active' : '' ?>">
          <span class="material-icons">dashboard</span>
          <span class="label" data-i18n="nav.ot_dashboard"><?= __('nav.ot_dashboard', 'Tổng quan tăng ca') ?></span>
        </a>
        <?php endif; ?>
        <?php if (hasPermission('overtime.import')): ?>
        <a href="index.php?mainpage=overtime&subpage=import" class="submenu-item <?= ($current_main === 'overtime' && $current_sub === 'import') ? 'active' : '' ?>">
          <span class="material-icons">upload_file</span>
          <span class="label" data-i18n="nav.ot_import"><?= __('nav.ot_import', 'Import dữ liệu Excel') ?></span>
        </a>
        <?php endif; ?>
        <?php if (hasPermission('overtime.reconcile')): ?>
        <a href="index.php?mainpage=overtime&subpage=reconciliation" class="submenu-item <?= ($current_main === 'overtime' && $current_sub === 'reconciliation') ? 'active' : '' ?>">
          <span class="material-icons">fact_check</span>
          <span class="label" data-i18n="nav.ot_reconcile"><?= __('nav.ot_reconcile', 'Đối soát tăng ca') ?></span>
        </a>
        <?php endif; ?>
        <?php if (hasPermission('overtime.explain')): ?>
        <a href="index.php?mainpage=overtime&subpage=explanations" class="submenu-item <?= ($current_main === 'overtime' && $current_sub === 'explanations') ? 'active' : '' ?>">
          <span class="material-icons">rate_review</span>
          <span class="label" data-i18n="nav.ot_explain"><?= __('nav.ot_explain', 'Giải trình tăng ca') ?></span>
        </a>
        <?php endif; ?>
        <?php if (hasPermission('overtime.yearly')): ?>
        <a href="index.php?mainpage=overtime&subpage=yearly_control" class="submenu-item <?= ($current_main === 'overtime' && $current_sub === 'yearly_control') ? 'active' : '' ?>">
          <span class="material-icons">alarm_on</span>
          <span class="label" data-i18n="nav.ot_yearly"><?= __('nav.ot_yearly', 'Kiểm soát luỹ kế năm') ?></span>
        </a>
        <?php endif; ?>
        <?php if (hasPermission('overtime.view')): ?>
        <a href="index.php?mainpage=overtime&subpage=records" class="submenu-item <?= ($current_main === 'overtime' && $current_sub === 'records') ? 'active' : '' ?>">
          <span class="material-icons">list_alt</span>
          <span class="label" data-i18n="nav.ot_records"><?= __('nav.ot_records', 'Dữ liệu tăng ca') ?></span>
        </a>
        <?php endif; ?>
        <?php if (hasPermission('overtime.export')): ?>
        <a href="index.php?mainpage=overtime&subpage=export" class="submenu-item <?= ($current_main === 'overtime' && $current_sub === 'export') ? 'active' : '' ?>">
          <span class="material-icons">file_download</span>
          <span class="label" data-i18n="nav.ot_export"><?= __('nav.ot_export', 'Xuất báo cáo') ?></span>
        </a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- 3.2. Quản lý kho -->
    <?php if (hasPermission(['warehouse.view', 'warehouse.create', 'warehouse.check', 'warehouse.approve', 'warehouse.issue', 'warehouse.handover'])): ?>
    <div class="has-submenu <?= ($current_main === 'warehouse') ? 'open' : '' ?>">
      <div class="menu-item menu-parent <?= ($current_main === 'warehouse') ? 'active' : '' ?>" onclick="toggleSubmenu(this)">
        <span class="material-icons">inventory_2</span>
        <span class="label" data-i18n="nav.warehouse"><?= __('nav.warehouse', 'Quản lý kho') ?></span>
        <span class="material-icons arrow-icon">expand_more</span>
      </div>
      <div class="submenu">
        <a href="index.php?mainpage=warehouse&subpage=issue_request" class="submenu-item <?= ($current_main === 'warehouse' && $current_sub === 'issue_request') ? 'active' : '' ?>">
          <span class="material-icons">post_add</span>
          <span class="label" data-i18n="nav.wh_issue"><?= __('nav.wh_issue', 'Yêu cầu xuất kho') ?></span>
        </a>
        <a href="index.php?mainpage=warehouse&subpage=approval" class="submenu-item <?= ($current_main === 'warehouse' && $current_sub === 'approval') ? 'active' : '' ?>">
          <span class="material-icons">fact_check</span>
          <span class="label" data-i18n="nav.wh_approval"><?= __('nav.wh_approval', 'Phê duyệt yêu cầu') ?></span>
        </a>
        <a href="index.php?mainpage=warehouse&subpage=materials" class="submenu-item <?= ($current_main === 'warehouse' && $current_sub === 'materials') ? 'active' : '' ?>">
          <span class="material-icons">category</span>
          <span class="label" data-i18n="nav.wh_materials"><?= __('nav.wh_materials', 'Danh mục vật liệu') ?></span>
        </a>
        <a href="index.php?mainpage=warehouse&subpage=reorder_tracking" class="submenu-item <?= ($current_main === 'warehouse' && $current_sub === 'reorder_tracking') ? 'active' : '' ?>">
          <span class="material-icons">track_changes</span>
          <span class="label" data-i18n="nav.wh_reorder"><?= __('nav.wh_reorder', 'Cảnh báo tồn kho') ?></span>
        </a>
        <a href="index.php?mainpage=warehouse&subpage=dashboard" class="submenu-item <?= ($current_main === 'warehouse' && $current_sub === 'dashboard') ? 'active' : '' ?>">
          <span class="material-icons">insights</span>
          <span class="label" data-i18n="nav.wh_dashboard"><?= __('nav.wh_dashboard', 'Bảng tổng quan kho') ?></span>
        </a>
        <?php if (hasPermission(['role.manage', 'warehouse.settings', 'admin'])): ?>
        <a href="index.php?mainpage=warehouse&subpage=settings" class="submenu-item <?= ($current_main === 'warehouse' && $current_sub === 'settings') ? 'active' : '' ?>">
          <span class="material-icons">tune</span>
          <span class="label" data-i18n="nav.wh_settings"><?= __('nav.wh_settings', 'Thiết lập định mức') ?></span>
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
        <span class="label" data-i18n="nav.document"><?= __('nav.document', 'Quản lý tài liệu') ?></span>
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
        <span class="label" data-i18n="nav.iot"><?= __('nav.iot', 'Giám sát IoT') ?></span>
        <span class="material-icons arrow-icon">expand_more</span>
      </div>
      <div class="submenu">
        <?php if ($canIotView): ?>
        <a href="index.php?mainpage=iot&subpage=device_status" class="submenu-item <?= ($current_main === 'iot' && $current_sub === 'device_status') ? 'active' : '' ?>">
          <span class="material-icons">monitor_heart</span>
          <span class="label" data-i18n="nav.iot_status"><?= __('nav.iot_status', 'Trạng thái thiết bị') ?></span>
        </a>
        <?php endif; ?>
        <?php if ($canIotHistory): ?>
        <a href="index.php?mainpage=iot&subpage=device_history" class="submenu-item <?= ($current_main === 'iot' && $current_sub === 'device_history') ? 'active' : '' ?>">
          <span class="material-icons">history</span>
          <span class="label" data-i18n="nav.iot_history"><?= __('nav.iot_history', 'Lịch sử thiết bị') ?></span>
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
        <span class="label" data-i18n="nav.viscosity"><?= __('nav.viscosity', 'Độ nhớt vật liệu') ?></span>
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
        <span class="label" data-i18n="nav.five_s"><?= __('nav.five_s', 'Quản lý 5S') ?></span>
        <span class="material-icons arrow-icon">expand_more</span>
      </div>
      <div class="submenu">
        <?php if ($can5sView): ?>
        <a href="index.php?mainpage=five_s&subpage=overview" class="submenu-item <?= ($current_main === 'five_s' && $current_sub === 'overview') ? 'active' : '' ?>">
          <span class="material-icons">dashboard</span>
          <span class="label" data-i18n="nav.five_s_overview"><?= __('nav.five_s_overview', 'Tổng quan 5S') ?></span>
        </a>
        <a href="index.php?mainpage=five_s&subpage=list" class="submenu-item <?= ($current_main === 'five_s' && $current_sub === 'list') ? 'active' : '' ?>">
          <span class="material-icons">rule</span>
          <span class="label" data-i18n="nav.five_s_list"><?= __('nav.five_s_list', 'Danh sách vi phạm') ?></span>
        </a>
        <?php endif; ?>
        <?php if ($can5sProps): ?>
        <a href="index.php?mainpage=five_s&subpage=proposals" class="submenu-item <?= ($current_main === 'five_s' && $current_sub === 'proposals') ? 'active' : '' ?>">
          <span class="material-icons">lightbulb</span>
          <span class="label" data-i18n="nav.five_s_proposals"><?= __('nav.five_s_proposals', 'Đề xuất cải tiến') ?></span>
        </a>
        <?php endif; ?>
        <?php if ($can5sAudit): ?>
        <a href="index.php?mainpage=five_s&subpage=mobile_audit" class="submenu-item <?= ($current_main === 'five_s' && $current_sub === 'mobile_audit') ? 'active' : '' ?>">
          <span class="material-icons">fact_check</span>
          <span class="label" data-i18n="nav.five_s_audit"><?= __('nav.five_s_audit', 'Công việc kiểm tra') ?></span>
        </a>
        <?php endif; ?>
        <?php if ($can5sSet): ?>
        <a href="index.php?mainpage=five_s&subpage=settings" class="submenu-item <?= ($current_main === 'five_s' && $current_sub === 'settings') ? 'active' : '' ?>">
          <span class="material-icons">tune</span>
          <span class="label" data-i18n="nav.five_s_settings"><?= __('nav.five_s_settings', 'Thiết lập 5S') ?></span>
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
        <span class="label" data-i18n="nav.utilities"><?= __('nav.utilities', 'Tiện ích') ?></span>
        <span class="material-icons arrow-icon">expand_more</span>
      </div>
      <div class="submenu">
        <a href="index.php?mainpage=utilities&subpage=color_mixer" class="submenu-item <?= ($current_main === 'utilities' && $current_sub === 'color_mixer') ? 'active' : '' ?>">
          <span class="material-icons">palette</span>
          <span class="label" data-i18n="nav.color_mixer"><?= __('nav.color_mixer', 'Tra cứu trộn màu') ?></span>
        </a>
        <a href="index.php?mainpage=utilities&subpage=color_mixer_summary" class="submenu-item <?= ($current_main === 'utilities' && $current_sub === 'color_mixer_summary') ? 'active' : '' ?>">
          <span class="material-icons">grid_view</span>
          <span class="label" data-i18n="nav.color_mixer_summary"><?= __('nav.color_mixer_summary', 'Bảng tổng quan trộn') ?></span>
        </a>
      </div>
    </div>
    <?php endif; ?>

    <!-- 9. Cài đặt nâng cao / Quản trị hệ thống (Advanced Settings) -->
    <?php if (hasPermission(['role.manage', 'system.language', 'admin'])): ?>
    <div class="has-submenu <?= ($current_main === 'system') ? 'open' : '' ?>">
      <div class="menu-item menu-parent <?= ($current_main === 'system') ? 'active' : '' ?>" onclick="toggleSubmenu(this)">
        <span class="material-icons">settings_suggest</span>
        <span class="label" data-i18n="nav.system_settings"><?= __('nav.system_settings', 'Cài đặt nâng cao') ?></span>
        <span class="material-icons arrow-icon">expand_more</span>
      </div>
      <div class="submenu">
        <a href="index.php?mainpage=system&subpage=roles" class="submenu-item <?= ($current_main === 'system' && $current_sub === 'roles') ? 'active' : '' ?>">
          <span class="material-icons">manage_accounts</span>
          <span class="label" data-i18n="nav.roles"><?= __('nav.roles', 'Vai trò & Phân quyền') ?></span>
        </a>
        <a href="index.php?mainpage=system&subpage=language_settings" class="submenu-item <?= ($current_main === 'system' && in_array($current_sub, ['language_settings', 'languages'])) ? 'active' : '' ?>">
          <span class="material-icons">translate</span>
          <span class="label" data-i18n="nav.language_settings"><?= __('nav.language_settings', 'Thiết lập ngôn ngữ') ?></span>
        </a>
      </div>
    </div>
    <?php endif; ?>
    <?php endif; // End is_guest check ?>
  </nav>
</aside>

<!-- Nền mờ cho Mobile Sidebar -->
<button class="sidebar-backdrop" id="sidebarBackdrop" type="button" aria-label="Đóng menu" onclick="closeSidebar()"></button>