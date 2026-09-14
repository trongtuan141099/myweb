<?php
// modules/five_s/mobile_audit.php
if (!defined('INDEX_AUTH')) { define('INDEX_AUTH', true); }
?>

<div class="container-fluid py-3" style="max-width: 600px; margin: 0 auto;">
    <!-- Nút quét QR/NFC nhanh -->
    <div class="card border-0 shadow-sm mb-3 text-center bg-primary text-white p-3 rounded-4">
        <h5 class="fw-bold mb-1"><i class="bi bi-qr-code-scan me-2"></i>Bắt Đầu Tuần Tra 5S</h5>
        <p class="small mb-2 opacity-75">Quét mã QR tại khu vực nhà xưởng để xác thực vị trí</p>
        <button class="btn btn-light btn-sm fw-bold rounded-pill mx-auto" onclick="startQRScanner()">
            <i class="bi bi-camera me-1"></i>Mở Camera Quét QR
        </button>
    </div>

    <!-- Khung xem Checklist hạng mục -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 fw-bold">
            Checklist Hạng Mục Kiểm Tra 5S
        </div>
        <div class="card-body">
            <!-- Hạng mục ví dụ 1 -->
            <div class="border-bottom pb-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold text-dark">1. Hàng hóa đúng vị trí (S2)</span>
                    <div class="btn-group btn-group-sm" role="group">
                        <input type="radio" class="btn-check" name="check_1" id="ok_1" checked>
                        <label class="btn btn-outline-success" for="ok_1">ĐẠT</label>
                        <input type="radio" class="btn-check" name="check_1" id="ng_1">
                        <label class="btn btn-outline-danger" for="ng_1">VI PHẠM</label>
                    </div>
                </div>
                <div class="row g-2 text-center small">
                    <div class="col-6">
                        <span class="text-success d-block fw-bold mb-1">Chuẩn OK</span>
                        <img src="resources/images/sample_ok.jpg" class="img-fluid rounded border" onerror="this.src='https://via.placeholder.com/150x100?text=Mau+OK'">
                    </div>
                    <div class="col-6">
                        <span class="text-danger d-block fw-bold mb-1">Chuẩn NG</span>
                        <img src="resources/images/sample_ng.jpg" class="img-fluid rounded border" onerror="this.src='https://via.placeholder.com/150x100?text=Mau+NG'">
                    </div>
                </div>
            </div>

            <!-- Nút gửi báo cáo -->
            <button class="btn btn-primary w-100 fw-bold py-2 rounded-3" onclick="submitAuditList()">
                <i class="bi bi-send-check me-1"></i>Hoàn Tất & Gửi Báo Cáo
            </button>
        </div>
    </div>
</div>