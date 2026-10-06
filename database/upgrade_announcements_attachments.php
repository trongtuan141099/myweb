<?php
/**
 * Migration Script: Nâng cấp bảng announcements hỗ trợ nhiều ảnh & file tài liệu đính kèm
 * DX Plastic Group - Industrial Enterprise Design System
 */
require_once __DIR__ . '/../config/db.php';

echo "=== NÂNG CẤP BẢNG ANNOUNCEMENTS: ĐÍNH KÈM ẢNH & FILE ===\n";

if (!isset($conn) || !$conn instanceof mysqli) {
    die("Lỗi: Không thể kết nối CSDL!\n");
}

// 1. Tạo các thư mục lưu trữ nếu chưa có
$baseUpload = __DIR__ . '/../uploads/announcements';
$imgDir     = $baseUpload . '/images';
$thumbDir   = $imgDir . '/thumbs';
$fileDir    = $baseUpload . '/files';

$dirs = [$baseUpload, $imgDir, $thumbDir, $fileDir];
foreach ($dirs as $d) {
    if (!file_exists($d)) {
        mkdir($d, 0777, true);
        echo "Tạo thư mục: {$d}\n";
    }
}

// 2. Bổ sung cột `images` và `attachments` vào bảng `announcements`
$checkImagesCol = $conn->query("SHOW COLUMNS FROM `announcements` LIKE 'images'");
if ($checkImagesCol && $checkImagesCol->num_rows == 0) {
    $conn->query("ALTER TABLE `announcements` ADD COLUMN `images` LONGTEXT NULL AFTER `content`");
    echo "1. Đã thêm cột `images` vào bảng `announcements`\n";
} else {
    echo "1. Cột `images` đã tồn tại\n";
}

$checkAttachCol = $conn->query("SHOW COLUMNS FROM `announcements` LIKE 'attachments'");
if ($checkAttachCol && $checkAttachCol->num_rows == 0) {
    $conn->query("ALTER TABLE `announcements` ADD COLUMN `attachments` LONGTEXT NULL AFTER `images`");
    echo "2. Đã thêm cột `attachments` vào bảng `announcements`\n";
} else {
    echo "2. Cột `attachments` đã tồn tại\n";
}

// 3. Khởi tạo một số file mẫu và cập nhật cho thông báo mẫu ban đầu (để kiểm thử sinh động)
$samplePdfPath = $fileDir . '/Quy_Dinh_PCCC_2026.pdf';
if (!file_exists($samplePdfPath)) {
    // Tạo file PDF giả lập nội dung mẫu
    file_put_contents($samplePdfPath, "%PDF-1.4\n%DX Plastic Group - Quy Dinh PCCC 2026\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000000 65535 f \n0000000053 00000 n \n0000000100 00000 n \n0000000153 00000 n \ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n230\n%%EOF");
}

$sampleDocPath = $fileDir . '/Ke_Hoach_Dien_Tap.docx';
if (!file_exists($sampleDocPath)) {
    file_put_contents($sampleDocPath, "Kế hoạch diễn tập PCCC DX Plastic 2026");
}

$sampleXlsPath = $fileDir . '/Dinh_Muc_Hao_Hut_Nguyen_Lieu.xlsx';
if (!file_exists($sampleXlsPath)) {
    file_put_contents($sampleXlsPath, "Bảng định mức hao hụt hạt nhựa DX Plastic 2026");
}

// Cập nhật thông báo mẫu 1 có attachments
$sampleAttach1 = json_encode([
    [
        'url' => 'uploads/announcements/files/Quy_Dinh_PCCC_2026.pdf',
        'name' => 'Quy_Dinh_PCCC_2026.pdf',
        'ext' => 'pdf',
        'size' => 245760,
        'size_formatted' => '240 KB',
        'uploaded_at' => date('Y-m-d H:i:s')
    ],
    [
        'url' => 'uploads/announcements/files/Ke_Hoach_Dien_Tap.docx',
        'name' => 'Ke_Hoach_Dien_Tap.docx',
        'ext' => 'docx',
        'size' => 112640,
        'size_formatted' => '110 KB',
        'uploaded_at' => date('Y-m-d H:i:s')
    ]
], JSON_UNESCAPED_UNICODE);

// Cập nhật thông báo mẫu 2 có attachment excel
$sampleAttach2 = json_encode([
    [
        'url' => 'uploads/announcements/files/Dinh_Muc_Hao_Hut_Nguyen_Lieu.xlsx',
        'name' => 'Dinh_Muc_Hao_Hut_Nguyen_Lieu.xlsx',
        'ext' => 'xlsx',
        'size' => 524288,
        'size_formatted' => '512 KB',
        'uploaded_at' => date('Y-m-d H:i:s')
    ]
], JSON_UNESCAPED_UNICODE);

// Sử dụng lại ảnh svg banner sẵn có làm ảnh mẫu cho thông báo 1
$sampleImg1 = json_encode([
    [
        'url' => 'uploads/dashboard_banners/banner_5s_week40.svg',
        'thumb' => 'uploads/dashboard_banners/thumbs/banner_5s_week40.svg',
        'name' => 'banner_5s_week40.svg',
        'order' => 0
    ],
    [
        'url' => 'uploads/dashboard_banners/banner_5s_week40_step2.svg',
        'thumb' => 'uploads/dashboard_banners/thumbs/banner_5s_week40_step2.svg',
        'name' => 'banner_5s_week40_step2.svg',
        'order' => 1
    ]
], JSON_UNESCAPED_UNICODE);

$conn->query("UPDATE `announcements` SET `attachments` = '{$conn->real_escape_string($sampleAttach1)}', `images` = '{$conn->real_escape_string($sampleImg1)}' WHERE `id` = 1");
$conn->query("UPDATE `announcements` SET `attachments` = '{$conn->real_escape_string($sampleAttach2)}' WHERE `id` = 2");

echo "3. Cập nhật dữ liệu mẫu đính kèm cho bài viết 1 & 2: THÀNH CÔNG!\n";
echo "=== HOÀN TẤT NÂNG CẤP CƠ SỞ DỮ LIỆU ===\n";

