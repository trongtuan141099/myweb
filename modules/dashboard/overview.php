<?php
/**
 * Module: Dashboard - Tổng Quan Bộ Phận & Truyền Thông Nội Bộ
 * DX Plastic Group - Industrial Enterprise Design System
 */
?>
<div class="app-page-wrapper">
  <!-- 1. TIÊU ĐỀ TRANG TỔNG QUAN -->
  <div class="app-page-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
      <h1 class="app-page-title" data-i18n="dashboard.title">
        <span class="material-icons">dashboard</span>
        <?= __('dashboard.title', 'Tổng Quan Bộ Phận') ?>
      </h1>
      <p class="app-page-subtitle" data-i18n="dashboard.subtitle">
        <?= __('dashboard.subtitle', 'Theo dõi các mục tiêu trọng tâm năm 2026 và chỉ số nhà máy DX Plastic') ?>
      </p>
    </div>
    <div class="d-flex align-items-center gap-2" id="bannerAdminActionGroup">
      <button type="button" class="app-btn app-btn-secondary app-btn-sm d-flex align-items-center gap-1" onclick="openBannerManagerModal()">
        <span class="material-icons" style="font-size: 18px;">view_carousel</span>
        <span>Quản Lý Banner Hoạt Động</span>
      </button>
    </div>
  </div>

  <!-- 2. KHU VỰC 3 MỤC TIÊU LỚN CỦA BỘ PHẬN -->
  <div class="target-grid-wrapper mb-4">
    <!-- 1. Mục tiêu Chất lượng -->
    <div class="target-card target-quality">
      <div class="target-icon">
        <span class="material-icons">verified</span>
      </div>
      <div class="target-info">
        <span class="target-label" data-i18n="dashboard.yearly_target"><?= __('dashboard.yearly_target', 'MỤC TIÊU NĂM') ?></span>
        <h5 class="target-title" data-i18n="dashboard.target_quality"><?= __('dashboard.target_quality', 'Mục tiêu Chất lượng') ?></h5>
        <p class="target-sub">Quality Objectives 2026</p>
      </div>
      <button type="button" class="btn-view-target" onclick="openPdfModal('Mục tiêu Chất lượng 2026', 'documents/QAR-00344-01 2026 mục tiêu chất lượng.pdf')">
        <span class="material-icons">visibility</span> <span data-i18n="dashboard.view_pdf"><?= __('dashboard.view_pdf', 'Xem PDF') ?></span>
      </button>
    </div>

    <!-- 2. Mục tiêu Môi trường -->
    <div class="target-card target-env">
      <div class="target-icon">
        <span class="material-icons">eco</span>
      </div>
      <div class="target-info">
        <span class="target-label" data-i18n="dashboard.yearly_target"><?= __('dashboard.yearly_target', 'MỤC TIÊU NĂM') ?></span>
        <h5 class="target-title" data-i18n="dashboard.target_env"><?= __('dashboard.target_env', 'Mục tiêu Môi trường') ?></h5>
        <p class="target-sub">Environmental Objectives</p>
      </div>
      <button type="button" class="btn-view-target" onclick="openPdfModal('Mục tiêu Môi trường 2026', 'documents/ISO_Environmental Target_Plastic Extrusion_24 Jun 2026.pdf')">
        <span class="material-icons">visibility</span> <span data-i18n="dashboard.view_pdf"><?= __('dashboard.view_pdf', 'Xem PDF') ?></span>
      </button>
    </div>

    <!-- 3. Mục tiêu An toàn -->
    <div class="target-card target-safety">
      <div class="target-icon">
        <span class="material-icons">health_and_safety</span>
      </div>
      <div class="target-info">
        <span class="target-label" data-i18n="dashboard.yearly_target"><?= __('dashboard.yearly_target', 'MỤC TIÊU NĂM') ?></span>
        <h5 class="target-title" data-i18n="dashboard.target_safety"><?= __('dashboard.target_safety', 'Mục tiêu An toàn') ?></h5>
        <p class="target-sub">Safety Objectives (ISO 45001)</p>
      </div>
      <button type="button" class="btn-view-target" onclick="openPdfModal('Mục tiêu An toàn 2026', 'documents/HIRAC - PLASTIC_EXTRUSION_2026.pdf')">
        <span class="material-icons">visibility</span> <span data-i18n="dashboard.view_pdf"><?= __('dashboard.view_pdf', 'Xem PDF') ?></span>
      </button>
    </div>
  </div>

  <!-- 3. KHU VỰC TRUYỀN THÔNG NỘI BỘ: 5S - KAIZEN - THÀNH TÍCH NỔI BẬT (CAROUSEL TỰ ĐỘNG) -->
  <div class="dx-communication-section mb-4">
    <!-- Header của khu vực Carousel -->
    <div class="dx-carousel-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
      <div class="d-flex align-items-center gap-2">
        <div class="dx-section-badge-icon">
          <span class="material-icons text-amber">stars</span>
        </div>
        <div>
          <h4 class="dx-section-title m-0 d-flex align-items-center gap-2">
            <span>Các Hoạt động và Thành tích nổi bật</span>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-xs py-1 px-2" id="carouselItemCountBadge">0 hoạt động</span>
          </h4>
          <p class="dx-section-desc m-0 text-muted fs-xs">
            Truyền thông nội bộ: Hoạt động 5S hàng tuần, đề xuất Kaizen tiêu biểu, tuyên dương & thành tích
          </p>
        </div>
      </div>

      <!-- Thanh công cụ & Bộ lọc Danh mục -->
      <div class="d-flex align-items-center flex-wrap gap-1">
        <div class="dx-category-pills" id="carouselCategoryFilters">
          <button type="button" class="dx-cat-pill active" data-cat="all">Tất cả</button>
          <button type="button" class="dx-cat-pill" data-cat="5S">✨ 5S</button>
          <button type="button" class="dx-cat-pill" data-cat="An toàn">💡 An toàn</button>
          <button type="button" class="dx-cat-pill" data-cat="Cải tiến">⚡ Cải tiến</button>
          <button type="button" class="dx-cat-pill" data-cat="Tuyên dương">🏆 Tuyên dương</button>
          <button type="button" class="dx-cat-pill" data-cat="Giải thưởng">🎖️ Giải thưởng</button>
          <button type="button" class="dx-cat-pill" data-cat="Hoạt động sản xuất">⚙️ Sản xuất</button>
        </div>
        <button type="button" class="app-btn app-btn-secondary app-btn-sm btn-icon-only ms-1" title="Cài đặt Carousel" onclick="openCarouselSettingsModal()">
          <span class="material-icons" style="font-size: 17px;">tune</span>
        </button>
      </div>
    </div>

    <!-- Khung chính trình chiếu Carousel -->
    <div class="dx-carousel-container" id="dxMainCarousel" tabindex="0" aria-label="Hoạt động nổi bật Carousel">
      <!-- Thanh tiến trình tự động (Progress Bar) -->
      <div class="dx-carousel-progressbar" id="carouselProgressBar"></div>

      <!-- Nút Điều Hướng Trái (Previous) -->
      <button type="button" class="dx-carousel-btn dx-btn-prev" id="btnCarouselPrev" aria-label="Slide trước">
        <span class="material-icons">arrow_back_ios_new</span>
      </button>

      <!-- Track chứa các Slides -->
      <div class="dx-carousel-track" id="carouselTrack">
        <!-- Đang tải dữ liệu ban đầu -->
        <div class="dx-carousel-loading d-flex align-items-center justify-content-center w-100 h-100 p-5 text-muted">
          <div class="spinner-border spinner-border-sm me-2 text-primary" role="status"></div>
          <span>Đang nạp hoạt động nổi bật & sáng kiến cải tiến...</span>
        </div>
      </div>

      <!-- Nút Điều Hướng Phải (Next) -->
      <button type="button" class="dx-carousel-btn dx-btn-next" id="btnCarouselNext" aria-label="Slide kế tiếp">
        <span class="material-icons">arrow_forward_ios</span>
      </button>

      <!-- Khung đáy: Chỉ số Dots & Bộ đếm số slide -->
      <div class="dx-carousel-footer-bar">
        <div class="dx-carousel-indicators" id="carouselIndicators"></div>
        <div class="dx-carousel-counter" id="carouselCounter">01 / 01</div>
      </div>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- 3.1. KHU VỰC THÔNG BÁO MỚI NHẤT (LATEST ANNOUNCEMENTS SECTION)            -->
<!-- ========================================================================= -->
<div class="dx-announcements-section mb-4" id="dxAnnouncementsSection">
  <div class="dx-announcements-card card border-0 shadow-sm">
    <!-- Header của Khu vực thông báo -->
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div class="d-flex align-items-center gap-2">
        <div class="dx-announcements-icon-wrapper d-flex align-items-center justify-content-center">
          <span class="material-icons text-primary fs-4">campaign</span>
        </div>
        <div>
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <h5 class="fw-bold m-0 text-dark fs-6" style="letter-spacing: 0.3px;">THÔNG BÁO MỚI NHẤT</h5>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill fs-xs fw-semibold" id="announcementsCountBadge">0 thông báo</span>
            <span class="badge bg-danger text-white px-2 py-1 rounded-pill fs-xs fw-bold shadow-sm" id="announcementsUnreadBadge" style="display: none;">0 chưa xem</span>
          </div>
          <p class="text-muted fs-xs m-0 mt-1">Thông báo các thông tin quan trọng</p>
        </div>
      </div>

      <!-- Action & Bộ lọc -->
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <div class="btn-group btn-group-sm" role="group" id="announcementFilterGroup">
          <button type="button" class="btn btn-outline-secondary active btn-sm" data-filter="all" onclick="filterAnnouncementsTab('all')">Tất cả</button>
          <button type="button" class="btn btn-outline-secondary btn-sm" data-filter="important" onclick="filterAnnouncementsTab('important')">
            <span class="material-icons fs-6 text-danger" style="vertical-align: -3px;">push_pin</span> Quan trọng
          </button>
          <button type="button" class="btn btn-outline-secondary btn-sm" data-filter="unread" onclick="filterAnnouncementsTab('unread')">Chưa xem</button>
        </div>

        <button type="button" class="app-btn app-btn-secondary app-btn-sm d-none align-items-center gap-1" id="announcementAdminActionBtn" onclick="openAnnouncementManagerModal()">
          <span class="material-icons" style="font-size: 17px;">edit_notifications</span>
          <span>Quản Lý Thông Báo</span>
        </button>
      </div>
    </div>

    <!-- Body chứa Danh sách Thông báo -->
    <div class="card-body p-3 p-md-4 bg-light bg-opacity-25">
      <div class="row g-3" id="announcementsListContainer">
        <!-- Đang tải -->
        <div class="col-12 text-center py-4 text-muted">
          <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
          <span class="fs-sm">Đang tải danh sách thông báo mới nhất...</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- 4. MODAL QUẢN LÝ BANNER HOẠT ĐỘNG (DÀNH CHO ADMIN & EDITOR)               -->
<!-- ========================================================================= -->
<div class="modal fade" id="bannerManagerModal" tabindex="-1" aria-labelledby="bannerManagerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-primary text-white py-2 px-3">
        <div class="d-flex align-items-center gap-2">
          <span class="material-icons">view_carousel</span>
          <h5 class="modal-title fs-6 fw-bold m-0" id="bannerManagerModalLabel">Quản Lý Banner Hoạt Động & Cải Tiến</h5>
        </div>
        <button type="button" class="btn-close-custom" data-bs-dismiss="modal" aria-label="Close">
          <span class="material-icons">close</span>
        </button>
      </div>

      <div class="modal-body p-0">
        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs px-3 pt-2 bg-light border-bottom" id="bannerManagerTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active d-flex align-items-center gap-1 py-2 px-3" id="tab-list-btn" data-bs-toggle="tab" data-bs-target="#tab-banner-list" type="button" role="tab">
              <span class="material-icons fs-6">format_list_bulleted</span> Danh Sách Banner
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center gap-1 py-2 px-3" id="tab-form-btn" data-bs-toggle="tab" data-bs-target="#tab-banner-form" type="button" role="tab">
              <span class="material-icons fs-6" id="formTabIcon">add_circle_outline</span> <span id="formTabTitle">Thêm Hoạt Động Mới</span>
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center gap-1 py-2 px-3" id="tab-settings-btn" data-bs-toggle="tab" data-bs-target="#tab-banner-settings" type="button" role="tab">
              <span class="material-icons fs-6">tune</span> Cài Đặt Trình Chiếu
            </button>
          </li>
        </ul>

        <div class="tab-content p-3" id="bannerManagerTabContent">
          <!-- TAB 1: DANH SÁCH BANNER -->
          <div class="tab-pane fade show active" id="tab-banner-list" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
              <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 480px;">
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-white"><span class="material-icons fs-6 text-muted">search</span></span>
                  <input type="text" class="form-control" id="bannerSearchInput" placeholder="Tìm theo tiêu đề hoặc nội dung..." onkeyup="filterManagerTable()">
                </div>
                <select class="form-select form-select-sm" id="bannerCategoryFilterSelect" style="max-width: 160px;" onchange="filterManagerTable()">
                  <option value="all">Tất cả loại</option>
                  <option value="5S">5S</option>
                  <option value="An toàn">An toàn</option>
                  <option value="Cải tiến">Cải tiến</option>
                  <option value="Tuyên dương">Tuyên dương</option>
                  <option value="Giải thưởng">Giải thưởng</option>
                  <option value="Hoạt động sản xuất">Hoạt động sản xuất</option>
                  <option value="Khác">Khác</option>
                </select>
              </div>
              <button type="button" class="app-btn app-btn-primary app-btn-sm d-flex align-items-center gap-1" onclick="switchToCreateForm()">
                <span class="material-icons fs-6">add</span> Thêm Mới Banner
              </button>
            </div>

            <div class="table-responsive border rounded" style="max-height: 480px;">
              <table class="table table-hover table-striped align-middle mb-0" id="bannerManagerTable">
                <thead class="table-light sticky-top">
                  <tr>
                    <th style="width: 50px;" class="text-center">Thứ tự</th>
                    <th style="width: 100px;">Hình ảnh</th>
                    <th>Tiêu đề & Loại hoạt động</th>
                    <th style="width: 120px;" class="text-center">Ngày đăng</th>
                    <th style="width: 100px;" class="text-center">Trạng thái</th>
                    <th style="width: 110px;" class="text-center">Thao tác</th>
                  </tr>
                </thead>
                <tbody id="bannerManagerTableBody">
                  <tr>
                    <td colspan="6" class="text-center py-4 text-muted">Đang tải danh sách banner...</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2 px-1 text-muted fs-xs">
              <span>💡 Mẹo: Sử dụng nút ▲ / ▼ để sắp xếp thứ tự hiển thị ưu tiên trên Carousel.</span>
              <span id="managerTableTotalCount">Tổng cộng: 0 banner</span>
            </div>
          </div>

          <!-- TAB 2: FORM THÊM MỚI / CHỈNH SỬA BANNER -->
          <div class="tab-pane fade" id="tab-banner-form" role="tabpanel">
            <form id="bannerForm" onsubmit="handleSaveBanner(event)" enctype="multipart/form-data">
              <input type="hidden" id="bannerFormId" name="id" value="0">
              <input type="hidden" id="bannerFormAction" name="action" value="create">

              <div class="row g-3">
                <!-- Tiêu đề -->
                <div class="col-md-8">
                  <label class="form-label fw-semibold fs-sm required">Tiêu đề hoạt động <span class="text-danger">*</span></label>
                  <input type="text" class="form-control form-control-sm" id="bannerTitle" name="title" required placeholder="Nhập tiêu đề nổi bật (Ví dụ: Hoạt động 5S Tuần 40...)">
                </div>

                <!-- Loại hoạt động -->
                <div class="col-md-4">
                  <label class="form-label fw-semibold fs-sm required">Loại hoạt động <span class="text-danger">*</span></label>
                  <select class="form-select form-select-sm" id="bannerCategory" name="category" required>
                    <option value="5S">5S</option>
                    <option value="An toàn">An toàn</option>
                    <option value="Cải tiến">Cải tiến</option>
                    <option value="Tuyên dương">Tuyên dương</option>
                    <option value="Giải thưởng">Giải thưởng</option>
                    <option value="Hoạt động sản xuất">Hoạt động sản xuất</option>
                    <option value="Khác">Khác</option>
                  </select>
                </div>

                <!-- Mô tả ngắn -->
                <div class="col-12">
                  <label class="form-label fw-semibold fs-sm">Mô tả ngắn gọn (Hiển thị trên Slide)</label>
                  <textarea class="form-control form-control-sm" id="bannerSummary" name="summary" rows="2" placeholder="Tóm tắt ngắn gọn nội dung hoạt động, kết quả đạt được (khoảng 2-3 câu)..."></textarea>
                </div>

                <!-- Ngày đăng & Thứ tự & Trạng thái -->
                <div class="col-md-4">
                  <label class="form-label fw-semibold fs-sm">Ngày diễn ra / Ngày đăng</label>
                  <input type="date" class="form-control form-control-sm" id="bannerEventDate" name="event_date" value="<?= date('Y-m-d') ?>">
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-semibold fs-sm">Thứ tự hiển thị (Số nhỏ xếp trước)</label>
                  <input type="number" class="form-control form-control-sm" id="bannerDisplayOrder" name="display_order" value="0" min="0">
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-semibold fs-sm">Trạng thái hiển thị</label>
                  <select class="form-select form-select-sm" id="bannerStatus" name="status">
                    <option value="active">Hiển thị (Active)</option>
                    <option value="inactive">Ẩn (Inactive)</option>
                  </select>
                </div>

                <!-- KHU VỰC HÌNH ẢNH: UPLOAD & GALLERY -->
                <div class="col-12">
                  <label class="form-label fw-semibold fs-sm mb-1">
                    <span>Hình ảnh hoạt động</span>
                    <span class="text-muted fw-normal fs-xs">(Hỗ trợ JPG, PNG, WEBP. Tối đa 5 ảnh. Hệ thống tự động tối ưu & nén ảnh)</span>
                  </label>

                  <!-- Drag & Drop Upload Box -->
                  <div class="dx-dropzone" id="bannerDropzone" onclick="document.getElementById('bannerImageFiles').click()">
                    <span class="material-icons text-primary fs-2 mb-1">cloud_upload</span>
                    <div class="fw-semibold fs-sm">Kéo thả hình ảnh vào đây hoặc nhấp để duyệt file</div>
                    <div class="text-muted fs-xs">Khuyến nghị ảnh kích thước tỉ lệ 16:9 hoặc 21:9 (ví dụ: 1200x500px, 1600x900px)</div>
                    <input type="file" id="bannerImageFiles" name="images[]" multiple accept=".jpg,.jpeg,.png,.webp,.svg" style="display:none;" onchange="handleImageSelection(this)">
                  </div>

                  <!-- Danh sách ảnh xem trước (Preview Grid) -->
                  <div class="dx-image-preview-grid mt-2" id="bannerImagePreviewGrid"></div>
                </div>

                <!-- Nội dung chi tiết (Tùy chọn) -->
                <div class="col-12">
                  <label class="form-label fw-semibold fs-sm">Nội dung chi tiết / Ghi chú bổ sung (Tùy chọn)</label>
                  <textarea class="form-control form-control-sm" id="bannerContent" name="content" rows="3" placeholder="Nhập chi tiết danh sách cá nhân được khen thưởng, chi tiết phương án Kaizen..."></textarea>
                </div>
              </div>

              <!-- Nút Lưu & Hủy -->
              <div class="d-flex justify-content-end gap-2 mt-4 pt-2 border-top">
                <button type="button" class="app-btn app-btn-secondary" onclick="switchToListTab()">Hủy / Quay Lại</button>
                <button type="submit" class="app-btn app-btn-primary d-flex align-items-center gap-1" id="btnSubmitBannerForm">
                  <span class="material-icons fs-6">save</span> <span id="btnSubmitBannerText">Lưu Hoạt Động</span>
                </button>
              </div>
            </form>
          </div>

          <!-- TAB 3: CÀI ĐẶT TRÌNH CHIẾU CAROUSEL -->
          <div class="tab-pane fade" id="tab-banner-settings" role="tabpanel">
            <form id="carouselSettingsForm" onsubmit="handleSaveCarouselSettings(event)">
              <div class="row g-3" style="max-width: 640px;">
                <div class="col-12">
                  <div class="form-check form-switch py-1">
                    <input class="form-check-input" type="checkbox" role="switch" id="settingAutoplay" checked>
                    <label class="form-check-label fw-semibold fs-sm" for="settingAutoplay">Tự động chuyển slide (Autoplay)</label>
                    <div class="text-muted fs-xs">Khi bật, Carousel sẽ tự động chuyển sang slide tiếp theo theo thời gian định sẵn.</div>
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold fs-sm">Thời gian chuyển mỗi slide (Giây)</label>
                  <div class="input-group input-group-sm">
                    <input type="number" class="form-control" id="settingIntervalSeconds" value="5" min="2" max="60" step="1" required>
                    <span class="input-group-text">giây</span>
                  </div>
                  <div class="text-muted fs-xs mt-1">Mặc định: 5 giây (Tương đương 5000 ms).</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold fs-sm">Số lượng slide tối đa hiển thị</label>
                  <input type="number" class="form-control form-control-sm" id="settingMaxItems" value="10" min="1" max="30" required>
                  <div class="text-muted fs-xs mt-1">Số banner kích hoạt hiển thị tối đa trên thanh trượt.</div>
                </div>

                <!-- Cấu hình Chuyển Ảnh Con Trong Cùng Hoạt Động (Sub-slideshow) -->
                <div class="col-12 border-top pt-2">
                  <h6 class="fw-bold fs-xs text-primary text-uppercase mb-2">Trình Chiếu Ảnh Trong Hoạt Động (Sub-Slideshow)</h6>
                  <div class="form-check form-switch py-1">
                    <input class="form-check-input" type="checkbox" role="switch" id="settingImageAutoplay" checked>
                    <label class="form-check-label fw-semibold fs-sm" for="settingImageAutoplay">Tự động chuyển ảnh trong cùng một hoạt động</label>
                    <div class="text-muted fs-xs">Tự động luân phiên đổi giữa các ảnh của cùng một hoạt động khi slide đang chiếu.</div>
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold fs-sm">Thời gian chuyển mỗi ảnh con (Giây)</label>
                  <div class="input-group input-group-sm">
                    <input type="number" class="form-control" id="settingImageIntervalSeconds" value="3" min="1" max="30" step="0.5" required>
                    <span class="input-group-text">giây</span>
                  </div>
                  <div class="text-muted fs-xs mt-1">Mặc định: 3 giây. Tốc độ chuyển đổi giữa các ảnh con.</div>
                </div>

                <div class="col-md-6 d-flex align-items-center">
                  <div class="form-check form-switch py-1 mt-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="settingShowSubControls" checked>
                    <label class="form-check-label fw-semibold fs-sm" for="settingShowSubControls">Hiện nút điều hướng & chấm ảnh con</label>
                    <div class="text-muted fs-xs">Hiển thị nút ◀/▶ và chấm chỉ số ảnh trên slide.</div>
                  </div>
                </div>

                <div class="col-12">
                  <div class="form-check form-switch py-1">
                    <input class="form-check-input" type="checkbox" role="switch" id="settingPauseOnHover" checked>
                    <label class="form-check-label fw-semibold fs-sm" for="settingPauseOnHover">Tạm dừng khi rê chuột (Pause on Hover)</label>
                    <div class="text-muted fs-xs">Tự động dừng chạy slide khi người dùng di chuột qua để đọc chi tiết.</div>
                  </div>
                </div>

                <div class="col-12">
                  <div class="form-check form-switch py-1">
                    <input class="form-check-input" type="checkbox" role="switch" id="settingShowIndicators" checked>
                    <label class="form-check-label fw-semibold fs-sm" for="settingShowIndicators">Hiển thị các chấm chỉ số trang (Indicators)</label>
                  </div>
                </div>

                <div class="col-12">
                  <div class="form-check form-switch py-1">
                    <input class="form-check-input" type="checkbox" role="switch" id="settingShowArrows" checked>
                    <label class="form-check-label fw-semibold fs-sm" for="settingShowArrows">Hiển thị nút mũi tên chuyển trang (Previous / Next)</label>
                  </div>
                </div>

                <div class="col-12">
                  <div class="form-check form-switch py-1">
                    <input class="form-check-input" type="checkbox" role="switch" id="settingShowBadges" checked>
                    <label class="form-check-label fw-semibold fs-sm" for="settingShowBadges">Hiển thị huy hiệu loại hoạt động (5S, Kaizen...)</label>
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label fw-semibold fs-sm">Hiệu ứng chuyển cảnh</label>
                  <select class="form-select form-select-sm" id="settingAnimationEffect">
                    <option value="slide">Lướt ngang mượt (Slide Transition)</option>
                    <option value="fade">Mờ dần điện ảnh (Cross Fade)</option>
                  </select>
                </div>

                <div class="col-12 pt-3 border-top d-flex gap-2">
                  <button type="submit" class="app-btn app-btn-primary d-flex align-items-center gap-1">
                    <span class="material-icons fs-6">save</span> Lưu Cấu Hình Carousel
                  </button>
                  <button type="button" class="app-btn app-btn-secondary" onclick="switchToListTab()">Quay Lại</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- 5. MODAL XEM CHI TIẾT & PHÓNG TO HÌNH ẢNH HOẠT ĐỘNG (LIGHTBOX MODAL)      -->
