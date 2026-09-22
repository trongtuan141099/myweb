/**
 * DX Plastic Group - Document Management Master Application (v4.0)
 * Hỗ trợ: Duyệt cây thư mục động, CRUD thư mục, Xem tài liệu dạng Thẻ và Trình xem PDF.
 */

let allFolders = [];
let allDocuments = [];
let selectedFolderId = null; // null = tất cả tài liệu
let currentDoc = null;
let currentViewMode = 'explorer'; // 'explorer' hoặc 'pdf'
let searchKeyword = '';

// Hàm bảo vệ chống lỗi nếu chưa nạp hàm hasPermission
if (typeof hasPermission !== 'function') {
    window.hasPermission = function(code) {
        return window.CURRENT_USER_PERMISSIONS && Array.isArray(window.CURRENT_USER_PERMISSIONS)
            ? window.CURRENT_USER_PERMISSIONS.includes(code)
            : true;
    };
}

document.addEventListener("DOMContentLoaded", () => {
    initDocumentApp();
});

async function initDocumentApp() {
    await loadFolders();
    await fetchDocuments();
    selectRootFolder();
}

// --------------------------------------------------------------------------
// 1. QUẢN LÝ DỮ LIỆU THƯ MỤC (FOLDERS)
// --------------------------------------------------------------------------

async function loadFolders() {
    try {
        const res = await fetch('api/manage_document_folders.php?action=get');
        const result = await res.json();
        if (result.success) {
            allFolders = result.data || [];
            renderFolderTree();
            populateFolderDropdowns();
        }
    } catch (err) {
        console.error("Lỗi khi tải danh sách thư mục:", err);
    }
}

// Tìm node thư mục theo ID trong cây
function findFolderById(nodes, id) {
    for (const node of nodes) {
        if (node.id === id) return node;
        if (node.children && node.children.length > 0) {
            const found = findFolderById(node.children, id);
            if (found) return found;
        }
    }
    return null;
}

// Lấy toàn bộ ID của thư mục và các thư mục con của nó
function getAllFolderDescendantIds(folder) {
    let ids = [folder.id];
    if (folder.children && folder.children.length > 0) {
        folder.children.forEach(child => {
            ids = ids.concat(getAllFolderDescendantIds(child));
        });
    }
    return ids;
}

// Đếm số lượng tài liệu trong thư mục (kèm con)
function countDocsInFolder(folderId) {
    const folder = findFolderById(allFolders, folderId);
    if (!folder) return 0;
    const descendantIds = getAllFolderDescendantIds(folder);
    return allDocuments.filter(d => descendantIds.includes(d.folder_id)).length;
}

// --------------------------------------------------------------------------
// 2. RENDER CÂY THƯ MỤC (TREE UI)
// --------------------------------------------------------------------------

function renderFolderTree() {
    const treeRoot = document.getElementById("treeRoot");
    const allCountBadge = document.getElementById("allDocsCount");
    if (!treeRoot) return;

    if (allCountBadge) {
        allCountBadge.textContent = allDocuments.length;
    }

    treeRoot.innerHTML = "";
    renderTreeNodes(allFolders, treeRoot, 0);
}

