<?php
require "config/db.php";
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


                <!-- <div class="col-2">
                    <div class="accordion-body">
                        <h2 class="accordion-header" id="flush-headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseTwo" aria-expanded="false" aria-controls="flush-collapseTwo">
                                Tổng quan
                            </button>
                        </h2>
                        <ul>
                            <li><a href="#">Dashboard #1</a></li>
                            <li><a href="#">Dashboard #2</a></li>
                        </ul>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="flush-headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseTwo" aria-expanded="false" aria-controls="flush-collapseTwo">
                                Master Data
                            </button>
                        </h2>
                        <div id="flush-collapseTwo" class="accordion-collapse collapse" aria-labelledby="flush-headingTwo" data-bs-parent="#accordionFlushExample">
                            <div class="accordion-body">
                                <ul>
                                    <li><a href="#">Danh sách phòng ban</a></li>
                                    <li><a href="#">Quy định</a></li>
                                    <li><a href="#">Tài khoản</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="flush-headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseThree" aria-expanded="false" aria-controls="flush-collapseThree">
                                Nhân viên
                            </button>
                        </h2>
                        <div id="flush-collapseThree" class="accordion-collapse collapse" aria-labelledby="flush-headingThree" data-bs-parent="#accordionFlushExample">
                            <div class="accordion-body">
                                <ul>
                                    <li><a href="index.php?page=nhanvien_danhsach">Danh sách nhân viên</a></li>
                                    <li><a href="index.php?page=nhanvien_them">Thêm nhân viên</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>

                      <div -->
                            <div
        class="d-flex flex-column flex-shrink-0 p-3 bg-body-tertiary"
        style="width: 280px"
      >
        <a
          href="/"
          class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-body-emphasis text-decoration-none"
        >
          <svg
            class="bi pe-none me-2"
            width="40"
            height="32"
            aria-hidden="true"
          >
            <use xlink:href="#bootstrap"></use>
          </svg>
          <span class="fs-4">Sidebar</span>
        </a>
        <hr />
        <ul class="nav nav-pills flex-column mb-auto">
          <li class="nav-item">
            <a href="#" class="nav-link active" aria-current="page">
              <svg
                class="bi pe-none me-2"
                width="16"
                height="16"
                aria-hidden="true"
              >
                <use xlink:href="#home"></use>
              </svg>
              Home
            </a>
          </li>
          <li>
            <a href="#" class="nav-link link-body-emphasis">
              <svg
                class="bi pe-none me-2"
                width="16"
                height="16"
                aria-hidden="true"
              >
                <use xlink:href="#speedometer2"></use>
              </svg>
              Dashboard
            </a>
          </li>
          <li>
            <a href="#" class="nav-link link-body-emphasis">
              <svg
                class="bi pe-none me-2"
                width="16"
                height="16"
                aria-hidden="true"
              >
                <use xlink:href="#table"></use>
              </svg>
              Orders
            </a>
          </li>
          <li>
            <a href="#" class="nav-link link-body-emphasis">
              <svg
                class="bi pe-none me-2"
                width="16"
                height="16"
                aria-hidden="true"
              >
                <use xlink:href="#grid"></use>
              </svg>
              Products
            </a>
          </li>
          <li>
            <a href="#" class="nav-link link-body-emphasis">
              <svg
                class="bi pe-none me-2"
                width="16"
                height="16"
                aria-hidden="true"
              >
                <use xlink:href="#people-circle"></use>
              </svg>
              Customers
            </a>
          </li>
        </ul>
        <hr />
        <div class="dropdown">
          <a
            href="#"
            class="d-flex align-items-center link-body-emphasis text-decoration-none dropdown-toggle"
            data-bs-toggle="dropdown"
            aria-expanded="false"
          >
            <img
              src="https://github.com/mdo.png"
              alt=""
              width="32"
              height="32"
              class="rounded-circle me-2"
            />
            <strong>mdo</strong>
          </a>
          <ul class="dropdown-menu text-small shadow">
            <li><a class="dropdown-item" href="#">New project...</a></li>
            <li><a class="dropdown-item" href="#">Settings</a></li>
            <li><a class="dropdown-item" href="#">Profile</a></li>
            <li><hr class="dropdown-divider" /></li>
            <li><a class="dropdown-item" href="#">Sign out</a></li>
          </ul>
        </div>
      </div>

            <!-- <div class="col-2">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="flush-headingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseTwo" aria-expanded="false" aria-controls="flush-collapseTwo">
                            Master Data
                        </button>
                    </h2>
                    <div id="flush-collapseTwo" class="accordion-collapse collapse" aria-labelledby="flush-headingTwo" data-bs-parent="#accordionFlushExample">
                        <div class="accordion-body">
                            <div class="list-group">
                                <a href="#" class="list-group-item list-group-item-action">Danh sách phòng ban</a>
                                <a href="#" class="list-group-item list-group-item-action">Quy định</a>
                                <a href="#" class="list-group-item list-group-item-action">Form chuẩn</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="flush-headingThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseThree" aria-expanded="false" aria-controls="flush-collapseThree">
                            Nhân viên
                        </button>
                    </h2>
                    <div id="flush-collapseThree" class="accordion-collapse collapse" aria-labelledby="flush-headingThree" data-bs-parent="#accordionFlushExample">
                        <div class="accordion-body">
                            <ul>
                                <li><a href="index.php?page=nhanvien_danhsach">Danh sách nhân viên</a></li>
                                <li><a href="index.php?page=nhanvien_them">Thêm nhân viên</a></li>
                            </ul>
                            <div class="list-group">
                                <a href="index.php?page=nhanvien_danhsach" class="list-group-item list-group-item-action">Danh sách phòng ban</a>
                                <a href="index.php?page=nhanvien_them" class="list-group-item list-group-item-action">Quy định</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div> -->

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