<?php
// modules/five_s/overview.php
if (!defined('INDEX_AUTH')) {
    define('INDEX_AUTH', true);
}
$current_month = date('Y-m');
?>

<style>
/* Layout Container cố định tỉ lệ giúp tọa độ Pin chính xác 100% khi zoom/resize */
.layout-container {
    position: relative;
    display: inline-block;
    width: 100%;
    max-width: 1000px;
    margin: 0 auto;
}

.layout-container img {
    width: 100%;
    height: auto;
    display: block;
}

.pin-marker {
    position: absolute;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 2px solid #ffffff;
    transform: translate(-50%, -50%);
    cursor: pointer;
    box-shadow: 0 0 8px rgba(0,0,0,0.5);
    animation: pulse 1.8s infinite;
    z-index: 10;
}

.pin-pending { background-color: #dc3545; }

@keyframes pulse {
    0% { transform: translate(-50%, -50%) scale(0.9); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
    70% { transform: translate(-50%, -50%) scale(1.2); box-shadow: 0 0 0 8px rgba(220, 53, 69, 0); }
    100% { transform: translate(-50%, -50%) scale(0.9); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
}

/* Canvas Crop Box Style */
#crop-canvas {
    border: 2px dashed #0d6efd;
    max-width: 100%;
    cursor: move;
}
</style>

<script src="resources/apexcharts/apexcharts.min.js"></script>

<div class="app-page-wrapper">
    <!-- Header -->
    <div class="app-page-header">
        <div>
            <h1 class="app-page-title">
                <span class="material-icons">verified</span>
                Tổng Quan 5S Nhà Máy
            </h1>
            <p class="app-page-subtitle">Hiển thị các điểm lỗi 5S phát sinh chưa được xử lý trên sơ đồ trực quan</p>
        </div>
        <div class="app-page-actions">
            <div class="d-flex align-items-center gap-1">
                <label for="filter_month" class="app-form-label mb-0 text-nowrap">Chọn tháng:</label>
                <input type="month" id="filter_month" class="app-form-control" value="<?php echo $current_month; ?>" onchange="loadDashboardData()">
            </div>
            <button class="app-btn app-btn-primary" data-bs-toggle="modal" data-bs-target="#modalNewAudit">
                <span class="material-icons">add</span> Báo cáo Patron 5S
            </button>
        </div>
    </div>

    <!-- Thẻ KPI -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-4 border-primary h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase text-muted fw-bold small">Tổng Số Vi Phạm</div>
                        <div class="h3 fw-bold mb-0" id="kpi-total">0</div>
                    </div>
                    <div class="badge bg-primary-subtle text-primary p-3 rounded-circle"><i class="bi bi-exclamation-octagon fs-4"></i></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-4 border-success h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase text-muted fw-bold small">Đã Khắc Phục</div>
                        <div class="h3 fw-bold mb-0 text-success" id="kpi-resolved">0</div>
                    </div>
                    <div class="badge bg-success-subtle text-success p-3 rounded-circle"><i class="bi bi-check-circle fs-4"></i></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-4 border-danger h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase text-muted fw-bold small">Chưa Khắc Phục (Hiển thị)</div>
                        <div class="h3 fw-bold mb-0 text-danger" id="kpi-pending">0</div>
                    </div>
                    <div class="badge bg-danger-subtle text-danger p-3 rounded-circle"><i class="bi bi-x-circle fs-4"></i></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-4 border-info h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase text-muted fw-bold small">Tỷ Lệ Phục Hồi</div>
                        <div class="h3 fw-bold mb-0 text-info" id="kpi-rate">0%</div>
                    </div>
                    <div class="badge bg-info-subtle text-info p-3 rounded-circle"><i class="bi bi-pie-chart fs-4"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sơ Đồ Layout & Biểu Đồ -->
    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold m-0"><i class="bi bi-map me-2"></i>Vị Trí Đang Vi Phạm 5S (Chưa Khắc Phục)</h6>
                </div>
                <div class="card-body text-center p-3 overflow-auto">
                    <div class="layout-container">
                        <img id="layout-img" src="resources/images/factory_layout.png" alt="Layout Nhà Xưởng" onerror="this.src='https://via.placeholder.com/1000x500?text=S%C6%A1+%C4%90%E1%BB%93+M%E1%BA%B7t+B%E1%BA%B1ng'">
                        <div id="pin-container"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold m-0"><i class="bi bi-bar-chart-line me-2"></i>Phân Tích Loại Vi Phạm</h6>
                </div>
                <div class="card-body">
                    <div id="chart-5s-categories" style="min-height: 280px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 1: TẠO MỚI KIỂM TRA -->
<div class="modal fade" id="modalNewAudit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Ghi Nhận Kiểm Tra 5S</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formNewAudit" onsubmit="submitNewAudit(event)">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Khu Vực <span class="text-danger">*</span></label>
                            <select name="zone_id" id="audit_zone_id" class="form-select" required></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Hạng Mục 5S <span class="text-danger">*</span></label>
                            <select name="s_category" class="form-select" required>
                                <option value="S1">S1 - Sàng lọc (Seiri)</option>
                                <option value="S2">S2 - Sắp xếp (Seiton)</option>
                                <option value="S3">S3 - Sạch sẽ (Seiso)</option>
                                <option value="S4">S4 - Săn sóc (Seiketsu)</option>
                                <option value="S5">S5 - Sẵn sàng (Shitsuke)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Chọn Vị Trí Trên Layout <span class="text-danger">*</span></label>
                            <div class="layout-container border cursor-pointer" onclick="setLocationPin(event)">
                                <img src="resources/images/factory_layout.png" alt="Layout">
                                <div id="temp-pin" class="pin-marker pin-pending" style="display: none;"></div>
                            </div>
                            <input type="hidden" name="pos_x" id="pos_x" required>
                            <input type="hidden" name="pos_y" id="pos_y" required>
                            <small class="text-muted">Tọa độ: X=<span id="val_x">0</span>%, Y=<span id="val_y">0</span>%</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Mô Tả Vi Phạm <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Ảnh Vi Phạm Phát Sinh <span class="text-danger">*</span></label>
                            <input type="file" name="before_image" class="form-control" accept="image/*" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu Báo Cáo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 2: POPUP CHI TIẾT VI PHẠM KHI CLICK PIN -->
<div class="modal fade" id="modalIssueDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Chi Tiết Vi Phạm 5S</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1"><strong>Khu vực:</strong> <span id="detail-zone"></span></p>
                <p class="mb-1"><strong>Tiêu chí:</strong> <span id="detail-category"></span></p>
                <p class="mb-1"><strong>Mô tả lỗi:</strong> <span id="detail-desc"></span></p>
                <p class="mb-2"><strong>Người phụ trách:</strong> <span id="detail-assignee"></span></p>
                <div class="text-center my-2">
                    <img id="detail-img-before" src="" class="img-fluid rounded border" style="max-height: 200px;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-success" id="btn-go-resolve"><i class="bi bi-tools me-1"></i>Hành Động Khắc Phục</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 3: CẮT/THU NHỎ VÀ CẬP NHẬT ẢNH KHẮC PHỤC -->
<div class="modal fade" id="modalResolve" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-success">Cập Nhật Khắc Phục Vi Phạm</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formResolve" onsubmit="submitResolve(event)">
                <input type="hidden" name="issue_id" id="resolve_issue_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Ghi Chú Khắc Phục</label>
                        <textarea name="resolution_note" class="form-control" rows="2" placeholder="Nội dung dọn dẹp..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Chọn Ảnh Khắc Phục <span class="text-danger">*</span></label>
                        <input type="file" id="input-after-image" class="form-control" accept="image/*" onchange="previewAndCropImage(event)" required>
                    </div>
                    <div class="mb-3 text-center d-none" id="crop-wrapper">
                        <label class="form-label small text-muted">Ảnh đã nén/chuẩn hóa kích thước khung:</label>
                        <div class="border p-2 bg-light d-inline-block rounded">
                            <canvas id="crop-canvas" width="400" height="300"></canvas>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success">Hoàn Tất Khắc Phục</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 4: CẤU HÌNH PHÂN CÔNG THEO THÁNG -->
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

<!-- MODAL POPUP THÔNG BÁO LỊCH TUẦN TRA (BƯỚC 1) -->
<div class="modal fade" id="modalScheduleNotice" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-start border-4 border-warning">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-warning"><i class="bi bi-bell-fill me-2"></i>Lịch Tuần Tra 5S Hôm Nay</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">Danh sách các khu vực bạn được phân công tuần tra trong ngày:</p>
                <div class="list-group" id="schedule-list-group"></div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL BÁO CÁO KIỂM TRA CHUẨN OK/NG (BƯỚC 2 & 3) -->
<div class="modal fade" id="modalChecklistAudit" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-qr-code-scan me-2"></i>Kiểm Tra 5S Khu Vực</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formChecklistAudit" onsubmit="submitChecklistAudit(event)">
                <input type="hidden" name="schedule_id" id="audit_schedule_id">
                <input type="hidden" name="zone_id" id="audit_scan_zone_id">
                <input type="hidden" name="qr_code" id="audit_qr_code">
                <input type="hidden" name="checklist_json" value="[&quot;legacy-audit&quot;]">
                
                <div class="modal-body">
                    <!-- Bước 2: Quét mã QR/NFC -->
                    <div id="step-verify-location" class="p-3 bg-light rounded text-center mb-3">
                        <h6><i class="bi bi-geo-alt me-1"></i>Xác Thực Vị Trí Về Mặt Bằng</h6>
                        <div class="input-group my-2 w-75 mx-auto">
                            <input type="text" id="input_qr_code" class="form-control" placeholder="Quét hoặc nhập mã QR/NFC khu vực...">
                            <button type="button" class="btn btn-outline-primary" onclick="verifyLocation()">Xác Nhận</button>
                        </div>
                        <small id="verify-status" class="text-danger d-block"></small>
                    </div>

                    <!-- Bước 3: Form Checklist Đạt/Vi phạm (Ẩn cho đến khi xác thực thành công) -->
                    <div id="step-audit-content" class="d-none">
                        <!-- Mẫu chuẩn OK / NG -->
                        <div class="row text-center mb-3 g-2">
                            <div class="col-6">
                                <div class="border rounded p-2 bg-success-subtle">
                                    <span class="badge bg-success mb-1">Chuẩn OK</span>
                                    <img src="resources/images/sample_ok.jpg" class="img-fluid rounded border d-block mx-auto" style="max-height:100px" onerror="this.src='https://via.placeholder.com/150x100?text=Mau+OK'">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2 bg-danger-subtle">
                                    <span class="badge bg-danger mb-1">Chuẩn NG (Lỗi)</span>
                                    <img src="resources/images/sample_ng.jpg" class="img-fluid rounded border d-block mx-auto" style="max-height:100px" onerror="this.src='https://via.placeholder.com/150x100?text=Mau+NG'">
                                </div>
                            </div>
                        </div>

                        <!-- Chọn Trạng Thái -->
                        <div class="mb-3 text-center">
                            <label class="form-label fw-bold d-block">Đánh Giá Khu Vực:</label>
                            <div class="btn-group w-50" role="group">
                                <input type="radio" class="btn-check" name="audit_result" id="res_ok" value="OK" checked onchange="toggleAuditForm()">
                                <label class="btn btn-outline-success" for="res_ok"><i class="bi bi-check-circle me-1"></i>ĐẠT (OK)</label>

                                <input type="radio" class="btn-check" name="audit_result" id="res_ng" value="NG" onchange="toggleAuditForm()">
                                <label class="btn btn-outline-danger" for="res_ng"><i class="bi bi-exclamation-triangle me-1"></i>VI PHẠM (NG)</label>
                            </div>
                        </div>

                        <!-- Phần nhập khi NG -->
                        <div id="ng-fields" class="d-none border-top pt-3">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Hạng Mục Lỗi</label>
                                    <select name="s_category" class="form-select">
                                        <option value="S1">S1 - Sàng lọc</option>
                                        <option value="S2">S2 - Sắp xếp</option>
                                        <option value="S3">S3 - Sạch sẽ</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Ảnh Minh Họa Lỗi <span class="text-danger">*</span></label>
                                    <input type="file" name="before_image" id="before_image_input" class="form-control" accept="image/*">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Mô Tả Vi Phạm <span class="text-danger">*</span></label>
                                    <textarea name="description" id="ng_desc_input" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label fw-bold">Ảnh thực tế khu vực <span class="text-danger">*</span></label>
                            <input type="file" name="actual_image" id="actual_image_input" class="form-control" accept="image/*" capture="environment" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" id="btn-submit-audit" class="btn btn-primary d-none">Gửi Báo Cáo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let globalIssuesData = [];
let chartInstance = null;
let croppedBlobData = null;

document.addEventListener("DOMContentLoaded", loadDashboardData);

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
                
                const pendingIssues = globalIssuesData.filter(i => i.status === 'pending');
                renderLayoutPins(pendingIssues);
                renderCategoryChart(data.chart_data);
                populateDropdowns(data.zones, data.users);
            }
        });
}

