<?php
/**
 * Migration Script: Tạo bảng dashboard_banners và dữ liệu mẫu truyền thông nội bộ
 */
require_once __DIR__ . '/../config/db.php';

echo "=== KHỞI TẠO BẢNG DASHBOARD_BANNERS ===\n";

// 1. Tạo thư mục upload nếu chưa có
$uploadDir = __DIR__ . '/../uploads/dashboard_banners';
$thumbDir  = __DIR__ . '/../uploads/dashboard_banners/thumbs';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}
if (!file_exists($thumbDir)) {
    mkdir($thumbDir, 0777, true);
}

// 2. Tạo bảng dashboard_banners
$sqlTable = "CREATE TABLE IF NOT EXISTS `dashboard_banners` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `summary` TEXT NULL,
  `content` LONGTEXT NULL,
  `category` VARCHAR(50) NOT NULL DEFAULT '5S',
  `image_url` VARCHAR(500) NOT NULL,
  `images` LONGTEXT NULL,
  `event_date` DATE NOT NULL,
  `display_order` INT NOT NULL DEFAULT 0,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `link_url` VARCHAR(500) NULL,
  `created_by` VARCHAR(100) NULL DEFAULT 'Admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_status_order` (`status`, `display_order`, `event_date`),
  INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sqlTable)) {
    echo "1. Tạo/Kiểm tra bảng `dashboard_banners`: THÀNH CÔNG!\n";
} else {
    die("Lỗi tạo bảng: " . $conn->error . "\n");
}

