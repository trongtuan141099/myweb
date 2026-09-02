<?php
// Tự động kiểm tra chế độ (Thêm hay Sửa)
$isEdit = false;
$employee_code_param = $_GET['id'] ?? '';

// Biến lưu dữ liệu Form
$emp = [
    'employee_code' => '',
    'full_name'     => '',
    'email'         => '',
    'phone'         => '',
    'dob'           => '',
    'gender'        => '',
    'cost_center'   => '',
    'job_level'     => '',
    'hire_date'     => '',
    'salary'        => '',
    'status'        => 'Active'
];

// Nếu có ID truyền lên -> Lấy dữ liệu cũ từ CSDL để Sửa
if (!empty($employee_code_param)) {
    $isEdit = true;
    $stmt = $conn->prepare("SELECT * FROM employees WHERE employee_code = ?");
    $stmt->bind_param("s", $employee_code_param);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $emp = array_merge($emp, $row);
    }
    $stmt->close();
}

// Xử lý khi người dùng nhấn nút LƯU (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code       = trim($_POST['employee_code'] ?? '');
    $name       = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $dob        = $_POST['dob'] ?: NULL;
    $gender     = $_POST['gender'] ?? '';
    $cost_center= $_POST['cost_center'] ?? '';
    $job_level  = trim($_POST['job_level'] ?? '');
    $hire_date  = $_POST['hire_date'] ?: NULL;
    $salary     = !empty($_POST['salary']) ? floatval(str_replace(['.', ','], '', $_POST['salary'])) : 0;
    $status     = $_POST['status'] ?? 'Active';

    if ($isEdit) {
        // CẬP NHẬT DỮ LIỆU CŨ
        $sql = "UPDATE employees SET full_name=?, email=?, phone=?, dob=?, gender=?, cost_center=?, job_level=?, hire_date=?, salary=?, status=? WHERE employee_code=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssdss", $name, $email, $phone, $dob, $gender, $cost_center, $job_level, $hire_date, $salary, $status, $code);
        $stmt->execute();
        $stmt->close();
    } else {
        // THÊM MỚI HÀNG DỮ LIỆU
        $sql = "INSERT INTO employees (employee_code, full_name, email, phone, dob, gender, cost_center, job_level, hire_date, salary, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssdss", $code, $name, $email, $phone, $dob, $gender, $cost_center, $job_level, $hire_date, $salary, $status);
        $stmt->execute();
        $stmt->close();
    }

    // Sau khi lưu xong -> Điều hướng ngay về trang Danh sách nhân viên
    echo "<script>window.location.href='index.php?mainpage=employees';</script>";
    exit;
}
?>

<style>
/* Scope giao diện riêng biệt cho Form */
.form-wrapper {
  padding: 20px;
  height: calc(100vh - var(--header-height) - var(--footer-height));
  overflow-y: auto;
  background-color: var(--bg-main);
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--bg-card);
  padding: 14px 20px;
  border-radius: 10px;
  border: 1px solid var(--border-color);
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}

.form-header h1 {
  font-size: 18px;
  font-weight: 700;
  color: var(--text-main);
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 0;
}

.form-header h1 .material-icons {
  color: var(--primary);
  font-size: 24px;
}

/* Card chứa Form */
.form-card {
  background: var(--bg-card);
  border-radius: 10px;
  border: 1px solid var(--border-color);
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
  max-width: 900px;
  margin: 0 auto;
  width: 100%;
  overflow: hidden;
}

.form-card-title {
  background-color: var(--primary);
  color: #ffffff;
  padding: 12px 20px;
  font-size: 14px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

.form-body {
  padding: 24px;
}

/* Form Group Layout */
.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px 20px;
}

@media (max-width: 768px) {
  .form-grid { grid-template-columns: 1fr; }
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-main);
}

.form-group label .required {
  color: var(--danger);
}

.form-control-custom {
  width: 100%;
  padding: 9px 12px;
  background: var(--bg-main);
  border: 1px solid var(--border-color);
  color: var(--text-main);
  border-radius: 6px;
  font-size: 13px;
  outline: none;
  transition: border-color 0.15s ease;
}

.form-control-custom:focus {
  border-color: var(--primary);
  background: #ffffff;
}

.form-control-custom:disabled {
  background: var(--bg-hover);
  color: var(--text-muted);
  cursor: not-allowed;
}

/* Footer Buttons */
.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid var(--border-color);
}

.btn-save {
  background-color: var(--primary);
  color: #ffffff;
  border: none;
  padding: 9px 20px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
}

.btn-save:hover {
  background-color: var(--primary-hover);
}

.btn-cancel {
  background-color: var(--bg-main);
  color: var(--text-main);
  border: 1px solid var(--border-color);
  padding: 9px 20px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 6px;
}

.btn-cancel:hover {
  background-color: var(--bg-hover);
}
</style>