function renderLayoutPins(issues) {
    const container = document.getElementById('pin-container');
    container.innerHTML = '';
    
    issues.forEach((issue) => {
        const pin = document.createElement('div');
        pin.className = 'pin-marker pin-pending';
        pin.style.left = `${issue.pos_x}%`;
        pin.style.top = `${issue.pos_y}%`;
        pin.title = `${issue.s_category}: ${issue.description}`;
        
        pin.onclick = (e) => {
            e.stopPropagation();
            openIssueDetail(issue);
        };
        container.appendChild(pin);
    });
}

function openIssueDetail(issue) {
    document.getElementById('detail-zone').innerText = issue.zone_name;
    document.getElementById('detail-category').innerText = issue.s_category;
    document.getElementById('detail-desc').innerText = issue.description;
    document.getElementById('detail-assignee').innerText = issue.assignee_name || 'Chưa gán';
    document.getElementById('detail-img-before').src = issue.before_image;
    
    document.getElementById('btn-go-resolve').onclick = () => {
        bootstrap.Modal.getInstance(document.getElementById('modalIssueDetail')).hide();
        openResolveModal(issue.id);
    };

    new bootstrap.Modal(document.getElementById('modalIssueDetail')).show();
}

function setLocationPin(event) {
    const rect = event.currentTarget.getBoundingClientRect();
    const x = (((event.clientX - rect.left) / rect.width) * 100).toFixed(2);
    const y = (((event.clientY - rect.top) / rect.height) * 100).toFixed(2);

    document.getElementById('pos_x').value = x;
    document.getElementById('pos_y').value = y;
    document.getElementById('val_x').innerText = x;
    document.getElementById('val_y').innerText = y;

    const tempPin = document.getElementById('temp-pin');
    tempPin.style.left = `${x}%`;
    tempPin.style.top = `${y}%`;
    tempPin.style.display = 'block';
}

