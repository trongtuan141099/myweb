<?php
// Nhúng Core Phân Quyền dùng chung
require_once __DIR__ . '/../../core/check_permission.php';

// Kiểm tra quyền xem trang
if (!hasPermission('document.view')) {
    die("Bạn không có quyền truy cập module Tài liệu!");
}
?>

<!-- Nhúng Script phân quyền sang JS -->
<?php renderPermissionScript(); ?>

<!-- Nhúng thư viện Icon Google -->
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<div class="sop-app-wrapper">
    <!-- Toolbar -->
    <div class="sop-toolbar">
        <div class="sop-toolbar-left">
            <div class="sop-search-box">
                <input type="text" id="searchInput" placeholder="Tìm nhanh mã, tên tài liệu..." onkeyup="filterTreeAndDocs()">
            </div>
        </div>
        <div class="sop-toolbar-right">
          <button class="sop-btn sop-btn-primary" onclick="openUploadModal()">+ Upload File</button>
          <button class="sop-btn sop-btn-danger" onclick="deleteFile()">Xóa File</button>
        </div>

    </div>

    <!-- Main Container -->
    <div class="sop-main-container">
        <!-- Cột 1: Cây Thư Mục -->
        <aside class="sop-tree-panel" id="treePanel">
            <div class="sop-panel-header">
                <span class="sop-panel-title">Danh mục tài liệu</span>
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
                    <span class="sop-panel-title" id="pdfTitle">Chọn tài liệu để xem</span>
                </div>
                <div id="pdfActions" style="display:none; gap: 8px;">     
                    <!-- Nút xem Popup Toàn màn hình -->
                    <button class="sop-btn sop-btn-primary" id="fullscreenBtn" onclick="openFullscreenModal()"  title="Xem toàn màn hình">
                        Xem toàn màn hình<span class="material-icons">fullscreen</span>
                    </button>     
                    <!-- <button class="sop-btn sop-btn-warning" onclick="toggleStatus()">Vô Hiệu Hóa / Kích Hoạt</button> -->
                </div>
            </div>
            <div class="sop-iframe-container">
                <iframe id="pdfViewer" src=""></iframe>
            </div>
        </section>
    </div>

    <!-- Modal Upload -->
    <div id="uploadModal" class="sop-modal">
        <div class="sop-modal-content">
            <h3 style="font-size:15px; font-weight:700;">Upload File Về Thư Mục Chỉ Định</h3>
            <form id="uploadForm" onsubmit="handleUpload(event)">
                <div>
                    <label>Mã tài liệu:</label>
                    <input type="text" id="docCode" placeholder="VD: PMW-00243-B" required>
                </div>
                <div>
                    <label>Tên tài liệu:</label>
                    <input type="text" id="docName" placeholder="VD: HDCV Vận hành máy đùn" required>
                </div>
                <div>
                    <label>Thư mục chứa:</label>
                    <select id="docCategorySelect" required></select>
                </div>
                <div>
                    <label>File PDF:</label>
                    <input type="file" id="docFile" accept="application/pdf" required>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:10px;">
                    <button type="button" class="sop-btn" style="background:#e2e8f0; color:#333;" onclick="closeUploadModal()">Hủy</button>
                    <button type="submit" class="sop-btn sop-btn-primary">Lưu File</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Xem PDF Fullscreen Popup -->
    <div id="fullscreenModal" class="sop-fullscreen-modal">
        <div class="sop-fullscreen-header">
            <span id="fullscreenTitle" style="font-weight:700; color:#fff;">Xem Toàn Màn Hình</span>
            <button class="sop-btn-icon" onclick="closeFullscreenModal()" style="color:#fff;" title="Đóng toàn màn hình">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="sop-fullscreen-body">
            <iframe id="fullscreenViewer" src=""></iframe>
        </div>
    </div>
</div>

<script src="/myweb/modules/document/tree-config.js?v=2.1"></script>
<script src="/myweb/modules/document/app.js?v=2.1"></script>