<!-- ========================================================================= -->
<div class="modal fade" id="bannerDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 overflow-hidden shadow-lg bg-dark text-white">
      <div class="modal-header border-0 py-2 px-3 bg-black bg-opacity-50">
        <div class="d-flex align-items-center gap-2">
          <span class="badge" id="modalDetailCategoryBadge">5S</span>
          <span class="fs-xs text-white-50" id="modalDetailDate">01/10/2026</span>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-0 position-relative text-center bg-black">
        <!-- Ảnh chính phóng to -->
        <div class="dx-lightbox-image-wrapper">
          <img id="modalDetailImage" src="" alt="Banner image" class="img-fluid" style="max-height: 520px; object-fit: contain;">
        </div>

        <!-- Mũi tên chuyển ảnh nếu có nhiều ảnh -->
        <button type="button" class="dx-lightbox-arrow dx-lb-prev" id="btnLightboxPrev" onclick="navigateLightboxImage(-1)" style="display:none;">
          <span class="material-icons">chevron_left</span>
        </button>
        <button type="button" class="dx-lightbox-arrow dx-lb-next" id="btnLightboxNext" onclick="navigateLightboxImage(1)" style="display:none;">
          <span class="material-icons">chevron_right</span>
        </button>

        <!-- Gallery Thumbnails bên dưới ảnh nếu có nhiều hơn 1 ảnh -->
        <div class="dx-lightbox-thumbs p-2 d-flex justify-content-center gap-2" id="modalDetailThumbs" style="display:none;"></div>
      </div>

      <div class="modal-footer border-0 p-3 bg-dark justify-content-start flex-column align-items-start">
        <h5 class="fw-bold m-0 text-white" id="modalDetailTitle">Tiêu đề</h5>
        <p class="text-white-50 fs-sm mt-1 mb-0" id="modalDetailSummary">Mô tả tóm tắt</p>
        <div class="text-white-50 fs-xs mt-2 border-top border-secondary pt-2 w-100" id="modalDetailContent" style="display:none;"></div>
      </div>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- 6. MODAL XEM TRỰC TIẾP TÀI LIỆU PDF (GIỮ NGUYÊN TỪ DASHBOARD CŨ)          -->
<!-- ========================================================================= -->
<div class="modal fade" id="targetPdfModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title d-flex align-items-center gap-2">
          <span class="material-icons" style="font-size: 19px;">description</span>
          <span id="modalDocTitle">Mục tiêu</span>
        </h6>
        <button type="button" class="btn-close-custom" onclick="closePdfModal()" aria-label="Close">
          <span class="material-icons">close</span>
        </button>
      </div>
      <div class="modal-body p-0">
        <iframe id="modalPdfViewer" src="about:blank"></iframe>
      </div>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- 6.1. MODAL XEM CHI TIẾT THÔNG BÁO (ANNOUNCEMENT DETAIL MODAL)             -->
<!-- ========================================================================= -->
<div class="modal fade" id="announcementDetailModal" tabindex="-1" aria-labelledby="announcementDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header py-2 px-3 bg-light border-bottom align-items-center">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <span class="material-icons text-primary" id="modalAnnHeaderIcon">campaign</span>
          <span class="badge bg-danger text-white d-none" id="modalAnnImportantBadge">
            <span class="material-icons fs-6" style="vertical-align: -2px;">push_pin</span> QUAN TRỌNG
          </span>
          <span class="badge" id="modalAnnPriorityBadge">Bình thường</span>
          <span class="text-muted fs-xs" id="modalAnnValidDate">06/10/2026</span>
        </div>
        <button type="button" class="btn-close-custom text-dark" data-bs-dismiss="modal" aria-label="Close">
          <span class="material-icons">close</span>
        </button>
      </div>

      <div class="modal-body p-4">
        <!-- Tiêu đề lớn -->
        <h4 class="fw-bold text-dark mb-3" id="modalAnnTitle" style="line-height: 1.4;">Tiêu đề thông báo</h4>

        <!-- Dải thông tin metadata & Thống kê lượt xem -->
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 p-2 px-3 bg-light rounded-2 border mb-3 fs-xs text-muted">
          <div class="d-flex align-items-center gap-3 flex-wrap">
            <span><strong class="text-dark">Người đăng:</strong> <span id="modalAnnCreatedBy">Admin</span></span>
            <span><strong class="text-dark">Hiệu lực:</strong> <span id="modalAnnValidRange">06/10/2026 - Vô thời hạn</span></span>
          </div>
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1" id="modalAnnStatsBadge">
              <span class="material-icons fs-6" style="vertical-align: -3px;">visibility</span> 0 người xem (0 lượt)
            </span>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" id="modalAnnUserStatusBadge">
              <span class="material-icons fs-6" style="vertical-align: -3px;">done_all</span> Đã xem
            </span>
          </div>
        </div>

        <!-- Tóm tắt thông báo nếu có -->
        <div class="p-3 bg-primary bg-opacity-10 rounded-2 border-start border-4 border-primary mb-3" id="modalAnnSummaryWrapper">
          <div class="fw-semibold text-primary fs-xs mb-1">TÓM TẮT THÔNG BÁO:</div>
          <div class="text-dark fs-sm" id="modalAnnSummary">Nội dung tóm tắt</div>
        </div>

        <!-- Khung Gallery / Carousel Hình ảnh đính kèm (nếu có) -->
        <div class="mb-4" id="modalAnnGalleryWrapper" style="display: none;">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="fw-bold fs-xs text-uppercase text-secondary d-flex align-items-center gap-1">
              <span class="material-icons fs-6 text-primary">photo_library</span> HÌNH ẢNH HOẠT ĐỘNG / VĂN BẢN ĐÍNH KÈM (<span id="modalAnnGalleryCount">0</span>)
            </span>
            <span class="fs-xs text-muted">Nhấn vào ảnh để phóng to toàn màn hình</span>
          </div>

          <!-- Slider / Carousel hiển thị ảnh -->
          <div class="ann-gallery-slider border rounded-2 overflow-hidden position-relative bg-light text-center">
            <div class="ann-gallery-main-view p-2 d-flex align-items-center justify-content-center" style="min-height: 260px; max-height: 460px; background: #0f172a;">
              <img id="modalAnnMainImage" src="" alt="Ảnh thông báo" class="img-fluid rounded cursor-zoom" style="max-height: 440px; object-fit: contain; cursor: zoom-in;" onclick="openAnnCurrentLightbox()">
            </div>
            
            <!-- Nút điều hướng Carousel ảnh nếu có > 1 ảnh -->
            <button type="button" class="ann-gallery-nav-btn ann-nav-prev" id="btnAnnGalleryPrev" onclick="navigateAnnGallery(-1)" style="display: none;" title="Ảnh trước">
              <span class="material-icons">chevron_left</span>
            </button>
            <button type="button" class="ann-gallery-nav-btn ann-nav-next" id="btnAnnGalleryNext" onclick="navigateAnnGallery(1)" style="display: none;" title="Ảnh tiếp theo">
              <span class="material-icons">chevron_right</span>
            </button>

            <!-- Bộ đếm ảnh trên ảnh chính -->
            <div class="ann-gallery-counter" id="modalAnnGalleryCounter" style="display: none;">1 / 1</div>
          </div>

          <!-- Dải Thumbnails bên dưới để chọn nhanh ảnh -->
          <div class="ann-gallery-thumbs-track d-flex gap-2 mt-2 pb-1 overflow-auto" id="modalAnnGalleryThumbs">
            <!-- Render các thumbnails -->
          </div>
        </div>

        <!-- Nội dung chi tiết bài viết -->
        <div class="announcement-full-content text-dark fs-sm mb-4" id="modalAnnContent" style="line-height: 1.7;">
          <!-- Render nội dung HTML -->
        </div>

        <!-- Khung Danh sách Tài liệu đính kèm (PDF, Word, Excel, PowerPoint...) -->
        <div class="mt-4 pt-3 border-top" id="modalAnnAttachmentsWrapper" style="display: none;">
          <h6 class="fw-bold fs-sm text-dark d-flex align-items-center gap-2 mb-3">
            <span class="material-icons text-primary fs-5">attach_file</span>
            <span>TÀI LIỆU ĐÍNH KÈM (<span id="modalAnnAttachmentsCount">0</span> TỆP)</span>
          </h6>
          <div class="row g-2" id="modalAnnAttachmentsList">
            <!-- Render danh sách file đính kèm -->
          </div>
        </div>
      </div>

      <div class="modal-footer py-2 px-3 bg-light border-top justify-content-between">
        <div>
          <button type="button" class="app-btn app-btn-secondary app-btn-sm d-none align-items-center gap-1" id="btnViewStatsFromDetail" onclick="openStatsFromDetailModal()">
            <span class="material-icons fs-6">groups</span>
            <span>Danh Sách Người Đã Xem</span>
          </button>
        </div>
        <button type="button" class="app-btn app-btn-primary app-btn-sm" data-bs-dismiss="modal">
          Đã Hiểu / Đóng
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- 6.2. MODAL QUẢN LÝ THÔNG BÁO (ANNOUNCEMENT MANAGER MODAL)                 -->
<!-- ========================================================================= -->
<div class="modal fade" id="announcementManagerModal" tabindex="-1" aria-labelledby="announcementManagerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-dark text-white py-2 px-3">
        <div class="d-flex align-items-center gap-2">
          <span class="material-icons text-warning">campaign</span>
          <h5 class="modal-title fs-6 fw-bold m-0" id="announcementManagerModalLabel">Quản Lý Thông Báo Nội Bộ Nhà Máy</h5>
        </div>
        <button type="button" class="btn-close-custom" data-bs-dismiss="modal" aria-label="Close">
          <span class="material-icons">close</span>
        </button>
      </div>

      <div class="modal-body p-0">
        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs px-3 pt-2 bg-light border-bottom" id="announcementManagerTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active d-flex align-items-center gap-1 py-2 px-3" id="tab-ann-list-btn" data-bs-toggle="tab" data-bs-target="#tab-ann-list" type="button" role="tab">
              <span class="material-icons fs-6">format_list_bulleted</span> Danh Sách Thông Báo
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center gap-1 py-2 px-3" id="tab-ann-form-btn" data-bs-toggle="tab" data-bs-target="#tab-ann-form" type="button" role="tab">
              <span class="material-icons fs-6" id="annFormTabIcon">add_circle_outline</span> <span id="annFormTabTitle">Thêm Thông Báo Mới</span>
            </button>
          </li>
        </ul>

        <!-- Tab Contents -->
        <div class="tab-content p-3" id="announcementTabsContent">
          <!-- TAB 1: DANH SÁCH THÔNG BÁO -->
          <div class="tab-pane fade show active" id="tab-ann-list" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <input type="text" class="form-control form-control-sm" id="annAdminSearch" placeholder="Tìm kiếm theo tiêu đề..." style="width: 250px;" oninput="debounceAnnAdminSearch()">
                <select class="form-select form-select-sm" id="annAdminFilterPriority" style="width: 150px;" onchange="loadAnnouncementAdminList()">
                  <option value="">-- Mọi mức ưu tiên --</option>
                  <option value="urgent">Khẩn cấp</option>
                  <option value="high">Ưu tiên cao</option>
                  <option value="normal">Bình thường</option>
                </select>
                <select class="form-select form-select-sm" id="annAdminFilterStatus" style="width: 130px;" onchange="loadAnnouncementAdminList()">
                  <option value="">-- Trạng thái --</option>
                  <option value="active">Hiển thị</option>
                  <option value="inactive">Đang ẩn</option>
                </select>
              </div>
              <button type="button" class="app-btn app-btn-primary app-btn-sm d-flex align-items-center gap-1" onclick="switchToNewAnnTab()">
                <span class="material-icons fs-6">add</span> Thêm Mới Thông Báo
              </button>
            </div>

            <!-- Bảng danh sách thông báo quản trị -->
            <div class="table-responsive border rounded-2" style="max-height: 520px;">
              <table class="table table-hover table-striped align-middle mb-0 fs-sm" id="tblAnnouncementsAdmin">
                <thead class="table-light sticky-top">
                  <tr>
                    <th style="width: 50px;" class="text-center">#</th>
                    <th style="min-width: 220px;">Tiêu Đề Thông Báo</th>
                    <th style="width: 120px;" class="text-center">Mức Ưu Tiên</th>
                    <th style="width: 100px;" class="text-center">Quan Trọng</th>
                    <th style="width: 150px;">Thời Gian Hiệu Lực</th>
                    <th style="width: 110px;" class="text-center">Trạng Thái</th>
                    <th style="width: 130px;" class="text-center">Lượt Xem</th>
                    <th style="width: 160px;" class="text-center">Thao Tác</th>
                  </tr>
                </thead>
                <tbody id="tblAnnouncementsAdminBody">
                  <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                      <div class="spinner-border spinner-border-sm text-primary me-2"></div> Đang tải dữ liệu...
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- TAB 2: THÊM MỚI / CHỈNH SỬA THÔNG BÁO -->
          <div class="tab-pane fade" id="tab-ann-form" role="tabpanel">
            <form id="announcementManageForm" onsubmit="handleSaveAnnouncementForm(event)">
              <input type="hidden" id="annFormId" value="0">

              <div class="row g-3">
                <div class="col-md-8">
                  <label class="form-label fw-semibold fs-sm required">Tiêu đề thông báo <span class="text-danger">*</span></label>
                  <input type="text" class="form-control form-control-sm" id="annFormTitle" required placeholder="Nhập tiêu đề rõ ràng, súc tích...">
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-semibold fs-sm">Mức độ ưu tiên</label>
                  <select class="form-select form-select-sm" id="annFormPriority">
                    <option value="normal">Bình thường (Normal)</option>
                    <option value="high">Ưu tiên cao (High)</option>
                    <option value="urgent">Khẩn cấp (Urgent)</option>
                  </select>
                </div>

                <div class="col-md-3">
                  <label class="form-label fw-semibold fs-sm">Từ ngày (Hiệu lực) <span class="text-danger">*</span></label>
                  <input type="date" class="form-control form-control-sm" id="annFormValidFrom" required>
                </div>

                <div class="col-md-3">
                  <label class="form-label fw-semibold fs-sm">Đến ngày (Tùy chọn)</label>
                  <input type="date" class="form-control form-control-sm" id="annFormValidTo" placeholder="Để trống nếu vô thời hạn">
                  <div class="form-text fs-xs">Để trống nếu có hiệu lực vô thời hạn</div>
                </div>

                <div class="col-md-3">
                  <label class="form-label fw-semibold fs-sm">Trạng thái xuất bản</label>
                  <select class="form-select form-select-sm" id="annFormStatus">
                    <option value="active">Hiển thị (Active)</option>
                    <option value="inactive">Ẩn (Inactive)</option>
                  </select>
                </div>

                <div class="col-md-3 d-flex align-items-center pt-3">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="annFormIsImportant">
                    <label class="form-check-label fw-semibold fs-sm text-danger" for="annFormIsImportant">
                      <span class="material-icons fs-6" style="vertical-align: -3px;">push_pin</span> Đánh dấu QUAN TRỌNG (Ghim đầu trang)
                    </label>
                  </div>
                </div>

                <div class="col-12">
                  <label class="form-label fw-semibold fs-sm">Tóm tắt ngắn (Summary)</label>
                  <textarea class="form-control form-control-sm" id="annFormSummary" rows="2" placeholder="Tóm tắt ngắn gọn 1-2 câu hiển thị ở danh sách ngoài (nếu để trống hệ thống tự trích từ nội dung)..."></textarea>
                </div>

                <div class="col-12">
                  <label class="form-label fw-semibold fs-sm required">Nội dung chi tiết <span class="text-danger">*</span></label>
                  <textarea class="form-control form-control-sm font-monospace" id="annFormContent" rows="7" required placeholder="Nhập nội dung đầy đủ của thông báo (hỗ trợ các thẻ HTML cơ bản <p>, <ul>, <li>, <strong>, <em>)..."></textarea>
                  <div class="form-text fs-xs">Bạn có thể viết văn bản thông thường hoặc dùng thẻ HTML như &lt;p&gt;, &lt;b&gt;, &lt;ul&gt;, &lt;li&gt; để định dạng đẹp mắt.</div>
                </div>

                <!-- Khu vực Quản lý Upload nhiều Hình ảnh -->
                <div class="col-12 p-3 bg-light rounded-2 border">
                  <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <label class="form-label fw-semibold fs-sm m-0 d-flex align-items-center gap-1 text-primary">
                      <span class="material-icons fs-6">photo_library</span> Hình ảnh đính kèm (Gallery / Trình chiếu)
                    </label>
                    <label class="btn btn-outline-primary btn-sm m-0 d-flex align-items-center gap-1" style="cursor: pointer;">
                      <span class="material-icons fs-6">add_photo_alternate</span> Chọn ảnh từ máy tính
                      <input type="file" id="annFormImagesInput" multiple accept="image/*" class="d-none" onchange="handleAnnImagesSelect(event)">
                    </label>
                  </div>
                  <div class="form-text fs-xs mb-2">Hỗ trợ JPG, PNG, WEBP, GIF, SVG (tối đa 15MB/ảnh). Bạn có thể bấm nút mũi tên để sắp xếp thứ tự hiển thị ảnh.</div>
                  
                  <!-- Danh sách Preview hình ảnh đã chọn -->
                  <div class="d-flex flex-wrap gap-2" id="annFormImagesPreview">
                    <div class="text-muted fs-xs fst-italic p-2 w-100" id="annFormNoImagesNotice">Chưa có hình ảnh nào được đính kèm.</div>
                  </div>
                </div>

                <!-- Khu vực Quản lý Upload nhiều File đính kèm -->
                <div class="col-12 p-3 bg-light rounded-2 border">
                  <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <label class="form-label fw-semibold fs-sm m-0 d-flex align-items-center gap-1 text-primary">
                      <span class="material-icons fs-6">attach_file</span> Tài liệu đính kèm (PDF, Word, Excel, PPT...)
                    </label>
                    <label class="btn btn-outline-primary btn-sm m-0 d-flex align-items-center gap-1" style="cursor: pointer;">
                      <span class="material-icons fs-6">upload_file</span> Đính kèm tài liệu
                      <input type="file" id="annFormAttachmentsInput" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar" class="d-none" onchange="handleAnnAttachmentsSelect(event)">
                    </label>
                  </div>
                  <div class="form-text fs-xs mb-2">Hỗ trợ PDF, DOC/DOCX, XLS/XLSX, PPT/PPTX, TXT, ZIP, RAR (tối đa 50MB/file). Cho phép tải về trực tiếp.</div>

                  <!-- Danh sách Tài liệu đã chọn -->
                  <div class="d-flex flex-column gap-2" id="annFormAttachmentsList">
                    <div class="text-muted fs-xs fst-italic p-2" id="annFormNoAttachmentsNotice">Chưa có tài liệu nào được đính kèm.</div>
                  </div>
                </div>

                <div class="col-12 pt-3 border-top d-flex gap-2">
                  <button type="submit" class="app-btn app-btn-primary d-flex align-items-center gap-1" id="btnSubmitAnnForm">
                    <span class="material-icons fs-6">save</span> Lưu Thông Báo
                  </button>
                  <button type="button" class="app-btn app-btn-secondary" onclick="switchToAnnListTab()">
                    Hủy Bỏ / Quay Lại
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- 6.3. MODAL THỐNG KÊ NGƯỜI ĐÃ XEM THÔNG BÁO (ANNOUNCEMENT VIEWERS MODAL)    -->
<!-- ========================================================================= -->
<div class="modal fade" id="announcementViewersModal" tabindex="-1" aria-labelledby="announcementViewersModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-primary text-white py-2 px-3">
        <div class="d-flex align-items-center gap-2">
          <span class="material-icons">groups</span>
          <h5 class="modal-title fs-6 fw-bold m-0" id="announcementViewersModalLabel">Danh Sách Người Đã Xem Thông Báo</h5>
        </div>
        <button type="button" class="btn-close-custom" data-bs-dismiss="modal" aria-label="Close">
          <span class="material-icons">close</span>
        </button>
      </div>

      <div class="modal-body p-3">
        <!-- Thông tin tóm tắt thông báo đang xem -->
        <div class="p-3 bg-light rounded-2 border mb-3">
          <h6 class="fw-bold text-dark m-0 mb-2" id="viewersModalAnnTitle">Tiêu đề thông báo</h6>
          <div class="d-flex align-items-center gap-3 fs-xs text-muted flex-wrap">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fs-xs" id="viewersModalUniqueCount">
              <span class="material-icons fs-6" style="vertical-align: -3px;">person</span> 0 người đã xem
            </span>
            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1 fs-xs" id="viewersModalTotalViews">
              <span class="material-icons fs-6" style="vertical-align: -3px;">visibility</span> 0 tổng lượt đọc
            </span>
          </div>
        </div>

        <!-- Ô lọc nhanh người xem -->
        <div class="mb-3">
          <input type="text" class="form-control form-control-sm" id="viewersTableSearch" placeholder="Lọc theo họ tên hoặc username..." oninput="filterViewersTable()">
        </div>

        <!-- Bảng danh sách người xem -->
        <div class="table-responsive border rounded-2" style="max-height: 380px;">
          <table class="table table-hover table-striped align-middle mb-0 fs-sm" id="tblViewersList">
            <thead class="table-light sticky-top">
              <tr>
                <th style="width: 50px;" class="text-center">#</th>
                <th style="min-width: 150px;">Họ và Tên</th>
                <th style="width: 120px;">Tài Khoản</th>
                <th style="width: 100px;" class="text-center">Số Lần Xem</th>
                <th style="width: 140px;">Lần Đầu Xem</th>
                <th style="width: 140px;">Lần Xem Gần Nhất</th>
              </tr>
            </thead>
            <tbody id="tblViewersListBody">
              <tr>
                <td colspan="6" class="text-center py-4 text-muted">Đang tải danh sách người xem...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer py-2 px-3 bg-light border-top">
        <button type="button" class="app-btn app-btn-secondary app-btn-sm" data-bs-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- 6.4. MODAL PHÓNG TO XEM HÌNH ẢNH THÔNG BÁO (IMAGE LIGHTBOX)               -->
