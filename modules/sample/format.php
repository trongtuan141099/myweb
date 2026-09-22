<?php
// modules/sample/format.php
// Production Standard Template for DX Plastic Modules
require_once __DIR__ . '/../../core/check_permission.php';
?>

<style>
/* Module-specific styles scoped to this view */
.sample-custom-box {
    padding: 16px;
    background: var(--dx-bg-main);
    border-radius: var(--dx-radius-md);
    border: 1px dashed var(--dx-border);
}
</style>

<div class="app-page-wrapper">
    <!-- Standard Page Header -->
    <div class="app-page-header">
        <div>
            <h1 class="app-page-title">
                <span class="material-icons">extension</span>
                Module Tiêu Chuẩn Mẫu
            </h1>
            <p class="app-page-subtitle">Mẫu định dạng chuẩn dành cho việc phát triển các tính năng mới</p>
        </div>
        <div class="app-page-actions">
            <button type="button" class="app-btn app-btn-secondary">
                <span class="material-icons">refresh</span> Tải Lại
            </button>
            <button type="button" class="app-btn app-btn-primary">
                <span class="material-icons">add</span> Thêm Mới
            </button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="app-filter-card mb-3">
        <div class="row g-3 w-100">
            <div class="col-md-6">
                <input type="text" class="app-form-control" placeholder="Tìm kiếm nhanh...">
            </div>
            <div class="col-md-3">
                <select class="app-form-control">
                    <option value="">-- Tất cả trạng thái --</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Main Content Card -->
    <div class="app-card">
        <div class="p-3 border-bottom fw-bold d-flex align-items-center gap-2">
            <span class="material-icons text-primary fs-5">table_chart</span> Dữ Liệu Module
        </div>
        <div class="app-table-responsive">
            <table class="app-table table-sticky-header">
                <thead>
                    <tr>
                        <th style="width: 60px;">STT</th>
                        <th>Tên Bản Ghi</th>
                        <th>Trạng Thái</th>
                        <th style="width: 120px; text-align: center;">Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>#1</td>
                        <td>Mẫu bản ghi tham khảo</td>
                        <td><span class="app-badge badge-success">Đang hoạt động</span></td>
                        <td class="text-center">
                            <button class="app-btn app-btn-secondary py-1 px-2">
                                <span class="material-icons fs-6">edit</span>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Module-specific JavaScript logic
document.addEventListener("DOMContentLoaded", () => {
    console.log("Sample module initialized.");
});
</script>