<style>
.sop-app-wrapper {
  display: flex; flex-direction: column;
  height: calc(100vh - var(--header-height, 48px) - var(--footer-height, 28px));
  width: 100%; background-color: var(--bg-main, #f8fafc);
  box-sizing: border-box; overflow: hidden;
}
.sop-app-wrapper * { box-sizing: border-box; }

/* Toolbar */
.sop-toolbar {
  height: 48px; background-color: var(--bg-card, #ffffff);
  border-bottom: 1px solid var(--border-color, #e2e8f0);
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 16px; flex-shrink: 0;
}
.sop-toolbar-left { display: flex; align-items: center; gap: 12px; }
.sop-search-box input {
  width: 280px; padding: 6px 12px; border-radius: 6px;
  border: 1px solid var(--border-color, #e2e8f0);
  background-color: var(--bg-main, #f8fafc); font-size: 13px; outline: none;
}
.sop-search-box input:focus { border-color: var(--primary, #2563eb); background-color: #fff; }

/* Main Container 2 Cột */
.sop-main-container { display: flex; flex: 1; height: calc(100% - 48px); overflow: hidden; }
.sop-panel-title { font-size: 14px; font-weight: 700; color: var(--text-main, #0f172a); }
.sop-panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }

/* Cột 1: Cây Thư Mục & File */
.sop-tree-panel {
  width: 300px; background-color: var(--bg-card, #ffffff);
  border-right: 1px solid var(--border-color, #e2e8f0);
  padding: 14px; overflow-y: auto; flex-shrink: 0; transition: all 0.2s ease;
}
.sop-tree-panel.collapsed { width: 0; padding: 0; border-right: none; overflow: hidden; }

.sop-tree, .sop-tree ul { list-style: none; padding-left: 12px; margin: 0; }

.sop-folder-header {
  display: flex; align-items: center; gap: 6px; padding: 6px 8px;
  border-radius: 6px; cursor: pointer; color: var(--text-main, #0f172a);
  font-size: 13px; font-weight: 600;
}
.sop-folder-header:hover { background-color: var(--bg-hover, #f1f5f9); color: var(--primary, #2563eb); }
.sop-folder-header.active-folder { background-color: #dbeafe; color: var(--primary, #2563eb); }

.sop-toggle-icon { font-size: 16px !important; transition: transform 0.2s ease; color: var(--text-muted, #64748b); }
.sop-folder-node.open > .sop-folder-header .sop-toggle-icon { transform: rotate(90deg); }

.sop-folder-children { display: none; }
.sop-folder-node.open > .sop-folder-children { display: block; }

/* File lồng trong cây */
.sop-tree-file-item {
  display: flex; align-items: center; gap: 6px; padding: 5px 8px 5px 22px;
  border-radius: 4px; font-size: 12px; color: var(--text-muted, #64748b);
  cursor: pointer; transition: all 0.15s ease; margin: 2px 0;
}
.sop-tree-file-item:hover { background-color: var(--bg-hover, #f1f5f9); color: var(--primary, #2563eb); }
.sop-tree-file-item.active-tree-file {
  background-color: #f0f7ff; color: var(--primary, #2563eb);
  font-weight: 600; border-left: 3px solid var(--primary, #2563eb);
}

/* Cột 2: Trình xem PDF Tối Đa Area */
.sop-viewer-panel {
  flex: 1; background-color: var(--bg-main, #f8fafc);
  padding: 12px; display: flex; flex-direction: column; height: 100%;
}
.sop-viewer-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.sop-iframe-container {
  flex: 1; background-color: #323639; border-radius: 8px;
  border: 1px solid var(--border-color, #e2e8f0); overflow: hidden;
}
.sop-iframe-container iframe { width: 100%; height: 100%; border: none; }

/* Buttons & Modal */
.sop-btn { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; border: none; }
.sop-btn-primary { background-color: var(--primary, #2563eb); color: #ffffff; }
.sop-btn-warning { background-color: var(--warning, #d97706); color: #ffffff; }
.sop-btn-danger { background-color: var(--danger, #dc2626); color: #ffffff; }

.sop-btn-icon { background: transparent; border: none; color: var(--text-muted, #64748b); cursor: pointer; padding: 4px; border-radius: 4px; display: flex; align-items: center; }
.sop-btn-icon:hover { background-color: var(--bg-hover, #f1f5f9); color: var(--primary, #2563eb); }

.sop-modal { display: none; position: fixed; z-index: 1000; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(2px); justify-content: center; align-items: center; }
.sop-modal-content { background-color: var(--bg-card, #ffffff); padding: 20px; border-radius: 10px; width: 420px; display: flex; flex-direction: column; gap: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: 1px solid var(--border-color, #e2e8f0); }
.sop-modal-content label { font-size: 12px; font-weight: 600; color: var(--text-main, #0f172a); }
.sop-modal-content input, .sop-modal-content select { width: 100%; padding: 8px; border: 1px solid var(--border-color, #e2e8f0); border-radius: 6px; font-size: 13px; background-color: var(--bg-main, #f8fafc); }
/* --- Popup Fullscreen PDF Modal --- */
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