<div class="form-wrapper">
    <!-- Header -->
    <div class="form-header">
        <h1>
            <span class="material-icons"><?= $isEdit ? 'edit_note' : 'person_add'; ?></span> 
            <?= $isEdit ? 'Chỉnh Sửa Thông Tin Nhân Viên' : 'Thêm Nhân Viên Mới'; ?>
        </h1>
        <a href="index.php?mainpage=employees" class="btn-cancel">
            <span class="material-icons" style="font-size:18px;">arrow_back</span> Quay Lại
        </a>
    </div>

    <!-- Card Form -->
    <div class="form-card">
        <div class="form-card-title">
            <span class="material-icons" style="font-size:18px;">badge</span>
            Thông Tin Chi Tiết Nhân Viên
        </div>

        <form method="POST" class="form-body">
            <div class="form-grid">
                <!-- Mã nhân viên -->
                <div class="form-group">
                    <label>Mã nhân viên <span class="required">*</span></label>
                    <input type="text" name="employee_code" class="form-control-custom" 
                           value="<?= htmlspecialchars($emp['employee_code']); ?>" 
                           placeholder="Ví dụ: EMP001" required <?= $isEdit ? 'readonly' : ''; ?>>
                </div>

                <!-- Tên đầy đủ -->
                <div class="form-group">
                    <label>Tên đầy đủ <span class="required">*</span></label>
                    <input type="text" name="full_name" class="form-control-custom" 
                           value="<?= htmlspecialchars($emp['full_name']); ?>" 
                           placeholder="Nhập họ và tên..." required>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" name="email" class="form-control-custom" 
                           value="<?= htmlspecialchars($emp['email']); ?>" 
                           placeholder="nguyenvana@email.com" required>
                </div>

                <!-- Điện thoại -->
                <div class="form-group">
                    <label>Điện thoại</label>
                    <input type="text" name="phone" class="form-control-custom" 
                           value="<?= htmlspecialchars($emp['phone']); ?>" 
                           placeholder="0901234567">
                </div>

                <!-- Ngày sinh -->
                <div class="form-group">
                    <label>Ngày sinh</label>
                    <input type="date" name="dob" class="form-control-custom" 
                           value="<?= htmlspecialchars($emp['dob']); ?>">
                </div>

                <!-- Giới tính -->
                <div class="form-group">
                    <label>Giới tính</label>
                    <select name="gender" class="form-control-custom">
                        <option value="">-- Chọn giới tính --</option>
                        <option value="Nam" <?= $emp['gender'] === 'Nam' ? 'selected' : ''; ?>>Nam</option>
                        <option value="Nữ" <?= $emp['gender'] === 'Nữ' ? 'selected' : ''; ?>>Nữ</option>
                    </select>
                </div>

                <!-- Phòng ban / Cost Center -->
                <div class="form-group">
                    <label>Phòng ban (Cost Center)</label>
                    <select name="cost_center" class="form-control-custom">
                        <option value="">-- Chọn phòng ban --</option>
                        <option value="IT" <?= $emp['cost_center'] === 'IT' ? 'selected' : ''; ?>>IT</option>
                        <option value="HR" <?= $emp['cost_center'] === 'HR' ? 'selected' : ''; ?>>HR</option>
                        <option value="Sales" <?= $emp['cost_center'] === 'Sales' ? 'selected' : ''; ?>>Sales</option>
                        <option value="Finance" <?= $emp['cost_center'] === 'Finance' ? 'selected' : ''; ?>>Finance</option>
                        <option value="A00330" <?= $emp['cost_center'] === 'A00330' ? 'selected' : ''; ?>>A00330</option>
                    </select>
                </div>

                <!-- Vị trí / Cấp bậc -->
                <div class="form-group">
                    <label>Vị trí / Cấp bậc</label>
                    <input type="text" name="job_level" class="form-control-custom" 
                           value="<?= htmlspecialchars($emp['job_level']); ?>" 
                           placeholder="vd: Kỹ sư phần mềm">
                </div>

                <!-- Ngày tuyển -->
                <div class="form-group">
                    <label>Ngày tuyển</label>
                    <input type="date" name="hire_date" class="form-control-custom" 
                           value="<?= htmlspecialchars($emp['hire_date']); ?>">
                </div>

                <!-- Lương cơ bản -->
                <div class="form-group">
                    <label>Lương cơ bản (VND)</label>
                    <input type="text" name="salary" class="form-control-custom" 
                           value="<?= $emp['salary'] ? number_format($emp['salary'], 0, ',', '.') : ''; ?>" 
                           placeholder="25.000.000">
                </div>

                <!-- Trạng thái -->
                <div class="form-group">
                    <label>Trạng thái</label>
                    <select name="status" class="form-control-custom">
                        <option value="Active" <?= $emp['status'] === 'Active' ? 'selected' : ''; ?>>Hoạt động</option>
                        <option value="Inactive" <?= $emp['status'] === 'Inactive' ? 'selected' : ''; ?>>Không hoạt động</option>
                        <option value="On Leave" <?= $emp['status'] === 'On Leave' ? 'selected' : ''; ?>>Đang nghỉ</option>
                    </select>
                </div>
            </div>

            <!-- Nút thao tác -->
            <div class="form-actions">
                <button type="submit" class="btn-save">
                    <span class="material-icons" style="font-size:18px;">save</span> 
                    <?= $isEdit ? 'Lưu Cập Nhật' : 'Lưu Nhân Viên'; ?>
                </button>
                <a href="index.php?mainpage=employees" class="btn-cancel">
                    <span class="material-icons" style="font-size:18px;">cancel</span> Hủy
                </a>
            </div>
        </form>
    </div>
</div>