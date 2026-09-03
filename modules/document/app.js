let allDocuments = [];
let selectedFolderId = null;

document.addEventListener("DOMContentLoaded", () => {
    initTree();
    fetchDocuments();
});

function initTree() {
    const treeRoot = document.getElementById("treeRoot");
    const folderSelect = document.getElementById("folderSelect") || document.getElementById("docCategorySelect");
    
    // Kiểm tra nếu chưa nạp được dữ liệu từ tree-config.js
    if (typeof MANUAL_TREE_DATA === 'undefined') {
        console.error("Lỗi: Chưa nạp được file tree-config.js!");
        return;
    }

    if (!treeRoot) {
        console.error("Lỗi: Không tìm thấy thẻ <ul id='treeRoot'> trong HTML!");
        return;
    }

    treeRoot.innerHTML = "";
    if (folderSelect) {
        folderSelect.innerHTML = '<option value="">-- Chọn thư mục --</option>';
    }

    renderTreeNodes(MANUAL_TREE_DATA, treeRoot, folderSelect, 0);
}

function renderTreeNodes(nodes, parentEl, selectEl, level) {
    nodes.forEach(node => {
        // Tạo thẻ danh sách cho cây thư mục
        const li = document.createElement("li");
        li.style.listStyle = "none";
        li.style.margin = "5px 0";
        li.style.cursor = "pointer";
        
        li.innerHTML = `<span onclick="selectFolder('${node.id}', '${node.name}')">${node.name}</span>`;
        
        // Tạo option cho ô chọn thư mục khi upload
        if (selectEl) {
            const option = document.createElement("option");
            option.value = node.id;
            option.textContent = "—".repeat(level) + " " + node.name;
            selectEl.appendChild(option);
        }

        // Đệ quy nếu có thư mục con
        if (node.children && node.children.length > 0) {
            const ul = document.createElement("ul");
            ul.style.paddingLeft = "15px";
            renderTreeNodes(node.children, ul, selectEl, level + 1);
            li.appendChild(ul);
        }
        parentEl.appendChild(li);
    });
}

// Gọi API PHP lấy danh sách file
async function fetchDocuments() {
    const res = await fetch('/myweb/api/get_documents.php');
    allDocuments = await res.json();
    renderDocs();
}

function selectFolder(folderId, folderName) {
    selectedFolderId = folderId;
    document.getElementById("currentFolderName").innerText = folderName;
    renderDocs();
}

// Render thẻ file ở Cột 2
function renderDocs() {
    const searchInput = document.getElementById("searchInput");
    const search = searchInput ? searchInput.value.toLowerCase() : "";
    const docList = document.getElementById("docList");
    if (!docList) return;

    docList.innerHTML = "";

    const filtered = allDocuments.filter(doc => {
        const matchFolder = selectedFolderId ? doc.folder_id === selectedFolderId : true;
        const matchSearch = doc.title.toLowerCase().includes(search) || doc.doc_code.toLowerCase().includes(search);
        return matchFolder && matchSearch;
    });

    filtered.forEach(doc => {
        const card = document.createElement("div");
        const isActive = doc.status === 'Active';
        
        card.className = `sop-doc-card ${!isActive ? 'inactive' : ''}`;
        card.onclick = () => previewDoc(doc, card);

        card.innerHTML = `
            <span class="sop-badge ${isActive ? 'sop-badge-active' : 'sop-badge-inactive'}">${doc.status}</span>
            <span class="sop-doc-code">${doc.doc_code}</span>
            <div class="sop-doc-name">${doc.title}</div>
        `;
        docList.appendChild(card);
    });
}

// Mở trực tiếp file PDF ở Cột 3
function previewDoc(doc, cardElement) {
    currentDoc = doc;
    
    // Highlight thẻ đang chọn
    document.querySelectorAll('.sop-doc-card').forEach(el => el.classList.remove('active-card'));
    if(cardElement) cardElement.classList.add('active-card');

    // Cập nhật tiêu đề & Viewer
    document.getElementById("pdfTitle").innerText = `${doc.doc_code} - ${doc.title}`;
    
    // Đưa đường dẫn file PDF vào Iframe (Thêm param xem chuẩn toolbar)
    const pdfIframe = document.getElementById("pdfViewer");
    pdfIframe.src = doc.file_path + "#toolbar=1&navpanes=0";

    // Hiển thị cụm nút Admin Action
    const actions = document.getElementById("pdfActions");
    if (actions) actions.style.display = "flex";
}