function renderTreeNodes(nodes, parentEl, level) {
    const canEdit = hasPermission('document.edit');
    const canDelete = hasPermission('document.delete');

    nodes.forEach(node => {
        const li = document.createElement("li");
        li.className = "sop-folder-node";
        li.setAttribute("data-folder-id", node.id);

        const hasSubFolders = node.children && node.children.length > 0;
        const folderDocs = allDocuments.filter(doc => doc.folder_id === node.id);
        const docCount = countDocsInFolder(node.id);

        const row = document.createElement("div");
        row.className = `sop-folder-row ${selectedFolderId === node.id ? 'active-folder' : ''}`;
        
        let actionsHtml = '';
        if (canEdit || canDelete) {
            actionsHtml = `<div class="sop-folder-actions">`;
            if (canEdit) {
                actionsHtml += `
                    <button class="sop-btn-icon py-0 px-1" onclick="openAddFolderModal('${node.id}', event)" title="Thêm thư mục con">
                        <span class="material-icons" style="font-size:14px;">add</span>
                    </button>
                    <button class="sop-btn-icon py-0 px-1" onclick="openEditFolderModal('${node.id}', '${escapeJs(node.name)}', event)" title="Đổi tên">
                        <span class="material-icons" style="font-size:14px;">edit</span>
                    </button>
                `;
            }
            if (canDelete) {
                actionsHtml += `
                    <button class="sop-btn-icon sop-btn-icon-danger py-0 px-1" onclick="openDeleteFolderModal('${node.id}', '${escapeJs(node.name)}', event)" title="Xóa thư mục">
                        <span class="material-icons" style="font-size:14px;">delete</span>
                    </button>
                `;
            }
            actionsHtml += `</div>`;
        }

        row.innerHTML = `
            <div class="sop-folder-left">
                ${hasSubFolders 
                    ? `<span class="material-icons sop-toggle-icon" onclick="toggleFolderNode(event, this)">chevron_right</span>` 
                    : `<span style="width:16px; display:inline-block;"></span>`
                }
                <span class="material-icons text-warning" style="font-size:16px;">folder</span>
                <span class="sop-folder-name" title="${escapeHtml(node.name)}">${escapeHtml(node.name)}</span>
            </div>
            <div class="d-flex align-items-center gap-1">
                <span class="sop-folder-count">${docCount}</span>
                ${actionsHtml}
            </div>
        `;

        row.onclick = (e) => {
            if (e.target.closest('.sop-toggle-icon') || e.target.closest('.sop-folder-actions')) return;
            selectFolder(node.id);
        };

        li.appendChild(row);

        // Render các thư mục con và file
        const ul = document.createElement("ul");
        ul.className = "sop-folder-children";

        if (hasSubFolders) {
            renderTreeNodes(node.children, ul, level + 1);
        }

        // Render các file trực thuộc thư mục trong cây
        folderDocs.forEach(doc => {
            const docLi = document.createElement("li");
            docLi.className = `sop-tree-file-item ${currentDoc && currentDoc.id === doc.id ? 'active-tree-file' : ''}`;
            docLi.setAttribute("data-doc-id", doc.id);
            docLi.setAttribute("data-search-text", `${doc.doc_code} ${doc.title}`.toLowerCase());
            
            docLi.onclick = (e) => {
                e.stopPropagation();
                previewDoc(doc);
            };

            docLi.innerHTML = `
                <span class="material-icons text-primary" style="font-size:14px;">description</span>
                <span title="${escapeHtml(doc.title)}">${escapeHtml(doc.doc_code)} - ${escapeHtml(doc.title)}</span>
            `;
            ul.appendChild(docLi);
        });

        if (hasSubFolders || folderDocs.length > 0) {
            li.appendChild(ul);
        }

        parentEl.appendChild(li);
    });
}

function toggleFolderNode(event, toggleIcon) {
    if (event) event.stopPropagation();
    const nodeLi = toggleIcon.closest('.sop-folder-node');
    if (nodeLi) {
        nodeLi.classList.toggle('open');
    }
}

// --------------------------------------------------------------------------
// 3. ĐIỀU HƯỚNG & DUYỆT THƯ MỤC / XEM FILE (DUAL VIEW ENGINE)
// --------------------------------------------------------------------------

function selectRootFolder() {
    selectedFolderId = null;
    currentDoc = null;

    document.querySelectorAll('.sop-tree-root-item, .sop-folder-row').forEach(el => el.classList.remove('active-folder'));
    const rootItem = document.getElementById("rootAllDocsItem");
    if (rootItem) rootItem.classList.add('active-folder');

    updateBreadcrumb([{ name: "Tất cả tài liệu", id: null }]);
    updateFolderHero("Tất cả tài liệu", "Danh mục tổng hợp toàn bộ các tài liệu và tiêu chuẩn SOP trong nhà máy", "dashboard");
    switchToFolderView();
}

