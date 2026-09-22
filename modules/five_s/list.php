<?php
// modules/five_s/list.php
if (!defined('INDEX_AUTH')) {
    define('INDEX_AUTH', true);
}
$current_month = date('Y-m');
?>

<div class="app-page-wrapper">
    <!-- Header -->
    <div class="app-page-header">
        <div>
            <h1 class="app-page-title">
                <span class="material-icons">rule</span>
                Danh Sách Ghi Nhận Kiểm Tra 5S Chi Tiết
            </h1>
            <p class="app-page-subtitle">Quản lý, tìm kiếm và đối ứng các lượt ghi nhận vi phạm 5S</p>
        </div>
        <div class="app-page-actions">
            <a href="index.php?mainpage=five_s&subpage=overview" class="app-btn app-btn-secondary">
                <span class="material-icons">arrow_back</span> Về Dashboard 5S
            </a>
            <input type="month" id="filter_month" class="app-form-control" value="<?php echo $current_month; ?>" onchange="loadListData()">
            <select id="filter_status" class="app-form-select" onchange="renderTable()">
                <option value="all">Tất cả trạng thái</option>
                <option value="pending">Chưa khắc phục</option>
                <option value="resolved">Đã khắc phục</option>
            </select>
        </div>
    </div>

    <!-- Bảng Dữ Liệu Chi Tiết -->
    <div class="app-card">
        <div class="app-table-container">
            <table class="app-table table-sticky-header">
                <thead>
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
        <div class="app-card-footer">
            <div id="fiveSListPagination" class="w-100"></div>
        </div>
    </div>
</div>

<script>
let listIssuesData = [];
let fiveSPagination = null;

document.addEventListener("DOMContentLoaded", loadListData);

function loadListData() {
    let month = document.getElementById('filter_month').value;
    if (!month) {
        month = new Date().toISOString().slice(0, 7);
        document.getElementById('filter_month').value = month;
    }
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
    const filterFn = (item) => status === 'all' || item.status === status;

    if (!fiveSPagination) {
        fiveSPagination = createClientTablePagination({
            tbodyId: 'table-list-body',
            paginationId: 'fiveSListPagination',
            data: listIssuesData,
            colSpan: 10,
            defaultPageSize: 10,
            pageSizeOptions: [10, 25, 50, 100],
            emptyMessage: 'Không tìm thấy dữ liệu vi phạm 5S nào.',
            filterFn: filterFn,
            renderRow: (item, index) => {
                const isResolved = item.status === 'resolved';
                const badgeClass = isResolved ? 'badge-ok' : 'badge-ng';
                const statusText = isResolved ? 'Đã khắc phục' : 'Chờ xử lý';

                return `
                <tr>
                    <td><b>${index}</b></td>
                    <td>${item.created_at}</td>
                    <td><span class="app-badge badge-stopped">${escapeHtml(item.zone_name)}</span></td>
                    <td><strong class="text-primary">${escapeHtml(item.s_category)}</strong></td>
                    <td>${escapeHtml(item.description)}</td>
                    <td><a href="${item.before_image}" target="_blank"><img src="${item.before_image}" class="rounded border" width="40" height="40" style="object-fit:cover;"></a></td>
                    <td>${escapeHtml(item.assignee_name || 'Chưa gán')}</td>
                    <td><span class="app-badge ${badgeClass}">${statusText}</span></td>
                    <td>${item.after_image ? `<a href="${item.after_image}" target="_blank"><img src="${item.after_image}" class="rounded border" width="40" height="40" style="object-fit:cover;"></a>` : '<span class="text-muted small">N/A</span>'}</td>
                    <td class="text-center">
                        <button class="app-btn app-btn-danger app-btn-sm" title="Xóa dòng này" onclick="deleteIssue(${item.id})">
                            <span class="material-icons" style="font-size:15px;">delete</span> Xóa
                        </button>
                    </td>
                </tr>`;
            }
        });
    } else {
        fiveSPagination.setData(listIssuesData);
        fiveSPagination.setFilter(filterFn);
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
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