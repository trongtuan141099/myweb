<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DX Plastic Group</title>
  <link rel="stylesheet" href="resources/icon.css">
  <link rel="stylesheet" href="css/main.css">
  <link rel="stylesheet" href="css/sidebar.css">
  <link rel="stylesheet" href="css/header.css">
  <link rel="stylesheet" href="css/footer.css">
  <link rel="stylesheet" href="resources/css/bootstrap.min.css">
  <script src="resources/apexcharts.js"></script>
  <script src="resources/js/bootstrap.bundle.min.js"></script>


      <!-- <style>
        :root {
            --primary-color: #1976d2;
            --primary-dark: #1565c0;
            --sidebar-bg: #263238;
            --sidebar-hover: #37474f;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%) !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 0.5rem 0 !important;
            flex-shrink: 0;
            min-height: auto;
        }

        .navbar-brand {
            font-weight: 600;
            font-size: 1.5rem;
            color: white !important;
            line-height: 1.2;
        }

        .navbar .nav-link {
            color: white !important;
            padding-top: 0.25rem !important;
            padding-bottom: 0.25rem !important;
            line-height: 1.2;
            height: auto;
        }

        .navbar .navbar-nav {
            align-items: center;
        }

        .navbar-toggler {
            padding: 0.25rem 0.5rem !important;
            font-size: 1rem;
        }

        .navbar .nav-item {
            display: flex;
            align-items: center;
            height: auto;
        }

        .navbar .d-flex {
            gap: 0.5rem;
            align-items: center;
        }

        .navbar .form-control {
            border-radius: 20px;
            border: none;
            padding: 0.4rem 1rem;
            height: 36px;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .navbar .btn-outline-light {
            border-radius: 20px;
            padding: 0.4rem 1rem;
            height: 36px;
            flex-shrink: 0;
        }

        .navbar .dropdown-menu {
            background-color: white;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        .navbar .dropdown-item:hover {
            background-color: #f0f0f0;
            color: var(--primary-color);
        }

        .navbar-content {
            display: flex;
            align-items: center;
            width: 100%;
            padding: 0 1rem;
        }

        .navbar-collapse {
            padding-top: 0 !important;
            padding-bottom: 0 !important;
        }

        .navbar .form-control,
        .navbar .btn {
            margin: 0;
        }

        .container-wrapper {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 0;
        }

        .main-content {
            display: flex;
            flex: 1;
            min-height: 0;
        }

        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            color: white;
            padding: 20px 0;
            box-shadow: 2px 0 8px rgba(0,0,0,0.1);
        }

        .sidebar .accordion-flush {
            background-color: var(--sidebar-bg) !important;
        }

        .sidebar .accordion-button {
            background-color: var(--sidebar-bg) !important;
            color: white !important;
            border: none;
            padding: 12px 20px;
            font-weight: 500;
        }

        .sidebar .accordion-button.collapsed {
            background-color: var(--sidebar-bg) !important;
            color: white !important;
        }

        .sidebar .accordion-button:not(.collapsed) {
            background-color: var(--sidebar-hover) !important;
            color: white !important;
        }

        .sidebar .accordion-button:focus {
            box-shadow: none !important;
            background-color: var(--sidebar-hover) !important;
            color: white !important;
            outline: none;
        }

        .sidebar .accordion-button::after {
            filter: invert(1) brightness(2);
        }

        .sidebar .accordion-item {
            background-color: transparent !important;
            border: none !important;
        }

        .sidebar .accordion-body {
            padding: 0;
            background-color: #37474f;
        }

        .sidebar .list-group {
            background-color: #37474f !important;
        }

        .sidebar .list-group-item {
            background-color: #37474f !important;
            border: none !important;
            color: #ccc !important;
            padding: 10px 30px;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .sidebar .list-group-item:hover,
        .sidebar .list-group-item.active {
            background-color: var(--primary-color) !important;
            color: white !important;
            padding-left: 40px;
        }

        .sidebar .list-group-item:focus {
            background-color: var(--primary-color) !important;
            color: white !important;
            box-shadow: none !important;
        }

        .content-area {
            flex: 1;
            padding: 30px;
            background-color: #f5f5f5;
        }

        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border-radius: 8px;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            border-radius: 8px 8px 0 0;
            padding: 15px 20px;
            font-weight: 600;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            border-radius: 5px;
            padding: 8px 20px;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(25, 118, 210, 0.3);
        }

        .btn-success {
            background-color: #388e3c;
            border: none;
            border-radius: 5px;
        }

        .btn-success:hover {
            background-color: #2e7d32;
        }

        .btn-danger {
            background-color: #d32f2f;
            border: none;
            border-radius: 5px;
        }

        .btn-danger:hover {
            background-color: #c62828;
        }

        .table {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
        }

        .table thead {
            background-color: #f5f5f5;
        }

        .table th {
            color: var(--primary-color);
            font-weight: 600;
            border: none;
            padding: 15px;
        }

        .table td {
            padding: 12px 15px;
            border: none;
            border-bottom: 1px solid #eee;
        }

        .table tbody tr:hover {
            background-color: #fafafa;
        }

        .form-label {
            color: var(--primary-color);
            font-weight: 500;
            margin-bottom: 8px;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(25, 118, 210, 0.15);
        }

        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(25, 118, 210, 0.15);
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .stat-card .stat-label {
            color: #666;
            font-size: 0.9rem;
            margin-top: 10px;
        }

        .footer {
            background-color: #f5f5f5;
            padding: 20px;
            text-align: center;
            color: #999;
            border-top: 1px solid #ddd;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .badge-success {
            background-color: #4caf50;
        }

        .badge-danger {
            background-color: #f44336;
        }

        .badge-warning {
            background-color: #ff9800;
        }

        .badge-info {
            background-color: #2196f3;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
            }

            .main-content {
                flex-direction: column;
            }

            .content-area {
                padding: 15px;
            }
        }
    </style> -->

