let allDocuments = [];
let currentDoc = null;

// Hàm bảo vệ chống lỗi nếu hàm kiểm tra quyền chưa nạp
if (typeof hasPermission !== 'function') {
    window.hasPermission = function(code) {
        return window.CURRENT_USER_PERMISSIONS && Array.isArray(window.CURRENT_USER_PERMISSIONS)
            ? window.CURRENT_USER_PERMISSIONS.includes(code)
            : true;
    };
}

document.addEventListener("DOMContentLoaded", () => {
    applyPermissionUI();
    initTree();
    fetchDocuments();
});

// Điều khiển ẩn/hiện nút Upload theo quyền
function applyPermissionUI() {
    const btnUpload = document.getElementById("btnUpload");
    if (btnUpload) {
        btnUpload.style.display = hasPermission("document.upload") ? "inline-flex" : "none";
    }
}

function initTree() {
    const treeRoot = document.getElementById("treeRoot");
    const folderSelect = document.getElementById("docCategorySelect") || document.getElementById("folderSelect");
    
    if (typeof MANUAL_TREE_DATA === 'undefined' || !treeRoot) return;

    treeRoot.innerHTML = "";
    if (folderSelect) {
        folderSelect.innerHTML = '<option value="">-- Chọn thư mục --</option>';
    }

    renderTreeNodes(MANUAL_TREE_DATA, treeRoot, folderSelect, 0);
}

// Gọi API lấy danh sách file
async function fetchDocuments() {
    try {
        const res = await fetch('/myweb/api/get_documents.php');
        allDocuments = await res.json();
        initTree();
    } catch (error) {
        console.error("Lỗi nạp tài liệu:", error);
    }
}

// Vẽ Cây Thư Mục & File Lồng Nhau
function renderTreeNodes(nodes, parentEl, selectEl, level) {
    nodes.forEach(node => {
        const li = document.createElement("li");
        li.className = "sop-folder-node";
        
        const folderDocs = allDocuments.filter(doc => doc.folder_id === node.id);
        const hasSubFolders = node.children && node.children.length > 0;
        const hasContent = hasSubFolders || folderDocs.length > 0;
        
        li.innerHTML = `
            <div class="sop-folder-header" onclick="handleFolderClick(event, this)">
                ${hasContent ? '<span class="material-icons sop-toggle-icon">chevron_right</span>' : '<span style="width:16px;"></span>'}
                <span>📁 ${node.name}</span>
            </div>
        `;
        
        if (selectEl) {
            const option = document.createElement("option");
            option.value = node.id;
            option.textContent = "—".repeat(level) + " " + node.name;
            selectEl.appendChild(option);
        }

        const ul = document.createElement("ul");
        ul.className = "sop-folder-children";

        if (hasSubFolders) {
            renderTreeNodes(node.children, ul, selectEl, level + 1);
        }

        folderDocs.forEach(doc => {
            const docLi = document.createElement("li");
            docLi.className = "sop-tree-file-item";
            docLi.setAttribute("data-search-text", `${doc.doc_code} ${doc.title}`.toLowerCase());
            
            docLi.onclick = (e) => {
                e.stopPropagation();
                previewDoc(doc, docLi);
            };
            
            docLi.innerHTML = `
                <span class="material-icons" style="font-size:15px; color:var(--primary, #2563eb);">description</span>
                <span title="${doc.title}">${doc.doc_code} - ${doc.title}</span>
            `;
            ul.appendChild(docLi);
        });

        if (hasContent) li.appendChild(ul);
        parentEl.appendChild(li);
    });
}

function handleFolderClick(event, element) {
    event.stopPropagation();
    const parentLi = element.closest('.sop-folder-node');
    if (parentLi) parentLi.classList.toggle('open');
}

// Chọn File PDF & Bật nút Chỉnh Sửa / Xóa theo Phân Quyền
function previewDoc(doc, element) {
    currentDoc = doc;
    
    document.querySelectorAll('.sop-tree-file-item').forEach(el => el.classList.remove('active-tree-file'));
    if (element) element.classList.add('active-tree-file');

    const titleEl = document.getElementById("pdfTitle");
    if (titleEl) titleEl.innerText = `${doc.doc_code} - ${doc.title}`;
    
    const pdfIframe = document.getElementById("pdfViewer");
    if (pdfIframe) pdfIframe.src = doc.file_path + "#toolbar=1&navpanes=0";

    const actions = document.getElementById("pdfActions");
    if (actions) actions.style.display = "flex";

    // Kích hoạt hiển thị nút Chỉnh Sửa & Xóa
    const btnEdit = document.getElementById("btnEdit");
    const btnDelete = document.getElementById("btnDelete");
    
    if (btnEdit) btnEdit.style.display = hasPermission("document.edit") ? "inline-flex" : "none";
    if (btnDelete) btnDelete.style.display = hasPermission("document.delete") ? "inline-flex" : "none";
}

// --- QUẢN LÝ POPUP UPLOAD & CHỈNH SỬA ---

function openUploadModal() {
    document.getElementById("editDocId").value = "";
    document.getElementById("modalFormTitle").innerText = "Upload File Về Thư Mục Chỉ Định";
    document.getElementById("docCode").value = "";
    document.getElementById("docName").value = "";
    document.getElementById("docFile").required = true;
    document.getElementById("fileHelpText").style.display = "none";
    
    const modal = document.getElementById("uploadModal");
    if (modal) modal.style.display = "flex";
}

