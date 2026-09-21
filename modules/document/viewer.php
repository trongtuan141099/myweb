<?php
// modules/document/viewer.php
require_once __DIR__ . '/../../core/check_permission.php';

// Kiểm tra quyền xem trang
if (!hasPermission('document.view')) {
    echo '<div class="alert alert-danger m-3">Bạn không có quyền truy cập vào Module Tài liệu!</div>';
    return;
}
?>

<!-- Xuất hàm hasPermission() cho JS -->
<?php renderPermissionScript(); ?>

<style>
/* Module-specific styles for SOP Document Viewer */
.sop-app-wrapper {
  display: flex;
  flex-direction: column;
  height: calc(100vh - var(--dx-header-height) - var(--dx-footer-height) - 10px);
  width: 100%;
  background-color: var(--dx-bg-main);
  box-sizing: border-box;
  overflow: hidden;
  border-radius: var(--dx-radius-md);
  border: 1px solid var(--dx-border);
}

/* Toolbar */
.sop-toolbar {
  height: 52px;
  background-color: var(--dx-card-bg);
  border-bottom: 1px solid var(--dx-border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 16px;
  flex-shrink: 0;
}
.sop-toolbar-left { display: flex; align-items: center; gap: 12px; }
.sop-search-box input {
  width: 300px;
  padding: 7px 12px;
  border-radius: var(--dx-radius-sm);
  border: 1px solid var(--dx-border);
  background-color: var(--dx-bg-main);
  font-size: 13px;
  outline: none;
  color: var(--dx-text-main);
}
.sop-search-box input:focus {
  border-color: var(--dx-primary);
  background-color: var(--dx-bg-card);
  box-shadow: var(--dx-focus-ring);
}

/* Main Container 2 Cột */
.sop-main-container {
  display: flex;
  flex: 1;
  height: calc(100% - 52px);
  overflow: hidden;
}
.sop-panel-title {
  font-size: 14px;
  font-weight: 700;
  color: var(--dx-text-main);
  display: flex;
  align-items: center;
  gap: 8px;
}
.sop-panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  padding-bottom: 8px;
  border-bottom: 1px solid var(--dx-border);
}

/* Cột 1: Cây Thư Mục & File */
.sop-tree-panel {
  width: 320px;
  background-color: var(--dx-card-bg);
  border-right: 1px solid var(--dx-border);
  padding: 14px;
  overflow-y: auto;
  flex-shrink: 0;
  transition: all 0.2s ease;
}
.sop-tree-panel.collapsed { width: 0; padding: 0; border-right: none; overflow: hidden; }

.sop-tree, .sop-tree ul { list-style: none; padding-left: 12px; margin: 0; }

.sop-folder-header {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 7px 10px;
  border-radius: var(--dx-radius-sm);
  cursor: pointer;
  color: var(--dx-text-main);
  font-size: 13px;
  font-weight: 600;
  user-select: none;
}
.sop-folder-header:hover {
  background-color: var(--dx-primary-light);
  color: var(--dx-primary);
}
.sop-folder-header.active-folder {
  background-color: var(--dx-primary-light);
  color: var(--dx-primary);
}

.sop-toggle-icon {
  font-size: 16px !important;
  transition: transform 0.2s ease;
  color: var(--dx-text-muted);
}
.sop-folder-node.open > .sop-folder-header .sop-toggle-icon { transform: rotate(90deg); }

.sop-folder-children { display: none; }
.sop-folder-node.open > .sop-folder-children { display: block; }

/* File lồng trong cây */
.sop-tree-file-item {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 10px 6px 22px;
  border-radius: var(--dx-radius-sm);
  font-size: 12px;
  color: var(--dx-text-muted);
  cursor: pointer;
  transition: all 0.15s ease;
  margin: 2px 0;
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

/* Cột 2: Trình xem PDF Tối Đa Area */
.sop-viewer-panel {
  flex: 1;
  background-color: var(--dx-bg-main);
  padding: 12px;
  display: flex;
  flex-direction: column;
  height: 100%;
}
.sop-viewer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}
.sop-viewer-title-group {
  display: flex;
  align-items: center;
  gap: 8px;
}
.sop-iframe-container {
  flex: 1;
  background-color: #323639;
  border-radius: var(--dx-radius-sm);
  border: 1px solid var(--dx-border);
  overflow: hidden;
}
.sop-iframe-container iframe { width: 100%; height: 100%; border: none; }