</head>

  <div class="app-container">
    <aside class="sidebar" id="sidebar">
      <!-- logo area -->
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
  <?php 
    // Lấy tham số mainpage và subpage từ URL
    $mainpage = isset($_GET['mainpage']) ? $_GET['mainpage'] : 'dashboard';
    $subpage = isset($_GET['subpage']) ? $_GET['subpage'] : 'overview';
  ?>

  <!-- 1. Tổng quan -->
  <div class="has-submenu <?= ($mainpage === 'dashboard') ? 'open' : '' ?>">
    <a href="/myweb/index.php?mainpage=dashboard&subpage=overview" class="menu-item menu-parent <?= ($mainpage === 'dashboard') ? 'active' : '' ?>">
      <span class="material-icons">dashboard</span>
      <span class="label">Tổng quan</span>
      <span class="material-icons arrow-icon">expand_more</span>
    </a>
    
    <div class="submenu">
      <a href="/myweb/index.php?mainpage=dashboard&subpage=plan" class="submenu-item <?= ($mainpage === 'dashboard' && $subpage === 'plan') ? 'active' : '' ?>">
        <span class="material-icons sub-icon">tune</span>
        <span class="label">Kế hoạch sản xuất</span>
      </a>
      <a href="/myweb/index.php?mainpage=dashboard&subpage=yield" class="submenu-item <?= ($mainpage === 'dashboard' && $subpage === 'yield') ? 'active' : '' ?>">
        <span class="material-icons sub-icon">check_circle_outline</span>
        <span class="label">Tỷ lệ thành phẩm</span>
      </a>
    </div>
  </div>

  <!-- 2. Quản lý nhân sự -->
  <div class="has-submenu <?= ($mainpage === 'hrm') ? 'open' : '' ?>">
    <a href="/myweb/index.php?mainpage=hrm&subpage=list" class="menu-item menu-parent <?= ($mainpage === 'hrm') ? 'active' : '' ?>">
      <span class="material-icons">people</span>
      <span class="label">Quản lý nhân sự</span>
      <span class="material-icons arrow-icon">expand_more</span>
    </a>
    
    <div class="submenu">
      <a href="/myweb/index.php?mainpage=hrm&subpage=list" class="submenu-item <?= ($mainpage === 'hrm' && $subpage === 'list') ? 'active' : '' ?>">
        <span class="material-icons sub-icon">people</span>
        <span class="label">Danh sách nhân viên</span>
      </a>
      <a href="/myweb/index.php?mainpage=hrm&subpage=chart" class="submenu-item <?= ($mainpage === 'hrm' && $subpage === 'chart') ? 'active' : '' ?>">
        <span class="material-icons sub-icon">bar_chart</span>
        <span class="label">Sơ đồ tổ chức</span>
      </a>
    </div>
  </div>

  <!-- 3. Quản lý tài liệu -->
  <div class="has-submenu <?= ($mainpage === 'document') ? 'open' : '' ?>">
    <a href="/myweb/index.php?mainpage=document&subpage=admin" class="menu-item menu-parent <?= ($mainpage === 'document') ? 'active' : '' ?>">
      <span class="material-icons">description</span>
      <span class="label">Quản lý tài liệu</span>
      <span class="material-icons arrow-icon">expand_more</span>
    </a>
    
    <div class="submenu">
      <a href="/myweb/index.php?mainpage=document&subpage=admin" class="submenu-item <?= ($mainpage === 'document' && $subpage === 'admin') ? 'active' : '' ?>">
        <span class="material-icons sub-icon">list</span>
        <span class="label">Danh sách tài liệu</span>
      </a>
      <a href="/myweb/index.php?mainpage=document&subpage=viewer" class="submenu-item <?= ($mainpage === 'document' && $subpage === 'viewer') ? 'active' : '' ?>">
        <span class="material-icons sub-icon">visibility</span>
        <span class="label">Xem tài liệu</span>
      </a>
    </div>
  </div>

  <!-- 4. Quản lý thiết bị -->
  <div class="has-submenu <?= ($mainpage === 'iot') ? 'open' : '' ?>">
    <a href="/myweb/index.php?mainpage=iot&subpage=admin" class="menu-item menu-parent <?= ($mainpage === 'iot') ? 'active' : '' ?>">
      <span class="material-icons">devices</span>
      <span class="label">Quản lý thiết bị</span>
      <span class="material-icons arrow-icon">expand_more</span>
    </a>
    
    <div class="submenu">
      <a href="/myweb/index.php?mainpage=iot&subpage=device_status" class="submenu-item <?= ($mainpage === 'iot' && $subpage === 'device_status') ? 'active' : '' ?>">
        <span class="material-icons sub-icon">list</span>
        <span class="label">Trạng thái thiết bị</span>
      </a>
      <a href="/myweb/index.php?mainpage=iot&subpage=viewer" class="submenu-item <?= ($mainpage === 'iot' && $subpage === 'viewer') ? 'active' : '' ?>">
        <span class="material-icons sub-icon">visibility</span>
        <span class="label">Xem thiết bị</span>
      </a>
    </div>
  </div>
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