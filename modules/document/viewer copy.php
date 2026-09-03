<div class="sop-app-wrapper">
    <!-- Sub-header nội bộ của chức năng -->
    <div class="sop-toolbar">
        <div class="sop-search-box">
            <input type="text" id="searchInput" placeholder="Tìm nhanh mã tài liệu, tên HDCV..." onkeyup="filterDocs()">
        </div>
        <button class="sop-btn sop-btn-primary" onclick="openModal()">+ Upload Tài Liệu</button>
    </div>

    <!-- Main Content 3 Cột -->
    <div class="sop-main-container">
        <!-- Cột 1: Cây thư mục -->
        <aside class="sop-tree-panel">
            <h3 class="sop-title">Cây Thư Mục</h3>
            <ul class="sop-tree-view" id="treeView">
                <li>
                    <span class="sop-folder" onclick="toggleFolder(this)">📁 Xưởng Đùn Nhựa</span>
                    <ul class="sop-nested sop-active">
                        <li onclick="filterByCategory('HDCV-Dun')">📄 HDCV Vận Hành</li>
                        <li onclick="filterByCategory('TCCL-Dun')">📄 Tiêu Chuẩn Chất Lượng</li>
                    </ul>
                </li>
                <li>
                    <span class="sop-folder" onclick="toggleFolder(this)">📁 Xưởng Trộn Nguyên Liệu</span>
                    <ul class="sop-nested">
                        <li onclick="filterByCategory('HDCV-Tron')">📄 HDCV Sấy & Trộn</li>
                    </ul>
                </li>
            </ul>
        </aside>

        <!-- Cột 2: Danh sách tài liệu -->
        <section class="sop-doc-list-panel">
            <h3 class="sop-title">Danh Sách Tài Liệu</h3>
            <div id="docList" class="sop-doc-list"></div>
        </section>

        <!-- Cột 3: Trình xem PDF -->
        <section class="sop-viewer-panel">
            <div class="sop-viewer-header">
                <h3 id="currentDocTitle" class="sop-title">Chọn tài liệu để xem</h3>
                <div class="sop-actions" id="adminActions" style="display:none;">
                    <button class="sop-btn sop-btn-warning" onclick="toggleStatus()">Vô Hiệu Hóa / Kích Hoạt</button>
                    <button class="sop-btn sop-btn-danger" onclick="deleteDoc()">Xóa</button>
                </div>
            </div>
            <div class="sop-iframe-container">
                <iframe id="pdfViewer" src="" frameborder="0"></iframe>
            </div>
        </section>
    </div>

    <!-- Modal Upload -->
    <div id="uploadModal" class="sop-modal">
        <div class="sop-modal-content">
            <span class="sop-close" onclick="closeModal()">&times;</span>
            <h2>Upload Tài Liệu Mới</h2>
            <form id="uploadForm" onsubmit="handleUpload(event)">
                <label>Mã tài liệu:</label>
                <input type="text" id="docCode" required placeholder="VD: PMW-00243-B">
                
                <label>Tên tài liệu:</label>
                <input type="text" id="docName" required placeholder="VD: HDCV Vận hành máy in">
                
                <label>Thư mục:</label>
                <select id="docCategory">
                    <option value="HDCV-Dun">Xưởng Đùn - HDCV</option>
                    <option value="TCCL-Dun">Xưởng Đùn - TCCL</option>
                    <option value="HDCV-Tron">Xưởng Trộn - HDCV</option>
                </select>
                
                <label>File PDF:</label>
                <input type="file" id="docFile" accept="application/pdf" required>
                
                <button type="submit" class="sop-btn sop-btn-primary" style="margin-top: 15px;">Lưu & Phát Hành</button>
            </form>
        </div>
    </div>
</div>

    <Style>
       /* =========================================================
   SOP MODULE SCOPED CSS (Loại bỏ triệt để xung đột)
   ========================================================= */

/* Container chính ôm trọn Module SOP */
.sop-app-wrapper {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 70px); /* Chiều cao tự điều chỉnh theo khung layout có sẵn */
    width: 100%;
    font-family: Arial, sans-serif;
    background-color: #f4f6f9;
    box-sizing: border-box;
}

.sop-app-wrapper * {
    box-sizing: border-box;
}

/* Thanh công cụ phụ (Toolbar) */
.sop-app-wrapper .sop-toolbar {
    height: 50px;
    background: #0052cc;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 15px;
    border-bottom: 1px solid #003d99;
}