function selectFolder(folderId) {
    selectedFolderId = folderId;
    currentDoc = null;

    document.querySelectorAll('.sop-tree-root-item, .sop-folder-row').forEach(el => el.classList.remove('active-folder'));
    const targetLi = document.querySelector(`.sop-folder-node[data-folder-id="${folderId}"] > .sop-folder-row`);
    if (targetLi) targetLi.classList.add('active-folder');

    // Mở node cha nếu đang đóng
    let parent = targetLi ? targetLi.closest('.sop-folder-node') : null;
    while (parent) {
        parent.classList.add('open');
        parent = parent.parentElement.closest('.sop-folder-node');
    }

    const folder = findFolderById(allFolders, folderId);
    if (folder) {
        const path = getFolderPath(folderId);
        updateBreadcrumb(path);
        updateFolderHero(folder.name, `Thư mục tài liệu: ${folder.name}`, "folder_open");
    }

    switchToFolderView();
}

function getFolderPath(folderId) {
    const path = [];
    function traverse(nodes, targetId, currentPath) {
        for (const node of nodes) {
            const nextPath = [...currentPath, { name: node.name, id: node.id }];
            if (node.id === targetId) {
                path.push(...nextPath);
                return true;
            }
            if (node.children && node.children.length > 0) {
                if (traverse(node.children, targetId, nextPath)) return true;
            }
        }
        return false;
    }
    traverse(allFolders, folderId, []);
    return path;
}

function updateBreadcrumb(pathItems) {
    const nav = document.getElementById("breadcrumbNav");
    if (!nav) return;

    let html = `<span class="sop-crumb-link" onclick="selectRootFolder()">Tài liệu SOP</span>`;
    
    pathItems.forEach((item, idx) => {
        html += ` <span>&rsaquo;</span> `;
        const isLast = (idx === pathItems.length - 1) && !currentDoc;
        if (isLast) {
            html += `<span class="sop-crumb-current">${escapeHtml(item.name)}</span>`;
        } else {
            html += `<span class="sop-crumb-link" onclick="selectFolder('${item.id}')">${escapeHtml(item.name)}</span>`;
        }
    });

    if (currentDoc) {
        html += ` <span>&rsaquo;</span> <span class="sop-crumb-current">[${escapeHtml(currentDoc.doc_code)}] ${escapeHtml(currentDoc.title)}</span>`;
    }

    nav.innerHTML = html;
}

function updateFolderHero(title, desc, iconName) {
    const heroTitle = document.getElementById("heroFolderName");
    const heroDesc = document.getElementById("heroFolderDesc");
    const heroIcon = document.getElementById("heroFolderIcon");
    if (heroTitle) heroTitle.textContent = title;
    if (heroDesc) heroDesc.textContent = desc;
    if (heroIcon) heroIcon.textContent = iconName || 'folder';
}

function switchToFolderView() {
    currentViewMode = 'explorer';
    document.getElementById("explorerView").style.display = "flex";
    document.getElementById("pdfView").style.display = "none";
    document.getElementById("btnBackToExplorer").style.display = "none";
    document.getElementById("pdfViewerActions").style.display = "none";

    renderDocGrid();
}

function previewDoc(doc) {
    if (!doc) return;
    currentDoc = doc;
    currentViewMode = 'pdf';

    // Cập nhật tree selection
    document.querySelectorAll('.sop-tree-file-item').forEach(el => el.classList.remove('active-tree-file'));
    const docItem = document.querySelector(`.sop-tree-file-item[data-doc-id="${doc.id}"]`);
    if (docItem) {
        docItem.classList.add('active-tree-file');
        let parent = docItem.closest('.sop-folder-node');
        while (parent) {
            parent.classList.add('open');
            parent = parent.parentElement.closest('.sop-folder-node');
        }
    }

    // Cập nhật Breadcrumb
    const path = doc.folder_id ? getFolderPath(doc.folder_id) : [];
    updateBreadcrumb(path);

    // Chuyển view sang PDF
    document.getElementById("explorerView").style.display = "none";
    document.getElementById("pdfView").style.display = "flex";
    document.getElementById("btnBackToExplorer").style.display = "inline-flex";
    document.getElementById("pdfViewerActions").style.display = "inline-flex";

    // Set src cho iframe
    const iframe = document.getElementById("pdfViewerIframe");
    let safePath = doc.file_path || '';
    if (safePath.startsWith('/myweb/')) {
        safePath = safePath.replace('/myweb/', '');
    }
    iframe.src = safePath + "#toolbar=1&navpanes=0";

    // Set download link
    const dlBtn = document.getElementById("btnDownloadPdf");
    if (dlBtn) dlBtn.href = safePath;
}

