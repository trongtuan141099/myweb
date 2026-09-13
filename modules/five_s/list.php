<?php
// modules/five_s/list.php
if (!defined('INDEX_AUTH')) {
    define('INDEX_AUTH', true);
}
$current_month = date('Y-m');
?>

<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-primary"><i class="bi bi-list-check me-2"></i>Danh Sách Ghi Nhận Kiểm Tra 5S Chi Tiết</h4>
            <p class="text-muted small mb-0">Quản lý, tìm kiếm và xóa thông tin các lượt ghi nhận vi phạm 5S</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="index.php?mainpage=five_s&subpage=overview" class="btn btn-outline-secondary btn-sm text-nowrap">
                <i class="bi bi-arrow-left me-1"></i>Quay Về Dashboard
            </a>
            <input type="month" id="filter_month" class="form-control form-control-sm" value="<?php echo $current_month; ?>" onchange="loadListData()">
            <select id="filter_status" class="form-select form-select-sm w-auto" onchange="renderTable()">
                <option value="all">Tất cả trạng thái</option>
                <option value="pending">Chưa khắc phục</option>
                <option value="resolved">Đã khắc phục</option>
            </select>
        </div>
    </div>

    <!-- Bảng Dữ Liệu Chi Tiết -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Ngày kiểm tra</th>
                            <th>Khu vực</th>
                            <th>Tiêu chí 5S</th>
                            <th>Mô tả vi phạm</th>
                            <th>Ảnh trước</th>
                            <th>Người phụ trách</th>
                            <th>Trạng thái</th>
                            <th>Ảnh sau</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="table-list-body">
                        <!-- Data render Javascript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
let listIssuesData = [];

document.addEventListener("DOMContentLoaded", loadListData);

function loadListData() {
    const month = document.getElementById('filter_month').value;
    fetch(`api/five_s_get_dashboard.php?month=${month}`)
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                listIssuesData = data.issues || [];
                renderTable();
            }
        });
}

function renderTable() {
    const status = document.getElementById('filter_status').value;
    const tbody = document.getElementById('table-list-body');
    tbody.innerHTML = '';

    const filtered = listIssuesData.filter(item => status === 'all' || item.status === status);

    if (filtered.length === 0) {
        tbody.innerHTML = `<tr><td colspan="10" class="text-center py-4 text-muted">Không tìm thấy dữ liệu vi phạm 5S nào.</td></tr>`;
        return;
    }

    filtered.forEach((item, index) => {
        const isResolved = item.status === 'resolved';
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${index + 1}</td>
            <td>${item.created_at}</td>
            <td><span class="badge bg-secondary">${item.zone_name}</span></td>
            <td><strong class="text-primary">${item.s_category}</strong></td>
            <td>${item.description}</td>
            <td><a href="${item.before_image}" target="_blank"><img src="${item.before_image}" class="rounded border" width="45" height="45"></a></td>
            <td>${item.assignee_name || 'Chưa gán'}</td>
            <td><span class="badge ${isResolved ? 'bg-success' : 'bg-danger'}">${isResolved ? 'Đã khắc phục' : 'Chờ xử lý'}</span></td>
            <td>${item.after_image ? `<a href="${item.after_image}" target="_blank"><img src="${item.after_image}" class="rounded border" width="45" height="45"></a>` : '<span class="text-muted small">N/A</span>'}</td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-danger" title="Xóa dòng này" onclick="deleteIssue(${item.id})">
                    <i class="bi bi-trash"></i> Xóa
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function deleteIssue(issueId) {
    if (!confirm("Bạn có chắc chắn muốn xóa bản ghi vi phạm 5S này? Thao tác này sẽ không thể khôi phục.")) {
        return;
    }

    const formData = new FormData();
    formData.append('issue_id', issueId);

    fetch('api/five_s_delete_issue.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if(res.success) {
            loadListData();
        } else {
            alert(res.message || 'Lỗi khi xóa dòng dữ liệu');
        }
    });
}
</script>