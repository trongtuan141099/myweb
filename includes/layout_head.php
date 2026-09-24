<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - DX Plastic Group' : 'DX Plastic Group - Quản Lý Sản Xuất' ?></title>

  <!-- Local Icon & CSS Libraries -->
  <link rel="stylesheet" href="resources/icon.css">
  <link rel="stylesheet" href="css/material-icons.css">
  <link rel="stylesheet" href="resources/css/bootstrap.min.css">

  <!-- Thư Viện CSS Chung Duy Nhất (Industrial Design System) -->
  <link rel="stylesheet" href="css/main.css?v=2.0">

  <!-- Khởi tạo Theme ngay lập tức để tránh hiện tượng chớp sáng (Zero-Flicker) -->
  <script>
    (function() {
      const savedTheme = localStorage.getItem('dx-theme') || 
        (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
      document.documentElement.setAttribute('data-theme', savedTheme);
    })();
  </script>

  <!-- Local Script Dependencies -->
  <script src="resources/apexcharts.js"></script>
  <script src="resources/js/bootstrap.bundle.min.js"></script>

  <!-- Global Permission System for Frontend JS -->
  <?php renderPermissionScript(); ?>
</head>
<body>
  <div class="app-container">