<!-- ========================================================================= -->
<div class="modal fade" id="announcementImageLightboxModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content border-0 bg-dark text-white shadow-lg overflow-hidden">
      <div class="modal-header border-0 py-2 px-3 bg-black bg-opacity-75 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <span class="material-icons fs-5 text-primary">photo_camera</span>
          <span class="fs-sm fw-semibold" id="annLightboxTitle">Hình ảnh đính kèm</span>
          <span class="badge bg-secondary fs-xs" id="annLightboxCounter">1 / 1</span>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0 text-center position-relative bg-black d-flex align-items-center justify-content-center" style="min-height: 480px; max-height: 82vh;">
        <img id="annLightboxImage" src="" alt="Ảnh phóng to" class="img-fluid" style="max-height: 80vh; max-width: 100%; object-fit: contain;">
        
        <!-- Nút Previous / Next -->
        <button type="button" class="ann-lb-arrow ann-lb-prev" id="btnAnnLbPrev" onclick="navigateAnnLightbox(-1)" title="Ảnh trước">
          <span class="material-icons">chevron_left</span>
        </button>
        <button type="button" class="ann-lb-arrow ann-lb-next" id="btnAnnLbNext" onclick="navigateAnnLightbox(1)" title="Ảnh tiếp theo">
          <span class="material-icons">chevron_right</span>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- 7. CSS RIÊNG CỦA MODULE DASHBOARD OVERVIEW & CAROUSEL TRUYỀN THÔNG NỘI BỘ -->
<!-- ========================================================================= -->
<style>
/* 7.1. Bố cục 3 Mục tiêu năm (Giữ nguyên phong cách công nghiệp) */
.target-grid-wrapper {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  width: 100%;
}
@media (max-width: 992px) {
  .target-grid-wrapper { grid-template-columns: 1fr; }
}
.target-card {
  position: relative;
  border-radius: var(--dx-radius-md);
  padding: 18px 20px;
  display: flex;
  align-items: center;
  gap: 14px;
  color: #ffffff;
  box-shadow: var(--dx-shadow-sm);
  overflow: hidden;
  transition: transform 0.15s ease;
}
.target-card:hover { transform: translateY(-2px); }
.target-quality { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); }
.target-env     { background: linear-gradient(135deg, #15803d 0%, #22c55e 100%); }
.target-safety  { background: linear-gradient(135deg, #b91c1c 0%, #ef4444 100%); }
.target-icon {
  width: 44px;
  height: 44px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 10px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
}
.target-icon .material-icons { font-size: 26px; color: #ffffff; }
.target-info { flex: 1; min-width: 0; }
.target-label { font-size: 10px; font-weight: 800; opacity: 0.85; display: block; letter-spacing: 0.5px; }
.target-title { margin: 2px 0 0; font-size: 15px; font-weight: 700; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.target-sub { margin: 0; font-size: 11px; opacity: 0.85; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.btn-view-target {
  background: rgba(255, 255, 255, 0.25);
  color: #ffffff !important;
  border: 1px solid rgba(255, 255, 255, 0.4);
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 11.5px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  white-space: nowrap;
  cursor: pointer;
  transition: all 0.15s;
}
.btn-view-target:hover { background: #ffffff; color: var(--dx-text-main) !important; }

/* 7.2. Giao diện Khu vực Carousel 5S - Kaizen - Thành tích nổi bật */
.dx-communication-section {
  width: 100%;
}
.dx-section-badge-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: rgba(245, 158, 11, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
}
.dx-section-title {
  font-size: 16px;
  font-weight: 700;
  color: var(--dx-text-main);
}
.dx-category-pills {
  display: flex;
  gap: 4px;
  flex-wrap: wrap;
}
.dx-cat-pill {
  border: 1px solid var(--dx-border);
  background: var(--dx-bg-card);
  color: var(--dx-text-muted);
  font-size: 11.5px;
  font-weight: 600;
  padding: 4px 10px;
  border-radius: 20px;
  cursor: pointer;
  transition: all 0.15s ease;
}
.dx-cat-pill:hover {
  background: var(--dx-bg-subtle);
  color: var(--dx-text-main);
  border-color: var(--dx-border-strong);
}
.dx-cat-pill.active {
  background: var(--dx-primary);
  color: #ffffff;
  border-color: var(--dx-primary);
}

/* 7.3. Khung Carousel Container */
.dx-carousel-container {
  position: relative;
  width: 100%;
  height: 360px;
  border-radius: var(--dx-radius-lg);
  overflow: hidden;
  box-shadow: var(--dx-shadow-md);
  border: 1px solid var(--dx-border);
  background: #0f172a;
  outline: none;
  user-select: none;
}
@media (max-width: 768px) {
  .dx-carousel-container { height: 310px; }
}

/* Progress bar chạy tự động */
.dx-carousel-progressbar {
  position: absolute;
  top: 0;
  left: 0;
  height: 3px;
  background: linear-gradient(90deg, #3b82f6, #60a5fa);
  width: 0%;
  z-index: 25;
  transition: width 0.1s linear;
}

/* Track và Slide */
.dx-carousel-track {
  display: flex;
  width: 100%;
  height: 100%;
  transition: transform 0.6s cubic-bezier(0.25, 1, 0.5, 1);
}
.dx-carousel-slide {
  position: relative;
  flex: 0 0 100%;
  width: 100%;
  height: 100%;
  overflow: hidden;
  cursor: pointer;
}

/* Hình nền của Slide & Sub-slideshow */
.dx-slide-image-wrapper {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
}
.dx-slide-image,
.dx-sub-slide-img {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  opacity: 0;
  transition: opacity 0.6s ease-in-out, transform 0.8s ease;
  pointer-events: none;
}
.dx-sub-slide-img.active {
  opacity: 1;
  pointer-events: auto;
  z-index: 2;
}
.dx-carousel-slide:hover .dx-sub-slide-img.active,
.dx-carousel-slide:hover .dx-slide-image {
  transform: scale(1.03);
}

/* Sub-controls: Điều hướng & Indicators ảnh trong cùng một hoạt động */
.dx-sub-controls-container {
  position: absolute;
  top: 18px;
  right: 22px;
  display: flex;
  align-items: center;
  gap: 8px;
  z-index: 12;
  background: rgba(15, 23, 42, 0.72);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.25);
  padding: 4px 10px;
  border-radius: 20px;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3);
  transition: all 0.2s ease;
}
.dx-sub-controls-container:hover {
  background: rgba(15, 23, 42, 0.88);
  border-color: rgba(255, 255, 255, 0.45);
}
.dx-sub-img-counter {
  color: #ffffff;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.5px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 0 4px;
}
.dx-sub-nav-btn {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.35);
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s ease;
  padding: 0;
}
.dx-sub-nav-btn:hover {
  background: #3b82f6;
  border-color: #60a5fa;
  transform: scale(1.1);
}
.dx-sub-nav-btn .material-icons {
  font-size: 16px;
}
.dx-sub-indicators-bar {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 0 3px;
}
.dx-sub-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.4);
  cursor: pointer;
  transition: all 0.25s ease;
}
.dx-sub-dot.active {
  background: #38bdf8;
  width: 16px;
  border-radius: 4px;
  box-shadow: 0 0 8px rgba(56, 189, 248, 0.8);
}
@media (max-width: 576px) {
  .dx-sub-controls-container {
    top: 10px;
    right: 10px;
    padding: 3px 8px;
    gap: 6px;
  }
  .dx-sub-nav-btn { width: 22px; height: 22px; }
  .dx-sub-nav-btn .material-icons { font-size: 14px; }
  .dx-sub-img-counter { font-size: 10px; }
  .dx-sub-dot { width: 6px; height: 6px; }
  .dx-sub-dot.active { width: 12px; }
}

/* Lớp phủ chuyển màu nghệ thuật bảo vệ độ tương phản */
.dx-slide-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(90deg, rgba(15, 23, 42, 0.94) 0%, rgba(15, 23, 42, 0.65) 55%, rgba(15, 23, 42, 0.2) 100%);
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  padding: 30px 45px 36px 45px;
  z-index: 5;
}
@media (max-width: 768px) {
  .dx-slide-overlay {
    background: linear-gradient(0deg, rgba(15, 23, 42, 0.95) 0%, rgba(15, 23, 42, 0.75) 60%, rgba(15, 23, 42, 0.3) 100%);
    padding: 20px;
  }
}

/* Nội dung văn bản trên Slide */
.dx-slide-content {
  max-width: 780px;
  animation: fadeInSlide 0.5s ease-out;
}
@keyframes fadeInSlide {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}
.dx-slide-meta {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
}
.dx-activity-badge {
  font-size: 11px;
  font-weight: 800;
  padding: 4px 12px;
  border-radius: 20px;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.badge-5s           { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.4); }
.badge-kaizen       { background: rgba(139, 92, 246, 0.2); color: #a78bfa; border: 1px solid rgba(139, 92, 246, 0.4); }
.badge-cai-tien     { background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4); }
.badge-tuyen-duong  { background: rgba(234, 179, 8, 0.2);  color: #fde047; border: 1px solid rgba(234, 179, 8, 0.4); }
.badge-giai-thuong  { background: rgba(239, 68, 68, 0.2);  color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4); }
.badge-san-xuat     { background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.4); }
.badge-khac         { background: rgba(148, 163, 184, 0.2);color: #cbd5e1; border: 1px solid rgba(148, 163, 184, 0.4); }

.dx-slide-date {
  color: rgba(255, 255, 255, 0.7);
  font-size: 12px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.dx-slide-title {
  color: #ffffff;
  font-size: 22px;
  font-weight: 800;
  line-height: 1.35;
  margin: 0 0 8px 0;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
}
@media (max-width: 768px) {
  .dx-slide-title { font-size: 16px; }
}
.dx-slide-desc {
  color: rgba(255, 255, 255, 0.85);
  font-size: 13.5px;
  line-height: 1.5;
  margin: 0 0 14px 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.dx-slide-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}
.dx-btn-slide-detail {
  background: rgba(255, 255, 255, 0.2);
  color: #ffffff;
  border: 1px solid rgba(255, 255, 255, 0.4);
  font-size: 12px;
  font-weight: 600;
  padding: 5px 14px;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  backdrop-filter: blur(4px);
  cursor: pointer;
  transition: all 0.15s ease;
}
.dx-btn-slide-detail:hover {
  background: #ffffff;
  color: #0f172a;
}
.dx-img-count-badge {
  background: rgba(0, 0, 0, 0.4);
  color: rgba(255, 255, 255, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.2);
  font-size: 11px;
  padding: 4px 8px;
  border-radius: 4px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

/* Nút Điều Hướng Trái/Phải (Prev / Next) */
.dx-carousel-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(15, 23, 42, 0.45);
  color: #ffffff;
  border: 1px solid rgba(255, 255, 255, 0.25);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 15;
  transition: all 0.2s ease;
  opacity: 0.7;
}
.dx-carousel-container:hover .dx-carousel-btn { opacity: 1; }
.dx-carousel-btn:hover {
  background: rgba(30, 64, 175, 0.85);
  border-color: #60a5fa;
  transform: translateY(-50%) scale(1.08);
}
.dx-btn-prev { left: 16px; }
.dx-btn-next { right: 16px; }
@media (max-width: 576px) {
  .dx-carousel-btn { width: 32px; height: 32px; }
  .dx-carousel-btn .material-icons { font-size: 18px; }
  .dx-btn-prev { left: 8px; }
  .dx-btn-next { right: 8px; }
}

/* Thanh Đáy: Indicators & Counter */
.dx-carousel-footer-bar {
  position: absolute;
  bottom: 12px;
  left: 0;
  right: 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 45px;
  z-index: 10;
}
@media (max-width: 768px) {
  .dx-carousel-footer-bar { padding: 0 20px; }
}
.dx-carousel-indicators {
  display: flex;
  gap: 6px;
  align-items: center;
}
.dx-indicator-dot {
  width: 8px;
  height: 8px;
  border-radius: 4px;
  background: rgba(255, 255, 255, 0.35);
  border: none;
  cursor: pointer;
  transition: all 0.25s ease;
  padding: 0;
}
.dx-indicator-dot.active {
  width: 24px;
  background: #3b82f6;
  box-shadow: 0 0 8px rgba(59, 130, 246, 0.7);
}
.dx-carousel-counter {
  color: rgba(255, 255, 255, 0.75);
  font-size: 11px;
  font-weight: 700;
  background: rgba(0, 0, 0, 0.4);
  padding: 2px 8px;
  border-radius: 12px;
  letter-spacing: 0.5px;
}

/* 7.4. Giao diện Drag & Drop Upload Zone */
.dx-dropzone {
  border: 2px dashed var(--dx-border-strong);
  border-radius: var(--dx-radius-md);
  padding: 24px 16px;
  text-align: center;
  background: var(--dx-bg-subtle);
  cursor: pointer;
  transition: all 0.15s ease;
}
.dx-dropzone:hover, .dx-dropzone.dragover {
  border-color: var(--dx-primary);
  background: var(--dx-primary-light);
}
.dx-image-preview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
  gap: 12px;
}
.dx-preview-item {
  position: relative;
  aspect-ratio: 16/9;
  border-radius: 6px;
  overflow: hidden;
  border: 2px solid var(--dx-border);
  background: #0f172a;
  box-shadow: 0 2px 5px rgba(0,0,0,0.15);
  transition: all 0.2s ease;
}
.dx-preview-item.is-cover {
  border-color: #f59e0b;
  box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.4);
}
.dx-preview-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.dx-cover-badge {
  position: absolute;
  top: 4px;
  left: 4px;
  background: rgba(245, 158, 11, 0.95);
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  padding: 1px 6px;
  border-radius: 3px;
  display: flex;
  align-items: center;
  gap: 2px;
  z-index: 3;
  box-shadow: 0 1px 3px rgba(0,0,0,0.3);
}
.dx-preview-order-tag {
  position: absolute;
  top: 4px;
  right: 4px;
  background: rgba(15, 23, 42, 0.8);
  color: #f8fafc;
  font-size: 10px;
  font-weight: 700;
  padding: 1px 5px;
  border-radius: 3px;
  z-index: 3;
}
.dx-preview-actions-bar {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 3px 4px;
  background: linear-gradient(0deg, rgba(15, 23, 42, 0.95) 0%, rgba(15, 23, 42, 0.5) 100%);
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 4px;
  z-index: 4;
}
.dx-preview-btn {
  width: 22px;
  height: 22px;
  border-radius: 3px;
  border: none;
  background: rgba(255, 255, 255, 0.2);
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s ease;
  padding: 0;
}
.dx-preview-btn:hover { background: rgba(255, 255, 255, 0.4); }
.dx-preview-btn.btn-set-cover {
  background: rgba(245, 158, 11, 0.85);
}
.dx-preview-btn.btn-set-cover:hover { background: #d97706; }
.dx-preview-btn.btn-del {
  background: rgba(239, 68, 68, 0.85);
}
.dx-preview-btn.btn-del:hover { background: #dc2626; }

/* 7.5. Giao diện Lightbox Gallery */
.dx-lightbox-image-wrapper {
  min-height: 280px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.dx-lightbox-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(255, 255, 255, 0.15);
  color: #fff;
  border: none;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.15s;
}
.dx-lightbox-arrow:hover { background: rgba(255, 255, 255, 0.3); }
.dx-lb-prev { left: 16px; }
.dx-lb-next { right: 16px; }
.dx-lb-thumb {
  width: 48px;
  height: 32px;
  border-radius: 4px;
  object-fit: cover;
  opacity: 0.6;
  cursor: pointer;
  border: 1px solid transparent;
}
.dx-lb-thumb.active {
  opacity: 1;
  border-color: #3b82f6;
}

/* CSS Modal PDF Viewer */
#targetPdfModal .modal-dialog {
  max-width: 92vw;
  width: 92vw;
  height: 88vh;
  margin: 6vh auto;
}
#targetPdfModal .modal-content {
  height: 100%;
  display: flex;
  flex-direction: column;
  border-radius: var(--dx-radius-md);
  overflow: hidden;
  border: none;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}
#targetPdfModal .modal-header {
  background-color: var(--dx-primary);
  color: #ffffff;
  height: 44px;
  padding: 0 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.btn-close-custom {
  background: transparent;
  border: none;
  color: #ffffff;
  padding: 4px;
  display: flex;
  align-items: center;
  cursor: pointer;
  border-radius: 4px;
}
.btn-close-custom:hover { background: rgba(255, 255, 255, 0.2); }
#targetPdfModal .modal-body {
  flex: 1;
  background-color: #323639;
}
#targetPdfModal #modalPdfViewer {
  width: 100%;
  height: 100%;
  border: none;
}
.fs-xs { font-size: 11px; }
.fs-sm { font-size: 12.5px; }

