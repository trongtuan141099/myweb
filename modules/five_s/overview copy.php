<?php
// modules/five_s/overview.php
if (!defined('INDEX_AUTH')) {
    define('INDEX_AUTH', true);
}

$current_month = date('Y-m');
?>

<style>
/* Tối ưu vùng hiển thị sơ đồ và bảng */
#layout-wrapper {
    max-height: 480px;
    overflow: auto;
    width: 100%;
}

#layout-img {
    max-height: 450px;
    width: auto;
    object-fit: contain;
}

.pin-marker {
    position: absolute;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid #fff;
    transform: translate(-50%, -50%);
    cursor: pointer;
    box-shadow: 0 0 8px rgba(0,0,0,0.4);
    animation: pulse 2s infinite;
}
.pin-pending { background-color: #dc3545; }
.pin-resolved { background-color: #198754; }

@keyframes pulse {
    0% { transform: translate(-50%, -50%) scale(0.95); }
    70% { transform: translate(-50%, -50%) scale(1.15); }
    100% { transform: translate(-50%, -50%) scale(0.95); }
}

.style-img-thumb {
    max-height: 140px;
    object-fit: cover;
    width: 100%;
}
</style>

<!-- Nạp thư viện ApexCharts -->
<script src="resources/apexcharts/apexcharts.min.js"></script>

<div class="container-fluid py-3">
    <!-- Header & Thanh điều hướng tác vụ -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-primary"><i class="bi bi-shield-check me-2"></i>Quản Lý & Kiểm Tra 5S</h4>
            <p class="text-muted small mb-0">Theo dõi, kiểm tra trực quan và đánh giá chỉ số 5S nhà xưởng</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <label for="filter_month" class="form-label mb-0 fw-bold small text-nowrap">Chọn tháng:</label>
            <input type="month" id="filter_month" class="form-control form-control-sm" value="<?php echo $current_month; ?>" onchange="loadDashboardData()">
            <button class="btn btn-primary btn-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#modalNewAudit">
                <i class="bi bi-plus-lg me-1"></i>Tạo Phiếu Kiểm Tra
            </button>
            <button class="btn btn-outline-secondary btn-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#modalAssignment">
                <i class="bi bi-person-gear me-1"></i>Phân Công
            </button>
        </div>
    </div>

    <!-- 1. THẺ KPI THỐNG KÊ -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-4 border-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase text-muted fw-bold small">Tổng Số Vi Phạm</div>
                            <div class="h3 fw-bold mb-0 text-dark" id="kpi-total">0</div>
                        </div>
                        <div class="badge bg-primary-subtle text-primary p-3 rounded-circle">
                            <i class="bi bi-exclamation-octagon fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-4 border-success h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase text-muted fw-bold small">Đã Khắc Phục</div>
                            <div class="h3 fw-bold mb-0 text-success" id="kpi-resolved">0</div>
                        </div>
                        <div class="badge bg-success-subtle text-success p-3 rounded-circle">
                            <i class="bi bi-check-circle fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-4 border-danger h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase text-muted fw-bold small">Chưa Khắc Phục</div>
                            <div class="h3 fw-bold mb-0 text-danger" id="kpi-pending">0</div>
                        </div>
                        <div class="badge bg-danger-subtle text-danger p-3 rounded-circle">
                            <i class="bi bi-x-circle fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-4 border-info h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-uppercase text-muted fw-bold small">Tỷ Lệ Phục Hồi</div>
                            <div class="h3 fw-bold mb-0 text-info" id="kpi-rate">0%</div>
                        </div>
                        <div class="badge bg-info-subtle text-info p-3 rounded-circle">
                            <i class="bi bi-pie-chart fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. LAYOUT VI PHẠM TRỰC QUAN & BIỂU ĐỒ -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold m-0"><i class="bi bi-map me-2"></i>Sơ Đồ Vi Phạm Trực Quan (Layout)</h6>
                    <small class="text-muted">* Nhấp vào điểm ghi nhận để xem chi tiết lỗi</small>
                </div>
                <div class="card-body p-2 position-relative text-center">
                    <div id="layout-wrapper" class="position-relative d-inline-block">
                        <img id="layout-img" src="resources/images/factory_layout.png" class="img-fluid rounded border" alt="Factory Layout" onerror="this.src='https://via.placeholder.com/1000x500?text=S%C6%A1+%C4%90%E1%BB%93+M%E1%BA%B7t+B%E1%BA%B1ng+Nh%C3%A0+X%C6%B0%E1%BB%9Fng'">
                        <div id="pin-container"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold m-0"><i class="bi bi-bar-chart-line me-2"></i>Phân Tích Loại Vi Phạm (5S)</h6>
                </div>
                <div class="card-body">
                    <div id="chart-5s-categories" style="min-height: 280px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. BẢNG DỮ LIỆU KIỂM TRA CHI TIẾT -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold m-0"><i class="bi bi-list-check me-2"></i>Danh Sách Ghi Nhận Kiểm Tra 5S Chi Tiết</h6>
            <select id="filter_status" class="form-select form-select-sm w-auto" onchange="renderTable()">
                <option value="all">Tất cả trạng thái</option>
                <option value="pending">Chưa khắc phục</option>
                <option value="resolved">Đã khắc phục</option>
            </select>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Ngày kiểm tra</th>
                            <th>Khu vực</th>
                            <th>Hạng mục 5S</th>
                            <th>Mô tả lỗi</th>
                            <th>Hình ảnh trước</th>
                            <th>Người phụ trách</th>
                            <th>Trạng thái</th>
                            <th>Hình ảnh sau</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody id="table-issue-body">
                        <!-- Data render bằng Javascript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 1: GHI NHẬN KIỂM TRA 5S -->
<div class="modal fade" id="modalNewAudit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-camera me-2"></i>Ghi Nhận Kiểm Tra 5S Mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formNewAudit" onsubmit="submitNewAudit(event)">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Chọn Khu Vực <span class="text-danger">*</span></label>
                            <select name="zone_id" id="audit_zone_id" class="form-select" required></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Phân Loại 5S <span class="text-danger">*</span></label>
                            <select name="s_category" class="form-select" required>
                                <option value="S1">S1 - Sàng lọc (Seiri)</option>
                                <option value="S2">S2 - Sắp xếp (Seiton)</option>
                                <option value="S3">S3 - Sạch sẽ (Seiso)</option>
                                <option value="S4">S4 - Săn sóc (Seiketsu)</option>
                                <option value="S5">S5 - Sẵn sàng (Shitsuke)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Chấm Vị Trí Lỗi Trên Layout <span class="text-danger">*</span></label>
                            <div class="position-relative border text-center overflow-auto" style="max-height: 280px; cursor: crosshair;" onclick="setLocationPin(event)">
                                <img id="modal-layout-img" src="resources/images/factory_layout.png" class="img-fluid" alt="Layout">
                                <div id="temp-pin" class="position-absolute bg-danger border border-white rounded-circle shadow" style="width: 16px; height: 16px; transform: translate(-50%, -50%); display: none;"></div>
                            </div>
                            <input type="hidden" name="pos_x" id="pos_x" required>
                            <input type="hidden" name="pos_y" id="pos_y" required>
                            <small class="text-muted d-block mt-1">Tọa độ đã chọn: X=<span id="val_x">0</span>%, Y=<span id="val_y">0</span>%</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Mô Tả Vi Phạm <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Nhập chi tiết lỗi phát sinh..." required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Hình Ảnh Chụp Thực Tế <span class="text-danger">*</span></label>
                            <input type="file" name="before_image" class="form-control" accept="image/*" capture="environment" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Lưu & Gửi Báo Cáo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 2: KHẮC PHỤC LỖI -->
<div class="modal fade" id="modalResolve" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-success"><i class="bi bi-check2-square me-2"></i>Cập Nhật Khắc Phục Lỗi 5S</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formResolve" onsubmit="submitResolve(event)">
                <input type="hidden" name="issue_id" id="resolve_issue_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Ghi Chú Xử Lý / Dọn Dẹp</label>
                        <textarea name="resolution_note" class="form-control" rows="2" placeholder="Mô tả hành động dọn dẹp, sắp xếp..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Hình Ảnh Sau Khi Khắc Phục <span class="text-danger">*</span></label>
                        <input type="file" name="after_image" class="form-control" accept="image/*" capture="environment" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-cloud-upload me-1"></i>Hoàn Tất Khắc Phục</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 3: CẤU HÌNH PHÂN CÔNG THEO THÁNG -->
<div class="modal fade" id="modalAssignment" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-gear me-2"></i>Phân Công Trách Nhiệm 5S</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAssignment" onsubmit="submitAssignment(event)">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tháng Phân Công <span class="text-danger">*</span></label>
                        <input type="month" name="month_year" class="form-control" value="<?php echo $current_month; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Khu Vực <span class="text-danger">*</span></label>
                        <select name="zone_id" id="assign_zone_id" class="form-select" required></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Người Kiểm Tra (Inspector) <span class="text-danger">*</span></label>
                        <select name="inspector_id" id="assign_inspector_id" class="form-select" required></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Người Phụ Trách Khắc Phục (Assignee) <span class="text-danger">*</span></label>
                        <select name="assignee_id" id="assign_assignee_id" class="form-select" required></select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Lưu Phân Công</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 4: XEM CHI TIẾT LỖI KHI CLICK PIN -->
<div class="modal fade" id="modalIssueDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Chi Tiết Lỗi Vi Phạm</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <span id="detail-status-badge"></span>
                </div>
                <p class="mb-1"><strong>Khu vực:</strong> <span id="detail-zone"></span></p>
                <p class="mb-1"><strong>Tiêu chí 5S:</strong> <span id="detail-category"></span></p>
                <p class="mb-1"><strong>Mô tả lỗi:</strong> <span id="detail-desc"></span></p>
                <p class="mb-1"><strong>Người phụ trách:</strong> <span id="detail-assignee"></span></p>
                <hr>
                <div class="row text-center">
                    <div class="col-6">
                        <p class="fw-bold small mb-1">Ảnh Khi Phát Sinh</p>
                        <img id="detail-img-before" src="" class="img-fluid rounded border style-img-thumb" alt="Before">
                    </div>
                    <div class="col-6">
                        <p class="fw-bold small mb-1">Ảnh Sau Khắc Phục</p>
                        <img id="detail-img-after" src="" class="img-fluid rounded border style-img-thumb" alt="After">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let globalIssuesData = [];
let chartInstance = null;

document.addEventListener("DOMContentLoaded", function () {
    loadDashboardData();
});

function loadDashboardData() {
    const month = document.getElementById('filter_month').value;
    
    fetch(`api/five_s_get_dashboard.php?month=${month}`)
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                globalIssuesData = data.issues || [];
                
                document.getElementById('kpi-total').innerText = data.kpi.total;
                document.getElementById('kpi-resolved').innerText = data.kpi.resolved;
                document.getElementById('kpi-pending').innerText = data.kpi.pending;
                document.getElementById('kpi-rate').innerText = data.kpi.rate + '%';
                
                renderLayoutPins(globalIssuesData);
                renderTable();
                renderCategoryChart(data.chart_data);
                populateDropdowns(data.zones, data.users);
            }
        })
        .catch(err => console.error("Error fetching 5S data:", err));
}