function openResolveModal(id) {
    document.getElementById('resolve_issue_id').value = id;
    document.getElementById('crop-wrapper').classList.add('d-none');
    croppedBlobData = null;
    new bootstrap.Modal(document.getElementById('modalResolve')).show();
}

function previewAndCropImage(e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function (evt) {
        const img = new Image();
        img.onload = function () {
            const canvas = document.getElementById('crop-canvas');
            const ctx = canvas.getContext('2d');
            
            const maxWidth = 800;
            const maxHeight = 600;
            let width = img.width;
            let height = img.height;

            if (width > height) {
                if (width > maxWidth) {
                    height *= maxWidth / width;
                    width = maxWidth;
                }
            } else {
                if (height > maxHeight) {
                    width *= maxHeight / height;
                    height = maxHeight;
                }
            }

            canvas.width = width;
            canvas.height = height;
            ctx.drawImage(img, 0, 0, width, height);

            document.getElementById('crop-wrapper').classList.remove('d-none');
            
            canvas.toBlob((blob) => {
                croppedBlobData = blob;
            }, 'image/jpeg', 0.8);
        };
        img.src = evt.target.result;
    };
    reader.readAsDataURL(file);
}

function submitResolve(event) {
    event.preventDefault();
    const formData = new FormData(document.getElementById('formResolve'));
    
    if (croppedBlobData) {
        formData.set('after_image', croppedBlobData, 'after_crop.jpg');
    }

    fetch('api/five_s_resolve_issue.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(res => {
        if(res.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalResolve')).hide();
            loadDashboardData();
        } else {
            alert(res.message);
        }
    });
}

