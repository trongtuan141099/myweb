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

<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-primary"><i class="bi bi-shield-check me-2"></i>Tổng Quan 5S & Sơ Đồ Vi Phạm</h4>
            <p class="text-muted small mb-0">Hiển thị các điểm lỗi 5S phát sinh chưa được xử lý trên sơ đồ trực quan</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <label for="filter_month" class="form-label mb-0 fw-bold small text-nowrap">Chọn tháng:</label>
            <input type="month" id="filter_month" class="form-control form-control-sm" value="<?php echo $current_month; ?>" onchange="loadDashboardData()">
            <a href="index.php?mainpage=five_s&subpage=list" class="btn btn-outline-primary btn-sm text-nowrap">
                <i class="bi bi-table me-1"></i>Xem Danh Sách Chi Tiết
            </a>
            <button class="btn btn-primary btn-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#modalNewAudit">
                <i class="bi bi-plus-lg me-1"></i>Tạo Phiếu Kiểm Tra
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
                    <!-- Khung cho phép xem trước và thu gọn ảnh tối ưu kích thước -->
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
                
                // Chỉ hiển thị điểm vi phạm CHƯA khắc phục trên sơ đồ
                const pendingIssues = globalIssuesData.filter(i => i.status === 'pending');
                renderLayoutPins(pendingIssues);
                renderCategoryChart(data.chart_data);
                populateDropdowns(data.zones);
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

// Hàm Xử lý Tải & Co Giãn / Nén Ảnh Trong Khung Cho Phép (Canvas)
function previewAndCropImage(e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function (evt) {
        const img = new Image();
        img.onload = function () {
            const canvas = document.getElementById('crop-canvas');
            const ctx = canvas.getContext('2d');
            
            // Kích thước chuẩn hóa khung (Max 800x600)
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
            
            // Xuất Blob JPEG đã nén dung lượng 80%
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

function populateDropdowns(zones) {
    if (!zones) return;
    document.getElementById('audit_zone_id').innerHTML = zones.map(z => `<option value="${z.id}">${z.zone_name}</option>`).join('');
}

function renderCategoryChart(chartData) {
    const options = {
        chart: { type: 'donut', height: 280 },
        series: chartData ? chartData.series : [0,0,0,0,0],
        labels: ['S1 - Sàng lọc', 'S2 - Sắp xếp', 'S3 - Sạch sẽ', 'S4 - Săn sóc', 'S5 - Sẵn sàng'],
        colors: ['#e74c3c', '#e67e22', '#f1c40f', '#2ecc71', '#3498db']
    };
    if (chartInstance) chartInstance.destroy();
    chartInstance = new ApexCharts(document.querySelector("#chart-5s-categories"), options);
    chartInstance.render();
}
</script>