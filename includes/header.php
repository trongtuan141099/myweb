<header class="top-header">
  <!-- <div class="header-title-group">
    <h1 class="header-title"><?= isset($pageTitle) ? $pageTitle : '生産管理 Dashboard v7' ?></h1>
    <div class="badge-group">
      <span class="badge badge-ng">NG: 12 Pcs</span>
      <span class="badge badge-oee">OEE: 78%</span>
    </div>
  </div> -->

  <div class="header-actions">
    <div class="lang-selector">
      <button class="lang-btn active">VN</button>
      <button class="lang-btn">JP</button>
      <button class="lang-btn">EN</button>
    </div>
    <!-- <button class="btn btn-secondary">
      <span class="material-icons">file_download</span>
      <span>Export Excel</span>
    </button> -->
    <button class="btn btn-primary" onclick="logout()">
      <span class="material-icons">logout</span>
      <span>Đăng xuất</span>
    </button>
    <!-- <button class="btn-logout" onclick="logout()">Đăng xuất</button> -->
  </div>
</header>