function submitNewAudit(event) {
    event.preventDefault();
    const formData = new FormData(document.getElementById('formNewAudit'));
    fetch('api/five_s_save_audit.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(res => {
        if(res.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalNewAudit')).hide();
            loadDashboardData();
        }
    });
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
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const options = {
        chart: { type: 'donut', height: 280, background: 'transparent' },
        theme: { mode: isDark ? 'dark' : 'light' },
        series: chartData ? chartData.series : [0,0,0,0,0],
        labels: ['S1 - Sàng lọc', 'S2 - Sắp xếp', 'S3 - Sạch sẽ', 'S4 - Săn sóc', 'S5 - Sẵn sàng'],
        colors: ['#ef4444', '#f97316', '#eab308', '#10b981', '#3b82f6'],
        legend: { labels: { colors: isDark ? '#94a3b8' : '#475569' } },
        stroke: { colors: [isDark ? '#111827' : '#ffffff'] }
    };
    if (chartInstance) chartInstance.destroy();
    chartInstance = new ApexCharts(document.querySelector("#chart-5s-categories"), options);
    chartInstance.render();
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

// Kiểm tra lịch và hiển thị Popup tự động khi nạp trang (Bước 1)
document.addEventListener("DOMContentLoaded", function() {
    loadDashboardData();
    checkDailySchedules();
});

