<?php
/**
 * Migration Script: Tạo bảng announcements & announcement_views và dữ liệu mẫu
 * DX Plastic Group - Industrial Enterprise Design System
 */
require_once __DIR__ . '/../config/db.php';

echo "=== KHỞI TẠO BẢNG ANNOUNCEMENTS & ANNOUNCEMENT_VIEWS ===\n";

if (!isset($conn) || !$conn instanceof mysqli) {
    die("Lỗi: Không thể kết nối CSDL!\n");
}

// 1. Tạo bảng announcements
$sqlAnnouncements = "CREATE TABLE IF NOT EXISTS `announcements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `summary` TEXT NULL,
  `content` LONGTEXT NULL,
  `priority` ENUM('normal', 'high', 'urgent') NOT NULL DEFAULT 'normal',
  `is_important` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `valid_from` DATE NOT NULL,
  `valid_to` DATE NULL,
  `total_views` INT NOT NULL DEFAULT 0,
  `created_by` VARCHAR(100) NULL DEFAULT 'Admin',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_status_valid` (`status`, `valid_from`, `valid_to`),
  INDEX `idx_important_priority` (`is_important`, `priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sqlAnnouncements)) {
    echo "1. Tạo/Kiểm tra bảng `announcements`: THÀNH CÔNG!\n";
} else {
    die("Lỗi tạo bảng `announcements`: " . $conn->error . "\n");
}

