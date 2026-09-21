<?php
// modules/five_s/proposals.php
if (!defined('INDEX_AUTH')) {
    define('INDEX_AUTH', true);
}
$current_month = date('Y-m');
?>

<style>
/* Module-specific styles for 5S Proposals */
.proposal-img-thumb {
    width: 48px;
    height: 48px;
    object-fit: cover;
    border-radius: var(--dx-radius-sm);
    border: 1px solid var(--dx-border);
    transition: transform 0.15s ease;
}
.proposal-img-thumb:hover {
    transform: scale(1.1);
}

.kpi-stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 14px;
    margin-bottom: 20px;
}
</style>

<div class="app-page-wrapper">
    <!-- Header -->
    <div class="app-page-header">
        <div class="app-page-title">
            <span class="material-icons text-primary">lightbulb</span>
            <div>
                <h1 style="font-size: 18px; margin: 0;">Ý KIẾN & ĐỀ XUẤT CẢI TIẾN 5S</h1>
                <p class="text-muted small mb-0">Ghi nhận sáng kiến nhân viên, phê duyệt và giám sát tiến độ thực hiện</p>
            </div>
        </div>
        <div class="app-page-actions">
            <div class="d-flex align-items-center gap-2">
                <label for="filter_month" class="small fw-bold text-nowrap text-muted">Tháng:</label>
                <input type="month" id="filter_month" class="app-form-control py-1 px-2" value="<?php echo $current_month; ?>" onchange="loadProposalsData()" style="width: auto;">
                <button class="app-btn app-btn-primary" data-bs-toggle="modal" data-bs-target="#modalNewProposal">
                    <span class="material-icons">add</span> Gửi Đề Xuất
                </button>
            </div>
        </div>
    </div>

    <!-- CARDS THỐNG KÊ TIẾN ĐỘ -->
    <div class="kpi-stat-grid">
        <div class="app-stat-card" style="border-left: 4px solid var(--dx-primary);">
            <div class="app-stat-label">Tổng Đề Xuất</div>
            <div class="app-stat-value text-primary" id="card-total">0</div>
        </div>
        <div class="app-stat-card" style="border-left: 4px solid var(--dx-warning);">
            <div class="app-stat-label">Chờ Duyệt</div>
            <div class="app-stat-value text-warning" id="card-pending">0</div>
        </div>
        <div class="app-stat-card" style="border-left: 4px solid #0284c7;">
            <div class="app-stat-label">Đang Thực Hiện</div>
            <div class="app-stat-value" style="color: #0284c7;" id="card-in-progress">0</div>
        </div>
        <div class="app-stat-card" style="border-left: 4px solid var(--dx-text-muted);">
            <div class="app-stat-label">Tạm Dừng</div>
            <div class="app-stat-value text-muted" id="card-paused">0</div>
        </div>
        <div class="app-stat-card" style="border-left: 4px solid var(--dx-success);">
            <div class="app-stat-label">Hoàn Thành</div>
            <div class="app-stat-value text-success" id="card-completed">0</div>
        </div>
        <div class="app-stat-card" style="border-left: 4px solid var(--dx-danger);">
            <div class="app-stat-label">Hủy Đề Xuất</div>
            <div class="app-stat-value text-danger" id="card-canceled">0</div>
        </div>
    </div>

    <!-- BẢNG DANH SÁCH ĐỀ XUẤT -->
    <div class="app-card">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="fw-bold m-0 d-flex align-items-center gap-2">
                <span class="material-icons text-primary fs-5">assignment</span> Danh Sách Đề Xuất Cải Tiến
            </h6>
            <select id="filter_status" class="app-form-control w-auto py-1 px-2" onchange="renderTable()">
                <option value="all">Tất cả trạng thái</option>
                <option value="pending">Chờ thực hiện</option>
                <option value="in_progress">Bắt đầu thực hiện</option>
                <option value="paused">Tạm dừng</option>
                <option value="completed">Hoàn thành</option>
                <option value="canceled">Hủy đề xuất</option>
            </select>
        </div>
        <div class="app-table-responsive" style="max-height: calc(100vh - 360px); overflow-y: auto;">
            <table class="app-table table-sticky-header">
                <thead>
                    <tr>
                        <th style="width: 50px;">STT</th>
                        <th style="width: 140px;">Ngày Tạo</th>
                        <th style="width: 160px;">Nhân Viên</th>
                        <th>Mô Tả Hiện Trạng</th>
                        <th style="width: 80px; text-align: center;">Ảnh Trước</th>
                        <th>Nội Dung Đề Xuất</th>
                        <th>Nhận Xét QL</th>
                        <th style="width: 130px; text-align: center;">Trạng Thái</th>
                        <th style="width: 80px; text-align: center;">Ảnh Sau</th>
                        <th style="width: 130px; text-align: center;">Thao Tác</th>
                    </tr>
                </thead>
                <tbody id="table-proposal-body">
                    <!-- Dynamic Data -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL 1: TẠO ĐỀ XUẤT MỚI -->
