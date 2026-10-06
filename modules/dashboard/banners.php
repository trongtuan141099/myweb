<?php
/**
 * Màn hình quản trị riêng: Dashboard → Quản Lý Banner Hoạt Động
 * DX Plastic Group - Industrial Enterprise Design System
 */
?>
<div class="app-page-wrapper">
  <!-- TIÊU ĐỀ TRANG -->
  <div class="app-page-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
      <h1 class="app-page-title">
        <span class="material-icons">view_carousel</span>
        Quản Lý Banner Hoạt Động
      </h1>
      <p class="app-page-subtitle">
        Truyền thông nội bộ: Hoạt động 5S, đề xuất Kaizen, tuyên dương cá nhân & giải thưởng nhà máy
      </p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="index.php?mainpage=dashboard&subpage=overview" class="app-btn app-btn-secondary app-btn-sm d-flex align-items-center gap-1">
        <span class="material-icons fs-6">arrow_back</span>
        <span>Quay Về Tổng Quan</span>
      </a>
    </div>
  </div>

  <!-- NỘI DUNG CHÍNH: KHUNG QUẢN TRỊ -->
  <div class="app-card border shadow-sm">
    <div class="p-0">
      <!-- Navigation Tabs -->
      <ul class="nav nav-tabs px-3 pt-2 bg-light border-bottom" id="bannerManagerPageTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active d-flex align-items-center gap-1 py-2 px-3" id="page-tab-list-btn" data-bs-toggle="tab" data-bs-target="#page-tab-banner-list" type="button" role="tab">
            <span class="material-icons fs-6">format_list_bulleted</span> Danh Sách Banner
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link d-flex align-items-center gap-1 py-2 px-3" id="page-tab-form-btn" data-bs-toggle="tab" data-bs-target="#page-tab-banner-form" type="button" role="tab">
            <span class="material-icons fs-6" id="pageFormTabIcon">add_circle_outline</span> <span id="pageFormTabTitle">Thêm Hoạt Động Mới</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link d-flex align-items-center gap-1 py-2 px-3" id="page-tab-settings-btn" data-bs-toggle="tab" data-bs-target="#page-tab-banner-settings" type="button" role="tab">
            <span class="material-icons fs-6">tune</span> Cài Đặt Trình Chiếu Carousel
          </button>
        </li>
      </ul>

      <div class="tab-content p-3" id="bannerManagerPageTabContent">
        <!-- TAB 1: DANH SÁCH BANNER -->
        <div class="tab-pane fade show active" id="page-tab-banner-list" role="tabpanel">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 520px;">
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><span class="material-icons fs-6 text-muted">search</span></span>
                <input type="text" class="form-control" id="pageSearchInput" placeholder="Tìm theo tiêu đề hoặc nội dung..." onkeyup="filterPageTable()">
              </div>
              <select class="form-select form-select-sm" id="pageCategoryFilterSelect" style="max-width: 170px;" onchange="filterPageTable()">
                <option value="all">Tất cả loại hoạt động</option>
                <option value="5S">5S</option>
                <option value="Kaizen">Kaizen</option>
                <option value="Cải tiến">Cải tiến</option>
                <option value="Tuyên dương">Tuyên dương</option>
                <option value="Giải thưởng">Giải thưởng</option>
                <option value="Hoạt động sản xuất">Hoạt động sản xuất</option>
                <option value="Khác">Khác</option>
              </select>
            </div>
            <button type="button" class="app-btn app-btn-primary app-btn-sm d-flex align-items-center gap-1" onclick="switchToPageCreateForm()">
              <span class="material-icons fs-6">add</span> Thêm Mới Banner
            </button>
          </div>

          <div class="table-responsive border rounded" style="min-height: 250px;">
            <table class="table table-hover table-striped align-middle mb-0" id="pageBannerTable">
              <thead class="table-light">
                <tr>
                  <th style="width: 70px;" class="text-center">Thứ tự</th>
                  <th style="width: 120px;">Hình ảnh</th>
                  <th>Tiêu đề & Loại hoạt động</th>
                  <th style="width: 130px;" class="text-center">Ngày đăng</th>
                  <th style="width: 110px;" class="text-center">Trạng thái</th>
                  <th style="width: 120px;" class="text-center">Thao tác</th>
                </tr>
              </thead>
              <tbody id="pageBannerTableBody">
                <tr>
                  <td colspan="6" class="text-center py-4 text-muted">Đang nạp danh sách banner...</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="d-flex justify-content-between align-items-center mt-2 px-1 text-muted fs-xs">
            <span>💡 Sử dụng nút ▲ / ▼ để sắp xếp thứ tự hiển thị ưu tiên trên Carousel trang Tổng Quan.</span>
            <span id="pageTableTotalCount">Tổng cộng: 0 banner</span>
          </div>
        </div>

        <!-- TAB 2: FORM THÊM MỚI / CHỈNH SỬA BANNER -->
        <div class="tab-pane fade" id="page-tab-banner-form" role="tabpanel">
          <form id="pageBannerForm" onsubmit="handleSavePageBanner(event)" enctype="multipart/form-data">
            <input type="hidden" id="pageBannerFormId" name="id" value="0">
            <input type="hidden" id="pageBannerFormAction" name="action" value="create">

            <div class="row g-3">
              <div class="col-md-8">
                <label class="form-label fw-semibold fs-sm required">Tiêu đề hoạt động <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-sm" id="pageBannerTitle" name="title" required placeholder="Nhập tiêu đề hoạt động...">
              </div>

              <div class="col-md-4">
                <label class="form-label fw-semibold fs-sm required">Loại hoạt động <span class="text-danger">*</span></label>
                <select class="form-select form-select-sm" id="pageBannerCategory" name="category" required>
                  <option value="5S">5S</option>
                  <option value="Kaizen">Kaizen</option>
                  <option value="Cải tiến">Cải tiến</option>
                  <option value="Tuyên dương">Tuyên dương</option>
                  <option value="Giải thưởng">Giải thưởng</option>
                  <option value="Hoạt động sản xuất">Hoạt động sản xuất</option>
                  <option value="Khác">Khác</option>
                </select>
              </div>

              <div class="col-12">
                <label class="form-label fw-semibold fs-sm">Mô tả ngắn gọn (Hiển thị trên Slide)</label>
                <textarea class="form-control form-control-sm" id="pageBannerSummary" name="summary" rows="2" placeholder="Tóm tắt ngắn gọn hoạt động (khoảng 2-3 câu)..."></textarea>
              </div>

              <div class="col-md-4">
                <label class="form-label fw-semibold fs-sm">Ngày diễn ra / Ngày đăng</label>
                <input type="date" class="form-control form-control-sm" id="pageBannerEventDate" name="event_date" value="<?= date('Y-m-d') ?>">
              </div>

              <div class="col-md-4">
                <label class="form-label fw-semibold fs-sm">Thứ tự hiển thị (Số nhỏ xếp trước)</label>
                <input type="number" class="form-control form-control-sm" id="pageBannerDisplayOrder" name="display_order" value="0" min="0">
              </div>

              <div class="col-md-4">
                <label class="form-label fw-semibold fs-sm">Trạng thái hiển thị</label>
                <select class="form-select form-select-sm" id="pageBannerStatus" name="status">
                  <option value="active">Hiển thị (Active)</option>
                  <option value="inactive">Ẩn (Inactive)</option>
                </select>
              </div>

              <!-- Upload Hình ảnh -->
              <div class="col-12">
                <label class="form-label fw-semibold fs-sm mb-1">
                  <span>Hình ảnh hoạt động</span>
                  <span class="text-muted fw-normal fs-xs">(Hỗ trợ JPG, PNG, WEBP, SVG. Hệ thống tự động nén & tạo thumbnail)</span>
                </label>
                <div class="dx-dropzone" id="pageBannerDropzone" onclick="document.getElementById('pageBannerImageFiles').click()">
                  <span class="material-icons text-primary fs-2 mb-1">cloud_upload</span>
                  <div class="fw-semibold fs-sm">Kéo thả hình ảnh vào đây hoặc nhấp để duyệt file</div>
                  <div class="text-muted fs-xs">Khuyến nghị ảnh kích thước tỉ lệ 16:9 hoặc 21:9 (ví dụ: 1200x500px, 1600x900px)</div>
                  <input type="file" id="pageBannerImageFiles" name="images[]" multiple accept=".jpg,.jpeg,.png,.webp,.svg" style="display:none;" onchange="handlePageImageSelection(this)">
                </div>
                <div class="dx-image-preview-grid mt-2" id="pageImagePreviewGrid"></div>
              </div>

              <div class="col-12">
                <label class="form-label fw-semibold fs-sm">Nội dung chi tiết (Tùy chọn)</label>
                <textarea class="form-control form-control-sm" id="pageBannerContent" name="content" rows="3" placeholder="Ghi chú chi tiết thêm..."></textarea>
              </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pt-2 border-top">
              <button type="button" class="app-btn app-btn-secondary" onclick="switchToPageListTab()">Hủy / Quay Lại</button>
              <button type="submit" class="app-btn app-btn-primary d-flex align-items-center gap-1" id="btnPageSubmitBanner">
                <span class="material-icons fs-6">save</span> <span id="btnPageSubmitBannerText">Lưu Hoạt Động</span>
              </button>
            </div>
          </form>
        </div>

        <!-- TAB 3: CÀI ĐẶT CAROUSEL -->
        <div class="tab-pane fade" id="page-tab-banner-settings" role="tabpanel">
          <form id="pageCarouselSettingsForm" onsubmit="handleSavePageCarouselSettings(event)">
            <div class="row g-3" style="max-width: 640px;">
              <div class="col-12">
                <div class="form-check form-switch py-1">
                  <input class="form-check-input" type="checkbox" role="switch" id="pageSettingAutoplay" checked>
                  <label class="form-check-label fw-semibold fs-sm" for="pageSettingAutoplay">Tự động chuyển slide (Autoplay)</label>
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold fs-sm">Thời gian chuyển mỗi slide (Giây)</label>
                <div class="input-group input-group-sm">
                  <input type="number" class="form-control" id="pageSettingIntervalSeconds" value="5" min="2" max="60" required>
                  <span class="input-group-text">giây</span>
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold fs-sm">Số lượng slide tối đa hiển thị</label>
                <input type="number" class="form-control form-control-sm" id="pageSettingMaxItems" value="10" min="1" max="30" required>
              </div>

              <!-- Cấu hình Chuyển Ảnh Con Trong Cùng Hoạt Động (Sub-slideshow) -->
              <div class="col-12 border-top pt-2">
                <h6 class="fw-bold fs-xs text-primary text-uppercase mb-2">Trình Chiếu Ảnh Trong Hoạt Động (Sub-Slideshow)</h6>
                <div class="form-check form-switch py-1">
                  <input class="form-check-input" type="checkbox" role="switch" id="pageSettingImageAutoplay" checked>
                  <label class="form-check-label fw-semibold fs-sm" for="pageSettingImageAutoplay">Tự động chuyển ảnh trong cùng một hoạt động</label>
                  <div class="text-muted fs-xs">Tự động luân phiên đổi giữa các ảnh của cùng một hoạt động khi slide đang chiếu.</div>
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold fs-sm">Thời gian chuyển mỗi ảnh con (Giây)</label>
                <div class="input-group input-group-sm">
                  <input type="number" class="form-control" id="pageSettingImageIntervalSeconds" value="3" min="1" max="30" step="0.5" required>
                  <span class="input-group-text">giây</span>
                </div>
                <div class="text-muted fs-xs mt-1">Mặc định: 3 giây. Tốc độ chuyển đổi giữa các ảnh con.</div>
              </div>

              <div class="col-md-6 d-flex align-items-center">
                <div class="form-check form-switch py-1 mt-2">
                  <input class="form-check-input" type="checkbox" role="switch" id="pageSettingShowSubControls" checked>
                  <label class="form-check-label fw-semibold fs-sm" for="pageSettingShowSubControls">Hiện nút điều hướng & chấm ảnh con</label>
                  <div class="text-muted fs-xs">Hiển thị nút ◀/▶ và chấm chỉ số ảnh trên slide.</div>
                </div>
              </div>

              <div class="col-12">
                <div class="form-check form-switch py-1">
                  <input class="form-check-input" type="checkbox" role="switch" id="pageSettingPauseOnHover" checked>
                  <label class="form-check-label fw-semibold fs-sm" for="pageSettingPauseOnHover">Tạm dừng khi rê chuột (Pause on Hover)</label>
                </div>
              </div>

              <div class="col-12">
                <div class="form-check form-switch py-1">
                  <input class="form-check-input" type="checkbox" role="switch" id="pageSettingShowIndicators" checked>
                  <label class="form-check-label fw-semibold fs-sm" for="pageSettingShowIndicators">Hiển thị các chấm chỉ số trang (Indicators)</label>
                </div>
              </div>

              <div class="col-12">
                <div class="form-check form-switch py-1">
                  <input class="form-check-input" type="checkbox" role="switch" id="pageSettingShowArrows" checked>
                  <label class="form-check-label fw-semibold fs-sm" for="pageSettingShowArrows">Hiển thị nút mũi tên chuyển trang (Previous / Next)</label>
                </div>
              </div>

              <div class="col-12">
                <div class="form-check form-switch py-1">
                  <input class="form-check-input" type="checkbox" role="switch" id="pageSettingShowBadges" checked>
                  <label class="form-check-label fw-semibold fs-sm" for="pageSettingShowBadges">Hiển thị huy hiệu loại hoạt động</label>
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold fs-sm">Hiệu ứng chuyển cảnh</label>
                <select class="form-select form-select-sm" id="pageSettingAnimationEffect">
                  <option value="slide">Lướt ngang mượt (Slide Transition)</option>
                  <option value="fade">Mờ dần điện ảnh (Cross Fade)</option>
                </select>
              </div>

              <div class="col-12 pt-3 border-top d-flex gap-2">
                <button type="submit" class="app-btn app-btn-primary d-flex align-items-center gap-1">
                  <span class="material-icons fs-6">save</span> Lưu Cấu Hình Carousel
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.dx-dropzone {
  border: 2px dashed var(--dx-border-strong);
  border-radius: var(--dx-radius-md);
  padding: 24px 16px;
  text-align: center;
  background: var(--dx-bg-subtle);
  cursor: pointer;
  transition: all 0.15s ease;
}
.dx-dropzone:hover {
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
.badge-5s           { background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.4); }
.badge-kaizen       { background: rgba(139, 92, 246, 0.2); color: #8b5cf6; border: 1px solid rgba(139, 92, 246, 0.4); }
.badge-cai-tien     { background: rgba(245, 158, 11, 0.2); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.4); }
.badge-tuyen-duong  { background: rgba(234, 179, 8, 0.2);  color: #eab308; border: 1px solid rgba(234, 179, 8, 0.4); }
.badge-giai-thuong  { background: rgba(239, 68, 68, 0.2);  color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.4); }
.badge-san-xuat     { background: rgba(59, 130, 246, 0.2); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.4); }
.badge-khac         { background: rgba(148, 163, 184, 0.2);color: #64748b; border: 1px solid rgba(148, 163, 184, 0.4); }
.fs-xs { font-size: 11px; }
.fs-sm { font-size: 12.5px; }
</style>

<script>
let pageBannersList = [];
let pageStagedImages = [];

document.addEventListener('DOMContentLoaded', function() {
  loadPageBannersTable();
  loadPageSettingsIntoForm();
});

async function loadPageBannersTable() {
  const tbody = document.getElementById('pageBannerTableBody');
  if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2 text-primary"></div>Đang tải dữ liệu...</td></tr>';

  try {
    const res = await fetch('api/dashboard_banners.php?action=list&status=');
    const result = await res.json();
    if (result.success) {
      pageBannersList = result.data || [];
      renderPageTableRows(pageBannersList);
    } else {
      if (tbody) tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">${escapeHtml(result.message)}</td></tr>`;
    }
  } catch (err) {
    console.error("Lỗi tải bảng banner:", err);
    if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-danger">Không thể kết nối máy chủ!</td></tr>';
  }
}

function renderPageTableRows(list) {
  const tbody = document.getElementById('pageBannerTableBody');
  const countEl = document.getElementById('pageTableTotalCount');
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
    const badgeClass = getBadgeClass(item.category);
    const isAct = item.status === 'active';
    const imgList = item.images_list && item.images_list.length > 0 ? item.images_list : [item.image_url];
    const imgUrl = item.image_url || imgList[0] || 'resources/placeholder_banner.svg';

    html += `
      <tr data-id="${item.id}">
        <td class="text-center fw-bold text-muted">
          <div class="d-flex flex-column align-items-center gap-1">
            <button type="button" class="btn btn-xs btn-outline-secondary p-0 px-1" title="Chuyển lên" ${!canEdit ? 'disabled' : ''} onclick="movePageBannerRow(${item.id}, -1)">▲</button>
            <span class="fs-xs">${item.display_order || index + 1}</span>
            <button type="button" class="btn btn-xs btn-outline-secondary p-0 px-1" title="Chuyển xuống" ${!canEdit ? 'disabled' : ''} onclick="movePageBannerRow(${item.id}, 1)">▼</button>
          </div>
        </td>
        <td>
          <div class="position-relative d-inline-block">
            <img src="${escapeHtml(imgUrl)}" alt="thumb" class="rounded border shadow-sm" style="width: 90px; height: 50px; object-fit: cover;" onerror="this.src='resources/placeholder_banner.svg'">
            ${imgList.length > 1 ? `<span class="badge bg-dark bg-opacity-75 text-white position-absolute bottom-0 end-0 m-1 p-0 px-1" style="font-size: 9px;"><span class="material-icons" style="font-size: 10px; vertical-align: middle;">photo_library</span> ${imgList.length}</span>` : ''}
          </div>
        </td>
        <td>
          <div class="fw-bold fs-sm text-truncate" style="max-width: 450px;">${escapeHtml(item.title)}</div>
          <div class="d-flex align-items-center gap-2 mt-1">
            <span class="badge ${badgeClass} fs-xs">${escapeHtml(item.category)}</span>
            <span class="text-muted fs-xs text-truncate" style="max-width: 350px;">${escapeHtml(item.summary || '')}</span>
          </div>
        </td>
        <td class="text-center text-muted fs-xs">${item.event_date_formatted || item.event_date}</td>
        <td class="text-center">
          <div class="form-check form-switch d-inline-block">
            <input class="form-check-input cursor-pointer" type="checkbox" role="switch" ${isAct ? 'checked' : ''} ${!canPublish ? 'disabled' : ''} onchange="togglePageBannerStatus(${item.id}, this)">
          </div>
        </td>
        <td class="text-center">
          <div class="btn-group btn-group-sm">
            ${canEdit ? `
              <button type="button" class="btn btn-outline-primary" title="Chỉnh sửa" onclick="editPageBannerItem(${item.id})">
                <span class="material-icons fs-6">edit</span>
              </button>
            ` : ''}
            ${canDelete ? `
              <button type="button" class="btn btn-outline-danger" title="Xóa" onclick="deletePageBannerItem(${item.id})">
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

function filterPageTable() {
  const query = (document.getElementById('pageSearchInput')?.value || '').toLowerCase().trim();
  const cat = document.getElementById('pageCategoryFilterSelect')?.value || 'all';

  const filtered = pageBannersList.filter(b => {
    const matchQ = !query || b.title.toLowerCase().includes(query) || (b.summary && b.summary.toLowerCase().includes(query));
    const matchC = (cat === 'all') || (b.category === cat);
    return matchQ && matchC;
  });

  renderPageTableRows(filtered);
}

function switchToPageListTab() {
  const tabBtn = document.getElementById('page-tab-list-btn');
  if (tabBtn) bootstrap.Tab.getOrCreateInstance(tabBtn).show();
}

function switchToPageCreateForm() {
  if (!hasPermission(['BannerActivity.Create', 'dashboard.edit'])) {
    alert("Bạn không có quyền tạo hoạt động mới! (Yêu cầu BannerActivity.Create)");
    return;
  }

  resetPageBannerForm();
  document.getElementById('pageFormTabTitle').textContent = 'Thêm Hoạt Động Mới';
  document.getElementById('pageFormTabIcon').textContent = 'add_circle_outline';
  document.getElementById('pageBannerFormAction').value = 'create';
  document.getElementById('pageBannerFormId').value = '0';
  document.getElementById('btnPageSubmitBannerText').textContent = 'Lưu Hoạt Động';

  const tabBtn = document.getElementById('page-tab-form-btn');
  if (tabBtn) bootstrap.Tab.getOrCreateInstance(tabBtn).show();
}

function resetPageBannerForm() {
  document.getElementById('pageBannerForm').reset();
  document.getElementById('pageBannerFormId').value = '0';
  document.getElementById('pageBannerEventDate').value = new Date().toISOString().split('T')[0];
  document.getElementById('pageBannerDisplayOrder').value = '0';
  pageStagedImages = [];
  renderPageImagePreviewGrid();
}

function handlePageImageSelection(input) {
  if (!input.files || input.files.length === 0) return;
  if (!hasPermission(['BannerActivity.UploadImage', 'BannerActivity.Create', 'BannerActivity.Edit', 'dashboard.edit'])) {
    alert("Bạn không có quyền tải lên hình ảnh! (Yêu cầu BannerActivity.UploadImage)");
    input.value = '';
    return;
  }

  const isFirstBatch = pageStagedImages.length === 0;
  Array.from(input.files).forEach((file, fIdx) => {
    pageStagedImages.push({
      isNew: true,
      file: file,
      url: URL.createObjectURL(file),
      isCover: (isFirstBatch && fIdx === 0)
    });
  });
  input.value = '';
  renderPageImagePreviewGrid();
}

function renderPageImagePreviewGrid() {
  const container = document.getElementById('pageImagePreviewGrid');
  if (!container) return;

  if (pageStagedImages.length === 0) {
    container.innerHTML = '<div class="text-muted fs-xs py-2">Chưa có ảnh nào được chọn. Hãy chọn hoặc kéo thả ít nhất 1 ảnh.</div>';
    return;
  }

  const hasCover = pageStagedImages.some(img => img.isCover);
  if (!hasCover && pageStagedImages.length > 0) {
    pageStagedImages[0].isCover = true;
  }

  const canReorder = hasPermission(['BannerActivity.ReorderImage', 'BannerActivity.Edit', 'dashboard.edit']);
  const canDeleteImg = hasPermission(['BannerActivity.DeleteImage', 'BannerActivity.Edit', 'dashboard.edit']);

  let html = '';
  pageStagedImages.forEach((img, idx) => {
    const isCover = !!img.isCover;
    const isFirst = idx === 0;
    const isLast = idx === pageStagedImages.length - 1;

    html += `
      <div class="dx-preview-item ${isCover ? 'is-cover' : ''}" data-idx="${idx}">
        <img src="${escapeHtml(img.url)}" alt="preview" onerror="this.src='resources/placeholder_banner.svg'">
        ${isCover ? '<span class="dx-cover-badge"><span class="material-icons" style="font-size: 11px;">star</span> Đại diện</span>' : ''}
        <span class="dx-preview-order-tag">#${idx + 1}</span>

        <div class="dx-preview-actions-bar">
          ${(canReorder && !isCover) ? `
            <button type="button" class="dx-preview-btn btn-set-cover" title="Đặt làm ảnh đại diện" onclick="setAsPageCoverImage(${idx})">
              <span class="material-icons" style="font-size: 13px;">star_border</span>
            </button>
          ` : ''}

          ${(canReorder && !isFirst) ? `
            <button type="button" class="dx-preview-btn" title="Chuyển sang trước" onclick="movePageStagedImage(${idx}, -1)">
              <span class="material-icons" style="font-size: 14px;">arrow_back</span>
            </button>
          ` : ''}

          ${(canReorder && !isLast) ? `
            <button type="button" class="dx-preview-btn" title="Chuyển ra sau" onclick="movePageStagedImage(${idx}, 1)">
              <span class="material-icons" style="font-size: 14px;">arrow_forward</span>
            </button>
          ` : ''}

          ${canDeleteImg ? `
            <button type="button" class="dx-preview-btn btn-del" title="Xóa ảnh này" onclick="removePageStagedImage(${idx})">
              <span class="material-icons" style="font-size: 13px;">delete</span>
            </button>
          ` : ''}
        </div>
      </div>
    `;
  });
  container.innerHTML = html;
}

function setAsPageCoverImage(idx) {
  if (idx < 0 || idx >= pageStagedImages.length) return;
  const [selected] = pageStagedImages.splice(idx, 1);
  pageStagedImages.forEach(img => img.isCover = false);
  selected.isCover = true;
  pageStagedImages.unshift(selected);
  renderPageImagePreviewGrid();
}

function movePageStagedImage(idx, direction) {
  const targetIdx = idx + direction;
  if (targetIdx < 0 || targetIdx >= pageStagedImages.length) return;
  const temp = pageStagedImages[idx];
  pageStagedImages[idx] = pageStagedImages[targetIdx];
  pageStagedImages[targetIdx] = temp;
  renderPageImagePreviewGrid();
}

function removePageStagedImage(idx) {
  if (!hasPermission(['BannerActivity.DeleteImage', 'BannerActivity.Edit', 'dashboard.edit'])) {
    alert("Bạn không có quyền xóa hình ảnh! (Yêu cầu BannerActivity.DeleteImage)");
    return;
  }
  const removed = pageStagedImages.splice(idx, 1)[0];
  if (removed && removed.isCover && pageStagedImages.length > 0) {
    pageStagedImages[0].isCover = true;
  }
  renderPageImagePreviewGrid();
}

function editPageBannerItem(id) {
  if (!hasPermission(['BannerActivity.Edit', 'dashboard.edit'])) {
    alert("Bạn không có quyền chỉnh sửa hoạt động! (Yêu cầu BannerActivity.Edit)");
    return;
  }

  const item = pageBannersList.find(b => b.id == id);
  if (!item) return;

  resetPageBannerForm();
  document.getElementById('pageFormTabTitle').textContent = `Chỉnh Sửa Hoạt Động #${item.id}`;
  document.getElementById('pageFormTabIcon').textContent = 'edit';
  document.getElementById('pageBannerFormAction').value = 'update';
  document.getElementById('pageBannerFormId').value = item.id;
  document.getElementById('btnPageSubmitBannerText').textContent = 'Cập Nhật Hoạt Động';

  document.getElementById('pageBannerTitle').value = item.title;
  document.getElementById('pageBannerCategory').value = item.category;
  document.getElementById('pageBannerSummary').value = item.summary || '';
  document.getElementById('pageBannerContent').value = item.content || '';
  document.getElementById('pageBannerEventDate').value = item.event_date;
  document.getElementById('pageBannerDisplayOrder').value = item.display_order || 0;
  document.getElementById('pageBannerStatus').value = item.status || 'active';

  const oldImgs = item.images_list && item.images_list.length > 0 ? item.images_list : [item.image_url];
  const coverUrl = item.image_url || oldImgs[0];

  pageStagedImages = oldImgs.map((url, i) => ({
    isNew: false,
    file: null,
    url: url,
    isCover: (url === coverUrl || (i === 0 && !oldImgs.includes(coverUrl)))
  }));

  const coverIdx = pageStagedImages.findIndex(img => img.isCover);
  if (coverIdx > 0) {
    const [cImg] = pageStagedImages.splice(coverIdx, 1);
    pageStagedImages.unshift(cImg);
  }

  renderPageImagePreviewGrid();

  const tabBtn = document.getElementById('page-tab-form-btn');
  if (tabBtn) bootstrap.Tab.getOrCreateInstance(tabBtn).show();
}

async function handleSavePageBanner(e) {
  e.preventDefault();
  const btnSubmit = document.getElementById('btnPageSubmitBanner');
  const btnText = document.getElementById('btnPageSubmitBannerText');
  const originalText = btnText.textContent;

  const action = document.getElementById('pageBannerFormAction').value;
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
  fd.append('id', document.getElementById('pageBannerFormId').value);
  fd.append('title', document.getElementById('pageBannerTitle').value);
  fd.append('category', document.getElementById('pageBannerCategory').value);
  fd.append('summary', document.getElementById('pageBannerSummary').value);
  fd.append('content', document.getElementById('pageBannerContent').value);
  fd.append('event_date', document.getElementById('pageBannerEventDate').value);
  fd.append('display_order', document.getElementById('pageBannerDisplayOrder').value);
  fd.append('status', document.getElementById('pageBannerStatus').value);

  const keepImages = pageStagedImages.filter(img => !img.isNew).map(img => img.url);
  fd.append('keep_images', JSON.stringify(keepImages));

  const newImages = pageStagedImages.filter(img => img.isNew);
  newImages.forEach(img => {
    fd.append('images[]', img.file);
  });

  const coverObj = pageStagedImages.find(img => img.isCover) || pageStagedImages[0];
  if (coverObj && !coverObj.isNew) {
    fd.append('cover_image', coverObj.url);
  }

  try {
    btnSubmit.disabled = true;
    btnText.textContent = 'Đang lưu...';

    const res = await fetch('api/dashboard_banners.php', { method: 'POST', body: fd });
    const result = await res.json();

    if (result.success) {
      alert(result.message);
      loadPageBannersTable();
      switchToPageListTab();
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

async function togglePageBannerStatus(id, switchEl) {
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
    if (!result.success) {
      alert("Lỗi: " + result.message);
      switchEl.checked = !switchEl.checked;
    }
  } catch (err) {
    console.error("Lỗi toggle status:", err);
    switchEl.checked = !switchEl.checked;
  }
}

async function movePageBannerRow(id, direction) {
  if (!hasPermission(['BannerActivity.Edit', 'dashboard.edit'])) {
    alert("Bạn không có quyền sắp xếp hoạt động! (Yêu cầu BannerActivity.Edit)");
    return;
  }

  const idx = pageBannersList.findIndex(b => b.id == id);
  if (idx === -1) return;
  const targetIdx = idx + direction;
  if (targetIdx < 0 || targetIdx >= pageBannersList.length) return;

  const temp = pageBannersList[idx];
  pageBannersList[idx] = pageBannersList[targetIdx];
  pageBannersList[targetIdx] = temp;

  renderPageTableRows(pageBannersList);

  try {
    const orderIds = pageBannersList.map(b => b.id);
    await fetch('api/dashboard_banners.php?action=reorder', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ order_ids: orderIds })
    });
  } catch (err) {
    console.error("Lỗi lưu thứ tự:", err);
  }
}

async function deletePageBannerItem(id) {
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
      loadPageBannersTable();
    } else {
      alert("Lỗi: " + result.message);
    }
  } catch (err) {
    console.error("Lỗi xóa banner:", err);
  }
}