function renderLayoutPins(issues) {
    const container = document.getElementById('pin-container');
    container.innerHTML = '';
    
    issues.forEach((issue) => {
        const pin = document.createElement('div');
        pin.className = `pin-marker ${issue.status === 'resolved' ? 'pin-resolved' : 'pin-pending'}`;
        pin.style.left = `${issue.pos_x}%`;
        pin.style.top = `${issue.pos_y}%`;
        pin.title = `${issue.s_category}: ${issue.description}`;
        
        pin.onclick = (e) => {
            e.stopPropagation();
            showIssueDetail(issue);
        };
        
        container.appendChild(pin);
    });
}

function renderTable() {
    const filterStatus = document.getElementById('filter_status').value;
    const tbody = document.getElementById('table-issue-body');
    tbody.innerHTML = '';

    const filtered = globalIssuesData.filter(item => filterStatus === 'all' || item.status === filterStatus);

    if (filtered.length === 0) {
        tbody.innerHTML = `<tr><td colspan="10" class="text-center py-4 text-muted">Không có dữ liệu vi phạm 5S.</td></tr>`;
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
            <td>
                ${!isResolved ? `<button class="btn btn-sm btn-outline-success" onclick="openResolveModal(${item.id})"><i class="bi bi-check-lg"></i> Xử lý</button>` : `<button class="btn btn-sm btn-light text-muted" disabled><i class="bi bi-check-all"></i> Xong</button>`}
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function setLocationPin(event) {
    const img = document.getElementById('modal-layout-img');
    const rect = img.getBoundingClientRect();
    
    const x = ((event.clientX - rect.left) / rect.width) * 100;
    const y = ((event.clientY - rect.top) / rect.height) * 100;

    const posX = x.toFixed(2);
    const posY = y.toFixed(2);

    document.getElementById('pos_x').value = posX;
    document.getElementById('pos_y').value = posY;
    document.getElementById('val_x').innerText = posX;
    document.getElementById('val_y').innerText = posY;

    const tempPin = document.getElementById('temp-pin');
    tempPin.style.left = `${posX}%`;
    tempPin.style.top = `${posY}%`;
    tempPin.style.display = 'block';
}

function submitNewAudit(event) {
    event.preventDefault();
    const formData = new FormData(document.getElementById('formNewAudit'));

    fetch('api/five_s_save_audit.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(res => {
        if(res.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalNewAudit')).hide();
            document.getElementById('formNewAudit').reset();
            document.getElementById('temp-pin').style.display = 'none';
            loadDashboardData();
        } else {
            alert(res.message || 'Có lỗi xảy ra!');
        }
    });
}

function openResolveModal(id) {
    document.getElementById('resolve_issue_id').value = id;
    new bootstrap.Modal(document.getElementById('modalResolve')).show();
}

function submitResolve(event) {
    event.preventDefault();
    const formData = new FormData(document.getElementById('formResolve'));

    fetch('api/five_s_resolve_issue.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(res => {
        if(res.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalResolve')).hide();
            document.getElementById('formResolve').reset();
            loadDashboardData();
        } else {
            alert(res.message || 'Lỗi cập nhật!');
        }
    });
}