.sop-app-wrapper .sop-search-box input {
    width: 320px;
    padding: 6px 12px;
    border-radius: 4px;
    border: 1px solid #ccc;
    font-size: 13px;
}

/* Bố cục 3 Cột */
.sop-app-wrapper .sop-main-container {
    display: flex;
    flex: 1;
    overflow: hidden;
}

.sop-app-wrapper .sop-title {
    font-size: 16px;
    font-weight: bold;
    margin-bottom: 12px;
    color: #333;
}

/* Cột 1: Cây thư mục */
.sop-app-wrapper .sop-tree-panel {
    width: 230px;
    background: #ffffff;
    border-right: 1px solid #e0e0e0;
    padding: 15px;
    overflow-y: auto;
}

.sop-app-wrapper .sop-tree-view, 
.sop-app-wrapper .sop-tree-view ul {
    list-style: none;
    padding-left: 12px;
    margin: 0;
}

.sop-app-wrapper .sop-tree-view li {
    margin: 6px 0;
    cursor: pointer;
    font-size: 13px;
    color: #444;
}

.sop-app-wrapper .sop-folder {
    font-weight: 600;
}

.sop-app-wrapper .sop-nested {
    display: none;
}

.sop-app-wrapper .sop-nested.sop-active {
    display: block;
}

/* Cột 2: Danh sách tài liệu */
.sop-app-wrapper .sop-doc-list-panel {
    width: 320px;
    background: #ffffff;
    border-right: 1px solid #e0e0e0;
    padding: 15px;
    overflow-y: auto;
}

.sop-app-wrapper .sop-doc-card {
    padding: 10px 12px;
    border: 1px solid #e0e0e0;
    border-radius: 5px;
    margin-bottom: 8px;
    cursor: pointer;
    background: #fff;
    transition: background 0.2s, border-color 0.2s;
}

.sop-app-wrapper .sop-doc-card:hover {
    border-color: #0052cc;
    background: #f0f7ff;
}

.sop-app-wrapper .sop-doc-card.inactive {
    opacity: 0.55;
    background: #f8f9fa;
}

.sop-app-wrapper .sop-badge {
    display: inline-block;
    padding: 2px 6px;
    font-size: 10px;
    border-radius: 3px;
    color: white;
    float: right;
    font-weight: bold;
}

