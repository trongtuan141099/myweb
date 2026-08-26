<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Báo cáo Sản xuất - MES Dashboard v7</title>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>

  <div class="app-container">
    <!-- 1. SIDEBAR BÊN TRÁI -->
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <div class="logo-area">
          <span class="material-icons logo-icon">precision_manufacturing</span>
          <span class="logo-text">MES DASHBOARD</span>
        </div>
        <button class="btn-toggle" onclick="toggleSidebar()">
          <span class="material-icons">menu</span>
        </button>
      </div>

      <nav class="sidebar-menu">
        <a href="#" class="menu-item active">
          <span class="material-icons">dashboard</span>
          <span class="label">Tổng quan</span>
        </a>
        <a href="#" class="menu-item">
          <span class="material-icons">event_note</span>
          <span class="label">Kế hoạch</span>
        </a>
        <a href="#" class="menu-item">
          <span class="material-icons">settings_suggest</span>
          <span class="label">CTSX / Chế tạo</span>
        </a>
        <a href="#" class="menu-item">
          <span class="material-icons">show_chart</span>
          <span class="label">Cycle / Pulse</span>
        </a>
        <a href="#" class="menu-item">
          <span class="material-icons">grid_on</span>
          <span class="label">Heatmap</span>
        </a>
        <a href="#" class="menu-item">
          <span class="material-icons">report_problem</span>
          <span class="label">Tổng hợp lỗi QC</span>
        </a>
        <a href="#" class="menu-item">
          <span class="material-icons">build</span>
          <span class="label">Bảo trì thiết bị</span>
        </a>
        <a href="#" class="menu-item">
          <span class="material-icons">analytics</span>
          <span class="label">Báo cáo & Năng lực</span>
        </a>
      </nav>

      <div class="sidebar-footer">
        <div class="user-info">
          <div class="user-avatar">Đ</div>
          <div class="user-details">
            <span class="user-name">Nguyễn Hải Đăng</span>
            <span class="user-role">Cold Drawing PIC</span>
          </div>
        </div>
      </div>
    </aside>

    <!-- 2. KHU VỰC NỘI DUNG CHÍNH -->
    <div class="main-wrapper">
      <!-- HEADER TRÊN -->
      <header class="top-header">
        <div class="header-title-group">
          <h1 class="header-title">生産管理 Dashboard v7</h1>
          <div class="badge-group">
            <span class="badge badge-ng">NG: 12 Pcs</span>
            <span class="badge badge-oee">OEE: 78%</span>
          </div>
        </div>

        <div class="header-actions">
          <div class="lang-selector">
            <button class="lang-btn active">VN</button>
            <button class="lang-btn">JP</button>
            <button class="lang-btn">EN</button>
          </div>
          <button class="btn btn-secondary">
            <span class="material-icons">file_download</span>
            <span>Export Excel</span>
          </button>
          <button class="btn btn-primary">
            <span class="material-icons">save</span>
            <span>Lưu tất cả</span>
          </button>
        </div>
      </header>

      <!-- NỘI DUNG DASHBOARD -->
      <main class="dashboard-body">
        <!-- KPIS -->
        <div class="kpi-grid">
          <div class="kpi-card">
            <div class="kpi-header">
              <span>SẢN LƯỢNG / CA</span>
              <span class="material-icons">speed</span>
            </div>
            <div class="kpi-value-box">
              <span class="kpi-value">450</span>
              <span class="kpi-unit">PCS / CA</span>
            </div>
            <input type="range" class="kpi-slider" value="75">
          </div>

          <div class="kpi-card">
            <div class="kpi-header">
              <span>UTILIZATION (HIỆU SUẤT)</span>
              <span class="material-icons">trending_up</span>
            </div>
            <div class="kpi-value-box">
              <span class="kpi-value">89%</span>
              <span class="kpi-unit">MỤC TIÊU 90%</span>
            </div>
            <input type="range" class="kpi-slider" value="89">
          </div>

          <div class="kpi-card">
            <div class="kpi-header">
              <span>CA SẢN XUẤT</span>
              <span class="material-icons">schedule</span>
            </div>
            <div class="kpi-value-box">
              <span class="kpi-value">2</span>
              <span class="kpi-unit">CA / NGÀY</span>
            </div>
            <input type="range" class="kpi-slider" value="50">
          </div>
        </div>

        <!-- BẢNG DỮ LIỆU KẾ HOẠCH -->
        <div class="data-card">
          <div class="filter-bar">
            <input type="month" class="form-control" value="2026-06">
            <select class="form-control">
              <option>Mã sản phẩm: MB63TD</option>
              <option>Mã sản phẩm: MB64TD</option>
            </select>
            <select class="form-control">
              <option>Nhóm Series: Cold Drawing</option>
            </select>
            <button class="btn btn-primary" style="margin-left: auto;">
              <span class="material-icons">filter_alt</span>
              <span>Lọc dữ liệu</span>
            </button>
          </div>

          <div class="table-container">
            <table class="mes-table">
              <thead>
                <tr>
                  <th style="width: 120px;">MÃ SP</th>
                  <th style="width: 100px;">KH / TT</th>
                  <th>1 (T2)</th>
                  <th>2 (T3)</th>
                  <th>3 (T4)</th>
                  <th>4 (T5)</th>
                  <th>5 (T6)</th>
                  <th>6 (T7)</th>
                  <th>7 (CN)</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td rowspan="2" style="font-weight: 700; color: #ffffff;">PB63TD</td>
                  <td class="cell-kh">Kế hoạch</td>
                  <td>100</td>
                  <td>100</td>
                  <td>120</td>
                  <td>120</td>
                  <td>100</td>
                  <td>-</td>
                  <td>-</td>
                </tr>
                <tr>
                  <td class="cell-tt">Thực tế</td>
                  <td>98</td>
                  <td>102</td>
                  <td>115</td>
                  <td>120</td>
                  <td>-</td>
                  <td>-</td>
                  <td>-</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </main>
    </div>
  </div>

  <script src="../js/dashboard.js"></script>
</body>
</html>