/* ========================================================================= */
/* CSS CHO KHU VỰC THÔNG BÁO MỚI NHẤT (ANNOUNCEMENTS INDUSTRIAL STYLING)     */
/* ========================================================================= */
.dx-announcements-icon-wrapper {
  width: 38px;
  height: 38px;
  background: rgba(14, 116, 144, 0.1);
  border-radius: 8px;
}
.dx-announcement-card-col {
  transition: transform 0.2s ease;
}
.dx-announcement-item {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 16px;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  transition: all 0.25s ease;
  position: relative;
  overflow: hidden;
}
.dx-announcement-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
  border-color: #cbd5e1;
}

/* Kiểu dáng riêng cho Thông Báo Quan Trọng (Important Pinned) */
.dx-announcement-item.is-important {
  border-left: 4px solid #ef4444 !important;
  border-color: rgba(239, 68, 68, 0.35);
  background: linear-gradient(180deg, #fffdfd 0%, #ffffff 100%);
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.08);
}
.dx-announcement-item.is-important::before {
  content: "";
  position: absolute;
  top: 0;
  right: 0;
  width: 0;
  height: 0;
  border-style: solid;
  border-width: 0 32px 32px 0;
  border-color: transparent #ef4444 transparent transparent;
}
.dx-announcement-item.is-important::after {
  content: "★";
  position: absolute;
  top: 1px;
  right: 4px;
  color: #ffffff;
  font-size: 11px;
  font-weight: bold;
}

/* Badge trạng thái đọc người dùng */
.read-status-pill {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 12px;
}
.read-status-pill.unread {
  background: #fff7ed;
  color: #ea580c;
  border: 1px solid #fed7aa;
}
.read-status-pill.unread .dot {
  width: 6px;
  height: 6px;
  background: #ea580c;
  border-radius: 50%;
  display: inline-block;
  animation: pulseUnread 2s infinite;
}
@keyframes pulseUnread {
  0% { transform: scale(0.95); opacity: 0.8; }
  50% { transform: scale(1.3); opacity: 1; }
  100% { transform: scale(0.95); opacity: 0.8; }
}
.read-status-pill.read {
  background: #f1f5f9;
  color: #64748b;
  border: 1px solid #e2e8f0;
}

/* Badge mức độ ưu tiên */
.priority-badge-urgent {
  background: #fee2e2;
  color: #b91c1c;
  border: 1px solid #fca5a5;
  font-size: 11px;
  font-weight: 700;
}
.priority-badge-high {
  background: #ffedd5;
  color: #c2410c;
  border: 1px solid #fdba74;
  font-size: 11px;
  font-weight: 600;
}
.priority-badge-normal {
  background: #e0f2fe;
  color: #0369a1;
  border: 1px solid #bae6fd;
  font-size: 11px;
  font-weight: 500;
}

.ann-item-title {
  font-size: 14.5px;
  font-weight: 700;
  color: #1e293b;
  margin-top: 6px;
  margin-bottom: 8px;
  line-height: 1.4;
  cursor: pointer;
  transition: color 0.15s;
}
.ann-item-title:hover {
  color: #0284c7;
}

.ann-item-summary {
  font-size: 12.5px;
  color: #64748b;
  line-height: 1.5;
  margin-bottom: 12px;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.ann-meta-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 10px;
  border-top: 1px dashed #e2e8f0;
  font-size: 11.5px;
  color: #64748b;
  flex-wrap: wrap;
  gap: 6px;
}
.ann-meta-item {
  display: inline-flex;
  align-items: center;
  gap: 3px;
}
.ann-meta-item .material-icons {
  font-size: 14px;
}

/* Gallery & Carousel trong Modal Thông Báo */
.ann-gallery-slider {
  background: #0f172a;
  position: relative;
}
.ann-gallery-nav-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 36px;
  height: 36px;
  background: rgba(0, 0, 0, 0.45);
  color: #ffffff;
  border: none;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  z-index: 5;
}
.ann-gallery-nav-btn:hover {
  background: rgba(0, 0, 0, 0.85);
  transform: translateY(-50%) scale(1.08);
}
.ann-nav-prev { left: 10px; }
.ann-nav-next { right: 10px; }
.ann-gallery-counter {
  position: absolute;
  bottom: 10px;
  right: 12px;
  background: rgba(0, 0, 0, 0.65);
  color: #ffffff;
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 12px;
  font-weight: 600;
}
.ann-gallery-thumb-item {
  width: 64px;
  height: 48px;
  border-radius: 4px;
  object-fit: cover;
  cursor: pointer;
  border: 2px solid transparent;
  opacity: 0.65;
  transition: all 0.2s;
  flex-shrink: 0;
}
.ann-gallery-thumb-item.active,
.ann-gallery-thumb-item:hover {
  border-color: #3b82f6;
  opacity: 1;
}

/* Thẻ tài liệu đính kèm */
.ann-attachment-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 14px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  transition: all 0.2s;
}
.ann-attachment-card:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}
.ann-file-icon {
  width: 36px;
  height: 36px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.ann-file-icon.pdf  { background: #fee2e2; color: #dc2626; }
.ann-file-icon.doc  { background: #dbeafe; color: #2563eb; }
.ann-file-icon.xls  { background: #dcfce7; color: #16a34a; }
.ann-file-icon.ppt  { background: #ffedd5; color: #ea580c; }
.ann-file-icon.file { background: #f1f5f9; color: #475569; }

/* Lightbox toàn màn hình */
.ann-lb-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 44px;
  height: 44px;
  background: rgba(255, 255, 255, 0.15);
  color: #ffffff;
  border: none;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  z-index: 10;
}
.ann-lb-arrow:hover {
  background: rgba(255, 255, 255, 0.35);
  transform: translateY(-50%) scale(1.1);
}
.ann-lb-prev { left: 20px; }
.ann-lb-next { right: 20px; }

/* Thẻ ảnh trong form Admin */
.staged-img-card {
  width: 100px;
  height: 100px;
  position: relative;
  border-radius: 6px;
  overflow: hidden;
  border: 1px solid #cbd5e1;
  background: #f1f5f9;
}
.staged-img-card img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.staged-img-card .img-controls {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: rgba(0, 0, 0, 0.65);
  display: flex;
  justify-content: space-between;
  padding: 2px 4px;
}
.staged-img-card .img-controls button {
  background: transparent;
  border: none;
  color: #ffffff;
  padding: 0;
  display: flex;
  align-items: center;
  cursor: pointer;
}
.staged-img-card .btn-remove-img {
  position: absolute;
  top: 2px;
  right: 2px;
  width: 20px;
  height: 20px;
  background: rgba(239, 68, 68, 0.9);
  color: #ffffff;
  border: none;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 14px;
}
.staged-img-card .cover-badge {
  position: absolute;
  top: 2px;
  left: 2px;
  background: #3b82f6;
  color: #ffffff;
  font-size: 9px;
  padding: 1px 4px;
  border-radius: 3px;
  font-weight: bold;
}
</style>

<!-- ========================================================================= -->
<!-- 8. JAVASCRIPT XỬ LÝ CAROUSEL & QUẢN LÝ BANNER HOẠT ĐỘNG                   -->
<!-- ========================================================================= -->
<script>
// Biến trạng thái toàn cục của Carousel & Quản trị
let allBannersData = [];
let filteredBanners = [];
let currentSlideIndex = 0;
let carouselTimer = null;
let carouselProgressTimer = null;
let carouselSettings = {
  autoplay: true,
  interval: 5000,
  image_autoplay: true,
  image_interval: 3000,
  show_sub_controls: true,
  pause_on_hover: true,
  animation_effect: 'slide',
  show_indicators: true,
  show_arrows: true,
  show_badges: true
};

let isCarouselPaused = false;
let currentActiveCategory = 'all';

// Quản lý trạng thái slideshow ảnh con (Sub-slideshow per Activity)
let currentSubImageIndexes = {}; // Mapping bannerId => currentSubIndex
let subImageAutoplayTimer = null;
let isSubImagePaused = false;

// Quản lý Modal instances
let pdfModalInstance = null;
let bannerManagerModalInstance = null;
let bannerDetailModalInstance = null;

// Quản lý trạng thái Announcements
let allAnnouncementsData = [];
let currentAnnouncementFilter = 'all'; // 'all', 'important', 'unread'
let announcementDetailModalInstance = null;
let announcementManagerModalInstance = null;
let announcementViewersModalInstance = null;
let announcementImageLightboxModalInstance = null;
let currentDetailAnnouncementId = 0;
let debounceAnnSearchTimer = null;
let currentAdminViewersData = [];

// Quản lý hình ảnh và tài liệu đính kèm cho Announcement
let stagedAnnImages = []; // Array of { url, thumb, name, ext, size, order }
let stagedAnnAttachments = []; // Array of { url, name, ext, size, size_formatted, uploaded_at }
let currentDetailAnnImages = []; // Danh sách ảnh của bài viết đang xem
let currentDetailAnnImageIndex = 0;
let currentAnnLightboxImages = []; // Danh sách ảnh đang hiển thị trong Lightbox
let currentAnnLightboxIndex = 0;

// Biến phục vụ Lightbox đa ảnh
let activeLightboxImages = [];
let activeLightboxIndex = 0;

// Biến lưu danh sách ảnh form khi sửa/thêm
let stagedFormImages = []; // Array of { isNew: bool, file: File, url: string, isCover: bool }

// ---------------------------------------------------------------------------
// 8.1. KHỞI TẠO DỮ LIỆU & TẢI BANNER TỪ API
// ---------------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function() {
  loadCarouselData();
  setupCarouselTouchGestures();

  // Khởi tạo Thông báo mới nhất
  loadAnnouncementsData();

  // Kiểm tra quyền hiển thị nút quản trị banner (BannerActivity.*)
  const btnAdmin = document.getElementById('bannerAdminActionGroup');
  if (btnAdmin) {
    const canManage = hasPermission([
      'BannerActivity.Create', 
      'BannerActivity.Edit', 
      'BannerActivity.Delete', 
      'BannerActivity.ConfigCarousel', 
      'BannerActivity.UploadImage',
      'dashboard.edit'
    ]);
    btnAdmin.style.display = canManage ? 'flex' : 'none';
  }

  // Kiểm tra quyền hiển thị nút Quản lý thông báo (Announcement.*)
  const btnAnnAdmin = document.getElementById('announcementAdminActionBtn');
  if (btnAnnAdmin) {
    const canManageAnn = hasPermission([
      'Announcement.Create',
      'Announcement.Edit',
      'Announcement.Delete',
      'Announcement.Publish'
    ]);
    if (canManageAnn) {
      btnAnnAdmin.classList.remove('d-none');
      btnAnnAdmin.classList.add('d-flex');
    }
  }

  // Sự kiện khi modal PDF đóng
  const pdfModalEl = document.getElementById('targetPdfModal');
  if (pdfModalEl) {
    pdfModalEl.addEventListener('hidden.bs.modal', function () {
      document.getElementById('modalPdfViewer').src = 'about:blank';
    });
  }

  // Tạm dừng khi rê chuột vào Carousel
  const container = document.getElementById('dxMainCarousel');
  if (container) {
    container.addEventListener('mouseenter', () => {
      if (carouselSettings.pause_on_hover) {
        isCarouselPaused = true;
        pauseProgressBar();
        pauseSubImageAutoplay();
      }
    });
    container.addEventListener('mouseleave', () => {
      if (carouselSettings.pause_on_hover) {
        isCarouselPaused = false;
        resumeProgressBar();
        resumeSubImageAutoplay();
      }
    });

    // Hỗ trợ phím mũi tên bàn phím
    container.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowLeft') { prevSlide(); }
      else if (e.key === 'ArrowRight') { nextSlide(); }
    });
  }

  // Lắng nghe click các tab lọc danh mục
  const filterBtns = document.querySelectorAll('#carouselCategoryFilters .dx-cat-pill');
  filterBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      filterBtns.forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      const cat = this.getAttribute('data-cat');
      filterCarouselByCategory(cat);
    });
  });
});

async function loadCarouselData() {
  try {
    const res = await fetch('api/dashboard_banners.php?action=list&status=active');
    const result = await res.json();
    if (result.success) {
      allBannersData = result.data || [];
      if (result.settings) {
        carouselSettings = Object.assign(carouselSettings, result.settings);
      }
      filterCarouselByCategory(currentActiveCategory);
      updateCarouselItemCountBadge();
    } else {
      showEmptyCarouselState("Không thể tải dữ liệu banner: " + result.message);
    }
  } catch (err) {
    console.error("Lỗi nạp dữ liệu Carousel:", err);
    showEmptyCarouselState("Lỗi kết nối máy chủ khi nạp banner!");
  }
}

function updateCarouselItemCountBadge() {
  const badge = document.getElementById('carouselItemCountBadge');
  if (badge) {
    const activeCount = allBannersData.length;
    badge.textContent = `${activeCount} hoạt động`;
  }
}

// ---------------------------------------------------------------------------
// 8.2. RENDER CAROUSEL TRÊN GIAO DIỆN & SUB-SLIDESHOW
// ---------------------------------------------------------------------------
function filterCarouselByCategory(cat) {
  currentActiveCategory = cat;
  if (cat === 'all') {
    filteredBanners = [...allBannersData];
  } else {
    filteredBanners = allBannersData.filter(b => b.category === cat);
  }

  currentSlideIndex = 0;
  renderCarouselSlides();
}

function renderCarouselSlides() {
  const track = document.getElementById('carouselTrack');
  const indicators = document.getElementById('carouselIndicators');
  const counter = document.getElementById('carouselCounter');
  if (!track) return;

  stopCarouselAutoplay();
  stopSubImageAutoplay();

  if (filteredBanners.length === 0) {
    showEmptyCarouselState("Chưa có hoạt động nào trong danh mục này.");
    if (indicators) indicators.innerHTML = '';
    if (counter) counter.textContent = '00 / 00';
    return;
  }

  // Render Slides kèm Sub-slideshow cho các hoạt động có nhiều ảnh
  let trackHtml = '';
  filteredBanners.forEach((item, idx) => {
    const badgeClass = getCategoryBadgeClass(item.category);
    const categoryIcon = getCategoryIcon(item.category);
    const imgList = item.images_list && item.images_list.length > 0 ? item.images_list : [item.image_url];
    const imgCount = imgList.length;
    currentSubImageIndexes[item.id] = 0; // Mặc định ảnh con đầu tiên

    // Khung nút sub-controls & sub-indicators cho ảnh trong hoạt động
    const subControlsHtml = (imgCount > 1 && carouselSettings.show_sub_controls !== false) ? `
      <div class="dx-sub-controls-container" onclick="event.stopPropagation()">
        <button type="button" class="dx-sub-nav-btn dx-sub-prev" title="Ảnh trước" onclick="changeSubImage(${item.id}, -1)">
          <span class="material-icons">chevron_left</span>
        </button>
        <div class="dx-sub-indicators-bar" id="subDotsBar-${item.id}">
          ${imgList.map((_, sIdx) => `
            <span class="dx-sub-dot ${sIdx === 0 ? 'active' : ''}" title="Ảnh ${sIdx + 1}" onclick="setSubImage(${item.id}, ${sIdx})"></span>
          `).join('')}
        </div>
        <span class="dx-sub-img-counter" id="subImgCounter-${item.id}">
          <span class="material-icons" style="font-size: 13px;">photo_camera</span> 1/${imgCount}
        </span>
        <button type="button" class="dx-sub-nav-btn dx-sub-next" title="Ảnh tiếp theo" onclick="changeSubImage(${item.id}, 1)">
          <span class="material-icons">chevron_right</span>
        </button>
      </div>
    ` : '';

    // Khung ảnh con hỗ trợ chuyển ảnh luân phiên mượt mà
    const slideImagesHtml = `
      <div class="dx-slide-image-wrapper" id="slideImgWrapper-${item.id}">
        ${imgList.map((img, sIdx) => `
          <img src="${escapeHtml(img)}" alt="${escapeHtml(item.title)}" 
               class="dx-sub-slide-img ${sIdx === 0 ? 'active' : ''}" 
               data-sub-idx="${sIdx}" 
               loading="lazy" onerror="this.src='resources/placeholder_banner.svg'">
        `).join('')}
      </div>
    `;

    trackHtml += `
      <div class="dx-carousel-slide" data-index="${idx}" data-banner-id="${item.id}" onclick="openBannerLightbox(${item.id})">
        ${slideImagesHtml}
        ${subControlsHtml}
        <div class="dx-slide-overlay">
          <div class="dx-slide-content">
            <div class="dx-slide-meta">
              <span class="dx-activity-badge ${badgeClass}">
                ${categoryIcon} ${escapeHtml(item.category)}
              </span>
              <span class="dx-slide-date">
                <span class="material-icons" style="font-size: 14px;">calendar_today</span>
                ${item.event_date_formatted || item.event_date}
              </span>
              ${imgCount > 1 ? `<span class="dx-img-count-badge"><span class="material-icons" style="font-size: 13px;">photo_library</span> ${imgCount} ảnh</span>` : ''}
            </div>

            <h3 class="dx-slide-title">${escapeHtml(item.title)}</h3>
            <p class="dx-slide-desc">${escapeHtml(item.summary || '')}</p>

            <div class="dx-slide-actions">
              <button type="button" class="dx-btn-slide-detail" onclick="event.stopPropagation(); openBannerLightbox(${item.id})">
                <span class="material-icons" style="font-size: 16px;">visibility</span> Xem chi tiết
              </button>
            </div>
          </div>
        </div>
      </div>
    `;
  });
  track.innerHTML = trackHtml;

  // Render Indicators cho các slide chính
  if (indicators) {
    if (carouselSettings.show_indicators && filteredBanners.length > 1) {
      indicators.style.display = 'flex';
      let indHtml = '';
      filteredBanners.forEach((_, idx) => {
        indHtml += `<button type="button" class="dx-indicator-dot ${idx === 0 ? 'active' : ''}" aria-label="Slide ${idx + 1}" onclick="goToSlide(${idx})"></button>`;
      });
      indicators.innerHTML = indHtml;
    } else {
      indicators.style.display = 'none';
      indicators.innerHTML = '';
    }
  }

  // Ẩn/Hiện nút Prev / Next theo cài đặt
  const btnPrev = document.getElementById('btnCarouselPrev');
  const btnNext = document.getElementById('btnCarouselNext');
  const shouldShowArrows = carouselSettings.show_arrows && filteredBanners.length > 1;
  if (btnPrev) btnPrev.style.display = shouldShowArrows ? 'flex' : 'none';
  if (btnNext) btnNext.style.display = shouldShowArrows ? 'flex' : 'none';

  updateSlidePosition(false);
  startCarouselAutoplay();
}