async function loadPageSettingsIntoForm() {
  try {
    const res = await fetch('api/dashboard_banners.php?action=get_settings');
    const result = await res.json();
    if (result.success && result.data) {
      const s = result.data;
      document.getElementById('pageSettingAutoplay').checked = s.autoplay !== false;
      document.getElementById('pageSettingIntervalSeconds').value = Math.round((s.interval || 5000) / 1000);
      document.getElementById('pageSettingImageAutoplay').checked = s.image_autoplay !== false;
      document.getElementById('pageSettingImageIntervalSeconds').value = Math.round((s.image_interval || 3000) / 1000);
      document.getElementById('pageSettingShowSubControls').checked = s.show_sub_controls !== false;
      document.getElementById('pageSettingPauseOnHover').checked = s.pause_on_hover !== false;
      document.getElementById('pageSettingMaxItems').value = s.max_items || 10;
      document.getElementById('pageSettingShowIndicators').checked = s.show_indicators !== false;
      document.getElementById('pageSettingShowArrows').checked = s.show_arrows !== false;
      document.getElementById('pageSettingShowBadges').checked = s.show_badges !== false;
      document.getElementById('pageSettingAnimationEffect').value = s.animation_effect || 'slide';
    }
  } catch (err) {
    console.error("Lỗi nạp settings:", err);
  }
}