function submitAssignment(event) {
    event.preventDefault();
    const formData = new FormData(document.getElementById('formAssignment'));

    fetch('api/five_s_save_assignment.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(res => {
        if(res.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalAssignment')).hide();
            alert('Lưu phân công thành công');
        } else {
            alert(res.message || 'Có lỗi xảy ra!');
        }
    });
}

function showIssueDetail(issue) {
    document.getElementById('detail-zone').innerText = issue.zone_name;
    document.getElementById('detail-category').innerText = issue.s_category;
    document.getElementById('detail-desc').innerText = issue.description;
    document.getElementById('detail-assignee').innerText = issue.assignee_name || 'N/A';
    document.getElementById('detail-img-before').src = issue.before_image;
    document.getElementById('detail-img-after').src = issue.after_image || 'https://via.placeholder.com/150?text=Ch%C6%B0a+Kh%E1%BA%AFc+Ph%E1%BB%A5c';
    
    const isResolved = issue.status === 'resolved';
    document.getElementById('detail-status-badge').className = `badge ${isResolved ? 'bg-success' : 'bg-danger'} p-2`;
    document.getElementById('detail-status-badge').innerText = isResolved ? 'ĐÃ KHẮC PHỤC HOÀN TẤT' : 'CHƯA KHẮC PHỤC';

    new bootstrap.Modal(document.getElementById('modalIssueDetail')).show();
}