function showEmptyCarouselState(msg) {
  const track = document.getElementById('carouselTrack');
  if (track) {
    track.innerHTML = `
      <div class="d-flex flex-column align-items-center justify-content-center w-100 h-100 p-4 text-center text-white-50">
        <span class="material-icons fs-1 mb-2 text-warning">campaign</span>
        <div class="fw-semibold text-white fs-sm mb-1">${escapeHtml(msg)}</div>
        <div class="fs-xs">Quản trị viên có thể bấm "Quản Lý Banner Hoạt Động" để thêm bài mới.</div>
      </div>
    `;
    track.style.transform = 'translateX(0%)';
  }
}

function updateSlidePosition(smooth = true) {
  const track = document.getElementById('carouselTrack');
  const counter = document.getElementById('carouselCounter');
  if (!track || filteredBanners.length === 0) return;

  track.style.transition = smooth ? 'transform 0.6s cubic-bezier(0.25, 1, 0.5, 1)' : 'none';
  track.style.transform = `translateX(-${currentSlideIndex * 100}%)`;

  // Cập nhật dots
  const dots = document.querySelectorAll('#carouselIndicators .dx-indicator-dot');
  dots.forEach((dot, idx) => {
    if (idx === currentSlideIndex) {
      dot.classList.add('active');
    } else {
      dot.classList.remove('active');
    }
  });

  // Cập nhật counter
  if (counter) {
    const cur = String(currentSlideIndex + 1).padStart(2, '0');
    const tot = String(filteredBanners.length).padStart(2, '0');
    counter.textContent = `${cur} / ${tot}`;
  }

  // Kích hoạt Sub-slideshow cho slide đang hiển thị
  restartSubImageForActiveSlide();
}

// ---------------------------------------------------------------------------
// 8.2.1. CÁC HÀM XỬ LÝ SLIDESHOW ẢNH CON TRONG CÙNG HOẠT ĐỘNG
// ---------------------------------------------------------------------------
function restartSubImageForActiveSlide() {
  stopSubImageAutoplay();
  if (filteredBanners.length === 0 || currentSlideIndex >= filteredBanners.length) return;

  const activeBanner = filteredBanners[currentSlideIndex];
  if (!activeBanner) return;

  const imgList = activeBanner.images_list && activeBanner.images_list.length > 0 ? activeBanner.images_list : [activeBanner.image_url];
  if (imgList.length > 1 && carouselSettings.image_autoplay !== false) {
    startSubImageAutoplay(activeBanner.id, imgList.length);
  }
}

function startSubImageAutoplay(bannerId, count) {
  stopSubImageAutoplay();
  if (count <= 1 || carouselSettings.image_autoplay === false) return;

  const intervalMs = Math.max(1000, parseInt(carouselSettings.image_interval) || 3000);
  subImageAutoplayTimer = setInterval(() => {
    if (!isSubImagePaused && !isCarouselPaused) {
      changeSubImage(bannerId, 1, false);
    }
  }, intervalMs);
}

function stopSubImageAutoplay() {
  if (subImageAutoplayTimer) {
    clearInterval(subImageAutoplayTimer);
    subImageAutoplayTimer = null;
  }
}

function pauseSubImageAutoplay() {
  isSubImagePaused = true;
}

function resumeSubImageAutoplay() {
  isSubImagePaused = false;
}

function changeSubImage(bannerId, direction, manual = true) {
  const banner = allBannersData.find(b => b.id == bannerId);
  if (!banner) return;
  const imgList = banner.images_list && banner.images_list.length > 0 ? banner.images_list : [banner.image_url];
  const count = imgList.length;
  if (count <= 1) return;

  const curIdx = currentSubImageIndexes[bannerId] || 0;
  const nextIdx = (curIdx + direction + count) % count;
  setSubImage(bannerId, nextIdx, manual);
}

function setSubImage(bannerId, targetIdx, manual = true) {
  const banner = allBannersData.find(b => b.id == bannerId);
  if (!banner) return;
  const imgList = banner.images_list && banner.images_list.length > 0 ? banner.images_list : [banner.image_url];
  const count = imgList.length;
  if (targetIdx < 0 || targetIdx >= count) return;

  currentSubImageIndexes[bannerId] = targetIdx;

  // 1. Chuyển ảnh active
  const wrapper = document.getElementById(`slideImgWrapper-${bannerId}`);
  if (wrapper) {
    const allImgs = wrapper.querySelectorAll('.dx-sub-slide-img');
    allImgs.forEach((img, sIdx) => {
      if (sIdx === targetIdx) {
        img.classList.add('active');
      } else {
        img.classList.remove('active');
      }
    });
  }

  // 2. Cập nhật counter
  const counterEl = document.getElementById(`subImgCounter-${bannerId}`);
  if (counterEl) {
    counterEl.innerHTML = `<span class="material-icons" style="font-size: 13px;">photo_camera</span> ${targetIdx + 1}/${count}`;
  }

  // 3. Cập nhật sub-dots
  const dotsBar = document.getElementById(`subDotsBar-${bannerId}`);
  if (dotsBar) {
    const dots = dotsBar.querySelectorAll('.dx-sub-dot');
    dots.forEach((dot, sIdx) => {
      if (sIdx === targetIdx) {
        dot.classList.add('active');
      } else {
        dot.classList.remove('active');
      }
    });
  }

  // Nếu thao tác tay (manual), restart timer để xem ảnh đủ thời gian
  if (manual) {
    startSubImageAutoplay(bannerId, count);
  }
}

function goToSlide(index) {
  if (index < 0 || index >= filteredBanners.length) return;
  currentSlideIndex = index;
  updateSlidePosition(true);
  resetProgressBar();
}

function nextSlide() {
  if (filteredBanners.length <= 1) return;
  currentSlideIndex = (currentSlideIndex + 1) % filteredBanners.length;
  updateSlidePosition(true);
  resetProgressBar();
}

function prevSlide() {
  if (filteredBanners.length <= 1) return;
  currentSlideIndex = (currentSlideIndex - 1 + filteredBanners.length) % filteredBanners.length;
  updateSlidePosition(true);
  resetProgressBar();
}

// ---------------------------------------------------------------------------
// 8.3. LOGIC TỰ ĐỘNG CHẠY (AUTOPLAY) & THANH TIẾN TRÌNH PROGRESS BAR
// ---------------------------------------------------------------------------
let progressStartTime = 0;
let progressDuration = 5000;
let progressElapsed = 0;
let isProgressPaused = false;

function startCarouselAutoplay() {
  stopCarouselAutoplay();
  if (!carouselSettings.autoplay || filteredBanners.length <= 1) {
    hideProgressBar();
    return;
  }

  progressDuration = parseInt(carouselSettings.interval) || 5000;
  progressElapsed = 0;
  isProgressPaused = false;
  progressStartTime = Date.now();

  runProgressBar();
}

function stopCarouselAutoplay() {
  if (carouselTimer) { clearTimeout(carouselTimer); carouselTimer = null; }
  if (carouselProgressTimer) { cancelAnimationFrame(carouselProgressTimer); carouselProgressTimer = null; }
  stopSubImageAutoplay();
  hideProgressBar();
}

function runProgressBar() {
  const bar = document.getElementById('carouselProgressBar');
  if (!bar) return;
  bar.style.display = 'block';

  function step() {
    if (!isProgressPaused) {
      const now = Date.now();
      const delta = now - progressStartTime;
      progressStartTime = now;
      progressElapsed += delta;

      const pct = Math.min(100, (progressElapsed / progressDuration) * 100);
      bar.style.width = pct + '%';

      if (progressElapsed >= progressDuration) {
        nextSlide();
        return;
      }
    }
    carouselProgressTimer = requestAnimationFrame(step);
  }
  carouselProgressTimer = requestAnimationFrame(step);
}

function pauseProgressBar() {
  isProgressPaused = true;
  pauseSubImageAutoplay();
}

function resumeProgressBar() {
  progressStartTime = Date.now();
  isProgressPaused = false;
  resumeSubImageAutoplay();
}

function resetProgressBar() {
  progressElapsed = 0;
  progressStartTime = Date.now();
  const bar = document.getElementById('carouselProgressBar');
  if (bar) bar.style.width = '0%';
}

function hideProgressBar() {
  const bar = document.getElementById('carouselProgressBar');
  if (bar) { bar.style.width = '0%'; bar.style.display = 'none'; }
}

// Gắn sự kiện nút bấm Next / Prev
document.getElementById('btnCarouselPrev')?.addEventListener('click', (e) => { e.stopPropagation(); prevSlide(); });
document.getElementById('btnCarouselNext')?.addEventListener('click', (e) => { e.stopPropagation(); nextSlide(); });

// ---------------------------------------------------------------------------
// 8.4. HỖ TRỢ VUỐT CHẠM (TOUCH SWIPE) TRÊN MOBILE & TABLET
// ---------------------------------------------------------------------------
function setupCarouselTouchGestures() {
  const container = document.getElementById('dxMainCarousel');
  if (!container) return;

  let touchStartX = 0;
  let touchStartY = 0;
  let touchEndX = 0;
  let isSwiping = false;

  container.addEventListener('touchstart', (e) => {
    if (e.touches.length === 1) {
      touchStartX = e.touches[0].clientX;
      touchStartY = e.touches[0].clientY;
      isSwiping = true;
    }
  }, { passive: true });

  container.addEventListener('touchmove', (e) => {
    if (!isSwiping || e.touches.length !== 1) return;
    touchEndX = e.touches[0].clientX;
  }, { passive: true });

  container.addEventListener('touchend', (e) => {
    if (!isSwiping) return;
    isSwiping = false;
    const diffX = touchEndX - touchStartX;
    if (Math.abs(diffX) > 45) {
      if (diffX < 0) { nextSlide(); }
      else { prevSlide(); }
    }
    touchStartX = 0;
    touchEndX = 0;
  });
}

// ---------------------------------------------------------------------------
// 8.5. LIGHTBOX MODAL: XEM CHI TIẾT & BỘ SƯU TẬP ẢNH PHÓNG TO
// ---------------------------------------------------------------------------
function openBannerLightbox(bannerId) {
  const item = allBannersData.find(b => b.id == bannerId);
  if (!item) return;

  activeLightboxImages = item.images_list || [item.image_url];
  activeLightboxIndex = 0;

  document.getElementById('modalDetailTitle').textContent = item.title;
  document.getElementById('modalDetailSummary').textContent = item.summary || '';
  document.getElementById('modalDetailDate').textContent = item.event_date_formatted || item.event_date;
  
  const badgeEl = document.getElementById('modalDetailCategoryBadge');
  if (badgeEl) {
    badgeEl.className = 'badge ' + getCategoryBadgeClass(item.category);
    badgeEl.textContent = item.category;
  }

  const contentEl = document.getElementById('modalDetailContent');
  if (contentEl) {
    if (item.content && item.content.trim()) {
      contentEl.textContent = item.content;
      contentEl.style.display = 'block';
    } else {
      contentEl.style.display = 'none';
    }
  }

  renderLightboxActiveImage();

  const modalEl = document.getElementById('bannerDetailModal');
  if (!bannerDetailModalInstance) {
    bannerDetailModalInstance = new bootstrap.Modal(modalEl);
  }
  bannerDetailModalInstance.show();
}

function renderLightboxActiveImage() {
  const imgEl = document.getElementById('modalDetailImage');
  const btnPrev = document.getElementById('btnLightboxPrev');
  const btnNext = document.getElementById('btnLightboxNext');
  const thumbsContainer = document.getElementById('modalDetailThumbs');

  if (activeLightboxImages.length > 0 && imgEl) {
    imgEl.src = activeLightboxImages[activeLightboxIndex];
  }

  const hasMultiple = activeLightboxImages.length > 1;
  if (btnPrev) btnPrev.style.display = hasMultiple ? 'flex' : 'none';
  if (btnNext) btnNext.style.display = hasMultiple ? 'flex' : 'none';

  if (thumbsContainer) {
    if (hasMultiple) {
      thumbsContainer.style.display = 'flex';
      let html = '';
      activeLightboxImages.forEach((img, idx) => {
        html += `<img src="${escapeHtml(img)}" class="dx-lb-thumb ${idx === activeLightboxIndex ? 'active' : ''}" onclick="setLightboxImage(${idx})">`;
      });
      thumbsContainer.innerHTML = html;
    } else {
      thumbsContainer.style.display = 'none';
    }
  }
}

function navigateLightboxImage(dir) {
  if (activeLightboxImages.length <= 1) return;
  activeLightboxIndex = (activeLightboxIndex + dir + activeLightboxImages.length) % activeLightboxImages.length;
  renderLightboxActiveImage();
}

function setLightboxImage(idx) {
  activeLightboxIndex = idx;
  renderLightboxActiveImage();
}

// ---------------------------------------------------------------------------
// 8.6. QUẢN LÝ BANNER: MÀN HÌNH QUẢN TRỊ (CRUD & REORDER & TOGGLE)
// ---------------------------------------------------------------------------
let allManagerBanners = [];

function openBannerManagerModal() {
  const modalEl = document.getElementById('bannerManagerModal');
  if (!bannerManagerModalInstance) {
    bannerManagerModalInstance = new bootstrap.Modal(modalEl, { backdrop: 'static' });
  }
  bannerManagerModalInstance.show();
  switchToListTab();
  loadManagerBannersTable();
  loadSettingsIntoForm();
}

function switchToListTab() {
  const tabBtn = document.getElementById('tab-list-btn');
  if (tabBtn) bootstrap.Tab.getOrCreateInstance(tabBtn).show();
}

function switchToCreateForm() {
  resetBannerForm();
  document.getElementById('formTabTitle').textContent = 'Thêm Hoạt Động Mới';
  document.getElementById('formTabIcon').textContent = 'add_circle_outline';
  document.getElementById('bannerFormAction').value = 'create';
  document.getElementById('bannerFormId').value = '0';
  document.getElementById('btnSubmitBannerText').textContent = 'Lưu Hoạt Động';

  const tabBtn = document.getElementById('tab-form-btn');
  if (tabBtn) bootstrap.Tab.getOrCreateInstance(tabBtn).show();
}

function openCarouselSettingsModal() {
  openBannerManagerModal();
  const tabBtn = document.getElementById('tab-settings-btn');
  if (tabBtn) bootstrap.Tab.getOrCreateInstance(tabBtn).show();
}

async function loadManagerBannersTable() {
  const tbody = document.getElementById('bannerManagerTableBody');
  if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2 text-primary"></div>Đang tải dữ liệu...</td></tr>';

  try {
    const res = await fetch('api/dashboard_banners.php?action=list&status=');
    const result = await res.json();
    if (result.success) {
      allManagerBanners = result.data || [];
      renderManagerTableRows(allManagerBanners);
    } else {
      if (tbody) tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">${escapeHtml(result.message)}</td></tr>`;
    }
  } catch (err) {
    console.error("Lỗi tải bảng quản lý:", err);
    if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-danger">Không thể kết nối máy chủ!</td></tr>';
  }
}

function renderManagerTableRows(list) {
  const tbody = document.getElementById('bannerManagerTableBody');
  const countEl = document.getElementById('managerTableTotalCount');
  if (!tbody) return;

  if (countEl) countEl.textContent = `Tổng cộng: ${list.length} banner`;

  if (list.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Chưa có banner nào trong hệ thống.</td></tr>';
    return;
  }

  const canEdit = hasPermission(['BannerActivity.Edit', 'dashboard.edit']);
  const canDelete = hasPermission(['BannerActivity.Delete', 'dashboard.edit']);
  const canPublish = hasPermission(['BannerActivity.Publish', 'dashboard.edit']);

  let html = '';
  list.forEach((item, index) => {
    const badgeClass = getCategoryBadgeClass(item.category);
    const isAct = item.status === 'active';
    const imgList = item.images_list && item.images_list.length > 0 ? item.images_list : [item.image_url];
    const imgUrl = item.image_url || imgList[0] || 'resources/placeholder_banner.svg';

    html += `
      <tr data-id="${item.id}">
        <td class="text-center fw-bold text-muted">
          <div class="d-flex flex-column align-items-center gap-1">
            <button type="button" class="btn btn-xs btn-outline-secondary p-0 px-1" title="Chuyển lên" ${!canEdit ? 'disabled' : ''} onclick="moveBannerRow(${item.id}, -1)">▲</button>
            <span class="fs-xs">${item.display_order || index + 1}</span>
            <button type="button" class="btn btn-xs btn-outline-secondary p-0 px-1" title="Chuyển xuống" ${!canEdit ? 'disabled' : ''} onclick="moveBannerRow(${item.id}, 1)">▼</button>
          </div>
        </td>
        <td>
          <div class="position-relative d-inline-block">
            <img src="${escapeHtml(imgUrl)}" alt="thumb" class="rounded border shadow-sm" style="width: 80px; height: 45px; object-fit: cover;" onerror="this.src='resources/placeholder_banner.svg'">
            ${imgList.length > 1 ? `<span class="badge bg-dark bg-opacity-75 text-white position-absolute bottom-0 end-0 m-1 p-0 px-1" style="font-size: 9px;"><span class="material-icons" style="font-size: 10px; vertical-align: middle;">photo_library</span> ${imgList.length}</span>` : ''}
          </div>
        </td>
        <td>
          <div class="fw-bold fs-sm text-truncate" style="max-width: 400px;">${escapeHtml(item.title)}</div>
          <div class="d-flex align-items-center gap-2 mt-1">
            <span class="badge ${badgeClass} fs-xs">${escapeHtml(item.category)}</span>
            <span class="text-muted fs-xs text-truncate" style="max-width: 300px;">${escapeHtml(item.summary || '')}</span>
          </div>
        </td>
        <td class="text-center text-muted fs-xs">${item.event_date_formatted || item.event_date}</td>
        <td class="text-center">
          <div class="form-check form-switch d-inline-block">
            <input class="form-check-input cursor-pointer" type="checkbox" role="switch" ${isAct ? 'checked' : ''} ${!canPublish ? 'disabled' : ''} onchange="toggleBannerStatus(${item.id}, this)">
          </div>
        </td>
        <td class="text-center">
          <div class="btn-group btn-group-sm">
            ${canEdit ? `
              <button type="button" class="btn btn-outline-primary" title="Chỉnh sửa" onclick="editBannerItem(${item.id})">
                <span class="material-icons fs-6">edit</span>
              </button>
            ` : ''}
            ${canDelete ? `
              <button type="button" class="btn btn-outline-danger" title="Xóa" onclick="deleteBannerItem(${item.id})">
                <span class="material-icons fs-6">delete</span>
              </button>
            ` : ''}
            ${(!canEdit && !canDelete) ? `<span class="text-muted fs-xs">Chỉ xem</span>` : ''}
          </div>
        </td>
      </tr>
    `;
  });
  tbody.innerHTML = html;
}