function checkDailySchedules() {
    fetch('api/five_s_get_schedules.php')
        .then(res => res.json())
        .then(data => {
            const todaySchedules = (data.schedules || []).filter(schedule => Number(schedule.can_audit) === 1);
            if (data.success && todaySchedules.length > 0) {
                const listGroup = document.getElementById('schedule-list-group');
                listGroup.innerHTML = '';
                
                todaySchedules.forEach(s => {
                    listGroup.innerHTML += `
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${s.zone_name}</strong> (${s.zone_code})
                                <br><small class="text-muted">Lịch: ${s.schedule_date}</small>
                            </div>
                            <button class="btn btn-sm btn-primary" onclick="startAuditProcess(${s.id}, ${s.zone_id})">Kiểm Tra Ngay</button>
                        </div>
                    `;
                });
                
                new bootstrap.Modal(document.getElementById('modalScheduleNotice')).show();
            }
        });
}

function startAuditProcess(scheduleId, zoneId) {
    bootstrap.Modal.getInstance(document.getElementById('modalScheduleNotice')).hide();
    document.getElementById('audit_schedule_id').value = scheduleId;
    document.getElementById('audit_scan_zone_id').value = zoneId;
    
    // Resets
    document.getElementById('step-verify-location').classList.remove('d-none');
    document.getElementById('step-audit-content').classList.add('d-none');
    document.getElementById('btn-submit-audit').classList.add('d-none');
    document.getElementById('input_qr_code').value = '';
    document.getElementById('verify-status').innerText = '';
    
    new bootstrap.Modal(document.getElementById('modalChecklistAudit')).show();
}

function verifyLocation() {
    const zoneId = document.getElementById('audit_scan_zone_id').value;
    const qrCode = document.getElementById('input_qr_code').value;

    const formData = new FormData();
    formData.append('zone_id', zoneId);
    formData.append('qr_code', qrCode);

    fetch('api/five_s_verify_location.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                document.getElementById('audit_qr_code').value = qrCode;
                document.getElementById('step-verify-location').classList.add('d-none');
                document.getElementById('step-audit-content').classList.remove('d-none');
                document.getElementById('btn-submit-audit').classList.remove('d-none');
            } else {
                document.getElementById('verify-status').innerText = res.message;
            }
        });
}

function toggleAuditForm() {
    const isNG = document.getElementById('res_ng').checked;
    const ngFields = document.getElementById('ng-fields');
    const imgInput = document.getElementById('before_image_input');
    const descInput = document.getElementById('ng_desc_input');
    const actualImageInput = document.getElementById('actual_image_input');

    if (isNG) {
        ngFields.classList.remove('d-none');
        imgInput.required = true;
        descInput.required = true;
        actualImageInput.required = false;
    } else {
        ngFields.classList.add('d-none');
        imgInput.required = false;
        descInput.required = false;
        actualImageInput.required = true;
    }
}

function submitChecklistAudit(event) {
    event.preventDefault();
    const formData = new FormData(document.getElementById('formChecklistAudit'));

    fetch('api/five_s_save_audit_full.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalChecklistAudit')).hide();
                loadDashboardData();
                alert("Đã ghi nhận kết quả thành công!");
            } else {
                alert(res.message);
            }
        });
}

// Lắng nghe sự kiện đổi chế độ Sáng / Tối để vẽ lại biểu đồ
window.addEventListener('dxThemeChanged', () => {
    loadDashboardData();
});
</script>