// --------------------------------------------------------------------------
// 4. HIỂN THỊ DANH SÁCH TÀI LIỆU DẠNG THẺ (CARD GRID)
// --------------------------------------------------------------------------

function renderDocGrid() {
    const grid = document.getElementById("docGridContainer");
    if (!grid) return;

    let docsToShow = allDocuments;
    if (selectedFolderId) {
        const folder = findFolderById(allFolders, selectedFolderId);
        if (folder) {
            const folderIds = getAllFolderDescendantIds(folder);
            docsToShow = allDocuments.filter(d => folderIds.includes(d.folder_id));
        }
    }

    if (searchKeyword) {
        const q = searchKeyword.toLowerCase().trim();
        docsToShow = docsToShow.filter(d => 
            (d.doc_code && d.doc_code.toLowerCase().includes(q)) ||
            (d.title && d.title.toLowerCase().includes(q))
        );
    }

    if (docsToShow.length === 0) {
        grid.innerHTML = `
            <div class="p-5 text-center text-muted" style="grid-column: 1 / -1; background: var(--dx-bg-card); border-radius: var(--dx-radius-md); border: 1px dashed var(--dx-border);">
                <span class="material-icons text-muted" style="font-size:48px; opacity:0.5;">folder_open</span>
                <h5 class="fw-bold mt-2" style="font-size:14px; color:var(--dx-text-main);">Không tìm thấy tài liệu nào</h5>
                <p class="small text-muted mb-3">Thư mục hiện tại chưa có file hoặc không khớp với từ khóa tìm kiếm.</p>
                ${hasPermission('document.upload') ? `
                <button class="app-btn app-btn-primary app-btn-sm" onclick="openUploadModal(selectedFolderId)">
                    <span class="material-icons">cloud_upload</span> Upload tài liệu vào đây
                </button>` : ''}
            </div>
        `;
        return;
    }

    const canEdit = hasPermission('document.edit');
    const canDelete = hasPermission('document.delete');

    let html = '';
    docsToShow.forEach(doc => {
        let safePath = doc.file_path || '';
        if (safePath.startsWith('/myweb/')) {
            safePath = safePath.replace('/myweb/', '');
        }

        html += `
            <div class="sop-doc-card" onclick="previewDoc(findDocById('${doc.id}'))">
                <div class="sop-doc-card-top">
                    <div class="sop-doc-icon">
                        <span class="material-icons">picture_as_pdf</span>
                    </div>
                    <div class="sop-doc-meta">
                        <div class="sop-doc-code">${escapeHtml(doc.doc_code || 'SOP')}</div>
                        <div class="sop-doc-title" title="${escapeHtml(doc.title)}">${escapeHtml(doc.title)}</div>
                    </div>
                </div>
                <div class="sop-doc-card-bottom">
                    <span>${escapeHtml(doc.updated_at || 'Đã cập nhật')}</span>
                    <div class="sop-doc-actions" onclick="event.stopPropagation()">
                        <a href="${safePath}" target="_blank" class="sop-btn-icon py-0 px-1" title="Tải về / Mở riêng">
                            <span class="material-icons" style="font-size:15px;">open_in_new</span>
                        </a>
                        ${canEdit ? `
                        <button class="sop-btn-icon py-0 px-1" onclick="openEditModal(findDocById('${doc.id}'))" title="Chỉnh sửa">
                            <span class="material-icons" style="font-size:15px;">edit</span>
                        </button>` : ''}
                        ${canDelete ? `
                        <button class="sop-btn-icon sop-btn-icon-danger py-0 px-1" onclick="openDeleteConfirmModal(findDocById('${doc.id}'))" title="Xóa">
                            <span class="material-icons" style="font-size:15px;">delete</span>
                        </button>` : ''}
                    </div>
                </div>
            </div>
        `;
    });

    grid.innerHTML = html;
}