async function handleSavePageCarouselSettings(e) {
  e.preventDefault();
  if (!hasPermission(['BannerActivity.ConfigCarousel', 'dashboard.edit'])) {
    alert("Bạn không có quyền cấu hình Carousel! (Yêu cầu BannerActivity.ConfigCarousel)");
    return;
  }

  const intervalSec = parseFloat(document.getElementById('pageSettingIntervalSeconds').value) || 5;
  const imageIntervalSec = parseFloat(document.getElementById('pageSettingImageIntervalSeconds').value) || 3;

  const payload = {
    autoplay: document.getElementById('pageSettingAutoplay').checked,
    interval: Math.max(2000, Math.round(intervalSec * 1000)),
    image_autoplay: document.getElementById('pageSettingImageAutoplay').checked,
    image_interval: Math.max(1000, Math.round(imageIntervalSec * 1000)),
    show_sub_controls: document.getElementById('pageSettingShowSubControls').checked,
    pause_on_hover: document.getElementById('pageSettingPauseOnHover').checked,
    max_items: parseInt(document.getElementById('pageSettingMaxItems').value) || 10,
    show_indicators: document.getElementById('pageSettingShowIndicators').checked,
    show_arrows: document.getElementById('pageSettingShowArrows').checked,
    show_badges: document.getElementById('pageSettingShowBadges').checked,
    animation_effect: document.getElementById('pageSettingAnimationEffect').value
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
      switchToPageListTab();
    } else {
      alert("Lỗi: " + result.message);
    }
  } catch (err) {
    console.error("Lỗi lưu cấu hình:", err);
    alert("Không thể lưu cấu hình Carousel!");
  }
}

function getBadgeClass(category) {
  switch (category) {
    case '5S': return 'badge-5s';
    case 'Kaizen': return 'badge-kaizen';
    case 'Cải tiến': return 'badge-cai-tien';
    case 'Tuyên dương': return 'badge-tuyen-duong';
    case 'Giải thưởng': return 'badge-giai-thuong';
    case 'Hoạt động sản xuất': return 'badge-san-xuat';
    default: return 'badge-khac';
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
</script>
