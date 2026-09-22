<?php
// modules/document/viewer.php
require_once __DIR__ . '/../../core/check_permission.php';

// Kiểm tra quyền xem trang
if (!hasPermission('document.view')) {
    echo '<div class="alert alert-danger m-3">Bạn không có quyền truy cập vào Module Tài liệu!</div>';
    return;
}
?>

<!-- Xuất danh sách quyền người dùng cho Client JS -->
<?php renderPermissionScript(); ?>

<style>
/* ==========================================================================
   MODULE DOCUMENT VIEWER & EXPLORER (QUẢN LÝ TÀI LIỆU TIÊU CHUẨN SOP)
   ========================================================================== */

.sop-app-wrapper {
  display: flex;
  flex-direction: column;
  height: calc(100vh - var(--dx-header-height) - var(--dx-footer-height) - 105px);
  min-height: 580px;
  width: 100%;
  background-color: var(--dx-bg-card);
  box-sizing: border-box;
  overflow: hidden;
  border-radius: var(--dx-radius-md);
  border: 1px solid var(--dx-border);
  box-shadow: var(--dx-shadow-sm);
}

/* Thanh công cụ chính (Top Toolbar) */
.sop-toolbar {
  height: 52px;
  background-color: var(--dx-bg-card);
  border-bottom: 1px solid var(--dx-border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 16px;
  flex-shrink: 0;
  gap: 12px;
}

.sop-toolbar-left {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
}

.sop-toolbar-right {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.sop-search-box {
  position: relative;
  width: 320px;
  max-width: 100%;
}

.sop-search-box .material-icons {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 18px;
  color: var(--dx-text-muted);
  pointer-events: none;
}

.sop-search-box input {
  width: 100%;
  padding: 7px 12px 7px 34px;
  border-radius: var(--dx-radius-sm);
  border: 1px solid var(--dx-border);
  background-color: var(--dx-bg-app);
  font-size: 12.5px;
  outline: none;
  color: var(--dx-text-main);
  transition: all 0.15s ease;
}

.sop-search-box input:focus {
  border-color: var(--dx-primary);
  background-color: var(--dx-bg-card);
  box-shadow: var(--dx-focus-ring);
}

/* Khung làm việc 2 cột */
.sop-main-container {
  display: flex;
  flex: 1;
  height: calc(100% - 52px);
  overflow: hidden;
  position: relative;
}

/* CỘT 1: CÂY THƯ MỤC */
.sop-tree-panel {
  width: 330px;
  min-width: 330px;
  max-width: 330px;
  background-color: var(--dx-bg-card);
  border-right: 1px solid var(--dx-border);
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  transition: width 0.28s cubic-bezier(0.4, 0, 0.2, 1), 
              min-width 0.28s cubic-bezier(0.4, 0, 0.2, 1),
              opacity 0.2s ease,
              padding 0.28s ease;
  overflow: hidden;
  z-index: 10;
}

.sop-tree-panel.collapsed {
  width: 0 !important;
  min-width: 0 !important;
  max-width: 0 !important;
  padding: 0 !important;
  border-right: none !important;
  opacity: 0;
  pointer-events: none;
}

.sop-tree-header {
  padding: 12px 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid var(--dx-border);
  background-color: var(--dx-bg-subtle);
  flex-shrink: 0;
}

.sop-tree-title {
  font-size: 13px;
  font-weight: 700;
  color: var(--dx-text-main);
  display: flex;
  align-items: center;
  gap: 6px;
}

.sop-tree-body {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 10px 8px;
}

.sop-tree, .sop-tree ul {
  list-style: none;
  padding-left: 14px;
  margin: 0;
}

.sop-tree-root-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 10px;
  border-radius: var(--dx-radius-sm);
  cursor: pointer;
  color: var(--dx-text-main);
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 6px;
  border: 1px solid transparent;
  transition: all 0.15s ease;
}

.sop-tree-root-item:hover,
.sop-tree-root-item.active-folder {
  background-color: var(--dx-primary-light);
  color: var(--dx-primary);
  border-color: var(--dx-primary-border, rgba(37, 99, 235, 0.25));
}

.sop-folder-node {
  margin: 2px 0;
}

.sop-folder-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 6px 8px;
  border-radius: var(--dx-radius-sm);
  cursor: pointer;
  color: var(--dx-text-main);
  font-size: 12.5px;
  user-select: none;
  transition: all 0.15s ease;
}