// Gọi API PHP Upload File
async function handleUpload(e) {
    e.preventDefault();
    
    // Tìm phần tử file input (thử cả 2 ID phổ biến)
    const fileInput = document.getElementById("docFile") || document.getElementById("fileInput");
    const folderSelect = document.getElementById("docCategorySelect") || document.getElementById("folderSelect");
    const docCodeInput = document.getElementById("docCode");
    const docTitleInput = document.getElementById("docName") || document.getElementById("docTitle");

    // Kiểm tra nếu không tìm thấy input file
    if (!fileInput) {
        alert("Lỗi: Không tìm thấy ô chọn file PDF trong giao diện!");
        return;
    }

    if (fileInput.files.length === 0) {
        alert("Vui lòng chọn 1 file PDF để upload!");
        return;
    }

    const formData = new FormData();
    formData.append("folder_id", folderSelect ? folderSelect.value : "");
    formData.append("doc_code", docCodeInput ? docCodeInput.value : "");
    formData.append("title", docTitleInput ? docTitleInput.value : "");
    formData.append("file", fileInput.files[0]);

    try {
        const res = await fetch('/myweb/api/upload_document.php', { 
            method: 'POST', 
            body: formData 
        });
        const result = await res.json();
        
        if (result.success) {
            closeUploadModal();
            fetchDocuments();
        } else {
            alert(result.message || "Lỗi khi lưu file!");
        }
    } catch (error) {
        console.error("Lỗi upload:", error);
    }
}

// Bổ sung các hàm đóng/mở Modal Upload
function openUploadModal() {
    const modal = document.getElementById("uploadModal");
    if (modal) {
        if (typeof selectedFolderId !== 'undefined' && selectedFolderId) {
            const folderSelect = document.getElementById("folderSelect");
            if (folderSelect) folderSelect.value = selectedFolderId;
        }
        modal.style.display = "flex";
    }
}

function closeUploadModal() {
    const modal = document.getElementById("uploadModal");
    if (modal) {
        modal.style.display = "none";
    }
}

// Nếu trong HTML đặt tên nút đóng là closeModal(), gán lại để tránh lỗi
function closeModal() {
    closeUploadModal();
}

// Render Cây thư mục có thể cuộn thu gọn/mở rộng
function renderTreeNodes(nodes, parentEl, selectEl, level) {
    nodes.forEach(node => {
        const li = document.createElement("li");
        li.className = "sop-folder-node";
        
        const hasChildren = node.children && node.children.length > 0;
        
        li.innerHTML = `
            <div class="sop-folder-header" id="folder-head-${node.id}" onclick="handleFolderClick(event, '${node.id}', '${node.name}', this)">
                ${hasChildren ? '<span class="material-icons sop-toggle-icon">chevron_right</span>' : '<span style="width:16px;"></span>'}
                <span>📁 ${node.name}</span>
            </div>
        `;
        
        // Option cho Modal Upload
        if (selectEl) {
            const option = document.createElement("option");
            option.value = node.id;
            option.textContent = "—".repeat(level) + " " + node.name;
            selectEl.appendChild(option);
        }

        if (hasChildren) {
            const ul = document.createElement("ul");
            ul.className = "sop-folder-children";
            renderTreeNodes(node.children, ul, selectEl, level + 1);
            li.appendChild(ul);
        }
        
        parentEl.appendChild(li);
    });
}

// Xử lý Click thư mục: Đóng/Mở thư mục con & Highlight vị trí chọn
function handleFolderClick(event, folderId, folderName, element) {
    event.stopPropagation();
    
    // Toggle trạng thái mở thư mục con
    const parentLi = element.closest('.sop-folder-node');
    if (parentLi) {
        parentLi.classList.toggle('open');
    }

    // Highlight vị trí đang chọn ở Cây thư mục
    document.querySelectorAll('.sop-folder-header').forEach(el => el.classList.remove('active-folder'));
    element.classList.add('active-folder');

    // Filter file ở Cột 2
    selectFolder(folderId, folderName);
}

// Thu gọn / Mở rộng Cột 1 (Cây Thư Mục)
function toggleTreePanel() {
    const treePanel = document.getElementById("treePanel");
    if (treePanel) {
        treePanel.classList.toggle("collapsed");
    }
}

// Thu gọn / Mở rộng Cột 2 (Danh sách tài liệu) để xem PDF tối đa
function toggleDocPanel() {
    const docPanel = document.getElementById("docPanel");
    const expandBtn = document.getElementById("expandDocBtn");
    
    if (docPanel) {
        docPanel.classList.toggle("collapsed");
        const isCollapsed = docPanel.classList.contains("collapsed");
        if (expandBtn) {
            expandBtn.style.display = isCollapsed ? "inline-flex" : "none";
        }
    }
}