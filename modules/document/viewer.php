<!-- Nhúng thư viện Icon Google -->
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<div class="sop-app-wrapper">
    <!-- Toolbar -->
    <div class="sop-toolbar">
        <div class="sop-toolbar-left">
            <button class="sop-btn-icon" onclick="toggleTreePanel()" title="Ẩn/Hiện Cây Thư Mục">
                <span class="material-icons">menu_open</span>
            </button>
            <div class="sop-search-box">
                <input type="text" id="searchInput" placeholder="Tìm nhanh mã tài liệu, tên HDCV..." onkeyup="renderDocs()">
            </div>
        </div>
        <button class="sop-btn sop-btn-primary" onclick="openUploadModal()">+ Upload File Vào Thư Mục</button>
    </div>

    <!-- Main 3 Columns Container -->
    <div class="sop-main-container">
        <!-- Cột 1: Cây Thư Mục -->
        <aside class="sop-tree-panel" id="treePanel">
            <div class="sop-panel-header">
                <span class="sop-panel-title">CÂY THƯ MỤC</span>
            </div>
            <ul id="treeRoot" class="sop-tree"></ul>
        </aside>

        <!-- Cột 2: Danh sách file -->
        <section class="sop-doc-panel" id="docPanel">
            <div class="sop-panel-header">
                <span class="sop-panel-title" id="currentFolderName">Tất cả tài liệu</span>
                <button class="sop-btn-icon" onclick="toggleDocPanel()" title="Thu gọn danh sách">
                    <span class="material-icons">chevron_left</span>
                </button>
            </div>
            <div id="docList" class="sop-doc-list"></div>
        </section>

        <!-- Cột 3: View PDF Trực Tiếp -->
        <section class="sop-viewer-panel">
            <div class="sop-viewer-header">
                <div class="sop-viewer-title-group">
                    <button class="sop-btn-icon" id="expandDocBtn" onclick="toggleDocPanel()" style="display:none;" title="Mở danh sách tài liệu">
                        <span class="material-icons">chevron_right</span>
                    </button>
                    <span class="sop-panel-title" id="pdfTitle">Chọn tài liệu để xem</span>
                </div>
                <div id="pdfActions" style="display:none; gap: 8px;">
                    <button class="sop-btn sop-btn-warning" onclick="toggleStatus()">Vô Hiệu Hóa / Kích Hoạt</button>
                    <button class="sop-btn sop-btn-danger" onclick="deleteFile()">Xóa File</button>
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
</div>

<script src="/myweb/modules/document/tree-config.js"></script>
<script src="/myweb/modules/document/app.js"></script>