<div class="modal fade" id="modalNewProposal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                    <span class="material-icons text-primary">rate_review</span> Gửi Ý Kiến / Đề Xuất 5S Mới
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNewProposal" onsubmit="submitNewProposal(event)">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">Mô Tả Hiện Trạng Hiện Tại <span class="text-danger">*</span></label>
                            <textarea name="current_status_desc" class="app-form-control" rows="2" placeholder="Chi tiết khu vực, vướng mắc 5S hiện tại..." required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Hình Ảnh Hiện Trạng <span class="text-danger">*</span></label>
                            <input type="file" name="current_image" class="app-form-control" accept="image/*" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Nội Dung Đề Xuất Cải Tiến <span class="text-danger">*</span></label>
                            <textarea name="proposal_desc" class="app-form-control" rows="3" placeholder="Đề xuất phương án sắp xếp, vệ sinh, làm mới..." required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="app-btn app-btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="app-btn app-btn-primary">Gửi Đề Xuất</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 2: QUẢN LÝ DUYỆT & CẬP NHẬT TRẠNG THÁI -->
<div class="modal fade" id="modalReviewProposal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-primary d-flex align-items-center gap-2">
                    <span class="material-icons text-primary">gavel</span> Xem Xét & Quyết Định Đề Xuất
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formReviewProposal" onsubmit="submitReviewProposal(event)">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="proposal_id" id="review_proposal_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nhận Xét / Ghi Chú Quản Lý</label>
                        <textarea name="manager_comment" id="review_comment" class="app-form-control" rows="3" placeholder="Ý kiến phản hồi từ quản lý..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Quyết Định Trạng Thái <span class="text-danger">*</span></label>
                        <select name="status" id="review_status" class="app-form-control" onchange="toggleAfterImageInput()" required>
                            <option value="pending">Chờ thực hiện</option>
                            <option value="in_progress">Bắt đầu thực hiện</option>
                            <option value="paused">Tạm dừng</option>
                            <option value="completed">Hoàn thành</option>
                            <option value="canceled">Hủy đề xuất</option>
                        </select>
                    </div>
                    <div class="mb-3 d-none" id="after-image-group">
                        <label class="form-label fw-bold">Cập Nhật Ảnh Sau Khi Hoàn Thành <span class="text-danger">*</span></label>
                        <input type="file" name="after_image" id="input_after_image" class="app-form-control" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="app-btn app-btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="app-btn app-btn-primary">Lưu Quyết Định</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let globalProposalsData = [];

document.addEventListener("DOMContentLoaded", loadProposalsData);

