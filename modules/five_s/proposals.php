<?php
// modules/five_s/proposals.php
if (!defined('INDEX_AUTH')) {
    define('INDEX_AUTH', true);
}
$current_month = date('Y-m');
?>

<style>
.proposal-img-thumb {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid #dee2e6;
}
</style>

<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-primary"><i class="bi bi-lightbulb me-2"></i>Ý Kiến & Đề Xuất Cải Tiến 5S</h4>
            <p class="text-muted small mb-0">Ghi nhận sáng kiến từ nhân viên, xét duyệt và theo dõi tiến độ thực hiện</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <label for="filter_month" class="form-label mb-0 fw-bold small text-nowrap">Chọn tháng:</label>
            <input type="month" id="filter_month" class="form-control form-control-sm" value="<?php echo $current_month; ?>" onchange="loadProposalsData()">
            <button class="btn btn-primary btn-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#modalNewProposal">
                <i class="bi bi-plus-lg me-1"></i>Gửi Đề Xuất Mới
            </button>
        </div>
    </div>

    <!-- CARDS THỐNG KÊ TIẾN ĐỘ -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm border-start border-4 border-primary h-100">
                <div class="card-body p-3">
                    <div class="text-uppercase text-muted fw-bold small">Tổng Đề Xuất</div>
                    <div class="h4 fw-bold mb-0 text-dark" id="card-total">0</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm border-start border-4 border-warning h-100">
                <div class="card-body p-3">
                    <div class="text-uppercase text-muted fw-bold small">Chờ Duyệt</div>
                    <div class="h4 fw-bold mb-0 text-warning" id="card-pending">0</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm border-start border-4 border-info h-100">
                <div class="card-body p-3">
                    <div class="text-uppercase text-muted fw-bold small">Đang Thực Hiện</div>
                    <div class="h4 fw-bold mb-0 text-info" id="card-in-progress">0</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm border-start border-4 border-secondary h-100">
                <div class="card-body p-3">
                    <div class="text-uppercase text-muted fw-bold small">Tạm Dừng</div>
                    <div class="h4 fw-bold mb-0 text-secondary" id="card-paused">0</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm border-start border-4 border-success h-100">
                <div class="card-body p-3">
                    <div class="text-uppercase text-muted fw-bold small">Hoàn Thành</div>
                    <div class="h4 fw-bold mb-0 text-success" id="card-completed">0</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm border-start border-4 border-danger h-100">
                <div class="card-body p-3">
                    <div class="text-uppercase text-muted fw-bold small">Hủy Đề Xuất</div>
                    <div class="h4 fw-bold mb-0 text-danger" id="card-canceled">0</div>
                </div>
            </div>
        </div>
    </div>

    <!-- BẢNG DANH SÁCH ĐỀ XUẤT -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold m-0"><i class="bi bi-journal-text me-2"></i>Danh Sách Đề Xuất Cải Tiến</h6>
            <select id="filter_status" class="form-select form-select-sm w-auto" onchange="renderTable()">
                <option value="all">Tất cả trạng thái</option>
                <option value="pending">Chờ thực hiện</option>
                <option value="in_progress">Bắt đầu thực hiện</option>
                <option value="paused">Tạm dừng</option>
                <option value="completed">Hoàn thành</option>
                <option value="canceled">Hủy đề xuất</option>
            </select>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Ngày tạo</th>
                            <th>Nhân viên</th>
                            <th>Mô tả hiện trạng</th>
                            <th>Ảnh hiện tại</th>
                            <th>Nội dung đề xuất</th>
                            <th>Nhận xét Quản lý</th>
                            <th>Trạng thái</th>
                            <th>Ảnh hoàn thành</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="table-proposal-body">
                        <!-- Data Javascript render -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 1: TẠO ĐỀ XUẤT MỚI -->
<div class="modal fade" id="modalNewProposal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Gửi Ý Kiến / Đề Xuất 5S Mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNewProposal" onsubmit="submitNewProposal(event)">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">Mô Tả Hiện Trạng Hiện Tại <span class="text-danger">*</span></label>
                            <textarea name="current_status_desc" class="form-control" rows="2" placeholder="Chi tiết khu vực, vướng mắc 5S hiện tại..." required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Hình Ảnh Hiện Trạng <span class="text-danger">*</span></label>
                            <input type="file" name="current_image" class="form-control" accept="image/*" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Nội Dung Đề Xuất Cải Tiến <span class="text-danger">*</span></label>
                            <textarea name="proposal_desc" class="form-control" rows="3" placeholder="Đề xuất phương án sắp xếp, vệ sinh, làm mới..." required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Gửi Đề Xuất</button>
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
                <h5 class="modal-title fw-bold text-primary">Xem Xét & Quyết Định Đề Xuất</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formReviewProposal" onsubmit="submitReviewProposal(event)">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="proposal_id" id="review_proposal_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nhận Xét / Ghi Chú Quản Lý</label>
                        <textarea name="manager_comment" id="review_comment" class="form-control" rows="3" placeholder="Ý kiến phản hồi từ quản lý..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Quyết Định Trạng Thái <span class="text-danger">*</span></label>
                        <select name="status" id="review_status" class="form-select" onchange="toggleAfterImageInput()" required>
                            <option value="pending">Chờ thực hiện</option>
                            <option value="in_progress">Bắt đầu thực hiện</option>
                            <option value="paused">Tạm dừng</option>
                            <option value="completed">Hoàn thành</option>
                            <option value="canceled">Hủy đề xuất</option>
                        </select>
                    </div>
                    <div class="mb-3 d-none" id="after-image-group">
                        <label class="form-label fw-bold">Cập Nhật Ảnh Sau Khi Hoàn Thành <span class="text-danger">*</span></label>
                        <input type="file" name="after_image" id="input_after_image" class="form-control" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Lưu Quyết Định</button>
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
        'pending': '<span class="badge bg-warning text-dark">Chờ thực hiện</span>',
        'in_progress': '<span class="badge bg-info text-dark">Bắt đầu thực hiện</span>',
        'paused': '<span class="badge bg-secondary">Tạm dừng</span>',
        'completed': '<span class="badge bg-success">Hoàn thành</span>',
        'canceled': '<span class="badge bg-danger">Hủy đề xuất</span>'
    };

    filtered.forEach((item, index) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${index + 1}</td>
            <td>${item.created_at}</td>
            <td><strong>${item.employee_name}</strong></td>
            <td>${item.current_status_desc}</td>
            <td><a href="${item.current_image}" target="_blank"><img src="${item.current_image}" class="proposal-img-thumb"></a></td>
            <td>${item.proposal_desc}</td>
            <td>${item.manager_comment || '<span class="text-muted small">Chưa có nhận xét</span>'}</td>
            <td>${statusBadges[item.status] || item.status}</td>
            <td>${item.after_image ? `<a href="${item.after_image}" target="_blank"><img src="${item.after_image}" class="proposal-img-thumb"></a>` : '<span class="text-muted small">N/A</span>'}</td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-primary" onclick="openReviewModal(${item.id})">
                    <i class="bi bi-pencil-square"></i> Duyệt / Cập nhật
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