function findDocById(id) {
    return allDocuments.find(d => d.id == id);
}

// --------------------------------------------------------------------------
// 5. TÌM KIẾM TỨC THÌ (INSTANT SEARCH)
// --------------------------------------------------------------------------

function handleSearchInput() {
    const input = document.getElementById("searchInput");
    searchKeyword = (input ? input.value : '').trim();
    const counter = document.getElementById("searchCounter");

    if (searchKeyword) {
        const q = searchKeyword.toLowerCase();
        const matches = allDocuments.filter(d => 
            (d.doc_code && d.doc_code.toLowerCase().includes(q)) ||
            (d.title && d.title.toLowerCase().includes(q))
        );

        if (counter) {
            counter.classList.remove('d-none');
            counter.innerHTML = `Tìm thấy <strong>${matches.length}</strong> kết quả`;
        }

        // Tự động mở rộng các folder chứa file khớp
        document.querySelectorAll('.sop-tree-file-item').forEach(item => {
            const text = item.getAttribute('data-search-text') || '';
            if (text.includes(q)) {
                item.style.display = 'flex';
                let parent = item.closest('.sop-folder-node');
                while (parent) {
                    parent.classList.add('open');
                    parent = parent.parentElement.closest('.sop-folder-node');
                }
            } else {
                item.style.display = 'none';
            }
        });
    } else {
        if (counter) counter.classList.add('d-none');
        document.querySelectorAll('.sop-tree-file-item').forEach(item => {
            item.style.display = 'flex';
        });
    }

    if (currentViewMode === 'explorer') {
        renderDocGrid();
    }
}

// --------------------------------------------------------------------------
// 6. THU GỌN / MỞ RỘNG CÂY THƯ MỤC MƯỢT MÀ (SMOOTH TOGGLE)
// --------------------------------------------------------------------------

function toggleTreePanel() {
    const panel = document.getElementById("treePanel");
    const icon = document.getElementById("btnToggleTreeIcon");
    const text = document.getElementById("btnToggleTreeText");
    const viewerBtn = document.getElementById("btnExpandTreeInViewer");

    if (!panel) return;

    const isCollapsed = panel.classList.toggle("collapsed");

    if (isCollapsed) {
        if (icon) icon.textContent = 'menu';
        if (text) text.textContent = 'Mở cây';
        if (viewerBtn) viewerBtn.style.display = 'inline-flex';
    } else {
        if (icon) icon.textContent = 'menu_open';
        if (text) text.textContent = 'Thu gọn cây';
        if (viewerBtn) viewerBtn.style.display = 'none';
    }
}

// --------------------------------------------------------------------------
// 7. QUẢN LÝ THƯ MỤC CRUD (ADD / EDIT / DELETE FOLDER)
// --------------------------------------------------------------------------

function populateFolderDropdowns() {
    const parentSelect = document.getElementById("newFolderParent");
    const docCatSelect = document.getElementById("docCategorySelect");

    if (parentSelect) {
        parentSelect.innerHTML = '<option value="">-- Tạo ở Thư mục gốc (Root) --</option>';
        appendOptionsRecursively(allFolders, parentSelect, 0);
    }

    if (docCatSelect) {
        docCatSelect.innerHTML = '<option value="">-- Chọn thư mục lưu trữ --</option>';
        appendOptionsRecursively(allFolders, docCatSelect, 0);
    }
}

function appendOptionsRecursively(nodes, selectEl, level) {
    nodes.forEach(node => {
        const opt = document.createElement("option");
        opt.value = node.id;
        opt.textContent = "—".repeat(level) + " " + node.name;
        selectEl.appendChild(opt);

        if (node.children && node.children.length > 0) {
            appendOptionsRecursively(node.children, selectEl, level + 1);
        }
    });
}

function openAddFolderModal(parentId = '', event = null) {
    if (event) event.stopPropagation();
    document.getElementById("newFolderName").value = "";
    const parentSelect = document.getElementById("newFolderParent");
    if (parentSelect) parentSelect.value = parentId || "";

    document.getElementById("modalAddFolder").style.display = "flex";
}

