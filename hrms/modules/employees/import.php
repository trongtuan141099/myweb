<h1 class="mb-4"><i class="bi bi-upload"></i> Nhập dữ liệu nhân viên</h1>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-file-earmark-excel"></i> Tải file Excel
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Tải file Excel có định dạng: Mã NV, Tên, Email, Phòng ban, Vị trí, Lương
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Chọn file Excel</label>
                        <input type="file" class="form-control" name="excelFile" accept=".xls,.xlsx" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload"></i> Nhập dữ liệu
                    </button>
                    <a href="modules/template_employees.xlsx" class="btn btn-secondary">
                        <i class="bi bi-download"></i> Tải template
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>