function filterManagerTable() {
  const query = (document.getElementById('bannerSearchInput')?.value || '').toLowerCase().trim();
  const cat = document.getElementById('bannerCategoryFilterSelect')?.value || 'all';

  const filtered = allManagerBanners.filter(b => {
    const matchQ = !query || b.title.toLowerCase().includes(query) || (b.summary && b.summary.toLowerCase().includes(query));
    const matchC = (cat === 'all') || (b.category === cat);
    return matchQ && matchC;
  });

  renderManagerTableRows(filtered);
}

// Bật/tắt trạng thái hiển thị nhanh
async function toggleBannerStatus(id, switchEl) {
  if (!hasPermission(['BannerActivity.Publish', 'dashboard.edit'])) {
    alert("Bạn không có quyền thay đổi trạng thái xuất bản! (Yêu cầu BannerActivity.Publish)");
    switchEl.checked = !switchEl.checked;
    return;
  }

  try {
    const fd = new FormData();
    fd.append('action', 'toggle_status');
    fd.append('id', id);

    const res = await fetch('api/dashboard_banners.php', { method: 'POST', body: fd });
    const result = await res.json();
    if (result.success) {
      loadCarouselData(); // Cập nhật lại Carousel ngay lập tức
    } else {
      alert("Lỗi: " + result.message);
      switchEl.checked = !switchEl.checked;
    }
  } catch (err) {
    console.error("Lỗi toggle status:", err);
    alert("Không thể đổi trạng thái banner!");
    switchEl.checked = !switchEl.checked;
  }
}

// Sắp xếp thứ tự hiển thị banner
async function moveBannerRow(id, direction) {
  if (!hasPermission(['BannerActivity.Edit', 'dashboard.edit'])) {
    alert("Bạn không có quyền sắp xếp hoạt động! (Yêu cầu BannerActivity.Edit)");
    return;
  }

  const idx = allManagerBanners.findIndex(b => b.id == id);
  if (idx === -1) return;
  const targetIdx = idx + direction;
  if (targetIdx < 0 || targetIdx >= allManagerBanners.length) return;

  // Hoán đổi vị trí
  const temp = allManagerBanners[idx];
  allManagerBanners[idx] = allManagerBanners[targetIdx];
  allManagerBanners[targetIdx] = temp;

  renderManagerTableRows(allManagerBanners);

  // Gửi mảng order IDs mới lên server
  try {
    const orderIds = allManagerBanners.map(b => b.id);
    const res = await fetch('api/dashboard_banners.php?action=reorder', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ order_ids: orderIds })
    });
    const result = await res.json();
    if (result.success) {
      loadCarouselData();
    }
  } catch (err) {
    console.error("Lỗi lưu thứ tự:", err);
  }
}

// ---------------------------------------------------------------------------
// 8.7. FORM XỬ LÝ ẢNH & THÊM/SỬA BANNER
// ---------------------------------------------------------------------------
function resetBannerForm() {
  document.getElementById('bannerForm').reset();
  document.getElementById('bannerFormId').value = '0';
  document.getElementById('bannerEventDate').value = new Date().toISOString().split('T')[0];
  document.getElementById('bannerDisplayOrder').value = '0';
  stagedFormImages = [];
  renderImagePreviewGrid();
}

function handleImageSelection(input) {
  if (!input.files || input.files.length === 0) return;
  if (!hasPermission(['BannerActivity.UploadImage', 'BannerActivity.Create', 'BannerActivity.Edit', 'dashboard.edit'])) {
    alert("Bạn không có quyền tải lên hình ảnh! (Yêu cầu BannerActivity.UploadImage)");
    input.value = '';
    return;
  }

  const isFirstBatch = stagedFormImages.length === 0;
  Array.from(input.files).forEach((file, fIdx) => {
    stagedFormImages.push({
      isNew: true,
      file: file,
      url: URL.createObjectURL(file),
      isCover: (isFirstBatch && fIdx === 0)
    });
  });
  input.value = ''; // Cho phép chọn lại cùng file
  renderImagePreviewGrid();
}

function renderImagePreviewGrid() {
  const container = document.getElementById('bannerImagePreviewGrid');
  if (!container) return;

  if (stagedFormImages.length === 0) {
    container.innerHTML = '<div class="text-muted fs-xs py-2">Chưa có ảnh nào được chọn. Hãy chọn hoặc kéo thả ít nhất 1 ảnh.</div>';
    return;
  }

  // Đảm bảo luôn có 1 ảnh được chọn làm cover
  const hasCover = stagedFormImages.some(img => img.isCover);
  if (!hasCover && stagedFormImages.length > 0) {
    stagedFormImages[0].isCover = true;
  }

  const canReorder = hasPermission(['BannerActivity.ReorderImage', 'BannerActivity.Edit', 'dashboard.edit']);
  const canDeleteImg = hasPermission(['BannerActivity.DeleteImage', 'BannerActivity.Edit', 'dashboard.edit']);

  let html = '';
  stagedFormImages.forEach((img, idx) => {
    const isCover = !!img.isCover;
    const isFirst = idx === 0;
    const isLast = idx === stagedFormImages.length - 1;

    html += `
      <div class="dx-preview-item ${isCover ? 'is-cover' : ''}" data-idx="${idx}">
        <img src="${escapeHtml(img.url)}" alt="preview" onerror="this.src='resources/placeholder_banner.svg'">
        ${isCover ? '<span class="dx-cover-badge"><span class="material-icons" style="font-size: 11px;">star</span> Đại diện</span>' : ''}
        <span class="dx-preview-order-tag">#${idx + 1}</span>

        <div class="dx-preview-actions-bar">
          ${(canReorder && !isCover) ? `
            <button type="button" class="dx-preview-btn btn-set-cover" title="Đặt làm ảnh đại diện" onclick="setAsCoverImage(${idx})">
              <span class="material-icons" style="font-size: 13px;">star_border</span>
            </button>
          ` : ''}

          ${(canReorder && !isFirst) ? `
            <button type="button" class="dx-preview-btn" title="Chuyển sang trước" onclick="moveStagedImage(${idx}, -1)">
              <span class="material-icons" style="font-size: 14px;">arrow_back</span>
            </button>
          ` : ''}

          ${(canReorder && !isLast) ? `
            <button type="button" class="dx-preview-btn" title="Chuyển ra sau" onclick="moveStagedImage(${idx}, 1)">
              <span class="material-icons" style="font-size: 14px;">arrow_forward</span>
            </button>
          ` : ''}

          ${canDeleteImg ? `
            <button type="button" class="dx-preview-btn btn-del" title="Xóa ảnh này" onclick="removeStagedImage(${idx})">
              <span class="material-icons" style="font-size: 13px;">delete</span>
            </button>
          ` : ''}
        </div>
      </div>
    `;
  });
  container.innerHTML = html;
}

function setAsCoverImage(idx) {
  if (idx < 0 || idx >= stagedFormImages.length) return;
  // Di chuyển ảnh được chọn lên đầu và đặt làm cover
  const [selected] = stagedFormImages.splice(idx, 1);
  stagedFormImages.forEach(img => img.isCover = false);
  selected.isCover = true;
  stagedFormImages.unshift(selected);
  renderImagePreviewGrid();
}

function moveStagedImage(idx, direction) {
  const targetIdx = idx + direction;
  if (targetIdx < 0 || targetIdx >= stagedFormImages.length) return;
  const temp = stagedFormImages[idx];
  stagedFormImages[idx] = stagedFormImages[targetIdx];
  stagedFormImages[targetIdx] = temp;
  renderImagePreviewGrid();
}

function removeStagedImage(idx) {
  if (!hasPermission(['BannerActivity.DeleteImage', 'BannerActivity.Edit', 'dashboard.edit'])) {
    alert("Bạn không có quyền xóa hình ảnh! (Yêu cầu BannerActivity.DeleteImage)");
    return;
  }
  const removed = stagedFormImages.splice(idx, 1)[0];
  if (removed && removed.isCover && stagedFormImages.length > 0) {
    stagedFormImages[0].isCover = true;
  }
  renderImagePreviewGrid();
}

function editBannerItem(id) {
  if (!hasPermission(['BannerActivity.Edit', 'dashboard.edit'])) {
    alert("Bạn không có quyền chỉnh sửa hoạt động! (Yêu cầu BannerActivity.Edit)");
    return;
  }

  const item = allManagerBanners.find(b => b.id == id);
  if (!item) return;

  resetBannerForm();
  document.getElementById('formTabTitle').textContent = `Chỉnh Sửa Hoạt Động #${item.id}`;
  document.getElementById('formTabIcon').textContent = 'edit';
  document.getElementById('bannerFormAction').value = 'update';
  document.getElementById('bannerFormId').value = item.id;
  document.getElementById('btnSubmitBannerText').textContent = 'Cập Nhật Hoạt Động';

  document.getElementById('bannerTitle').value = item.title;
  document.getElementById('bannerCategory').value = item.category;
  document.getElementById('bannerSummary').value = item.summary || '';
  document.getElementById('bannerContent').value = item.content || '';
  document.getElementById('bannerEventDate').value = item.event_date;
  document.getElementById('bannerDisplayOrder').value = item.display_order || 0;
  document.getElementById('bannerStatus').value = item.status || 'active';

  // Nạp danh sách ảnh cũ và xác định ảnh cover
  const oldImgs = item.images_list && item.images_list.length > 0 ? item.images_list : [item.image_url];
  const coverUrl = item.image_url || oldImgs[0];

  stagedFormImages = oldImgs.map((url, i) => ({
    isNew: false,
    file: null,
    url: url,
    isCover: (url === coverUrl || (i === 0 && !oldImgs.includes(coverUrl)))
  }));

  // Đưa ảnh cover lên đầu nếu có
  const coverIdx = stagedFormImages.findIndex(img => img.isCover);
  if (coverIdx > 0) {
    const [cImg] = stagedFormImages.splice(coverIdx, 1);
    stagedFormImages.unshift(cImg);
  }

  renderImagePreviewGrid();

  const tabBtn = document.getElementById('tab-form-btn');
  if (tabBtn) bootstrap.Tab.getOrCreateInstance(tabBtn).show();
}

async function handleSaveBanner(e) {
  e.preventDefault();
  const btnSubmit = document.getElementById('btnSubmitBannerForm');
  const btnText = document.getElementById('btnSubmitBannerText');
  const originalText = btnText.textContent;

  const action = document.getElementById('bannerFormAction').value;
  if (action === 'create' && !hasPermission(['BannerActivity.Create', 'dashboard.edit'])) {
    alert("Bạn không có quyền tạo hoạt động mới! (Yêu cầu BannerActivity.Create)");
    return;
  }
  if (action === 'update' && !hasPermission(['BannerActivity.Edit', 'dashboard.edit'])) {
    alert("Bạn không có quyền cập nhật hoạt động! (Yêu cầu BannerActivity.Edit)");
    return;
  }

  const fd = new FormData();
  fd.append('action', action);
  fd.append('id', document.getElementById('bannerFormId').value);
  fd.append('title', document.getElementById('bannerTitle').value);
  fd.append('category', document.getElementById('bannerCategory').value);
  fd.append('summary', document.getElementById('bannerSummary').value);
  fd.append('content', document.getElementById('bannerContent').value);
  fd.append('event_date', document.getElementById('bannerEventDate').value);
  fd.append('display_order', document.getElementById('bannerDisplayOrder').value);
  fd.append('status', document.getElementById('bannerStatus').value);

  // Phân tách ảnh cũ giữ lại (theo thứ tự sắp xếp) và ảnh mới upload
  const keepImages = stagedFormImages.filter(img => !img.isNew).map(img => img.url);
  fd.append('keep_images', JSON.stringify(keepImages));

  const newImages = stagedFormImages.filter(img => img.isNew);
  newImages.forEach(img => {
    fd.append('images[]', img.file);
  });

  // Xác định cover image
  const coverObj = stagedFormImages.find(img => img.isCover) || stagedFormImages[0];
  if (coverObj && !coverObj.isNew) {
    fd.append('cover_image', coverObj.url);
  }

  try {
    btnSubmit.disabled = true;
    btnText.textContent = 'Đang lưu...';

    const res = await fetch('api/dashboard_banners.php', {
      method: 'POST',
      body: fd
    });
    const result = await res.json();

    if (result.success) {
      alert(result.message);
      loadManagerBannersTable();
      loadCarouselData();
      switchToListTab();
    } else {
      alert("Lỗi: " + result.message);
    }
  } catch (err) {
    console.error("Lỗi khi lưu banner:", err);
    alert("Không thể lưu banner! Vui lòng thử lại.");
  } finally {
    btnSubmit.disabled = false;
    btnText.textContent = originalText;
  }
}

async function deleteBannerItem(id) {
  if (!hasPermission(['BannerActivity.Delete', 'dashboard.edit'])) {
    alert("Bạn không có quyền xóa hoạt động! (Yêu cầu BannerActivity.Delete)");
    return;
  }

  if (!confirm("Bạn có chắc chắn muốn xóa vĩnh viễn banner hoạt động này?")) return;

  try {
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);

    const res = await fetch('api/dashboard_banners.php', { method: 'POST', body: fd });
    const result = await res.json();
    if (result.success) {
      alert(result.message);
      loadManagerBannersTable();
      loadCarouselData();
    } else {
      alert("Lỗi: " + result.message);
    }
  } catch (err) {
    console.error("Lỗi xóa banner:", err);
    alert("Không thể xóa banner!");
  }
}

// ---------------------------------------------------------------------------
// 8.8. CẤU HÌNH CAROUSEL SETTINGS
// ---------------------------------------------------------------------------
function loadSettingsIntoForm() {
  document.getElementById('settingAutoplay').checked = !!carouselSettings.autoplay;
  document.getElementById('settingIntervalSeconds').value = Math.round((carouselSettings.interval || 5000) / 1000);
  document.getElementById('settingImageAutoplay').checked = carouselSettings.image_autoplay !== false;
  document.getElementById('settingImageIntervalSeconds').value = Math.round((carouselSettings.image_interval || 3000) / 1000);
  document.getElementById('settingShowSubControls').checked = carouselSettings.show_sub_controls !== false;
  document.getElementById('settingPauseOnHover').checked = !!carouselSettings.pause_on_hover;
  document.getElementById('settingMaxItems').value = carouselSettings.max_items || 10;
  document.getElementById('settingShowIndicators').checked = carouselSettings.show_indicators !== false;
  document.getElementById('settingShowArrows').checked = carouselSettings.show_arrows !== false;
  document.getElementById('settingShowBadges').checked = carouselSettings.show_badges !== false;
  document.getElementById('settingAnimationEffect').value = carouselSettings.animation_effect || 'slide';
}

async function handleSaveCarouselSettings(e) {
  e.preventDefault();
  if (!hasPermission(['BannerActivity.ConfigCarousel', 'dashboard.edit'])) {
    alert("Bạn không có quyền cấu hình Carousel! (Yêu cầu BannerActivity.ConfigCarousel)");
    return;
  }

  const intervalSec = parseFloat(document.getElementById('settingIntervalSeconds').value) || 5;
  const imageIntervalSec = parseFloat(document.getElementById('settingImageIntervalSeconds').value) || 3;

  const payload = {
    autoplay: document.getElementById('settingAutoplay').checked,
    interval: Math.max(2000, Math.round(intervalSec * 1000)),
    image_autoplay: document.getElementById('settingImageAutoplay').checked,
    image_interval: Math.max(1000, Math.round(imageIntervalSec * 1000)),
    show_sub_controls: document.getElementById('settingShowSubControls').checked,
    pause_on_hover: document.getElementById('settingPauseOnHover').checked,
    max_items: parseInt(document.getElementById('settingMaxItems').value) || 10,
    show_indicators: document.getElementById('settingShowIndicators').checked,
    show_arrows: document.getElementById('settingShowArrows').checked,
    show_badges: document.getElementById('settingShowBadges').checked,
    animation_effect: document.getElementById('settingAnimationEffect').value
  };

  try {
    const res = await fetch('api/dashboard_banners.php?action=save_settings', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const result = await res.json();

    if (result.success) {
      alert("Cập nhật cài đặt Carousel thành công!");
      carouselSettings = Object.assign(carouselSettings, result.data || payload);
      renderCarouselSlides();
      switchToListTab();
    } else {
      alert("Lỗi: " + result.message);
    }
  } catch (err) {
    console.error("Lỗi lưu cấu hình Carousel:", err);
    alert("Không thể lưu cấu hình Carousel!");
  }
}

// ---------------------------------------------------------------------------
// 8.9. TIỆN ÍCH HỖ TRỢ (HELPERS)
// ---------------------------------------------------------------------------
function getCategoryBadgeClass(category) {
  switch (category) {
    case '5S': return 'badge-5s';
    case 'An toàn': return 'badge-kaizen';
    case 'Cải tiến': return 'badge-cai-tien';
    case 'Tuyên dương': return 'badge-tuyen-duong';
    case 'Giải thưởng': return 'badge-giai-thuong';
    case 'Hoạt động sản xuất': return 'badge-san-xuat';
    default: return 'badge-khac';
  }
}

function getCategoryIcon(category) {
  switch (category) {
    case '5S': return '✨';
    case 'An toàn': return '💡';
    case 'Cải tiến': return '⚡';
    case 'Tuyên dương': return '🏆';
    case 'Giải thưởng': return '🎖️';
    case 'Hoạt động sản xuất': return '⚙️';
    default: return '📌';
  }
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

// Giữ lại chức năng xem PDF mục tiêu năm
function openPdfModal(title, filePath) {
  document.getElementById('modalDocTitle').textContent = title;
  document.getElementById('modalPdfViewer').src = filePath + '#toolbar=1';
  
  const modalEl = document.getElementById('targetPdfModal');
  if (!pdfModalInstance) {
    pdfModalInstance = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: true });
  }
  pdfModalInstance.show();
}

function closePdfModal() {
  if (pdfModalInstance) {
    pdfModalInstance.hide();
  }
}

// ===========================================================================
// 9. JAVASCRIPT XỬ LÝ KHU VỰC THÔNG BÁO MỚI NHẤT, ĐA HÌNH ẢNH & TỆP ĐÍNH KÈM
// ===========================================================================

// ---------------------------------------------------------------------------
// 9.1. TẢI VÀ RENDER DANH SÁCH THÔNG BÁO DASHBOARD
// ---------------------------------------------------------------------------
async function loadAnnouncementsData() {
  try {
    const res = await fetch('api/announcements.php?action=list');
    const result = await res.json();
    if (result.success) {
      allAnnouncementsData = result.data || [];
      const stats = result.stats || {};

      const countBadge = document.getElementById('announcementsCountBadge');
      if (countBadge) countBadge.textContent = `${stats.total || 0} thông báo`;

      const unreadBadge = document.getElementById('announcementsUnreadBadge');
      if (unreadBadge) {
        if (stats.unread && stats.unread > 0) {
          unreadBadge.textContent = `${stats.unread} chưa xem`;
          unreadBadge.style.display = 'inline-block';
        } else {
          unreadBadge.style.display = 'none';
        }
      }

      renderAnnouncementsList();
    } else {
      showEmptyAnnouncementsState('Không thể tải thông báo: ' + (result.message || 'Lỗi hệ thống'));
    }
  } catch (err) {
    console.error('Lỗi tải danh sách thông báo:', err);
    showEmptyAnnouncementsState('Lỗi kết nối khi tải danh sách thông báo!');
  }
}