// 3. Tạo file cấu hình Carousel mặc định nếu chưa có
$configFile = __DIR__ . '/../config/dashboard_carousel_settings.json';
if (!file_exists($configFile)) {
    $defaultSettings = [
        'autoplay'         => true,
        'interval'         => 5000,
        'pause_on_hover'   => true,
        'filter_category'  => 'all',
        'max_items'        => 10,
        'animation_effect' => 'slide',
        'show_indicators'  => true,
        'show_arrows'      => true,
        'show_badges'      => true
    ];
    file_put_contents($configFile, json_encode($defaultSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "2. Khởi tạo file cấu hình Carousel: THÀNH CÔNG!\n";
}

// 4. Hàm sinh file ảnh SVG chuyên nghiệp phong cách Industrial Enterprise
function generateBannerSvg($filename, $category, $title, $subtitle, $color1, $color2, $icon) {
    global $uploadDir, $thumbDir;
    $svgPath = $uploadDir . '/' . $filename;
    $thumbPath = $thumbDir . '/' . $filename;

    $svgContent = '<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 500" width="1200" height="500">
  <defs>
    <linearGradient id="bgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="' . $color1 . '" />
      <stop offset="100%" stop-color="' . $color2 . '" />
    </linearGradient>
    <linearGradient id="overlay" x1="0%" y1="0%" x2="100%" y2="0%">
      <stop offset="0%" stop-color="rgba(15, 23, 42, 0.85)" />
      <stop offset="60%" stop-color="rgba(15, 23, 42, 0.55)" />
      <stop offset="100%" stop-color="rgba(15, 23, 42, 0.25)" />
    </linearGradient>
    <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
      <path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(255, 255, 255, 0.08)" stroke-width="1"/>
    </pattern>
  </defs>

  <!-- Background Base -->
  <rect width="1200" height="500" fill="url(#bgGrad)" />
  <rect width="1200" height="500" fill="url(#grid)" />
  <rect width="1200" height="500" fill="url(#overlay)" />

  <!-- Decorative Shapes -->
  <circle cx="1050" cy="120" r="220" fill="rgba(255, 255, 255, 0.06)" />
  <circle cx="950" cy="380" r="160" fill="rgba(255, 255, 255, 0.04)" />
  
  <!-- Watermark Icon right -->
  <text x="960" y="320" font-family="-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif" font-size="140" fill="rgba(255, 255, 255, 0.12)" text-anchor="middle">' . $icon . '</text>

  <!-- Badge Container -->
  <rect x="70" y="65" width="220" height="42" rx="21" fill="rgba(255, 255, 255, 0.22)" stroke="rgba(255, 255, 255, 0.4)" stroke-width="1.5" />
  <text x="180" y="92" font-family="-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif" font-size="15" font-weight="800" fill="#ffffff" letter-spacing="1.5" text-anchor="middle">★ ' . htmlspecialchars($category) . ' ★</text>

  <!-- Title -->
  <text x="70" y="165" font-family="-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif" font-size="34" font-weight="800" fill="#ffffff">
    ' . htmlspecialchars($title) . '
  </text>

  <!-- Subtitle / Description -->
  <text x="70" y="215" font-family="-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif" font-size="19" font-weight="400" fill="rgba(255, 255, 255, 0.88)">
    ' . htmlspecialchars($subtitle) . '
  </text>

  <!-- Brand Footer -->
  <line x1="70" y1="410" x2="1130" y2="410" stroke="rgba(255, 255, 255, 0.15)" stroke-width="1" />
  <text x="70" y="445" font-family="-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif" font-size="14" font-weight="600" fill="rgba(255, 255, 255, 0.75)">
    DX PLASTIC GROUP • NHÀ MÁY THÔNG MINH 2026
  </text>
  <text x="1130" y="445" font-family="-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif" font-size="13" font-weight="500" fill="rgba(255, 255, 255, 0.65)" text-anchor="end">
    Chất lượng • An toàn • Cải tiến liên tục
  </text>
</svg>';

    file_put_contents($svgPath, $svgContent);
    file_put_contents($thumbPath, $svgContent);
}

// 5. Kiểm tra nếu chưa có dữ liệu mẫu thì thêm 5 hoạt động mẫu đại diện
$cntCheck = $conn->query("SELECT COUNT(*) as c FROM `dashboard_banners`")->fetch_assoc()['c'];

if ($cntCheck == 0) {
    echo "3. Chưa có banner, đang tạo 5 banner hoạt động mẫu tiêu biểu...\n";

    $samples = [
        [
            'title'       => 'Hoạt động 5S Tuần 40: Tổng vệ sinh & Chuẩn hóa khu vực máy đùn',
            'summary'     => '100% cán bộ công nhân viên tham gia chiến dịch 5S định kỳ, sàng lọc và sắp xếp trực quan giúp nâng cao an toàn và hiệu suất làm việc.',
            'category'    => '5S',
            'filename'    => 'banner_5s_week40.svg',
            'color1'      => '#065f46',
            'color2'      => '#10b981',
            'icon'        => '✨ 5S',
            'date'        => date('Y-m-d', strtotime('-2 days')),
            'order'       => 1
        ],
        [
            'title'       => 'Đề xuất Kaizen tiêu biểu: Tối ưu cụm gom ống tự động giảm 15% thời gian dừng máy',
            'summary'     => 'Sáng kiến cải tiến kỹ thuật từ Đội Kỹ thuật & Bảo trì giúp giảm thiểu kẹt ống, tiết kiệm hơn 120 giờ vận hành mỗi tháng.',
            'category'    => 'Kaizen',
            'filename'    => 'banner_kaizen_opt.svg',
            'color1'      => '#4c1d95',
            'color2'      => '#8b5cf6',
            'icon'        => '💡 KAIZEN',
            'date'        => date('Y-m-d', strtotime('-5 days')),
            'order'       => 2
        ],
        [
            'title'       => 'Tuyên dương Tổ Sản Xuất Đùn Ca 1: Đạt kỷ lục sản lượng và không sự cố an toàn',
            'summary'     => 'Chúc mừng tập thể Ca 1 đã hoàn thành xuất sắc 118% kế hoạch sản lượng tháng với chỉ số chất lượng đạt 99.8% OEE.',
            'category'    => 'Tuyên dương',
            'filename'    => 'banner_tuyen_duong_ca1.svg',
            'color1'      => '#78350f',
            'color2'      => '#f59e0b',
            'icon'        => '🏆 TOP',
            'date'        => date('Y-m-d', strtotime('-8 days')),
            'order'       => 3
        ],
        [
            'title'       => 'Vinh danh Giải Nhất Hội thi Cải tiến & Đổi mới Quy trình Sản xuất 2026',
            'summary'     => 'Giải thưởng được trao cho dự án Chuẩn hóa phối trộn hạt màu tự động, cắt giảm 2.4 tấn phế phẩm nhựa trong quý 3.',
            'category'    => 'Giải thưởng',
            'filename'    => 'banner_giai_thuong_2026.svg',
            'color1'      => '#991b1b',
            'color2'      => '#ef4444',
            'icon'        => '🎖️ AWARD',
            'date'        => date('Y-m-d', strtotime('-12 days')),
            'order'       => 4
        ],
        [
            'title'       => 'Hoạt động sản xuất nổi bật: Nghiệm thu dây chuyền đùn cao tốc thế hệ mới',
            'summary'     => 'Đưa vào vận hành thử nghiệm thành công line đùn ống PU/PA thế hệ mới, đáp ứng các đơn hàng tiêu chuẩn xuất khẩu khắt khe.',
            'category'    => 'Hoạt động sản xuất',
            'filename'    => 'banner_san_xuat_line_moi.svg',
            'color1'      => '#1e3a8a',
            'color2'      => '#3b82f6',
            'icon'        => '⚙️ FACTORY',
            'date'        => date('Y-m-d', strtotime('-15 days')),
            'order'       => 5
        ]
    ];

    $stmt = $conn->prepare("INSERT INTO `dashboard_banners` 
        (`title`, `summary`, `category`, `image_url`, `images`, `event_date`, `display_order`, `status`, `created_by`) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'active', 'Admin')");

    foreach ($samples as $s) {
        generateBannerSvg($s['filename'], $s['category'], $s['title'], $s['summary'], $s['color1'], $s['color2'], $s['icon']);
        
        $imgUrl = 'uploads/dashboard_banners/' . $s['filename'];
        $imagesJson = json_encode([$imgUrl], JSON_UNESCAPED_SLASHES);
        
        $stmt->bind_param("ssssssi", 
            $s['title'], 
            $s['summary'], 
            $s['category'], 
            $imgUrl, 
            $imagesJson, 
            $s['date'], 
            $s['order']
        );
        $stmt->execute();
        echo "   + Đã thêm banner: [{$s['category']}] {$s['title']}\n";
    }
    $stmt->close();
} else {
    echo "3. Bảng đã có sẵn $cntCheck banner.\n";
}

echo "=== HOÀN TẤT KHỞI TẠO HỆ THỐNG BANNER ===\n";