.sop-app-wrapper .sop-badge-active { background: #28a745; }
.sop-app-wrapper .sop-badge-inactive { background: #dc3545; }

/* Cột 3: Trình xem PDF */
.sop-app-wrapper .sop-viewer-panel {
    flex: 1;
    background: #ebecf0;
    padding: 15px;
    display: flex;
    flex-direction: column;
}

.sop-app-wrapper .sop-viewer-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.sop-app-wrapper .sop-iframe-container {
    flex: 1;
    background: #ffffff;
    border-radius: 4px;
    border: 1px solid #dcdcdc;
    overflow: hidden;
}

.sop-app-wrapper iframe {
    width: 100%;
    height: 100%;
    border: none;
}

/* Nút bấm (Buttons) */
.sop-app-wrapper .sop-btn {
    border: none;
    padding: 6px 14px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
}

.sop-app-wrapper .sop-btn-primary { background: #0065ff; color: #fff; }
.sop-app-wrapper .sop-btn-primary:hover { background: #0052cc; }
.sop-app-wrapper .sop-btn-warning { background: #ffab00; color: #fff; margin-right: 5px; }
.sop-app-wrapper .sop-btn-danger { background: #ff5630; color: #fff; }

/* Modal Upload */
.sop-app-wrapper .sop-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.4);
    justify-content: center;
    align-items: center;
}

.sop-app-wrapper .sop-modal-content {
    background: #ffffff;
    padding: 20px 25px;
    border-radius: 6px;
    width: 380px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.sop-app-wrapper .sop-modal-content label {
    font-size: 12px;
    font-weight: bold;
    color: #555;
    margin-top: 5px;
}

.sop-app-wrapper .sop-modal-content input,
.sop-app-wrapper .sop-modal-content select {
    padding: 6px 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 13px;
}

.sop-app-wrapper .sop-close {
    align-self: flex-end;
    cursor: pointer;
    font-size: 18px;
    color: #888;
}

    </Style>

    <script>
        // Dữ liệu mẫu ban đầu
let documents = [
    {
        id: "PMW-00243-B",
        title: "HDCV Vận hành máy in đùn nhựa",
        category: "HDCV-Dun",
        url: "https://pdfobject.com/pdf/sample.pdf",
        status: "Active"
    },
    {
        id: "FEWPM-00244-A",
        title: "HDCV Sử dụng máy laser đo đường kính",
        category: "HDCV-Dun",
        url: "https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf",
        status: "Active"
    },
    {
        id: "PMW-00416-01-B",
        title: "HDCV Máy sấy nguyên liệu trộn màu 30AS",
        category: "HDCV-Tron",
        url: "https://pdfobject.com/pdf/sample.pdf",
        status: "Inactive"
    }
];

let selectedDocId = null;

// Khởi tạo trang
document.addEventListener("DOMContentLoaded", () => {
    renderDocList(documents);
});

// Render danh sách tài liệu ra Cột 2
function renderDocList(data) {
    const docListEl = document.getElementById("docList");
    docListEl.innerHTML = "";

    data.forEach(doc => {
        const card = document.createElement("div");
        card.className = `sop-doc-card ${doc.status === 'Inactive' ? 'inactive' : ''}`;
        card.onclick = () => selectDoc(doc.id);
        
        card.innerHTML = `
            <span class="sop-badge ${doc.status === 'Active' ? 'sop-badge-active' : 'sop-badge-inactive'}">${doc.status}</span>
            <strong>${doc.id}</strong>
            <p style="font-size: 13px; margin-top: 5px; color: #555; margin-bottom:0;">${doc.title}</p>
        `;
        docListEl.appendChild(card);
    });
}

// Khi chọn 1 tài liệu -> Xem ở Cột 3
function selectDoc(id) {
    selectedDocId = id;
    const doc = documents.find(d => d.id === id);
    if (!doc) return;

    document.getElementById("currentDocTitle").innerText = `${doc.id} - ${doc.title}`;
    document.getElementById("pdfViewer").src = doc.url;
    document.getElementById("adminActions").style.display = "block";
}

// Tìm kiếm nhanh Real-time
function filterDocs() {
    const query = document.getElementById("searchInput").value.toLowerCase();
    const filtered = documents.filter(doc => 
        doc.id.toLowerCase().includes(query) || doc.title.toLowerCase().includes(query)
    );
    renderDocList(filtered);
}

// Lọc theo cây thư mục
function filterByCategory(cat) {
    const filtered = documents.filter(doc => doc.category === cat);
    renderDocList(filtered);
}

// Đóng/Mở thư mục
function toggleFolder(element) {
    element.nextElementSibling.classList.toggle("active");
}

// Vô hiệu hóa hoặc Kích hoạt lại tài liệu
function toggleStatus() {
    if (!selectedDocId) return;
    const doc = documents.find(d => d.id === selectedDocId);
    if (doc) {
        doc.status = doc.status === "Active" ? "Inactive" : "Active";
        renderDocList(documents);
        selectDoc(selectedDocId);
    }
}

// Xóa tài liệu
function deleteDoc() {
    if (!selectedDocId) return;
    if (confirm("Bạn có chắc chắn muốn xóa tài liệu này?")) {
        documents = documents.filter(d => d.id !== selectedDocId);
        document.getElementById("pdfViewer").src = "";
        document.getElementById("currentDocTitle").innerText = "Chọn tài liệu để xem";
        document.getElementById("adminActions").style.display = "none";
        renderDocList(documents);
    }
}

// Modal Upload Controls
function openModal() { document.getElementById("uploadModal").style.display = "flex"; }
function closeModal() { document.getElementById("uploadModal").style.display = "none"; }

// Thêm mới tài liệu (Upload)
function handleUpload(event) {
    event.preventDefault();
    const code = document.getElementById("docCode").value;
    const name = document.getElementById("docName").value;
    const category = document.getElementById("docCategory").value;
    const fileInput = document.getElementById("docFile");

    if (fileInput.files.length === 0) return;

    const file = fileInput.files[0];
    const fileURL = URL.createObjectURL(file); // Tạo Blob URL tạm thời để preview PDF

    const newDoc = {
        id: code,
        title: name,
        category: category,
        url: fileURL,
        status: "Active"
    };

    documents.unshift(newDoc);
    renderDocList(documents);
    closeModal();
    document.getElementById("uploadForm").reset();
    selectDoc(newDoc.id);
}
    </script>