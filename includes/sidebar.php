<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="logo-area">
      <!-- <span class="material-icons logo-icon">precision_manufacturing</span> -->
      <style>
            .logo{
        width:38px;
        height:38px;

        border-radius:10px;

        background:linear-gradient(
            135deg,
            #005BAC,
            #2F80ED
        );

        display:flex;
        align-items:center;
        justify-content:center;

        color:white;
        font-weight:bold;
    }
      </style>
      <div class="logo">DX</div>
      <span class="logo-text">Plastic Group</span>
    </div>
    <button class="btn-toggle" onclick="toggleSidebar()">
      <span class="material-icons">menu</span>
    </button>
  </div>

  <nav class="sidebar-menu">
    <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>

    <!-- 1. Tổng quan -->
    <div class="has-submenu open">
      <a href="dashboard.php" class="menu-item menu-parent <?= in_array($currentPage, [ 'dashboard.php', 'production_planing1.php', 'yield1.php']) ? 'active' : '' ?>">
        <span class="material-icons">dashboard</span>
        <span class="label">Tổng quan</span>
        <span class="material-icons arrow-icon">expand_more</span>
      </a>
      
      <!-- Danh sách chỉ mục con -->
      <div class="submenu">
        <a href="production_planing1.php" class="submenu-item <?= $currentPage == 'production_planing1.php' ? 'active' : '' ?>">
          <span class="material-icons sub-icon">tune</span>
          <span class="label">Kế hoạch sản xuất</span>
        </a>
        <a href="yield1.php" class="submenu-item <?= $currentPage == 'yield1.php' ? 'active' : '' ?>">
          <span class="material-icons sub-icon">check_circle_outline</span>
          <span class="label">Tỷ lệ thành phẩm</span>
        </a>
      </div>
    </div>

    <!-- 2. Nhân sự -->

    <div class="has-submenu open">
      <a  class="menu-item menu-parent <?= in_array($currentPage, ['hrdashboard.php', 'employeelist.php', 'hrchart.php']) ? 'active' : '' ?>">
        <span class="material-icons">people</span>
        <span class="label">Quản lý nhân sự</span>
        <span class="material-icons arrow-icon">expand_more</span>
      </a>
      
      <!-- Danh sách chỉ mục con -->
      <div class="submenu">
        <a  class="submenu-item <?= $currentPage == 'employeelist.php' ? 'active' : '' ?>">
          <span class="material-icons sub-icon">people</span>
          <span class="label">Danh sách nhân viên</span>
        </a>
        <a  class="submenu-item <?= $currentPage == 'hrchart.php' ? 'active' : '' ?>">
          <span class="material-icons sub-icon">bar_chart</span>
          <span class="label">Sơ đồ tổ chức</span>
        </a>
      </div>
    </div>

        <!-- 3. Quản lý tài liệu -->

    <div class="has-submenu open">
      <a  class="menu-item menu-parent <?= in_array($currentPage, ['document_admin.php', 'document_viewer.php']) ? 'active' : '' ?>">
        <span class="material-icons">description</span>
        <span class="label">Quản lý tài liệu</span>
        <span class="material-icons arrow-icon">expand_more</span>
      </a>
      
      <!-- Danh sách chỉ mục con -->
      <div class="submenu">
        <a href="document_admin.php" class="submenu-item <?= $currentPage == 'document_admin.php' ? 'active' : '' ?>">
          <span class="material-icons sub-icon">list</span>
          <span class="label">Danh sách tài liệu</span>
        </a>
        <a href="document_viewer.php" class="submenu-item <?= $currentPage == 'document_viewer.php' ? 'active' : '' ?>">
          <span class="material-icons sub-icon">visibility</span>
          <span class="label">Xem tài liệu</span>
        </a>
      </div>
    </div>

    <!-- 2. CÁC MENU KHÁC -->
    <!-- <a href="plan.php" class="menu-item <?= $currentPage == 'plan.php' ? 'active' : '' ?>">
      <span class="material-icons">event_note</span>
      <span class="label">Kế hoạch</span>
    </a>
    <a href="#" class="menu-item">
      <span class="material-icons">settings_suggest</span>
      <span class="label">CTSX / Chế tạo</span>
    </a>
    <a href="#" class="menu-item">
      <span class="material-icons">report_problem</span>
      <span class="label">Tổng hợp lỗi QC</span>
    </a> -->
  </nav>

  <div class="sidebar-footer">
    <div class="user-info">
      <div class="user-avatar">T</div>
      <div class="user-details">
        <span class="user-name" id="userFullname">-</span>
        <!-- <span class="user-role"></span> -->
      </div>
      </div>
      </div>
</aside>