// 2. Tạo bảng announcement_views
$sqlViews = "CREATE TABLE IF NOT EXISTS `announcement_views` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `announcement_id` INT NOT NULL,
  `user_id` INT NULL,
  `username` VARCHAR(100) NOT NULL,
  `fullname` VARCHAR(150) NULL,
  `view_count` INT NOT NULL DEFAULT 1,
  `first_viewed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `last_viewed_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ip_address` VARCHAR(45) NULL,
  UNIQUE KEY `uk_announcement_user` (`announcement_id`, `username`),
  INDEX `idx_username` (`username`),
  CONSTRAINT `fk_announcement_view` FOREIGN KEY (`announcement_id`) REFERENCES `announcements`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sqlViews)) {
    echo "2. Tạo/Kiểm tra bảng `announcement_views`: THÀNH CÔNG!\n";
} else {
    die("Lỗi tạo bảng `announcement_views`: " . $conn->error . "\n");
}

// 3. Khởi tạo dữ liệu mẫu nếu bảng đang trống
$checkData = $conn->query("SELECT COUNT(*) AS total FROM `announcements`");
$row = $checkData ? $checkData->fetch_assoc() : null;
$total = $row ? (int)$row['total'] : 0;

if ($total === 0) {
    echo "3. Bảng trống, đang nạp dữ liệu mẫu ban đầu...\n";
    $today = date('Y-m-d');
    $nextMonth = date('Y-m-d', strtotime('+30 days'));
    $nextYear = date('Y-m-d', strtotime('+180 days'));

    $sampleData = [
        [
            'title' => 'Thông báo diễn tập PCCC & Cứu nạn cứu hộ toàn nhà máy Quý IV/2026',
            'summary' => 'Yêu cầu toàn thể CBCNV các bộ phận Đùn nhựa, Cuộn nhựa, Kho vận và Văn phòng tham gia diễn tập phòng cháy chữa cháy định kỳ vào thứ Bảy tuần này.',
            'content' => "<p>Kính gửi: Toàn thể Cán bộ - Công nhân viên Công ty Cổ phần DX Plastic Group,</p>\n"
                       . "<p>Thực hiện kế hoạch An toàn vệ sinh lao động và Phòng cháy chữa cháy năm 2026, Ban An toàn Nhà máy phối hợp cùng Cảnh sát PCCC tổ chức buổi diễn tập phương án chữa cháy và thoát nạn định kỳ:</p>\n"
                       . "<ul>\n"
                       . "<li><strong>Thời gian:</strong> 08:30 - 11:30, Thứ Bảy ngày 17/10/2026.</li>\n"
                       . "<li><strong>Địa điểm:</strong> Toàn bộ khu vực Xưởng sản xuất Đùn - Cuộn và Sân trung tâm.</li>\n"
                       . "<li><strong>Nội dung:</strong> Hướng dẫn thoát hiểm khi có chuông báo cháy, thực hành dập lửa bằng bình khí CO2 và vòi rồng cứu hỏa.</li>\n"
                       . "</ul>\n"
                       . "<p><em>Lưu ý: Tất cả các chuyền sản xuất cần cử tối thiểu 50% quân số tham gia theo danh sách đã phân công từ Đội trưởng PCCC cơ sở.</em></p>",
            'priority' => 'urgent',
            'is_important' => 1,
            'status' => 'active',
            'valid_from' => $today,
            'valid_to' => $nextMonth,
            'created_by' => 'admin'
        ],
        [
            'title' => 'Quy định mới về kiểm soát hao hụt hạt nhựa nguyên sinh và phụ gia',
            'summary' => 'Áp dụng quy trình cân đối nguyên liệu chuẩn theo tiêu chuẩn ISO 9001:2015 từ ngày 10/10/2026 nhằm kiểm soát hao hụt dưới ngưỡng 0.8%.',
            'content' => "<p>Nhằm nâng cao hiệu quả sử dụng vật tư và giảm thiểu lãng phí trong quá trình vận hành máy Đùn nhựa:</p>\n"
                       . "<p>1. Tất cả các ca sản xuất bắt buộc thực hiện cân đối trọng bao hạt nhựa và tỉ lệ Masterbatch trước khi đổ phễu nạp liệu.</p>\n"
                       . "<p>2. Ghi nhận nhật ký tiêu hao trên phần mềm theo đúng lô nguyên liệu thực tế.</p>\n"
                       . "<p>3. Trưởng ca chịu trách nhiệm trực tiếp nếu tỉ lệ hao hụt vượt quá định mức 0.8%/ca mà không có giải trình bất thường hợp lệ.</p>",
            'priority' => 'high',
            'is_important' => 1,
            'status' => 'active',
            'valid_from' => $today,
            'valid_to' => $nextYear,
            'created_by' => 'admin'
        ],
        [
            'title' => 'Lịch khám sức khỏe định kỳ năm 2026 cho cán bộ công nhân viên',
            'summary' => 'Ban Nhân sự thông báo lịch khám sức khỏe tổng quát và tầm soát bệnh nghề nghiệp cho toàn bộ nhân viên nhà máy tại Bệnh viện Đa khoa Quốc tế.',
            'content' => "<p>Ban Nhân sự kính gửi thông báo về lịch khám sức khỏe định kỳ năm 2026:</p>\n"
                       . "<p>- <strong>Đợt 1 (Khối Văn phòng & Kỹ thuật):</strong> Ngày 22/10/2026.</p>\n"
                       . "<p>- <strong>Đợt 2 (Khối Sản xuất Ca 1, Ca 2):</strong> Ngày 24/10/2026.</p>\n"
                       . "<p>- <strong>Đợt 3 (Khối Sản xuất Ca 3 & Kho vận):</strong> Ngày 25/10/2026.</p>\n"
                       . "<p>CBCNV vui lòng nhịn ăn sáng trước khi lấy máu xét nghiệm. Mọi thắc mắc liên hệ phòng Y tế / Nhân sự để được hướng dẫn.</p>",
            'priority' => 'normal',
            'is_important' => 0,
            'status' => 'active',
            'valid_from' => $today,
            'valid_to' => $nextMonth,
            'created_by' => 'admin'
        ]
    ];

    $stmt = $conn->prepare("INSERT INTO `announcements` (`title`, `summary`, `content`, `priority`, `is_important`, `status`, `valid_from`, `valid_to`, `created_by`, `total_views`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
    
    foreach ($sampleData as $item) {
        $stmt->bind_param("ssssissss", 
            $item['title'], 
            $item['summary'], 
            $item['content'], 
            $item['priority'], 
            $item['is_important'], 
            $item['status'], 
            $item['valid_from'], 
            $item['valid_to'], 
            $item['created_by']
        );
        $stmt->execute();
    }
    $stmt->close();
    echo "-> Đã thêm " . count($sampleData) . " thông báo mẫu thành công!\n";
} else {
    echo "3. Bảng `announcements` đã có {$total} bản ghi, giữ nguyên dữ liệu hiện tại.\n";
}

echo "=== HOÀN TẤT MIGRATION ANNOUNCEMENTS ===\n";