function filterAnnouncementsTab(filterType) {
  currentAnnouncementFilter = filterType;
  const buttons = document.querySelectorAll('#announcementFilterGroup button');
  buttons.forEach(btn => {
    if (btn.getAttribute('data-filter') === filterType) {
      btn.classList.add('active');
    } else {
      btn.classList.remove('active');
    }
  });
  renderAnnouncementsList();
}

function renderAnnouncementsList() {
  const container = document.getElementById('announcementsListContainer');
  if (!container) return;

  let filtered = [...allAnnouncementsData];
  if (currentAnnouncementFilter === 'important') {
    filtered = filtered.filter(item => item.is_important === 1);
  } else if (currentAnnouncementFilter === 'unread') {
    filtered = filtered.filter(item => item.user_viewed === 0);
  }

  if (filtered.length === 0) {
    let emptyMsg = "Hiện không có thông báo nào.";
    if (currentAnnouncementFilter === 'important') emptyMsg = "Không có thông báo quan trọng nào.";
    if (currentAnnouncementFilter === 'unread') emptyMsg = "Bạn đã đọc hết tất cả thông báo!";
    showEmptyAnnouncementsState(emptyMsg);
    return;
  }

  let html = '';
  filtered.forEach(item => {
    const isImportant = item.is_important === 1;
    const priorityHtml = getPriorityBadgeHtml(item.priority);
    const readStatusHtml = item.user_viewed === 1
      ? `<span class="read-status-pill read"><span class="material-icons fs-6 text-success" style="vertical-align: -3px;">done_all</span> Đã xem</span>`
      : `<span class="read-status-pill unread"><span class="dot"></span> Chưa xem</span>`;

    // Huy hiệu ảnh & tệp đính kèm
    const imgBadgeHtml = (item.images_count && item.images_count > 0)
      ? `<span class="badge bg-light text-secondary border fs-xs d-inline-flex align-items-center gap-1" title="${item.images_count} hình ảnh đính kèm"><span class="material-icons" style="font-size: 13px;">photo_library</span> ${item.images_count} ảnh</span>`
      : '';

    const attachBadgeHtml = (item.attachments_count && item.attachments_count > 0)
      ? `<span class="badge bg-light text-primary border fs-xs d-inline-flex align-items-center gap-1" title="${item.attachments_count} tài liệu đính kèm"><span class="material-icons" style="font-size: 13px;">attach_file</span> ${item.attachments_count} tệp</span>`
      : '';

    const canViewStats = hasPermission(['Announcement.ViewStatistics']);
    const statsBtnHtml = canViewStats ? `
      <button type="button" class="btn btn-outline-secondary btn-sm p-1 px-2 fs-xs d-inline-flex align-items-center gap-1" title="Xem danh sách người đã đọc" onclick="openAnnouncementViewersModal(${item.id})">
        <span class="material-icons" style="font-size: 15px;">groups</span>
        <span>Người xem</span>
      </button>
    ` : '';

    html += `
      <div class="col-md-6 col-lg-4 dx-announcement-card-col">
        <div class="dx-announcement-item ${isImportant ? 'is-important' : ''}">
          <div>
            <!-- Top bar: Priority, Important & Read status -->
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
              <div class="d-flex align-items-center gap-1 flex-wrap">
                ${isImportant ? `<span class="badge bg-danger text-white fs-xs px-2 py-1 shadow-sm d-inline-flex align-items-center gap-1"><span class="material-icons" style="font-size: 13px;">push_pin</span> QUAN TRỌNG</span>` : ''}
                ${priorityHtml}
              </div>
              <div>
                ${readStatusHtml}
              </div>
            </div>

            <!-- Title -->
            <h6 class="ann-item-title" onclick="openAnnouncementDetail(${item.id})">
              ${escapeHtml(item.title)}
            </h6>

            <!-- Summary -->
            <p class="ann-item-summary">
              ${escapeHtml(item.summary || '')}
            </p>

            <!-- Attachments & Images Badges nếu có -->
            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
              ${imgBadgeHtml}
              ${attachBadgeHtml}
            </div>
          </div>

          <!-- Bottom bar: Creator, Date, Views & Action -->
          <div>
            <div class="ann-meta-bar mb-2">
              <span class="ann-meta-item" title="Người đăng & Ngày hiệu lực">
                <span class="material-icons">event</span>
                ${escapeHtml(item.valid_from_formatted)}
                <span class="text-muted ms-1">(${escapeHtml(item.created_by || 'Admin')})</span>
              </span>
              <span class="ann-meta-item" title="Số người xem & tổng lượt đọc">
                <span class="material-icons">visibility</span>
                ${item.unique_viewers_count} người (${item.total_views} lượt)
              </span>
            </div>

            <div class="d-flex align-items-center justify-content-between gap-2 pt-1">
              <button type="button" class="app-btn app-btn-primary app-btn-sm d-flex align-items-center gap-1 w-100 justify-content-center" onclick="openAnnouncementDetail(${item.id})">
                <span class="material-icons" style="font-size: 16px;">visibility</span>
                <span>Xem Chi Tiết</span>
              </button>
              ${statsBtnHtml}
            </div>
          </div>
        </div>
      </div>
    `;
  });

  container.innerHTML = html;
}

function showEmptyAnnouncementsState(msg) {
  const container = document.getElementById('announcementsListContainer');
  if (container) {
    container.innerHTML = `
      <div class="col-12 text-center py-5 text-muted">
        <span class="material-icons fs-1 text-secondary opacity-50 mb-2">campaign</span>
        <div class="fs-sm fw-medium">${escapeHtml(msg)}</div>
      </div>
    `;
  }
}

function getPriorityBadgeHtml(priority) {
  switch (priority) {
    case 'urgent':
      return `<span class="badge priority-badge-urgent px-2 py-1 rounded-1">Khẩn cấp</span>`;
    case 'high':
      return `<span class="badge priority-badge-high px-2 py-1 rounded-1">Ưu tiên cao</span>`;
    case 'normal':
    default:
      return `<span class="badge priority-badge-normal px-2 py-1 rounded-1">Bình thường</span>`;
  }
}

function getFileIconInfo(ext) {
  const lower = (ext || '').toLowerCase().replace('.', '');
  switch (lower) {
    case 'pdf':
      return { icon: 'picture_as_pdf', cls: 'pdf', label: 'PDF' };
    case 'doc':
    case 'docx':
      return { icon: 'description', cls: 'doc', label: 'Word' };
    case 'xls':
    case 'xlsx':
    case 'csv':
      return { icon: 'table_chart', cls: 'xls', label: 'Excel' };
    case 'ppt':
    case 'pptx':
      return { icon: 'slideshow', cls: 'ppt', label: 'PowerPoint' };
    default:
      return { icon: 'insert_drive_file', cls: 'file', label: 'Tài liệu' };
  }
}

// ---------------------------------------------------------------------------
// 9.2. CHI TIẾT THÔNG BÁO, GALLERY ẢNH & TÀI LIỆU ĐÍNH KÈM
// ---------------------------------------------------------------------------
async function openAnnouncementDetail(id) {
  currentDetailAnnouncementId = id;
  const modalEl = document.getElementById('announcementDetailModal');
  if (!announcementDetailModalInstance) {
    announcementDetailModalInstance = new bootstrap.Modal(modalEl);
  }

  announcementDetailModalInstance.show();

  // Reset modal state
  document.getElementById('modalAnnTitle').textContent = 'Đang tải thông báo...';
  document.getElementById('modalAnnContent').innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>';
  document.getElementById('modalAnnSummaryWrapper').style.display = 'none';
  document.getElementById('modalAnnGalleryWrapper').style.display = 'none';
  document.getElementById('modalAnnAttachmentsWrapper').style.display = 'none';

  try {
    const res = await fetch(`api/announcements.php?action=get_detail&id=${id}`);
    const result = await res.json();

    if (result.success && result.data) {
      const ann = result.data;

      document.getElementById('modalAnnTitle').textContent = ann.title;
      document.getElementById('modalAnnCreatedBy').textContent = ann.created_by || 'Admin';
      document.getElementById('modalAnnValidDate').textContent = ann.valid_from_formatted || '';
      document.getElementById('modalAnnValidRange').textContent = `${ann.valid_from_formatted} - ${ann.valid_to_formatted || 'Vô thời hạn'}`;

      const impBadge = document.getElementById('modalAnnImportantBadge');
      if (impBadge) {
        if (ann.is_important === 1) impBadge.classList.remove('d-none');
        else impBadge.classList.add('d-none');
      }

      const pBadge = document.getElementById('modalAnnPriorityBadge');
      if (pBadge) {
        pBadge.className = 'badge';
        if (ann.priority === 'urgent') {
          pBadge.classList.add('priority-badge-urgent');
          pBadge.textContent = 'Khẩn cấp';
        } else if (ann.priority === 'high') {
          pBadge.classList.add('priority-badge-high');
          pBadge.textContent = 'Ưu tiên cao';
        } else {
          pBadge.classList.add('priority-badge-normal');
          pBadge.textContent = 'Bình thường';
        }
      }

      const statsBadge = document.getElementById('modalAnnStatsBadge');
      if (statsBadge) {
        statsBadge.innerHTML = `<span class="material-icons fs-6" style="vertical-align: -3px;">visibility</span> ${ann.unique_viewers_count} người xem (${ann.total_views} lượt)`;
      }

      // Tóm tắt
      const sumWrap = document.getElementById('modalAnnSummaryWrapper');
      if (ann.summary && ann.summary.trim() !== '') {
        document.getElementById('modalAnnSummary').textContent = ann.summary;
        sumWrap.style.display = 'block';
      } else {
        sumWrap.style.display = 'none';
      }

      // Xử lý Gallery hình ảnh đính kèm
      currentDetailAnnImages = ann.images_list || [];
      currentDetailAnnImageIndex = 0;
      const galleryWrap = document.getElementById('modalAnnGalleryWrapper');
      if (currentDetailAnnImages.length > 0) {
        galleryWrap.style.display = 'block';
        document.getElementById('modalAnnGalleryCount').textContent = currentDetailAnnImages.length;
        renderDetailGalleryImages();
      } else {
        galleryWrap.style.display = 'none';
      }

      // Nội dung HTML chi tiết
      document.getElementById('modalAnnContent').innerHTML = ann.content || '<em class="text-muted">Không có nội dung chi tiết</em>';

      // Xử lý Danh sách Tài liệu đính kèm
      const attachWrap = document.getElementById('modalAnnAttachmentsWrapper');
      const attachList = document.getElementById('modalAnnAttachmentsList');
      const attaches = ann.attachments_list || [];
      if (attaches.length > 0) {
        attachWrap.style.display = 'block';
        document.getElementById('modalAnnAttachmentsCount').textContent = attaches.length;

        let attachHtml = '';
        attaches.forEach(file => {
          const iconInfo = getFileIconInfo(file.ext);
          const downloadUrl = `api/announcements.php?action=download_attachment&file_url=${encodeURIComponent(file.url)}&name=${encodeURIComponent(file.name)}`;

          attachHtml += `
            <div class="col-md-6">
              <div class="ann-attachment-card">
                <div class="d-flex align-items-center gap-3 overflow-hidden me-2">
                  <div class="ann-file-icon ${iconInfo.cls}">
                    <span class="material-icons">${iconInfo.icon}</span>
                  </div>
                  <div class="overflow-hidden">
                    <div class="fw-semibold text-dark text-truncate fs-sm" title="${escapeHtml(file.name)}">
                      ${escapeHtml(file.name)}
                    </div>
                    <div class="fs-xs text-muted">
                      ${file.size_formatted || ''} <span class="badge bg-light text-secondary border ms-1">${iconInfo.label}</span>
                    </div>
                  </div>
                </div>
                <a href="${downloadUrl}" class="app-btn app-btn-secondary app-btn-sm d-flex align-items-center gap-1 flex-shrink-0" title="Tải xuống tệp">
                  <span class="material-icons" style="font-size: 16px;">download</span>
                  <span>Tải Về</span>
                </a>
              </div>
            </div>
          `;
        });
        attachList.innerHTML = attachHtml;
      } else {
        attachWrap.style.display = 'none';
        attachList.innerHTML = '';
      }

      // Nút xem thống kê người đọc
      const btnStats = document.getElementById('btnViewStatsFromDetail');
      if (btnStats) {
        const canViewStats = hasPermission(['Announcement.ViewStatistics']);
        if (canViewStats) {
          btnStats.classList.remove('d-none');
          btnStats.classList.add('d-inline-flex');
        } else {
          btnStats.classList.add('d-none');
        }
      }

      // Cập nhật trạng thái local
      const localItem = allAnnouncementsData.find(i => i.id === id);
      if (localItem) {
        if (localItem.user_viewed === 0) {
          localItem.user_viewed = 1;
          const unreadBadge = document.getElementById('announcementsUnreadBadge');
          if (unreadBadge) {
            const currentUnread = parseInt(unreadBadge.textContent) || 0;
            if (currentUnread > 1) {
              unreadBadge.textContent = `${currentUnread - 1} chưa xem`;
            } else {
              unreadBadge.style.display = 'none';
            }
          }
        }
        localItem.total_views = ann.total_views;
        localItem.unique_viewers_count = ann.unique_viewers_count;
        renderAnnouncementsList();
      }
    } else {
      document.getElementById('modalAnnContent').innerHTML = `<div class="alert alert-danger">${escapeHtml(result.message || 'Không thể tải chi tiết thông báo')}</div>`;
    }
  } catch (err) {
    console.error('Lỗi tải chi tiết thông báo:', err);
    document.getElementById('modalAnnContent').innerHTML = `<div class="alert alert-danger">Lỗi kết nối khi tải chi tiết thông báo!</div>`;
  }
}

function renderDetailGalleryImages() {
  if (currentDetailAnnImages.length === 0) return;

  const currentImg = currentDetailAnnImages[currentDetailAnnImageIndex];
  const mainImgEl = document.getElementById('modalAnnMainImage');
  if (mainImgEl && currentImg) {
    mainImgEl.src = currentImg.url;
    mainImgEl.alt = currentImg.name || 'Ảnh thông báo';
  }

  // Điều khiển Prev/Next và Counter
  const btnPrev = document.getElementById('btnAnnGalleryPrev');
  const btnNext = document.getElementById('btnAnnGalleryNext');
  const counterEl = document.getElementById('modalAnnGalleryCounter');

  if (currentDetailAnnImages.length > 1) {
    if (btnPrev) btnPrev.style.display = 'flex';
    if (btnNext) btnNext.style.display = 'flex';
    if (counterEl) {
      counterEl.style.display = 'block';
      counterEl.textContent = `${currentDetailAnnImageIndex + 1} / ${currentDetailAnnImages.length}`;
    }
  } else {
    if (btnPrev) btnPrev.style.display = 'none';
    if (btnNext) btnNext.style.display = 'none';
    if (counterEl) counterEl.style.display = 'none';
  }

  // Render Thumbnails
  const thumbsTrack = document.getElementById('modalAnnGalleryThumbs');
  if (thumbsTrack) {
    let thumbsHtml = '';
    currentDetailAnnImages.forEach((img, idx) => {
      const activeCls = (idx === currentDetailAnnImageIndex) ? 'active' : '';
      const imgSrc = img.thumb || img.url;
      thumbsHtml += `
        <img src="${escapeHtml(imgSrc)}" class="ann-gallery-thumb-item ${activeCls}" alt="Thumb" onclick="setAnnGalleryImage(${idx})">
      `;
    });
    thumbsTrack.innerHTML = thumbsHtml;
  }
}

function navigateAnnGallery(direction) {
  if (currentDetailAnnImages.length <= 1) return;
  currentDetailAnnImageIndex += direction;
  if (currentDetailAnnImageIndex < 0) {
    currentDetailAnnImageIndex = currentDetailAnnImages.length - 1;
  } else if (currentDetailAnnImageIndex >= currentDetailAnnImages.length) {
    currentDetailAnnImageIndex = 0;
  }
  renderDetailGalleryImages();
}

function setAnnGalleryImage(index) {
  if (index >= 0 && index < currentDetailAnnImages.length) {
    currentDetailAnnImageIndex = index;
    renderDetailGalleryImages();
  }
}

function openAnnCurrentLightbox() {
  if (currentDetailAnnImages.length > 0) {
    openAnnLightbox(currentDetailAnnImages, currentDetailAnnImageIndex);
  }
}

function openAnnLightbox(imagesList, startIndex = 0) {
  currentAnnLightboxImages = imagesList || [];
  currentAnnLightboxIndex = startIndex;
  if (currentAnnLightboxImages.length === 0) return;

  const modalEl = document.getElementById('announcementImageLightboxModal');
  if (!announcementImageLightboxModalInstance) {
    announcementImageLightboxModalInstance = new bootstrap.Modal(modalEl);
  }

  renderAnnLightboxContent();
  announcementImageLightboxModalInstance.show();
}

function renderAnnLightboxContent() {
  if (currentAnnLightboxImages.length === 0) return;
  const cur = currentAnnLightboxImages[currentAnnLightboxIndex];
  const imgEl = document.getElementById('annLightboxImage');
  const counterEl = document.getElementById('annLightboxCounter');
  const prevBtn = document.getElementById('btnAnnLbPrev');
  const nextBtn = document.getElementById('btnAnnLbNext');

  if (imgEl && cur) {
    imgEl.src = cur.url;
  }
  if (counterEl) {
    counterEl.textContent = `${currentAnnLightboxIndex + 1} / ${currentAnnLightboxImages.length}`;
  }

  const showNav = currentAnnLightboxImages.length > 1;
  if (prevBtn) prevBtn.style.display = showNav ? 'flex' : 'none';
  if (nextBtn) nextBtn.style.display = showNav ? 'flex' : 'none';
}

function navigateAnnLightbox(direction) {
  if (currentAnnLightboxImages.length <= 1) return;
  currentAnnLightboxIndex += direction;
  if (currentAnnLightboxIndex < 0) {
    currentAnnLightboxIndex = currentAnnLightboxImages.length - 1;
  } else if (currentAnnLightboxIndex >= currentAnnLightboxImages.length) {
    currentAnnLightboxIndex = 0;
  }
  renderAnnLightboxContent();
}

function openStatsFromDetailModal() {
  if (currentDetailAnnouncementId > 0) {
    if (announcementDetailModalInstance) {
      announcementDetailModalInstance.hide();
    }
    setTimeout(() => {
      openAnnouncementViewersModal(currentDetailAnnouncementId);
    }, 300);
  }
}

// ---------------------------------------------------------------------------
// 9.3. QUẢN LÝ THÔNG BÁO CHO ADMIN (FORM, UPLOAD NHIỀU ẢNH, NHIỀU FILE, REORDER)
// ---------------------------------------------------------------------------
function openAnnouncementManagerModal() {
  const modalEl = document.getElementById('announcementManagerModal');
  if (!announcementManagerModalInstance) {
    announcementManagerModalInstance = new bootstrap.Modal(modalEl);
  }
  switchToAnnListTab();
  announcementManagerModalInstance.show();
  loadAnnouncementAdminList();
}

function switchToAnnListTab() {
  const listBtn = document.getElementById('tab-ann-list-btn');
  if (listBtn) {
    bootstrap.Tab.getOrCreateInstance(listBtn).show();
  }
}

function switchToNewAnnTab() {
  resetAnnouncementForm();
  document.getElementById('annFormTabTitle').textContent = 'Thêm Thông Báo Mới';
  document.getElementById('annFormTabIcon').textContent = 'add_circle_outline';
  const formBtn = document.getElementById('tab-ann-form-btn');
  if (formBtn) {
    bootstrap.Tab.getOrCreateInstance(formBtn).show();
  }
}