function openEditModal() {
    if (!currentDoc) return;

    document.getElementById("editDocId").value = currentDoc.id;
    document.getElementById("modalFormTitle").innerText = "Chỉnh Sửa Thông Tin Tài Liệu";
    document.getElementById("docCode").value = currentDoc.doc_code;
    document.getElementById("docName").value = currentDoc.title;
    
    const folderSelect = document.getElementById("docCategorySelect");
    if (folderSelect) folderSelect.value = currentDoc.folder_id;
    
    document.getElementById("docFile").required = false;
    document.getElementById("fileHelpText").style.display = "block";

    const modal = document.getElementById("uploadModal");
    if (modal) modal.style.display = "flex";
}

function closeUploadModal() {
    const modal = document.getElementById("uploadModal");
    if (modal) modal.style.display = "none";
}

// Xử lý gửi Form (Dùng chung cho cả Thêm Mới và Chỉnh Sửa)
async function handleSaveDocument(e) {
    e.preventDefault();
    const editId = document.getElementById("editDocId").value;
    const isEdit = editId !== "";

    const fileInput = document.getElementById("docFile");
    const folderSelect = document.getElementById("docCategorySelect");
    const docCodeInput = document.getElementById("docCode");
    const docTitleInput = document.getElementById("docName");

    const formData = new FormData();
    formData.append("id", editId);
    formData.append("folder_id", folderSelect ? folderSelect.value : "");
    formData.append("doc_code", docCodeInput ? docCodeInput.value : "");
    formData.append("title", docTitleInput ? docTitleInput.value : "");
    
    if (fileInput.files.length > 0) {
        formData.append("file", fileInput.files[0]);
    }

    const apiUrl = isEdit ? '/myweb/api/edit_document.php' : '/myweb/api/upload_document.php';

    try {
        const res = await fetch(apiUrl, { method: 'POST', body: formData });
        const result = await res.json();
        
        if (result.success) {
            closeUploadModal();
            fetchDocuments();
            
            if (isEdit && currentDoc && currentDoc.id === editId) {
                previewDoc(result.data, null);
            }
        } else {
            alert(result.message || "Lỗi xử lý dữ liệu!");
        }
    } catch (error) {
        console.error("Lỗi:", error);
    }
}

// --- QUẢN LÝ POPUP XÓA TÀI LIỆU ---

function openDeleteConfirmModal() {
    if (!currentDoc) return;
    
    const confirmText = document.getElementById("deleteConfirmText");
    if (confirmText) {
        confirmText.innerText = `Bạn có chắc chắn muốn xóa tài liệu [${currentDoc.doc_code}] "${currentDoc.title}" không? Hành động này không thể hoàn tác.`;
    }
    
    const modal = document.getElementById("deleteConfirmModal");
    if (modal) modal.style.display = "flex";
}

function closeDeleteConfirmModal() {
    const modal = document.getElementById("deleteConfirmModal");
    if (modal) modal.style.display = "none";
}

async function confirmDeleteDocument() {
    if (!currentDoc) return;

    const formData = new FormData();
    formData.append("id", currentDoc.id);

    try {
        const res = await fetch('/myweb/api/delete_document.php', { method: 'POST', body: formData });
        const result = await res.json();

        if (result.success) {
            closeDeleteConfirmModal();
            
            // Reset giao diện xem file
            document.getElementById("pdfViewer").src = "";
            document.getElementById("pdfTitle").innerText = "Chọn tài liệu để xem";
            document.getElementById("pdfActions").style.display = "none";
            document.getElementById("btnEdit").style.display = "none";
            document.getElementById("btnDelete").style.display = "none";
            currentDoc = null;

            fetchDocuments();
        } else {
            alert(result.message || "Lỗi khi xóa file!");
        }
    } catch (error) {
        console.error("Lỗi xóa file:", error);
    }
}

// --- CÁC HÀM BỔ TRỢ GIAO DIỆN ---

function openFullscreenModal() {
    if (!currentDoc) return;
    const modal = document.getElementById("fullscreenModal");
    const fsViewer = document.getElementById("fullscreenViewer");
    const fsTitle = document.getElementById("fullscreenTitle");
    
    if (modal && fsViewer) {
        fsTitle.innerText = `${currentDoc.doc_code} - ${currentDoc.title}`;
        fsViewer.src = currentDoc.file_path + "#toolbar=1&navpanes=0";
        modal.style.display = "flex";
    }
}

function closeFullscreenModal() {
    const modal = document.getElementById("fullscreenModal");
    const fsViewer = document.getElementById("fullscreenViewer");
    if (modal) {
        modal.style.display = "none";
        if (fsViewer) fsViewer.src = "";
    }
}

function toggleTreePanel() {
    const treePanel = document.getElementById("treePanel");
    if (treePanel) treePanel.classList.toggle("collapsed");
}

function filterTreeAndDocs() {
    const searchInput = document.getElementById("searchInput");
    if (!searchInput) return;

    const search = searchInput.value.toLowerCase().trim();
    const fileItems = document.querySelectorAll('.sop-tree-file-item');

    fileItems.forEach(item => {
        const text = item.getAttribute("data-search-text");
        if (search === "" || (text && text.includes(search))) {
            item.style.display = "flex";
            if (search !== "") {
                let parentNode = item.closest('.sop-folder-node');
                while (parentNode) {
                    parentNode.classList.add('open');
                    parentNode = parentNode.parentElement.closest('.sop-folder-node');
                }
            }
        } else {
            item.style.display = "none";
        }
    });
}