function populateDropdowns(zones, users) {
    const zoneSelect = document.getElementById('audit_zone_id');
    const assignZoneSelect = document.getElementById('assign_zone_id');
    const inspectorSelect = document.getElementById('assign_inspector_id');
    const assigneeSelect = document.getElementById('assign_assignee_id');

    if (zones) {
        let options = zones.map(z => `<option value="${z.id}">${z.zone_name} (${z.zone_code})</option>`).join('');
        zoneSelect.innerHTML = options;
        assignZoneSelect.innerHTML = options;
    }

    if (users) {
        let userOpts = users.map(u => `<option value="${u.id}">${u.fullname} (${u.username})</option>`).join('');
        inspectorSelect.innerHTML = userOpts;
        assigneeSelect.innerHTML = userOpts;
    }
}

function renderCategoryChart(chartData) {
    const options = {
        chart: { type: 'donut', height: 280 },
        series: chartData ? chartData.series : [0,0,0,0,0],
        labels: ['S1 - Sàng lọc', 'S2 - Sắp xếp', 'S3 - Sạch sẽ', 'S4 - Săn sóc', 'S5 - Sẵn sàng'],
        colors: ['#e74c3c', '#e67e22', '#f1c40f', '#2ecc71', '#3498db'],
        legend: { position: 'bottom' }
    };

    if (chartInstance) {
        chartInstance.destroy();
    }
    chartInstance = new ApexCharts(document.querySelector("#chart-5s-categories"), options);
    chartInstance.render();
}
</script>