function closeAddFolderModal() {
    document.getElementById("modalAddFolder").style.display = "none";
}

async function handleCreateFolder(e) {
    e.preventDefault();
    const name = document.getElementById("newFolderName").value.trim();
    const parentId = document.getElementById("newFolderParent").value;

    const formData = new FormData();
    formData.append('action', 'create');
    formData.append('name', name);
    formData.append('parent_id', parentId);

    try {
        const res = await fetch('api/manage_document_folders.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            closeAddFolderModal();
            allFolders = data.folders;
            renderFolderTree();
            populateFolderDropdowns();
            if (parentId) {
                // Mở node cha ra
                const pLi = document.querySelector(`.sop-folder-node[data-folder-id="${parentId}"]`);
                if (pLi) pLi.classList.add('open');
            }
        } else {
            alert(data.message || "Lỗi tạo thư mục");
        }
    } catch (err) {
        console.error("Lỗi:", err);
    }
}

function openEditFolderModal(id, name, event = null) {
    if (event) event.stopPropagation();
    document.getElementById("editFolderId").value = id;
    document.getElementById("editFolderName").value = name;
    document.getElementById("modalEditFolder").style.display = "flex";
}

function closeEditFolderModal() {
    document.getElementById("modalEditFolder").style.display = "none";
}

async function handleUpdateFolder(e) {
    e.preventDefault();
    const id = document.getElementById("editFolderId").value;
    const name = document.getElementById("editFolderName").value.trim();

    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('id', id);
    formData.append('name', name);

    try {
        const res = await fetch('api/manage_document_folders.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            closeEditFolderModal();
            allFolders = data.folders;
            renderFolderTree();
            populateFolderDropdowns();
            if (selectedFolderId === id) {
                selectFolder(id);
            }
        } else {
            alert(data.message || "Lỗi đổi tên");
        }
    } catch (err) {
        console.error("Lỗi:", err);
    }
}

function openDeleteFolderModal(id, name, event = null) {
    if (event) event.stopPropagation();
    document.getElementById("deleteFolderId").value = id;
    document.getElementById("deleteFolderMessage").innerHTML = `Bạn có chắc chắn muốn xóa thư mục <strong>"${escapeHtml(name)}"</strong> không? Lưu ý thư mục phải không chứa tài liệu và không có thư mục con.`;
    document.getElementById("modalDeleteFolder").style.display = "flex";
}

function closeDeleteFolderModal() {
    document.getElementById("modalDeleteFolder").style.display = "none";
}

async function confirmDeleteFolder() {
    const id = document.getElementById("deleteFolderId").value;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);

    try {
        const res = await fetch('api/manage_document_folders.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            closeDeleteFolderModal();
            allFolders = data.folders;
            renderFolderTree();
            populateFolderDropdowns();
            if (selectedFolderId === id) {
                selectRootFolder();
            }
        } else {
            alert(data.message || "Lỗi xóa thư mục");
        }
    } catch (err) {
        console.error("Lỗi:", err);
    }
}

// --------------------------------------------------------------------------
// 8. QUẢN LÝ TÀI LIỆU FILE (UPLOAD / EDIT / DELETE DOCUMENT)
// --------------------------------------------------------------------------

async function fetchDocuments() {
    try {
        const res = await fetch('api/get_documents.php');
        allDocuments = await res.json();
        renderFolderTree();
        updateSummaryStats();
    } catch (error) {
        console.error("Lỗi nạp danh sách tài liệu:", error);
    }
}

function updateSummaryStats() {
    const statsEl = document.getElementById("sopSummaryStats");
    if (statsEl) {
        statsEl.textContent = `Tổng cộng: ${allDocuments.length} tài liệu số hóa`;
    }
}

function openUploadModal(preselectedFolderId = null) {
    document.getElementById("editDocId").value = "";
    document.getElementById("modalDocFormTitle").innerHTML = '<span class="material-icons text-primary">cloud_upload</span> Upload Tài Liệu Mới';
    document.getElementById("docCode").value = "";
    document.getElementById("docName").value = "";
    document.getElementById("docFile").required = true;
    document.getElementById("fileHelpText").style.display = "none";

    const catSelect = document.getElementById("docCategorySelect");
    if (catSelect && preselectedFolderId) {
        catSelect.value = preselectedFolderId;
    }

    document.getElementById("uploadModal").style.display = "flex";
}