<style>
/* --- Cấu hình chung Module --- */
.sop-app-wrapper {
  display: flex;
  flex-direction: column;
  height: calc(100vh - var(--header-height, 48px) - var(--footer-height, 28px));
  width: 100%;
  background-color: var(--bg-main, #f8fafc);
  box-sizing: border-box;
  overflow: hidden;
}

.sop-app-wrapper * {
  box-sizing: border-box;
}

/* --- Toolbar --- */
.sop-toolbar {
  height: 48px;
  background-color: var(--bg-card, #ffffff);
  border-bottom: 1px solid var(--border-color, #e2e8f0);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 16px;
  flex-shrink: 0;
}

.sop-toolbar-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.sop-search-box input {
  width: 280px;
  padding: 6px 12px;
  border-radius: 6px;
  border: 1px solid var(--border-color, #e2e8f0);
  background-color: var(--bg-main, #f8fafc);
  font-size: 13px;
  outline: none;
}

.sop-search-box input:focus {
  border-color: var(--primary, #2563eb);
  background-color: #fff;
}

/* --- Layout 3 Cột --- */
.sop-main-container {
  display: flex;
  flex: 1;
  height: calc(100% - 48px);
  overflow: hidden;
}

.sop-panel-title {
  font-size: 14px;
  font-weight: 700;
  color: var(--text-main, #0f172a);
}

.sop-panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

/* Cột 1: Cây thư mục */
.sop-tree-panel {
  width: 250px;
  background-color: var(--bg-card, #ffffff);
  border-right: 1px solid var(--border-color, #e2e8f0);
  padding: 14px;
  overflow-y: auto;
  flex-shrink: 0;
  transition: all 0.2s ease;
}

.sop-tree-panel.collapsed {
  width: 0;
  padding: 0;
  border-right: none;
  overflow: hidden;
}

.sop-tree, .sop-tree ul {
  list-style: none;
  padding-left: 12px;
  margin: 0;
}

.sop-folder-header {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 8px;
  border-radius: 6px;
  cursor: pointer;
  color: var(--text-main, #0f172a);
  font-size: 13px;
  font-weight: 500;
}

.sop-folder-header:hover {
  background-color: var(--bg-hover, #f1f5f9);
  color: var(--primary, #2563eb);
}

.sop-folder-header.active-folder {
  background-color: #dbeafe;
  color: var(--primary, #2563eb);
  font-weight: 700;
}

.sop-toggle-icon {
  font-size: 16px !important;
  transition: transform 0.2s ease;
  color: var(--text-muted, #64748b);
}

.sop-folder-node.open > .sop-folder-header .sop-toggle-icon {
  transform: rotate(90deg);
}

.sop-folder-children { display: none; }
.sop-folder-node.open > .sop-folder-children { display: block; }

/* Cột 2: Danh sách file */
.sop-doc-panel {
  width: 300px;
  background-color: var(--bg-card, #ffffff);
  border-right: 1px solid var(--border-color, #e2e8f0);
  padding: 14px;
  overflow-y: auto;
  flex-shrink: 0;
  transition: all 0.2s ease;
}

.sop-doc-panel.collapsed {
  width: 0;
  padding: 0;
  border-right: none;
  overflow: hidden;
}

.sop-doc-card {
  padding: 10px 12px;
  border: 1px solid var(--border-color, #e2e8f0);
  border-radius: 8px;
  background-color: var(--bg-card, #ffffff);
  cursor: pointer;
  margin-bottom: 8px;
  position: relative;
  transition: all 0.15s ease;
}

.sop-doc-card:hover {
  border-color: var(--primary, #2563eb);
  box-shadow: 0 2px 6px rgba(0,0,0,0.04);
}

.sop-doc-card.active-card {
  border-color: var(--primary, #2563eb);
  background-color: #f0f7ff;
  box-shadow: inset 3px 0 0 var(--primary, #2563eb);
}

/* Cột 3: Trình xem PDF */
.sop-viewer-panel {
  flex: 1;
  background-color: var(--bg-main, #f8fafc);
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
  border-radius: 8px;
  border: 1px solid var(--border-color, #e2e8f0);
  overflow: hidden;
}

.sop-iframe-container iframe {
  width: 100%;
  height: 100%;
  border: none;
}

/* Buttons System */
.sop-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  border: none;
}

.sop-btn-primary { background-color: var(--primary, #2563eb); color: #ffffff; }
.sop-btn-warning { background-color: var(--warning, #d97706); color: #ffffff; }
.sop-btn-danger { background-color: var(--danger, #dc2626); color: #ffffff; }

.sop-btn-icon {
  background: transparent;
  border: none;
  color: var(--text-muted, #64748b);
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  display: flex;
  align-items: center;
}

.sop-btn-icon:hover {
  background-color: var(--bg-hover, #f1f5f9);
  color: var(--primary, #2563eb);
}

/* Modal Styling */
.sop-modal {
  display: none;
  position: fixed;
  z-index: 1000;
  top: 0; left: 0;
  width: 100vw; height: 100vh;
  background: rgba(15, 23, 42, 0.4);
  backdrop-filter: blur(2px);
  justify-content: center;
  align-items: center;
}

.sop-modal-content {
  background-color: var(--bg-card, #ffffff);
  padding: 20px;
  border-radius: 10px;
  width: 420px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.1);
  border: 1px solid var(--border-color, #e2e8f0);
}

.sop-modal-content label {
  font-size: 12px;
  font-weight: 600;
  color: var(--text-main, #0f172a);
}

.sop-modal-content input, 
.sop-modal-content select {
  width: 100%;
  padding: 8px;
  border: 1px solid var(--border-color, #e2e8f0);
  border-radius: 6px;
  font-size: 13px;
  background-color: var(--bg-main, #f8fafc);
}
</style>