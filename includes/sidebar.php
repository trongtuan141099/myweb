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
  <script src="resources/apexcharts.js"></script>
  <script src="resources/js/bootstrap.bundle.min.js"></script>

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