function openEditModal(doc) {
    if (!doc) return;
    document.getElementById("editDocId").value = doc.id;
    document.getElementById("modalDocFormTitle").innerHTML = '<span class="material-icons text-warning">edit</span> Chỉnh Sửa Tài Liệu';
    document.getElementById("docCode").value = doc.doc_code || '';
    document.getElementById("docName").value = doc.title || '';
    
    const catSelect = document.getElementById("docCategorySelect");
    if (catSelect) catSelect.value = doc.folder_id || '';

    document.getElementById("docFile").required = false;
    document.getElementById("fileHelpText").style.display = "block";

    document.getElementById("uploadModal").style.display = "flex";
}

function closeUploadModal() {
    document.getElementById("uploadModal").style.display = "none";
}

async function handleSaveDocument(e) {
    e.preventDefault();
    const editId = document.getElementById("editDocId").value;
    const isEdit = (editId !== "");

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

    const apiUrl = isEdit ? 'api/edit_document.php' : 'api/upload_document.php';

    try {
        const res = await fetch(apiUrl, { method: 'POST', body: formData });
        const result = await res.json();

        if (result.success) {
            closeUploadModal();
            await fetchDocuments();

            if (isEdit && currentDoc && currentDoc.id === editId) {
                previewDoc(result.data);
            } else if (currentViewMode === 'explorer') {
                renderDocGrid();
            }
        } else {
            alert(result.message || "Lỗi xử lý file!");
        }
    } catch (error) {
        console.error("Lỗi:", error);
    }
}

function openDeleteConfirmModal(doc) {
    if (!doc) return;
    currentDoc = doc;
    const confirmText = document.getElementById("deleteDocConfirmText");
    if (confirmText) {
        confirmText.innerHTML = `Bạn có chắc chắn muốn xóa tài liệu <strong>[${escapeHtml(doc.doc_code)}] ${escapeHtml(doc.title)}</strong> không? Thao tác sẽ gỡ bỏ file PDF này vĩnh viễn.`;
    }
    document.getElementById("deleteConfirmModal").style.display = "flex";
}

function closeDeleteConfirmModal() {
    document.getElementById("deleteConfirmModal").style.display = "none";
}

async function confirmDeleteDocument() {
    if (!currentDoc) return;
    const formData = new FormData();
    formData.append("id", currentDoc.id);

    try {
        const res = await fetch('api/delete_document.php', { method: 'POST', body: formData });
        const result = await res.json();

        if (result.success) {
            closeDeleteConfirmModal();
            currentDoc = null;
            await fetchDocuments();
            switchToFolderView();
        } else {
            alert(result.message || "Lỗi khi xóa file!");
        }
    } catch (error) {
        console.error("Lỗi:", error);
    }
}

// --------------------------------------------------------------------------
// 9. FULLSCREEN MODAL
// --------------------------------------------------------------------------

function openFullscreenModal() {
    if (!currentDoc) return;
    const modal = document.getElementById("fullscreenModal");
    const fsViewer = document.getElementById("fullscreenViewer");
    const fsTitle = document.getElementById("fullscreenTitle");

    let safePath = currentDoc.file_path || '';
    if (safePath.startsWith('/myweb/')) {
        safePath = safePath.replace('/myweb/', '');
    }

    if (modal && fsViewer) {
        fsTitle.innerHTML = `<span class="material-icons text-primary" style="font-size:18px;">description</span> [${escapeHtml(currentDoc.doc_code)}] ${escapeHtml(currentDoc.title)}`;
        fsViewer.src = safePath + "#toolbar=1&navpanes=0";
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

// --------------------------------------------------------------------------
// 10. HÀM TIỆN ÍCH
// --------------------------------------------------------------------------

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function escapeJs(str) {
    if (!str) return '';
    return str.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '&quot;');
}