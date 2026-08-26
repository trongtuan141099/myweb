<?php
require "./config/db.php";
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lý nhân sự</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <!-- <script>
        //     $(document).ready(function(){
        // alert("Hello, world!");
        // });
        $("#btsubmit").click(function(){
  alert("The paragraph was clicked.");
}); -->
    </script>
</head>
  <body>

    <div class="container-fluid">
        <!-- Content here -->
        <!-- <h1>Hello, world!</h1> -->

        <!-- 1. Header -->
        <div class="row">
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <a class="navbar-brand" href="#">🏢HRMS</a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#">Home</a>
                        </li>
                        <li class="nav-item">
                        <a class="nav-link" href="#">Link</a>
                        </li>
                        <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Dropdown
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="#">Action</a></li>
                            <li><a class="dropdown-item" href="#">Another action</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#">Something else here</a></li>
                        </ul>
                        </li>
                        <li class="nav-item">
                        <a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Disabled</a>
                        </li>
                    </ul> -->
                    <form class="d-flex">
                        <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search">
                        <button class="btn btn-outline-success" type="submit" id="btsubmit">Search</button>
                    </form>
                    </div>
                </div>
            </nav>
        </div>
        <!-- 1. Header -->

        <!-- 2. Sidebar & Content -->
        <div class="row">
            <!-- 2.1 Sidebar -->
        <div class="col-2">
            <ul class="list-group">

                <!-- MỤC CẤP 1 (Có danh sách con) -->
                <li class="list-group-item">
                <div class="d-flex justify-content-between align-items-center">
                    <span>Nhân viên</span>

                    <!-- NÚT BẤM ĐÓNG/MỞ (Dùng hình chữ V) -->
                    <button class="btn btn-sm btn-light dropdown-toggle"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#subList1"
                    aria-expanded="false">
                    </button>
                </div>

                <!-- CẤP 2 (Mặc định ẩn, sẽ mở khi bấm nút trên) -->
                <div class="collapse mt-2" id="subList1">
                    <ul class="list-group">
                    <a class="list-group-item bg-light" href="index.php?page=nhanvien_danhsach">Danh sách nhân viên</a>
                    <a class="list-group-item bg-light" href="index.php?page=nhanvien_them">Thêm nhân viên</a>
                    </ul>
                </div>
                </li>

                <!-- MỤC CẤP 1 KHÁC (Không có danh sách con) -->
                <li class="list-group-item">
                Cấp 1 - Mục số 2
                </li>

            </ul>
        </div>

            <!-- 2.2 Content -->
            <div class="col-10">
                
                <?php
                $page = $_GET['page'] ?? '';
                    if($page == 'nhanvien_danhsach'){
                        include "./pages/nhanvien_danhsach.php";
                    }
                ?>


            </div>
        </div>
        <!-- 2. Sidebar & Content -->

        <!-- 3. Footer -->
        <div class="row">
            <p class="text-center">Copyright @ 2026</p>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
  </body>
</html>