function loadProposalsData() {
    const month = document.getElementById('filter_month').value;
    fetch(`api/five_s_get_proposals.php?month=${month}`)
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                globalProposalsData = data.proposals || [];
                
                // Cập nhật các thẻ Card KPI
                document.getElementById('card-total').innerText = data.kpi.total;
                document.getElementById('card-pending').innerText = data.kpi.pending;
                document.getElementById('card-in-progress').innerText = data.kpi.in_progress;
                document.getElementById('card-paused').innerText = data.kpi.paused;
                document.getElementById('card-completed').innerText = data.kpi.completed;
                document.getElementById('card-canceled').innerText = data.kpi.canceled;

                renderTable();
            }
        });
}

function renderTable() {
    const status = document.getElementById('filter_status').value;
    const tbody = document.getElementById('table-proposal-body');
    tbody.innerHTML = '';

    const filtered = globalProposalsData.filter(item => status === 'all' || item.status === status);

    if (filtered.length === 0) {
        tbody.innerHTML = `<tr><td colspan="10" class="text-center py-4 text-muted">Không có đề xuất 5S nào trong tháng này.</td></tr>`;
        return;
    }

    const statusBadges = {
        'pending': '<span class="app-badge badge-warning">Chờ thực hiện</span>',
        'in_progress': '<span class="app-badge badge-info">Bắt đầu thực hiện</span>',
        'paused': '<span class="app-badge" style="background:#f1f5f9; color:#64748b;">Tạm dừng</span>',
        'completed': '<span class="app-badge badge-success">Hoàn thành</span>',
        'canceled': '<span class="app-badge badge-danger">Hủy đề xuất</span>'
    };

    filtered.forEach((item, index) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>#${index + 1}</td>
            <td class="text-muted small">${item.created_at}</td>
            <td><strong>${item.employee_name}</strong></td>
            <td>${item.current_status_desc}</td>
            <td class="text-center"><a href="${item.current_image}" target="_blank"><img src="${item.current_image}" class="proposal-img-thumb" alt="Before"></a></td>
            <td>${item.proposal_desc}</td>
            <td>${item.manager_comment || '<span class="text-muted small">Chưa có nhận xét</span>'}</td>
            <td class="text-center">${statusBadges[item.status] || item.status}</td>
            <td class="text-center">${item.after_image ? `<a href="${item.after_image}" target="_blank"><img src="${item.after_image}" class="proposal-img-thumb" alt="After"></a>` : '<span class="text-muted small">-</span>'}</td>
            <td class="text-center">
                <button class="app-btn app-btn-secondary py-1 px-2" onclick="openReviewModal(${item.id})">
                    <span class="material-icons fs-6">edit</span> Duyệt
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function submitNewProposal(event) {
    event.preventDefault();
    const formData = new FormData(document.getElementById('formNewProposal'));

    fetch('api/five_s_save_proposal.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(res => {
        if(res.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalNewProposal')).hide();
            document.getElementById('formNewProposal').reset();
            loadProposalsData();
        } else {
            alert(res.message);
        }
    });
}

function openReviewModal(id) {
    const item = globalProposalsData.find(p => p.id == id);
    if (!item) return;

    document.getElementById('review_proposal_id').value = item.id;
    document.getElementById('review_comment').value = item.manager_comment || '';
    document.getElementById('review_status').value = item.status;
    
    toggleAfterImageInput();
    new bootstrap.Modal(document.getElementById('modalReviewProposal')).show();
}

function toggleAfterImageInput() {
    const status = document.getElementById('review_status').value;
    const imgGroup = document.getElementById('after-image-group');
    const imgInput = document.getElementById('input_after_image');
    
    if (status === 'completed') {
        imgGroup.classList.remove('d-none');
    } else {
        imgGroup.classList.add('d-none');
        imgInput.value = '';
    }
}

function submitReviewProposal(event) {
    event.preventDefault();
    const formData = new FormData(document.getElementById('formReviewProposal'));

    fetch('api/five_s_save_proposal.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(res => {
        if(res.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalReviewProposal')).hide();
            loadProposalsData();
        } else {
            alert(res.message);
        }
    });
}
</script>