function resetAnnouncementForm() {
  document.getElementById('annFormId').value = '0';
  document.getElementById('annFormTitle').value = '';
  document.getElementById('annFormPriority').value = 'normal';
  document.getElementById('annFormStatus').value = 'active';
  document.getElementById('annFormIsImportant').checked = false;
  document.getElementById('annFormSummary').value = '';
  document.getElementById('annFormContent').value = '';

  const today = new Date().toISOString().split('T')[0];
  document.getElementById('annFormValidFrom').value = today;
  document.getElementById('annFormValidTo').value = '';

  // Reset mảng ảnh và file đính kèm
  stagedAnnImages = [];
  renderStagedAnnImages();

  stagedAnnAttachments = [];
  renderStagedAnnAttachments();
}

// Xử lý upload ảnh từ máy tính
async function handleAnnImagesSelect(event) {
  const files = event.target.files;
  if (!files || files.length === 0) return;

  const formData = new FormData();
  formData.append('action', 'upload_images');
  for (let i = 0; i < files.length; i++) {
    formData.append('images[]', files[i]);
  }

  try {
    const res = await fetch('api/announcements.php', {
      method: 'POST',
      body: formData
    });
    const result = await res.json();

    if (result.success && result.data) {
      result.data.forEach(img => {
        img.order = stagedAnnImages.length;
        stagedAnnImages.push(img);
      });
      renderStagedAnnImages();
    } else {
      alert('Lỗi tải ảnh: ' + (result.message || 'Không thể tải ảnh'));
    }
  } catch (err) {
    console.error('Lỗi upload ảnh:', err);
    alert('Không thể kết nối máy chủ để tải ảnh lên!');
  } finally {
    event.target.value = '';
  }
}

function renderStagedAnnImages() {
  const container = document.getElementById('annFormImagesPreview');
  const notice = document.getElementById('annFormNoImagesNotice');
  if (!container) return;

  if (stagedAnnImages.length === 0) {
    container.innerHTML = '<div class="text-muted fs-xs fst-italic p-2 w-100" id="annFormNoImagesNotice">Chưa có hình ảnh nào được đính kèm.</div>';
    return;
  }

  let html = '';
  stagedAnnImages.forEach((img, idx) => {
    const isCover = idx === 0;
    const canMoveLeft = idx > 0;
    const canMoveRight = idx < stagedAnnImages.length - 1;

    html += `
      <div class="staged-img-card" title="${escapeHtml(img.name || '')}">
        <img src="${escapeHtml(img.thumb || img.url)}" alt="Thumb">
        ${isCover ? '<span class="cover-badge">Bìa</span>' : ''}
        <button type="button" class="btn-remove-img" onclick="removeStagedAnnImage(${idx})" title="Xóa ảnh">&times;</button>
        <div class="img-controls">
          <button type="button" ${canMoveLeft ? `onclick="moveStagedAnnImage(${idx}, -1)" title="Chuyển sang trái"` : 'style="opacity:0.3; pointer-events:none;"'}>
            <span class="material-icons" style="font-size: 16px;">chevron_left</span>
          </button>
          <span class="text-white fs-xs">${idx + 1}</span>
          <button type="button" ${canMoveRight ? `onclick="moveStagedAnnImage(${idx}, 1)" title="Chuyển sang phải"` : 'style="opacity:0.3; pointer-events:none;"'}>
            <span class="material-icons" style="font-size: 16px;">chevron_right</span>
          </button>
        </div>
      </div>
    `;
  });

  container.innerHTML = html;
}

function moveStagedAnnImage(index, direction) {
  const targetIndex = index + direction;
  if (targetIndex < 0 || targetIndex >= stagedAnnImages.length) return;

  const temp = stagedAnnImages[index];
  stagedAnnImages[index] = stagedAnnImages[targetIndex];
  stagedAnnImages[targetIndex] = temp;

  stagedAnnImages.forEach((img, idx) => { img.order = idx; });
  renderStagedAnnImages();
}

function removeStagedAnnImage(index) {
  if (index >= 0 && index < stagedAnnImages.length) {
    stagedAnnImages.splice(index, 1);
    stagedAnnImages.forEach((img, idx) => { img.order = idx; });
    renderStagedAnnImages();
  }
}

// Xử lý upload tài liệu đính kèm
async function handleAnnAttachmentsSelect(event) {
  const files = event.target.files;
  if (!files || files.length === 0) return;

  const formData = new FormData();
  formData.append('action', 'upload_attachments');
  for (let i = 0; i < files.length; i++) {
    formData.append('attachments[]', files[i]);
  }

  try {
    const res = await fetch('api/announcements.php', {
      method: 'POST',
      body: formData
    });
    const result = await res.json();

    if (result.success && result.data) {
      result.data.forEach(f => {
        stagedAnnAttachments.push(f);
      });
      renderStagedAnnAttachments();
    } else {
      alert('Lỗi tải tài liệu: ' + (result.message || 'Không thể tải file'));
    }
  } catch (err) {
    console.error('Lỗi upload file:', err);
    alert('Không thể kết nối máy chủ để tải tài liệu lên!');
  } finally {
    event.target.value = '';
  }
}

function renderStagedAnnAttachments() {
  const container = document.getElementById('annFormAttachmentsList');
  if (!container) return;

  if (stagedAnnAttachments.length === 0) {
    container.innerHTML = '<div class="text-muted fs-xs fst-italic p-2" id="annFormNoAttachmentsNotice">Chưa có tài liệu nào được đính kèm.</div>';
    return;
  }

  let html = '';
  stagedAnnAttachments.forEach((file, idx) => {
    const iconInfo = getFileIconInfo(file.ext);
    html += `
      <div class="d-flex align-items-center justify-content-between p-2 bg-white rounded border">
        <div class="d-flex align-items-center gap-2 overflow-hidden me-2">
          <div class="ann-file-icon ${iconInfo.cls}" style="width: 28px; height: 28px;">
            <span class="material-icons" style="font-size: 16px;">${iconInfo.icon}</span>
          </div>
          <div class="overflow-hidden">
            <span class="fw-semibold text-dark fs-sm text-truncate d-block" style="max-width: 320px;" title="${escapeHtml(file.name)}">
              ${escapeHtml(file.name)}
            </span>
            <span class="fs-xs text-muted">${file.size_formatted || ''} (${iconInfo.label})</span>
          </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger p-1 d-flex align-items-center" onclick="removeStagedAnnAttachment(${idx})" title="Xóa tài liệu này">
          <span class="material-icons" style="font-size: 16px;">delete</span>
        </button>
      </div>
    `;
  });

  container.innerHTML = html;
}

function removeStagedAnnAttachment(index) {
  if (index >= 0 && index < stagedAnnAttachments.length) {
    stagedAnnAttachments.splice(index, 1);
    renderStagedAnnAttachments();
  }
}

async function loadAnnouncementAdminList() {
  const tbody = document.getElementById('tblAnnouncementsAdminBody');
  if (!tbody) return;

  tbody.innerHTML = `
    <tr>
      <td colspan="8" class="text-center py-4 text-muted">
        <div class="spinner-border spinner-border-sm text-primary me-2"></div> Đang tải danh sách thông báo...
      </td>
    </tr>
  `;

  const keyword = document.getElementById('annAdminSearch')?.value.trim() || '';
  const priority = document.getElementById('annAdminFilterPriority')?.value || '';
  const status = document.getElementById('annAdminFilterStatus')?.value || '';

  const query = new URLSearchParams({
    action: 'admin_list',
    keyword: keyword,
    priority: priority,
    status: status
  });

  try {
    const res = await fetch(`api/announcements.php?${query.toString()}`);
    const result = await res.json();

    if (result.success) {
      const list = result.data || [];
      if (list.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted">Không tìm thấy thông báo nào.</td></tr>`;
        return;
      }

      let html = '';
      list.forEach((item, idx) => {
        const isImportant = item.is_important === 1;
        const isActive = item.status === 'active';
        const priorityHtml = getPriorityBadgeHtml(item.priority);
        const canEdit = hasPermission(['Announcement.Edit']);
        const canPublish = hasPermission(['Announcement.Publish']);
        const canDelete = hasPermission(['Announcement.Delete']);
        const canStats = hasPermission(['Announcement.ViewStatistics']);

        const attachBadge = (item.attachments_count && item.attachments_count > 0)
          ? `<span class="badge bg-light text-primary border fs-xs ms-1">📎 ${item.attachments_count}</span>`
          : '';
        const imgBadge = (item.images_count && item.images_count > 0)
          ? `<span class="badge bg-light text-secondary border fs-xs ms-1">📷 ${item.images_count}</span>`
          : '';

        html += `
          <tr>
            <td class="text-center text-muted fw-bold">${idx + 1}</td>
            <td>
              <div class="fw-bold text-dark d-flex align-items-center flex-wrap gap-1">
                <span>${escapeHtml(item.title)}</span>
                ${imgBadge}
                ${attachBadge}
              </div>
              <div class="text-muted fs-xs text-truncate" style="max-width: 300px;">${escapeHtml(item.summary || '')}</div>
            </td>
            <td class="text-center">${priorityHtml}</td>
            <td class="text-center">
              <div class="form-check form-switch d-inline-block">
                <input class="form-check-input" type="checkbox" role="switch" ${isImportant ? 'checked' : ''} 
                  ${canEdit ? '' : 'disabled'} 
                  onchange="toggleAnnouncementImportant(${item.id})">
              </div>
            </td>
            <td>
              <div class="fs-xs text-dark"><span class="text-muted">Từ:</span> ${item.valid_from_formatted}</div>
              <div class="fs-xs text-muted"><span class="text-muted">Đến:</span> ${item.valid_to_formatted || 'Vô thời hạn'}</div>
            </td>
            <td class="text-center">
              <div class="form-check form-switch d-inline-block">
                <input class="form-check-input" type="checkbox" role="switch" ${isActive ? 'checked' : ''} 
                  ${canPublish ? '' : 'disabled'} 
                  onchange="toggleAnnouncementStatus(${item.id})">
              </div>
              <div class="fs-xs ${isActive ? 'text-success' : 'text-secondary'}">${isActive ? 'Hiển thị' : 'Đang ẩn'}</div>
            </td>
            <td class="text-center">
              <span class="badge bg-light text-dark border">
                👁️ ${item.unique_viewers_count} người (${item.total_views} lượt)
              </span>
            </td>
            <td class="text-center">
              <div class="d-flex align-items-center justify-content-center gap-1">
                ${canStats ? `
                  <button type="button" class="btn btn-sm btn-outline-info p-1" title="Xem người đã đọc" onclick="openAnnouncementViewersModal(${item.id})">
                    <span class="material-icons fs-6">groups</span>
                  </button>
                ` : ''}
                ${canEdit ? `
                  <button type="button" class="btn btn-sm btn-outline-primary p-1" title="Chỉnh sửa thông báo" onclick="editAnnouncement(${item.id})">
                    <span class="material-icons fs-6">edit</span>
                  </button>
                ` : ''}
                ${canDelete ? `
                  <button type="button" class="btn btn-sm btn-outline-danger p-1" title="Xóa thông báo" onclick="deleteAnnouncement(${item.id})">
                    <span class="material-icons fs-6">delete</span>
                  </button>
                ` : ''}
              </div>
            </td>
          </tr>
        `;
      });
      tbody.innerHTML = html;
    } else {
      tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">${escapeHtml(result.message || 'Lỗi nạp danh sách')}</td></tr>`;
    }
  } catch (err) {
    console.error('Lỗi tải danh sách quản trị thông báo:', err);
    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">Lỗi kết nối máy chủ!</td></tr>`;
  }
}

function debounceAnnAdminSearch() {
  clearTimeout(debounceAnnSearchTimer);
  debounceAnnSearchTimer = setTimeout(() => {
    loadAnnouncementAdminList();
  }, 350);
}

async function handleSaveAnnouncementForm(e) {
  e.preventDefault();
  const id = parseInt(document.getElementById('annFormId').value) || 0;
  const title = document.getElementById('annFormTitle').value.trim();
  const priority = document.getElementById('annFormPriority').value;
  const validFrom = document.getElementById('annFormValidFrom').value;
  const validTo = document.getElementById('annFormValidTo').value;
  const status = document.getElementById('annFormStatus').value;
  const isImportant = document.getElementById('annFormIsImportant').checked ? 1 : 0;
  const summary = document.getElementById('annFormSummary').value.trim();
  const content = document.getElementById('annFormContent').value.trim();

  if (!title) {
    alert('Vui lòng nhập tiêu đề thông báo!');
    return;
  }
  if (!validFrom) {
    alert('Vui lòng chọn ngày bắt đầu hiệu lực!');
    return;
  }
  if (validTo && validTo < validFrom) {
    alert('Ngày kết thúc hiệu lực không được nhỏ hơn ngày bắt đầu!');
    return;
  }
  if (!content) {
    alert('Vui lòng nhập nội dung chi tiết!');
    return;
  }

  const payload = {
    action: id > 0 ? 'update' : 'create',
    id: id,
    title: title,
    priority: priority,
    valid_from: validFrom,
    valid_to: validTo,
    status: status,
    is_important: isImportant,
    summary: summary,
    content: content,
    images: stagedAnnImages,
    attachments: stagedAnnAttachments
  };

  const btnSubmit = document.getElementById('btnSubmitAnnForm');
  if (btnSubmit) btnSubmit.disabled = true;

  try {
    const res = await fetch('api/announcements.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const result = await res.json();

    if (result.success) {
      alert(result.message || 'Lưu thông báo thành công!');
      switchToAnnListTab();
      loadAnnouncementAdminList();
      loadAnnouncementsData();
    } else {
      alert('Lỗi: ' + (result.message || 'Không thể lưu thông báo'));
    }
  } catch (err) {
    console.error('Lỗi lưu thông báo:', err);
    alert('Lỗi kết nối máy chủ khi lưu thông báo!');
  } finally {
    if (btnSubmit) btnSubmit.disabled = false;
  }
}

async function editAnnouncement(id) {
  try {
    const res = await fetch(`api/announcements.php?action=get_detail&id=${id}`);
    const result = await res.json();

    if (result.success && result.data) {
      const ann = result.data;
      document.getElementById('annFormId').value = ann.id;
      document.getElementById('annFormTitle').value = ann.title || '';
      document.getElementById('annFormPriority').value = ann.priority || 'normal';
      document.getElementById('annFormStatus').value = ann.status || 'active';
      document.getElementById('annFormIsImportant').checked = ann.is_important === 1;
      document.getElementById('annFormValidFrom').value = ann.valid_from || '';
      document.getElementById('annFormValidTo').value = ann.valid_to || '';
      document.getElementById('annFormSummary').value = ann.summary || '';
      document.getElementById('annFormContent').value = ann.content || '';

      // Nạp danh sách hình ảnh đã có
      stagedAnnImages = (ann.images_list || []).map((img, idx) => ({ ...img, order: idx }));
      renderStagedAnnImages();

      // Nạp danh sách tài liệu đính kèm đã có
      stagedAnnAttachments = ann.attachments_list || [];
      renderStagedAnnAttachments();

      document.getElementById('annFormTabTitle').textContent = 'Chỉnh Sửa Thông Báo';
      document.getElementById('annFormTabIcon').textContent = 'edit';

      const formBtn = document.getElementById('tab-ann-form-btn');
      if (formBtn) {
        bootstrap.Tab.getOrCreateInstance(formBtn).show();
      }
    } else {
      alert('Lỗi: ' + (result.message || 'Không tìm thấy thông báo'));
    }
  } catch (err) {
    console.error('Lỗi nạp thông tin để sửa:', err);
    alert('Lỗi kết nối khi nạp thông tin thông báo!');
  }
}

async function deleteAnnouncement(id) {
  if (!confirm('Bạn có chắc chắn muốn xóa thông báo này? Hành động này sẽ xóa cả hình ảnh và tài liệu đính kèm liên quan!')) {
    return;
  }

  try {
    const res = await fetch('api/announcements.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'delete', id: id })
    });
    const result = await res.json();

    if (result.success) {
      loadAnnouncementAdminList();
      loadAnnouncementsData();
    } else {
      alert('Lỗi: ' + (result.message || 'Không thể xóa'));
    }
  } catch (err) {
    console.error('Lỗi xóa thông báo:', err);
    alert('Lỗi kết nối khi xóa thông báo!');
  }
}

async function toggleAnnouncementStatus(id) {
  try {
    const res = await fetch('api/announcements.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'toggle_status', id: id })
    });
    const result = await res.json();
    if (result.success) {
      loadAnnouncementAdminList();
      loadAnnouncementsData();
    } else {
      alert('Lỗi: ' + result.message);
      loadAnnouncementAdminList();
    }
  } catch (err) {
    console.error('Lỗi cập nhật trạng thái:', err);
    loadAnnouncementAdminList();
  }
}

async function toggleAnnouncementImportant(id) {
  try {
    const res = await fetch('api/announcements.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'toggle_important', id: id })
    });
    const result = await res.json();
    if (result.success) {
      loadAnnouncementAdminList();
      loadAnnouncementsData();
    } else {
      alert('Lỗi: ' + result.message);
      loadAnnouncementAdminList();
    }
  } catch (err) {
    console.error('Lỗi cập nhật quan trọng:', err);
    loadAnnouncementAdminList();
  }
}

async function openAnnouncementViewersModal(id) {
  const modalEl = document.getElementById('announcementViewersModal');
  if (!announcementViewersModalInstance) {
    announcementViewersModalInstance = new bootstrap.Modal(modalEl);
  }
  announcementViewersModalInstance.show();

  const tbody = document.getElementById('tblViewersListBody');
  tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Đang tải danh sách người xem...</td></tr>`;

  try {
    const res = await fetch(`api/announcements.php?action=viewers_list&id=${id}`);
    const result = await res.json();

    if (result.success && result.data) {
      const ann = result.data.announcement;
      currentAdminViewersData = result.data.viewers || [];

      document.getElementById('viewersModalAnnTitle').textContent = ann.title;
      document.getElementById('viewersModalUniqueCount').innerHTML = `<span class="material-icons fs-6" style="vertical-align: -3px;">person</span> ${ann.unique_viewers} người đã xem`;
      document.getElementById('viewersModalTotalViews').innerHTML = `<span class="material-icons fs-6" style="vertical-align: -3px;">visibility</span> ${ann.sum_view_counts} tổng lượt đọc`;
      document.getElementById('viewersTableSearch').value = '';

      renderViewersTable(currentAdminViewersData);
    } else {
      tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">${escapeHtml(result.message || 'Không thể tải người xem')}</td></tr>`;
    }
  } catch (err) {
    console.error('Lỗi tải danh sách người xem:', err);
    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">Lỗi kết nối máy chủ!</td></tr>`;
  }
}

function renderViewersTable(list) {
  const tbody = document.getElementById('tblViewersListBody');
  if (!tbody) return;

  if (list.length === 0) {
    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">Chưa có người dùng nào xem thông báo này.</td></tr>`;
    return;
  }

  let html = '';
  list.forEach((v, idx) => {
    html += `
      <tr>
        <td class="text-center text-muted fw-bold">${idx + 1}</td>
        <td>
          <div class="fw-semibold text-dark">${escapeHtml(v.fullname || v.username)}</div>
        </td>
        <td>
          <code class="text-primary">${escapeHtml(v.username)}</code>
        </td>
        <td class="text-center">
          <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
            ${v.view_count} lần
          </span>
        </td>
        <td class="fs-xs text-muted">${v.first_viewed_formatted || ''}</td>
        <td class="fs-xs text-dark fw-medium">${v.last_viewed_formatted || ''}</td>
      </tr>
    `;
  });
  tbody.innerHTML = html;
}

function filterViewersTable() {
  const query = document.getElementById('viewersTableSearch')?.value.toLowerCase().trim() || '';
  if (!query) {
    renderViewersTable(currentAdminViewersData);
    return;
  }
  const filtered = currentAdminViewersData.filter(v => {
    const fn = (v.fullname || '').toLowerCase();
    const un = (v.username || '').toLowerCase();
    return fn.includes(query) || un.includes(query);
  });
  renderViewersTable(filtered);
}
</script>