.sop-btn-icon {
  background: transparent;
  border: none;
  color: var(--dx-text-muted);
  cursor: pointer;
  padding: 4px;
  border-radius: var(--dx-radius-sm);
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.sop-btn-icon:hover {
  background-color: var(--dx-bg-main);
  color: var(--dx-primary);
}

/* Modals */
.sop-modal {
  display: none;
  position: fixed;
  z-index: 1000;
  top: 0; left: 0;
  width: 100vw; height: 100vh;
  background: rgba(0, 0, 0, 0.65);
  backdrop-filter: blur(4px);
  justify-content: center;
  align-items: center;
}
.sop-modal-content {
  background-color: var(--dx-card-bg);
  padding: 24px;
  border-radius: var(--dx-radius-md);
  width: 440px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  box-shadow: var(--dx-shadow-lg);
  border: 1px solid var(--dx-border);
}
.sop-modal-content label {
  font-size: 12px;
  font-weight: 600;
  color: var(--dx-text-main);
  margin-bottom: 4px;
  display: block;
}

/* Fullscreen PDF Modal */
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
  background-color: #2a2a2a;
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

<div class="sop-app-wrapper">
    <!-- Toolbar -->
    <div class="sop-toolbar">
        <div class="sop-toolbar-left">
            <div class="sop-search-box">
                <input type="text" id="searchInput" placeholder="Tìm nhanh mã, tên tài liệu..." onkeyup="filterTreeAndDocs()">
            </div>
        </div>
        <div class="sop-toolbar-right" style="display:flex; gap:8px;">
            <button class="app-btn app-btn-primary" id="btnUpload" onclick="openUploadModal()">
                <span class="material-icons">cloud_upload</span> Upload File
            </button>
            <button class="app-btn app-btn-warning" id="btnEdit" onclick="openEditModal()" style="display:none;">
                <span class="material-icons">edit</span> Chỉnh Sửa
            </button>
            <button class="app-btn app-btn-danger" id="btnDelete" onclick="openDeleteConfirmModal()" style="display:none;">
                <span class="material-icons">delete</span> Xóa File
            </button>
        </div>
    </div>

    <!-- Main Container 2 Cột -->
    <div class="sop-main-container">
        <!-- Cột 1: Cây Thư Mục & File -->
        <aside class="sop-tree-panel" id="treePanel">
            <div class="sop-panel-header">
                <span class="sop-panel-title">
                    <span class="material-icons text-primary fs-6">folder</span> Danh mục tài liệu
                </span>
                <button class="sop-btn-icon" onclick="toggleTreePanel()" title="Ẩn/Hiện Cây Thư Mục">
                    <span class="material-icons">menu_open</span>
                </button>
            </div>
            <ul id="treeRoot" class="sop-tree"></ul>
        </aside>

        <!-- Cột 2: Trình xem PDF -->
        <section class="sop-viewer-panel">
            <div class="sop-viewer-header">
                <div class="sop-viewer-title-group">
                    <button class="sop-btn-icon" id="expandDocBtn" onclick="toggleTreePanel()" style="display:none;" title="Mở danh sách tài liệu">
                        <span class="material-icons">chevron_right</span>
                    </button>
                    <span class="sop-panel-title" id="pdfTitle">
                        <span class="material-icons text-muted fs-6">description</span> Chọn tài liệu để xem
                    </span>
                </div>
                <div id="pdfActions" style="display:none; gap: 8px;">     
                    <button class="app-btn app-btn-primary" id="fullscreenBtn" onclick="openFullscreenModal()" title="Xem toàn màn hình">
                        <span class="material-icons">fullscreen</span> Toàn màn hình
                    </button>     
                </div>
            </div>
            <div class="sop-iframe-container">
                <iframe id="pdfViewer" src=""></iframe>
            </div>
        </section>
    </div>

    <!-- Modal 1: Upload / Chỉnh Sửa File -->
    <div id="uploadModal" class="sop-modal">
        <div class="sop-modal-content">
            <h3 id="modalFormTitle" style="font-size:16px; font-weight:700; margin:0; color:var(--dx-text-main);">Upload File Về Thư Mục Chỉ Định</h3>
            <form id="uploadForm" onsubmit="handleSaveDocument(event)">
                <input type="hidden" id="editDocId" value="">
                <div class="mb-2">
                    <label>Mã tài liệu:</label>
                    <input type="text" id="docCode" class="app-form-control" placeholder="VD: PMW-00243-B" required>
                </div>
                <div class="mb-2">
                    <label>Tên tài liệu:</label>
                    <input type="text" id="docName" class="app-form-control" placeholder="VD: HDCV Vận hành máy đùn" required>
                </div>
                <div class="mb-2">
                    <label>Thư mục chứa:</label>
                    <select id="docCategorySelect" class="app-form-control" required></select>
                </div>
                <div class="mb-3">
                    <label id="fileInputLabel">File PDF:</label>
                    <input type="file" id="docFile" class="app-form-control" accept="application/pdf">
                    <small id="fileHelpText" style="color:var(--dx-text-muted); display:none; font-size:11px; margin-top:3px;">(Để trống nếu không muốn thay đổi file PDF cũ)</small>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:8px;">
                    <button type="button" class="app-btn app-btn-secondary" onclick="closeUploadModal()">Hủy</button>
                    <button type="submit" class="app-btn app-btn-primary">Lưu Dữ Liệu</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: Popup Xác Nhận Xóa Tài Liệu -->
    <div id="deleteConfirmModal" class="sop-modal">
        <div class="sop-modal-content" style="width:380px;">
            <h3 style="font-size:16px; font-weight:700; color:var(--dx-danger); margin:0;">Xác Nhận Xóa Tài Liệu</h3>
            <p style="font-size:13px; color:var(--dx-text-muted); margin:10px 0;" id="deleteConfirmText">Bạn có chắc chắn muốn xóa tài liệu này không?</p>
            <div style="display:flex; justify-content:flex-end; gap:8px;">
                <button type="button" class="app-btn app-btn-secondary" onclick="closeDeleteConfirmModal()">Hủy</button>
                <button type="button" class="app-btn app-btn-danger" onclick="confirmDeleteDocument()">Xóa Vĩnh Viễn</button>
            </div>
        </div>
    </div>

    <!-- Modal 3: Xem Fullscreen -->
    <div id="fullscreenModal" class="sop-fullscreen-modal">
        <div class="sop-fullscreen-header">
            <span id="fullscreenTitle" style="font-weight:700; color:#fff; font-size:14px;">Xem Toàn Màn Hình</span>
            <button class="sop-btn-icon" onclick="closeFullscreenModal()" style="color:#fff;" title="Đóng toàn màn hình">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="sop-fullscreen-body">
            <iframe id="fullscreenViewer" src=""></iframe>
        </div>
    </div>
</div>

<script src="modules/document/tree-config.js?v=3.1"></script>
<script src="modules/document/app.js?v=3.1"></script>