.sop-folder-row:hover {
  background-color: var(--dx-primary-light);
  color: var(--dx-primary);
}

.sop-folder-row.active-folder {
  background-color: var(--dx-primary-light);
  color: var(--dx-primary);
  font-weight: 600;
}

.sop-folder-left {
  display: flex;
  align-items: center;
  gap: 5px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  flex: 1;
}

.sop-folder-name {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.sop-folder-actions {
  display: none;
  align-items: center;
  gap: 2px;
}

.sop-folder-row:hover .sop-folder-actions {
  display: flex;
}

.sop-folder-count {
  font-size: 10.5px;
  padding: 1px 6px;
  border-radius: 10px;
  background: var(--dx-bg-subtle);
  color: var(--dx-text-muted);
  font-weight: 600;
}

.sop-toggle-icon {
  font-size: 16px !important;
  transition: transform 0.2s ease;
  color: var(--dx-text-muted);
  flex-shrink: 0;
}

.sop-folder-node.open > .sop-folder-row .sop-toggle-icon {
  transform: rotate(90deg);
}

.sop-folder-children {
  display: none;
}

.sop-folder-node.open > .sop-folder-children {
  display: block;
}

/* File trong cây */
.sop-tree-file-item {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 5px 8px 5px 18px;
  border-radius: var(--dx-radius-sm);
  font-size: 12px;
  color: var(--dx-text-muted);
  cursor: pointer;
  transition: all 0.15s ease;
  margin: 1px 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.sop-tree-file-item:hover {
  background-color: var(--dx-primary-light);
  color: var(--dx-primary);
}

.sop-tree-file-item.active-tree-file {
  background-color: var(--dx-primary-light);
  color: var(--dx-primary);
  font-weight: 600;
  border-left: 3px solid var(--dx-primary);
}

/* CỘT 2: KHU VỰC CHÍNH (FOLDER EXPLORER & VIEWER) */
.sop-viewer-panel {
  flex: 1;
  background-color: var(--dx-bg-app);
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden;
  position: relative;
}

.sop-viewer-topbar {
  padding: 8px 16px;
  background-color: var(--dx-bg-card);
  border-bottom: 1px solid var(--dx-border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-shrink: 0;
  gap: 10px;
}

.sop-breadcrumb-nav {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12.5px;
  color: var(--dx-text-muted);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.sop-breadcrumb-nav a,
.sop-breadcrumb-nav span.sop-crumb-link {
  color: var(--dx-text-muted);
  text-decoration: none;
  cursor: pointer;
}

.sop-breadcrumb-nav a:hover,
.sop-breadcrumb-nav span.sop-crumb-link:hover {
  color: var(--dx-primary);
}

.sop-breadcrumb-nav span.sop-crumb-current {
  color: var(--dx-text-main);
  font-weight: 700;
}

/* Chế độ 1: Trình duyệt Thư mục (Folder Explorer View) */
.sop-explorer-view {
  flex: 1;
  overflow-y: auto;
  padding: 16px 20px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.sop-folder-hero {
  background: var(--dx-bg-card);
  border: 1px solid var(--dx-border);
  border-radius: var(--dx-radius-md);
  padding: 16px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  box-shadow: var(--dx-shadow-sm);
}

.sop-folder-hero-title {
  font-size: 16px;
  font-weight: 700;
  color: var(--dx-text-main);
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.sop-folder-hero-subtitle {
  font-size: 12px;
  color: var(--dx-text-muted);
  margin: 0;
}

/* Lưới thẻ tài liệu (Document Cards Grid) */
.sop-doc-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 14px;
}

.sop-doc-card {
  background: var(--dx-bg-card);
  border: 1px solid var(--dx-border);
  border-radius: var(--dx-radius-md);
  padding: 14px 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  transition: all 0.2s ease;
  box-shadow: var(--dx-shadow-sm);
  cursor: pointer;
  position: relative;
}

.sop-doc-card:hover {
  border-color: var(--dx-primary);
  transform: translateY(-2px);
  box-shadow: var(--dx-shadow-md);
}

.sop-doc-card-top {
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

.sop-doc-icon {
  width: 38px;
  height: 38px;
  border-radius: var(--dx-radius-sm);
  background: var(--dx-primary-light);
  color: var(--dx-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.sop-doc-meta {
  flex: 1;
  min-width: 0;
}

.sop-doc-code {
  font-family: monospace;
  font-weight: 700;
  font-size: 12px;
  color: var(--dx-primary);
  margin-bottom: 2px;
}

.sop-doc-title {
  font-size: 13px;
  font-weight: 600;
  color: var(--dx-text-main);
  line-height: 1.35;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.sop-doc-card-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-top: 1px solid var(--dx-border);
  padding-top: 8px;
  font-size: 11.5px;
  color: var(--dx-text-muted);
}

.sop-doc-actions {
  display: flex;
  align-items: center;
  gap: 4px;
}

/* Chế độ 2: Trình xem PDF (PDF Viewer View) */
.sop-pdf-view {
  flex: 1;
  display: none;
  flex-direction: column;
  height: 100%;
  overflow: hidden;
  padding: 10px 14px;
}

.sop-pdf-container {
  flex: 1;
  background-color: #323639;
  border-radius: var(--dx-radius-sm);
  border: 1px solid var(--dx-border);
  overflow: hidden;
}

.sop-pdf-container iframe {
  width: 100%;
  height: 100%;
  border: none;
}

/* Các nút Icon thao tác */
.sop-btn-icon {
  background: transparent;
  border: 1px solid transparent;
  color: var(--dx-text-muted);
  cursor: pointer;
  padding: 4px 6px;
  border-radius: var(--dx-radius-sm);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s ease;
}

.sop-btn-icon:hover {
  background-color: var(--dx-bg-subtle);
  color: var(--dx-primary);
  border-color: var(--dx-border);
}

.sop-btn-icon-danger:hover {
  background-color: var(--dx-danger-bg);
  color: var(--dx-danger);
  border-color: var(--dx-danger-border);
}

/* Modals */
.sop-modal {
  display: none;
  position: fixed;
  z-index: 1000;
  top: 0; left: 0;
  width: 100vw; height: 100vh;
  background: rgba(0, 0, 0, 0.65);
  backdrop-filter: blur(3px);
  justify-content: center;
  align-items: center;
}

.sop-modal-content {
  background-color: var(--dx-bg-card);
  padding: 22px 24px;
  border-radius: var(--dx-radius-md);
  width: 460px;
  max-width: 92vw;
  display: flex;
  flex-direction: column;
  gap: 14px;
  box-shadow: var(--dx-shadow-lg);
  border: 1px solid var(--dx-border);
  position: relative;
}

.sop-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid var(--dx-border);
  padding-bottom: 10px;
}

.sop-modal-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--dx-text-main);
  margin: 0;
  display: flex;
  align-items: center;
  gap: 6px;
}

.sop-modal-body {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.sop-modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  border-top: 1px solid var(--dx-border);
  padding-top: 12px;
  margin-top: 6px;
}

/* Fullscreen Modal */
.sop-fullscreen-modal {
  display: none;
  position: fixed;
  z-index: 9999;
  top: 0; left: 0;
  width: 100vw; height: 100vh;
  background-color: #1a1a1a;
  flex-direction: column;
}

.sop-fullscreen-header {
  height: 48px;
  background-color: #242424;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 16px;
  border-bottom: 1px solid #333;
}

.sop-fullscreen-body {
  flex: 1;
  width: 100%;
  height: calc(100vh - 48px);
}

.sop-fullscreen-body iframe {
  width: 100%;
  height: 100%;
  border: none;
}
</style>

<div class="app-page-wrapper">
    <!-- TIÊU ĐỀ TRANG CHUẨN HÓA TOÀN HỆ THỐNG (5S STANDARD) -->
    <div class="app-page-header">
        <div>
            <h1 class="app-page-title">
                <span class="material-icons">description</span>
                Quản Lý Tài Liệu & Tiêu Chuẩn SOP
            </h1>
            <p class="app-page-subtitle">Hệ thống số hóa quy trình vận hành tiêu chuẩn, tài liệu kỹ thuật và an toàn nhà máy</p>
        </div>
        <div class="app-page-actions">
            <?php if (hasPermission('document.upload')): ?>
            <button class="app-btn app-btn-primary" onclick="openUploadModal()">
                <span class="material-icons">cloud_upload</span> Tải Lên Tài Liệu
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- KHUNG LÀM VIỆC SỐ HÓA TÀI LIỆU -->
    <div class="sop-app-wrapper">
        <!-- 1. Thanh Công Cụ (Toolbar) -->
        <div class="sop-toolbar">
            <div class="sop-toolbar-left">
                <!-- Nút ẩn / hiện cây thư mục -->
                <button class="app-btn app-btn-secondary app-btn-sm" id="btnToggleTree" onclick="toggleTreePanel()" title="Ẩn/Hiện cây danh mục thư mục">
                    <span class="material-icons" id="btnToggleTreeIcon">menu_open</span>
                    <span id="btnToggleTreeText">Thu gọn cây</span>
                </button>

                <!-- Thanh tìm kiếm nhanh -->
                <div class="sop-search-box">
                    <span class="material-icons">search</span>
                    <input type="text" id="searchInput" placeholder="Tìm nhanh mã hoặc tên tài liệu..." oninput="handleSearchInput()">
                </div>

                <div id="searchCounter" class="text-muted small d-none"></div>
            </div>

            <div class="sop-toolbar-right">
                <span class="text-muted small d-none d-md-inline" id="sopSummaryStats">
                    Đang nạp dữ liệu...
                </span>
                <?php if (hasPermission('document.upload')): ?>
                <button class="app-btn app-btn-primary app-btn-sm" onclick="openUploadModal()">
                    <span class="material-icons">add</span> Upload File
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2. Không Gian 2 Cột -->
        <div class="sop-main-container">
            <!-- CỘT 1: CÂY THƯ MỤC & FILE -->
            <aside class="sop-tree-panel" id="treePanel">
                <div class="sop-tree-header">
                    <span class="sop-tree-title">
                        <span class="material-icons text-primary" style="font-size:18px;">account_tree</span> Danh Mục Thư Mục
                    </span>
                    <div class="d-flex align-items-center gap-1">
                        <?php if (hasPermission('document.edit')): ?>
                        <button class="sop-btn-icon" onclick="openAddFolderModal()" title="Tạo thư mục mới">
                            <span class="material-icons" style="font-size:18px;">create_new_folder</span>
                        </button>
                        <?php endif; ?>
                        <button class="sop-btn-icon" onclick="toggleTreePanel()" title="Thu gọn bảng điều khiển">
                            <span class="material-icons" style="font-size:18px;">first_page</span>
                        </button>
                    </div>
                </div>

                <div class="sop-tree-body">
                    <!-- Nút Tổng quan: Tất cả tài liệu -->
                    <div class="sop-tree-root-item active-folder" id="rootAllDocsItem" onclick="selectRootFolder()">
                        <div class="d-flex align-items-center gap-2">
                            <span class="material-icons text-primary" style="font-size:18px;">dashboard</span>
                            <span>Tất cả tài liệu</span>
                        </div>
                        <span class="sop-folder-count" id="allDocsCount">0</span>
                    </div>

                    <!-- Danh sách cây thư mục động -->
                    <ul id="treeRoot" class="sop-tree" style="padding-left: 0;">
                        <!-- Render từ Javascript -->
                    </ul>
                </div>
            </aside>

            <!-- CỘT 2: KHU VỰC HIỂN THỊ CHÍNH -->
            <section class="sop-viewer-panel">
                <!-- Topbar chi tiết / Breadcrumb -->
                <div class="sop-viewer-topbar">
                    <div class="d-flex align-items-center gap-2">
                        <!-- Nút mở lại cây khi bị thu gọn -->
                        <button class="sop-btn-icon" id="btnExpandTreeInViewer" onclick="toggleTreePanel()" title="Mở danh mục thư mục" style="display:none;">
                            <span class="material-icons">chevron_right</span>
                        </button>
                        <div class="sop-breadcrumb-nav" id="breadcrumbNav">
                            <span class="sop-crumb-link" onclick="selectRootFolder()">Tài liệu SOP</span>
                            <span>&rsaquo;</span>
                            <span class="sop-crumb-current" id="currentFolderBreadcrumb">Tất cả tài liệu</span>
                        </div>
                    </div>

                    <!-- Nhóm nút tác vụ -->
                    <div class="d-flex align-items-center gap-2">
                        <!-- Nút quay lại duyệt thư mục (khi đang ở PDF mode) -->
                        <button class="app-btn app-btn-secondary app-btn-sm" id="btnBackToExplorer" onclick="switchToFolderView()" style="display:none;">
                            <span class="material-icons">arrow_back</span> Danh sách
                        </button>

                        <!-- Nút thao tác PDF -->
                        <div id="pdfViewerActions" style="display:none;" class="d-flex align-items-center gap-2">
                            <button class="app-btn app-btn-secondary app-btn-sm" onclick="openFullscreenModal()" title="Xem toàn màn hình">
                                <span class="material-icons">fullscreen</span> Toàn màn hình
                            </button>
                            <a id="btnDownloadPdf" href="#" target="_blank" class="app-btn app-btn-secondary app-btn-sm" title="Mở tab mới hoặc tải về">
                                <span class="material-icons">open_in_new</span> Mở riêng
                            </a>
                            <?php if (hasPermission('document.edit')): ?>
                            <button class="app-btn app-btn-warning app-btn-sm" onclick="openEditModal(currentDoc)" title="Chỉnh sửa tài liệu này">
                                <span class="material-icons">edit</span> Sửa
                            </button>
                            <?php endif; ?>
                            <?php if (hasPermission('document.delete')): ?>
                            <button class="app-btn app-btn-danger app-btn-sm" onclick="openDeleteConfirmModal(currentDoc)" title="Xóa tài liệu này">
                                <span class="material-icons">delete</span> Xóa
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- GIAO DIỆN 1: DUYỆT DANH SÁCH & THẺ TÀI LIỆU THEO THƯ MỤC -->
                <div class="sop-explorer-view" id="explorerView">
                    <!-- Banner Thư Mục -->
                    <div class="sop-folder-hero">
                        <div>
                            <div class="sop-folder-hero-title">
                                <span class="material-icons text-primary fs-4" id="heroFolderIcon">folder</span>
                                <span id="heroFolderName">Tất cả tài liệu</span>
                            </div>
                            <p class="sop-folder-hero-subtitle" id="heroFolderDesc">
                                Hiển thị danh mục các quy trình vận hành và tài liệu kỹ thuật đang lưu hành
                            </p>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <?php if (hasPermission('document.upload')): ?>
                            <button class="app-btn app-btn-primary app-btn-sm" onclick="openUploadModal(selectedFolderId)">
                                <span class="material-icons">upload_file</span> Thêm tài liệu vào đây
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Lưới Thẻ Tài Liệu (Document Cards) -->
                    <div class="sop-doc-grid" id="docGridContainer">
                        <!-- Render từ JS -->
                    </div>
                </div>

                <!-- GIAO DIỆN 2: TRÌNH XEM TRỰC TIẾP PDF (PDF VIEWER) -->
                <div class="sop-pdf-view" id="pdfView">
                    <div class="sop-pdf-container">
                        <iframe id="pdfViewerIframe" src=""></iframe>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<!-- ==========================================================================
     CÁC HỘP THOẠI (MODALS)
     ========================================================================== -->

<!-- MODAL 1: THÊM THƯ MỤC MỚI -->
<div id="modalAddFolder" class="sop-modal">
    <div class="sop-modal-content">
        <div class="sop-modal-header">
            <h3 class="sop-modal-title">
                <span class="material-icons text-primary">create_new_folder</span> Thêm Thư Mục Mới
            </h3>
            <button class="sop-btn-icon" onclick="closeAddFolderModal()">&times;</button>
        </div>
        <form id="formAddFolder" onsubmit="handleCreateFolder(event)">
            <div class="sop-modal-body">
                <div>
                    <label class="form-label small fw-bold">Tên thư mục <span class="text-danger">*</span></label>
                    <input type="text" id="newFolderName" class="app-form-control" placeholder="VD: Hướng dẫn công việc máy in..." required>
                </div>
                <div>
                    <label class="form-label small fw-bold">Thư mục cha</label>
                    <select id="newFolderParent" class="app-form-select">
                        <option value="">-- Tạo ở Thư mục gốc (Root) --</option>
                    </select>
                </div>
            </div>
            <div class="sop-modal-footer">
                <button type="button" class="app-btn app-btn-secondary" onclick="closeAddFolderModal()">Hủy</button>
                <button type="submit" class="app-btn app-btn-primary">Tạo Thư Mục</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 2: ĐỔI TÊN THƯ MỤC -->
<div id="modalEditFolder" class="sop-modal">
    <div class="sop-modal-content" style="width: 400px;">
        <div class="sop-modal-header">
            <h3 class="sop-modal-title">
                <span class="material-icons text-warning">edit</span> Đổi Tên Thư Mục
            </h3>
            <button class="sop-btn-icon" onclick="closeEditFolderModal()">&times;</button>
        </div>
        <form id="formEditFolder" onsubmit="handleUpdateFolder(event)">
            <input type="hidden" id="editFolderId">
            <div class="sop-modal-body">
                <div>
                    <label class="form-label small fw-bold">Tên thư mục mới <span class="text-danger">*</span></label>
                    <input type="text" id="editFolderName" class="app-form-control" required>
                </div>
            </div>
            <div class="sop-modal-footer">
                <button type="button" class="app-btn app-btn-secondary" onclick="closeEditFolderModal()">Hủy</button>
                <button type="submit" class="app-btn app-btn-primary">Lưu Thay Đổi</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 3: XÁC NHẬN XÓA THƯ MỤC -->
<div id="modalDeleteFolder" class="sop-modal">
    <div class="sop-modal-content" style="width: 400px;">
        <div class="sop-modal-header">
            <h3 class="sop-modal-title text-danger">
                <span class="material-icons text-danger">warning</span> Xác Nhận Xóa Thư Mục
            </h3>
            <button class="sop-btn-icon" onclick="closeDeleteFolderModal()">&times;</button>
        </div>
        <input type="hidden" id="deleteFolderId">
        <div class="sop-modal-body">
            <p class="text-muted small m-0" id="deleteFolderMessage">
                Bạn có chắc chắn muốn xóa thư mục này không? Thao tác không thể hoàn tác.
            </p>
        </div>
        <div class="sop-modal-footer">
            <button type="button" class="app-btn app-btn-secondary" onclick="closeDeleteFolderModal()">Hủy</button>
            <button type="button" class="app-btn app-btn-danger" onclick="confirmDeleteFolder()">Xóa Vĩnh Viễn</button>
        </div>
    </div>
</div>

<!-- MODAL 4: UPLOAD & CHỈNH SỬA TÀI LIỆU -->
<div id="uploadModal" class="sop-modal">
    <div class="sop-modal-content">
        <div class="sop-modal-header">
            <h3 class="sop-modal-title" id="modalDocFormTitle">
                <span class="material-icons text-primary">cloud_upload</span> Upload Tài Liệu SOP
            </h3>
            <button class="sop-btn-icon" onclick="closeUploadModal()">&times;</button>
        </div>
        <form id="uploadForm" onsubmit="handleSaveDocument(event)">
            <input type="hidden" id="editDocId" value="">
            <div class="sop-modal-body">
                <div>
                    <label class="form-label small fw-bold">Mã tài liệu <span class="text-danger">*</span></label>
                    <input type="text" id="docCode" class="app-form-control" placeholder="VD: PMW-00243-B" required>
                </div>
                <div>
                    <label class="form-label small fw-bold">Tên tài liệu / Tiêu đề <span class="text-danger">*</span></label>
                    <input type="text" id="docName" class="app-form-control" placeholder="VD: HDCV Vận hành máy in đùn nhựa" required>
                </div>
                <div>
                    <label class="form-label small fw-bold">Thư mục phân loại <span class="text-danger">*</span></label>
                    <select id="docCategorySelect" class="app-form-select" required></select>
                </div>
                <div>
                    <label class="form-label small fw-bold" id="fileInputLabel">File PDF tài liệu:</label>
                    <input type="file" id="docFile" class="app-form-control" accept="application/pdf">
                    <small id="fileHelpText" class="text-muted" style="display:none; font-size:11px; margin-top:3px;">
                        (Để trống nếu giữ nguyên file PDF hiện tại)
                    </small>
                </div>
            </div>
            <div class="sop-modal-footer">
                <button type="button" class="app-btn app-btn-secondary" onclick="closeUploadModal()">Hủy</button>
                <button type="submit" class="app-btn app-btn-primary">Lưu Dữ Liệu</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 5: XÁC NHẬN XÓA TÀI LIỆU -->
<div id="deleteConfirmModal" class="sop-modal">
    <div class="sop-modal-content" style="width: 400px;">
        <div class="sop-modal-header">
            <h3 class="sop-modal-title text-danger">
                <span class="material-icons text-danger">delete_forever</span> Xóa Tài Liệu
            </h3>
            <button class="sop-btn-icon" onclick="closeDeleteConfirmModal()">&times;</button>
        </div>
        <div class="sop-modal-body">
            <p class="text-muted small m-0" id="deleteDocConfirmText">
                Bạn có chắc chắn muốn xóa tài liệu này? File đính kèm sẽ bị gỡ bỏ khỏi hệ thống.
            </p>
        </div>
        <div class="sop-modal-footer">
            <button type="button" class="app-btn app-btn-secondary" onclick="closeDeleteConfirmModal()">Hủy</button>
            <button type="button" class="app-btn app-btn-danger" onclick="confirmDeleteDocument()">Xóa Vĩnh Viễn</button>
        </div>
    </div>
</div>

<!-- MODAL 6: XEM TOÀN MÀN HÌNH -->
<div id="fullscreenModal" class="sop-fullscreen-modal">
    <div class="sop-fullscreen-header">
        <span id="fullscreenTitle" style="font-weight:700; color:#fff; font-size:13.5px; display:flex; align-items:center; gap:6px;">
            <span class="material-icons text-primary" style="font-size:18px;">description</span> Xem Toàn Màn Hình
        </span>
        <button class="sop-btn-icon" onclick="closeFullscreenModal()" style="color:#fff;" title="Đóng toàn màn hình">
            <span class="material-icons">close</span>
        </button>
    </div>
    <div class="sop-fullscreen-body">
        <iframe id="fullscreenViewer" src=""></iframe>
    </div>
</div>

<!-- SCRIPT LOGIC -->
<script src="modules/document/app.js?v=4.0"></script>