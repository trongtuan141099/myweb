<?php
require "config/db.php";

// Insert sample departments
$departments = [
    ['IT', 'Phòng Công Nghệ Thông Tin'],
    ['HR', 'Phòng Nhân Sự'],
    ['Sales', 'Phòng Bán Hàng'],
    ['Finance', 'Phòng Tài Chính'],
    ['Operations', 'Phòng Vận Hành']
];

foreach ($departments as $dept) {
    $conn->query("INSERT IGNORE INTO departments (name, description) VALUES ('{$dept[0]}', '{$dept[1]}')");
}

// Insert sample employees
$employees = [
    ['EMP001', 'Nguyễn Văn A', 'nguyenvana@email.com', '0912345678', '1990-05-15', 'Male', 'Kỹ sư phần mềm', 'IT', '2020-01-15', 25000000, 'Active'],
    ['EMP002', 'Trần Thị B', 'tranthib@email.com', '0912345679', '1992-03-20', 'Female', 'Quản lý nhân sự', 'HR', '2019-06-01', 18000000, 'Active'],
    ['EMP003', 'Lê Văn C', 'levanc@email.com', '0912345680', '1988-07-10', 'Male', 'Giám đốc bán hàng', 'Sales', '2018-01-10', 30000000, 'Active'],
    ['EMP004', 'Phạm Thị D', 'phamthid@email.com', '0912345681', '1995-11-25', 'Female', 'Kỹ sư phần mềm', 'IT', '2021-03-15', 24000000, 'Active'],
    ['EMP005', 'Hoàng Văn E', 'hoangvane@email.com', '0912345682', '1991-08-30', 'Male', 'Nhân viên bán hàng', 'Sales', '2020-09-01', 15000000, 'Active'],
    ['EMP006', 'Đỗ Thị F', 'dothif@email.com', '0912345683', '1993-12-05', 'Female', 'Kế toán', 'Finance', '2019-02-15', 16000000, 'Active'],
];

foreach ($employees as $emp) {
    $sql = "INSERT IGNORE INTO employees (employee_id, full_name, email, phone, dob, gender, position, department, join_date, salary, status)
            VALUES ('$emp[0]', '$emp[1]', '$emp[2]', '$emp[3]', '$emp[4]', '$emp[5]', '$emp[6]', '$emp[7]', '$emp[8]', $emp[9], '$emp[10]')";
    $conn->query($sql);
}

// Insert sample attendance
$today = date('Y-m-d');
$emp_result = $conn->query("SELECT id FROM employees");
while ($emp = $emp_result->fetch_assoc()) {
    for ($i = 0; $i < 10; $i++) {
        $date = date('Y-m-d', strtotime($today . " -$i days"));
        $check_in = date('H:i', strtotime("08:00"));
        $check_out = date('H:i', strtotime("17:00"));
        $status = rand(1, 20) == 1 ? 'Absent' : (rand(1, 20) <= 2 ? 'Late' : 'Present');

        $conn->query("INSERT IGNORE INTO attendance (employee_id, date, check_in, check_out, status)
                     VALUES ({$emp['id']}, '$date', '$check_in', '$check_out', '$status')");
    }
}

echo '<div style="padding: 20px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px;">';
echo '<h3>✓ Dữ liệu mẫu đã được thêm thành công!</h3>';
echo '<p>Các dữ liệu sau đã được thêm vào:</p>';
echo '<ul>';
echo '<li>5 phòng ban</li>';
echo '<li>6 nhân viên</li>';
echo '<li>Dữ liệu chuyên cần cho 10 ngày qua</li>';
echo '</ul>';
echo '<p><a href="index.php" style="color: #155724; font-weight: bold;">Quay lại trang chủ</a></p>';
echo '</div>';

$conn->close();
?>
