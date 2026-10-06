<?php
/**
 * API Quản lý & Cung cấp dữ liệu Banner Hoạt động & Cải tiến (Dashboard Carousel)
 * DX Plastic Group - Industrial Enterprise Design System
 * Tích hợp hệ thống phân quyền chi tiết BannerActivity.*
 */
ob_start();
session_start();
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors', 0);

$response = [
    'success' => false,
    'message' => 'Lỗi không xác định!'
];

try {
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../core/check_permission.php';

    if (!isset($conn) || !$conn instanceof mysqli) {
        throw new Exception('Không thể kết nối cơ sở dữ liệu!');
    }

    $uploadDir = __DIR__ . '/../uploads/dashboard_banners';
    $thumbDir  = __DIR__ . '/../uploads/dashboard_banners/thumbs';
    if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
    if (!file_exists($thumbDir))  mkdir($thumbDir, 0777, true);

    $configFile = __DIR__ . '/../config/dashboard_carousel_settings.json';

    $action = $_GET['action'] ?? $_POST['action'] ?? 'list';

    // =========================================================================
    // HÀM KIỂM TRA PHÂN QUYỀN CHẶT CHẼ THEO BANNERACTIVITY.*
    // =========================================================================
    function verifyBannerPermission($requiredPerm) {
        $userRole = $_SESSION['user']['role'] ?? '';
        if ($userRole === 'admin') return true;

        $permsToCheck = is_array($requiredPerm) ? $requiredPerm : [$requiredPerm];
        // Cho phép backward-compatibility nếu có quyền tương đương
        if (hasPermission($permsToCheck)) {
            return true;
        }

        http_response_code(403);
        $reqStr = implode(', ', $permsToCheck);
        throw new Exception("Bạn không có quyền thực hiện thao tác này! (Yêu cầu quyền: $reqStr)");
    }

    // =========================================================================
    // HÀM HỖ TRỢ XỬ LÝ HÌNH ẢNH (RESIZE, NÉN, TẠO THUMBNAIL)
    // =========================================================================
    function processUploadedImage($fileTmp, $originalName, $uploadDir, $thumbDir) {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
        if (!in_array($ext, $allowedExts, true)) {
            throw new Exception("Định dạng file '$ext' không được hỗ trợ! Chỉ chấp nhận JPG, PNG, WEBP, SVG.");
        }

        $baseName = 'banner_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4));
        $destFile = $baseName . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
        $destPath = $uploadDir . '/' . $destFile;
        $thumbPath = $thumbDir . '/' . $destFile;

        // Nếu là SVG, copy trực tiếp
        if ($ext === 'svg') {
            if (!move_uploaded_file($fileTmp, $destPath)) {
                throw new Exception("Không thể lưu file SVG tải lên!");
            }
            copy($destPath, $thumbPath);
            return [
                'url'   => 'uploads/dashboard_banners/' . $destFile,
                'thumb' => 'uploads/dashboard_banners/thumbs/' . $destFile
            ];
        }

        // Nếu GD extension bật, thực hiện resize và nén tối ưu
        if (extension_loaded('gd')) {
            $srcImg = null;
            switch ($ext) {
                case 'jpg':
                case 'jpeg':
                    $srcImg = @imagecreatefromjpeg($fileTmp);
                    break;
                case 'png':
                    $srcImg = @imagecreatefrompng($fileTmp);
                    break;
                case 'webp':
                    $srcImg = @imagecreatefromwebp($fileTmp);
                    break;
            }

            if ($srcImg) {
                $srcW = imagesx($srcImg);
                $srcH = imagesy($srcImg);

                // 1. Resize ảnh lớn (Max width 1600, max height 900)
                $maxW = 1600;
                $maxH = 900;
                $ratio = min($maxW / $srcW, $maxH / $srcH, 1.0);
                $targetW = (int)round($srcW * $ratio);
                $targetH = (int)round($srcH * $ratio);

                $mainCanvas = imagecreatetruecolor($targetW, $targetH);
                if ($ext === 'png' || $ext === 'webp') {
                    imagealphablending($mainCanvas, false);
                    imagesavealpha($mainCanvas, true);
                    $transparent = imagecolorallocatealpha($mainCanvas, 255, 255, 255, 127);
                    imagefilledrectangle($mainCanvas, 0, 0, $targetW, $targetH, $transparent);
                }
                imagecopyresampled($mainCanvas, $srcImg, 0, 0, 0, 0, $targetW, $targetH, $srcW, $srcH);

                // Lưu ảnh chính tối ưu
                if ($ext === 'png') {
                    imagepng($mainCanvas, $destPath, 8);
                } elseif ($ext === 'webp') {
                    imagewebp($mainCanvas, $destPath, 85);
                } else {
                    imagejpeg($mainCanvas, $destPath, 85);
                }
                imagedestroy($mainCanvas);

                // 2. Tạo thumbnail nhỏ (Max width 360, max height 220)
                $thumbMaxW = 360;
                $thumbMaxH = 220;
                $thumbRatio = min($thumbMaxW / $srcW, $thumbMaxH / $srcH, 1.0);
                $tW = (int)round($srcW * $thumbRatio);
                $tH = (int)round($srcH * $thumbRatio);

                $thumbCanvas = imagecreatetruecolor($tW, $tH);
                if ($ext === 'png' || $ext === 'webp') {
                    imagealphablending($thumbCanvas, false);
                    imagesavealpha($thumbCanvas, true);
                    $transparent = imagecolorallocatealpha($thumbCanvas, 255, 255, 255, 127);
                    imagefilledrectangle($thumbCanvas, 0, 0, $tW, $tH, $transparent);
                }
                imagecopyresampled($thumbCanvas, $srcImg, 0, 0, 0, 0, $tW, $tH, $srcW, $srcH);

                if ($ext === 'png') {
                    imagepng($thumbCanvas, $thumbPath, 7);
                } elseif ($ext === 'webp') {
                    imagewebp($thumbCanvas, $thumbPath, 80);
                } else {
                    imagejpeg($thumbCanvas, $thumbPath, 80);
                }
                imagedestroy($thumbCanvas);
                imagedestroy($srcImg);

                return [
                    'url'   => 'uploads/dashboard_banners/' . $destFile,
                    'thumb' => 'uploads/dashboard_banners/thumbs/' . $destFile
                ];
            }
        }

        // Fallback lưu trực tiếp nếu GD không thể đọc
        if (!move_uploaded_file($fileTmp, $destPath)) {
            throw new Exception("Không thể lưu file ảnh tải lên!");
        }
        @copy($destPath, $thumbPath);

        return [
            'url'   => 'uploads/dashboard_banners/' . $destFile,
            'thumb' => 'uploads/dashboard_banners/thumbs/' . $destFile
        ];
    }

    // =========================================================================
    // 1. ACTION: LẤY DANH SÁCH BANNER (Dành cho Dashboard Carousel & Quản trị)
    // =========================================================================
    if ($action === 'list') {
        // Kiểm tra quyền xem
        verifyBannerPermission(['BannerActivity.View', 'dashboard.view']);

        $filterCategory = trim($_GET['category'] ?? 'all');
        $filterStatus   = trim($_GET['status'] ?? '');
        $limit          = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

        $whereClauses = [];
        $params = [];
        $types = '';

        if (!empty($filterStatus)) {
            $whereClauses[] = "`status` = ?";
            $params[] = $filterStatus;
            $types .= 's';
        }

        if (!empty($filterCategory) && $filterCategory !== 'all') {
            $whereClauses[] = "`category` = ?";
            $params[] = $filterCategory;
            $types .= 's';
        }

        $sql = "SELECT `id`, `title`, `summary`, `content`, `category`, `image_url`, `images`, 
                       `event_date`, `display_order`, `status`, `link_url`, `created_by`, `created_at` 
                FROM `dashboard_banners`";

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        $sql .= " ORDER BY `display_order` ASC, `event_date` DESC, `id` DESC LIMIT ?";
        $params[] = $limit;
        $types .= 'i';

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Lỗi truy vấn SQL: ' . $conn->error);
        }

        if (!empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $banners = [];
        while ($row = $result->fetch_assoc()) {
            $imgs = json_decode($row['images'] ?? '[]', true);
            if (!is_array($imgs)) $imgs = [];
            if (empty($imgs) && !empty($row['image_url'])) {
                $imgs = [$row['image_url']];
            }
            $row['images_list'] = $imgs;
            $row['event_date_formatted'] = date('d/m/Y', strtotime($row['event_date']));
            $banners[] = $row;
        }
        $stmt->close();

        // Nạp cấu hình Carousel
        $settings = [];
        if (file_exists($configFile)) {
            $settings = json_decode(file_get_contents($configFile), true) ?: [];
        }

        $response = [
            'success'  => true,
            'data'     => $banners,
            'settings' => $settings,
            'total'    => count($banners)
        ];
    }

    // =========================================================================
    // 2. ACTION: LẤY CẤU HÌNH CAROUSEL
    // =========================================================================
    elseif ($action === 'get_settings') {
        verifyBannerPermission(['BannerActivity.View', 'dashboard.view']);

        $settings = [];
        if (file_exists($configFile)) {
            $settings = json_decode(file_get_contents($configFile), true) ?: [];
        }
        $response = [
            'success' => true,
            'data'    => $settings
        ];
    }

    // =========================================================================
    // 3. ACTION: LƯU CẤU HÌNH CAROUSEL (Yêu cầu BannerActivity.ConfigCarousel)
    // =========================================================================
    elseif ($action === 'save_settings') {
        verifyBannerPermission(['BannerActivity.ConfigCarousel', 'dashboard.edit']);

        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        if (!is_array($data)) {
            $data = $_POST;
        }

        $currentSettings = file_exists($configFile) ? (json_decode(file_get_contents($configFile), true) ?: []) : [];

        $autoplay         = isset($data['autoplay']) ? filter_var($data['autoplay'], FILTER_VALIDATE_BOOLEAN) : ($currentSettings['autoplay'] ?? true);
        $interval         = isset($data['interval']) ? max(2000, (int)$data['interval']) : ($currentSettings['interval'] ?? 6000);
        $imageAutoplay    = isset($data['image_autoplay']) ? filter_var($data['image_autoplay'], FILTER_VALIDATE_BOOLEAN) : ($currentSettings['image_autoplay'] ?? true);
        $imageInterval    = isset($data['image_interval']) ? max(1000, (int)$data['image_interval']) : ($currentSettings['image_interval'] ?? 3000);
        $pauseOnHover     = isset($data['pause_on_hover']) ? filter_var($data['pause_on_hover'], FILTER_VALIDATE_BOOLEAN) : ($currentSettings['pause_on_hover'] ?? true);
        $filterCategory   = trim($data['filter_category'] ?? ($currentSettings['filter_category'] ?? 'all'));
        $maxItems         = isset($data['max_items']) ? max(1, (int)$data['max_items']) : ($currentSettings['max_items'] ?? 10);
        $animationEffect  = in_array($data['animation_effect'] ?? '', ['slide', 'fade'], true) ? $data['animation_effect'] : ($currentSettings['animation_effect'] ?? 'slide');
        $showIndicators   = isset($data['show_indicators']) ? filter_var($data['show_indicators'], FILTER_VALIDATE_BOOLEAN) : true;
        $showArrows       = isset($data['show_arrows']) ? filter_var($data['show_arrows'], FILTER_VALIDATE_BOOLEAN) : true;
        $showBadges       = isset($data['show_badges']) ? filter_var($data['show_badges'], FILTER_VALIDATE_BOOLEAN) : true;
        $showSubControls  = isset($data['show_sub_controls']) ? filter_var($data['show_sub_controls'], FILTER_VALIDATE_BOOLEAN) : true;

        $newSettings = [
            'autoplay'          => $autoplay,
            'interval'          => $interval,
            'image_autoplay'    => $imageAutoplay,
            'image_interval'    => $imageInterval,
            'pause_on_hover'    => $pauseOnHover,
            'filter_category'   => $filterCategory,
            'max_items'         => $maxItems,
            'animation_effect'  => $animationEffect,
            'show_indicators'   => $showIndicators,
            'show_arrows'       => $showArrows,
            'show_badges'       => $showBadges,
            'show_sub_controls' => $showSubControls
        ];

        file_put_contents($configFile, json_encode($newSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $response = [
            'success' => true,
            'message' => 'Lưu cài đặt Carousel thành công!',
            'data'    => $newSettings
        ];
    }

    // =========================================================================
    // 4. ACTION: THÊM MỚI BANNER (Yêu cầu BannerActivity.Create)
    // =========================================================================
    elseif ($action === 'create') {
        verifyBannerPermission(['BannerActivity.Create', 'dashboard.edit']);

        $title        = trim($_POST['title'] ?? '');
        $summary      = trim($_POST['summary'] ?? '');
        $content      = trim($_POST['content'] ?? '');
        $category     = trim($_POST['category'] ?? '5S');
        $eventDate    = trim($_POST['event_date'] ?? date('Y-m-d'));
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $status       = in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? $_POST['status'] : 'active';
        $linkUrl      = trim($_POST['link_url'] ?? '');
        $coverImage   = trim($_POST['cover_image'] ?? '');
        $createdBy    = $_SESSION['user']['fullname'] ?? $_SESSION['user']['username'] ?? 'Admin';

        if (empty($title)) {
            throw new Exception('Vui lòng nhập tiêu đề hoạt động!');
        }
        if (empty($category)) {
            throw new Exception('Vui lòng chọn loại hoạt động!');
        }
        if (empty($eventDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
            $eventDate = date('Y-m-d');
        }

        // Xử lý upload ảnh (yêu cầu BannerActivity.UploadImage nếu có file)
        $hasFiles = (isset($_FILES['images']) && is_array($_FILES['images']['name']) && !empty($_FILES['images']['name'][0]))
                 || (isset($_FILES['image']) && !empty($_FILES['image']['name']));

        if ($hasFiles) {
            verifyBannerPermission(['BannerActivity.UploadImage', 'BannerActivity.Create', 'dashboard.edit']);
        }

        $uploadedImages = [];
        if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
            $count = count($_FILES['images']['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $processed = processUploadedImage(
                        $_FILES['images']['tmp_name'][$i],
                        $_FILES['images']['name'][$i],
                        $uploadDir,
                        $thumbDir
                    );
                    $uploadedImages[] = $processed['url'];
                }
            }
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $processed = processUploadedImage(
                $_FILES['image']['tmp_name'],
                $_FILES['image']['name'],
                $uploadDir,
                $thumbDir
            );
            $uploadedImages[] = $processed['url'];
        }

        // Nếu không upload ảnh, tạo ảnh mặc định
        if (empty($uploadedImages)) {
            $sampleFilename = 'banner_auto_' . time() . '.svg';
            $svgColorMap = [
                '5S'                => ['#065f46', '#10b981', '✨ 5S'],
                'Kaizen'            => ['#4c1d95', '#8b5cf6', '💡 KAIZEN'],
                'Cải tiến'          => ['#7c2d12', '#ea580c', '⚡ CẢI TIẾN'],
                'Tuyên dương'       => ['#78350f', '#f59e0b', '🏆 TUYÊN DƯƠNG'],
                'Giải thưởng'       => ['#991b1b', '#ef4444', '🎖️ GIẢI THƯỞNG'],
                'Hoạt động sản xuất'=> ['#1e3a8a', '#3b82f6', '⚙️ SẢN XUẤT'],
                'Khác'              => ['#334155', '#64748b', '📌 THÔNG BÁO']
            ];
            $palette = $svgColorMap[$category] ?? ['#1e40af', '#3b82f6', '★ BANNER'];
            
            $svgBody = '<?xml version="1.0" encoding="UTF-8"?><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 500" width="1200" height="500"><defs><linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="' . $palette[0] . '"/><stop offset="100%" stop-color="' . $palette[1] . '"/></linearGradient></defs><rect width="1200" height="500" fill="url(#bg)"/><circle cx="1020" cy="150" r="200" fill="rgba(255,255,255,0.08)"/><rect x="70" y="65" width="220" height="42" rx="21" fill="rgba(255,255,255,0.2)"/><text x="180" y="92" font-family="sans-serif" font-size="15" font-weight="bold" fill="#fff" text-anchor="middle">★ ' . htmlspecialchars($category) . ' ★</text><text x="70" y="165" font-family="sans-serif" font-size="34" font-weight="bold" fill="#fff">' . htmlspecialchars($title) . '</text><text x="70" y="215" font-family="sans-serif" font-size="19" fill="rgba(255,255,255,0.88)">' . htmlspecialchars($summary ?: 'Hoạt động nội bộ DX Plastic Group') . '</text></svg>';
            file_put_contents($uploadDir . '/' . $sampleFilename, $svgBody);
            file_put_contents($thumbDir . '/' . $sampleFilename, $svgBody);
            $uploadedImages[] = 'uploads/dashboard_banners/' . $sampleFilename;
        }

        // Xác định ảnh đại diện
        $mainImageUrl = (!empty($coverImage) && in_array($coverImage, $uploadedImages, true)) ? $coverImage : $uploadedImages[0];
        $imagesJson   = json_encode($uploadedImages, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $stmt = $conn->prepare("INSERT INTO `dashboard_banners` 
            (`title`, `summary`, `content`, `category`, `image_url`, `images`, `event_date`, `display_order`, `status`, `link_url`, `created_by`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception('Lỗi SQL Prepare: ' . $conn->error);
        }

        $stmt->bind_param("sssssssisss", 
            $title, 
            $summary, 
            $content, 
            $category, 
            $mainImageUrl, 
            $imagesJson, 
            $eventDate, 
            $displayOrder, 
            $status, 
            $linkUrl, 
            $createdBy
        );
        $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();

        $response = [
            'success' => true,
            'message' => 'Đã thêm mới banner hoạt động thành công!',
            'id'      => $newId
        ];
    }

    // =========================================================================
    // 5. ACTION: CẬP NHẬT BANNER (Yêu cầu BannerActivity.Edit)
    // =========================================================================
    elseif ($action === 'update') {
        verifyBannerPermission(['BannerActivity.Edit', 'dashboard.edit']);

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            throw new Exception('ID banner không hợp lệ!');
        }

        // Lấy thông tin banner hiện tại
        $stmtCheck = $conn->prepare("SELECT * FROM `dashboard_banners` WHERE `id` = ?");
        $stmtCheck->bind_param("i", $id);
        $stmtCheck->execute();
        $currentBanner = $stmtCheck->get_result()->fetch_assoc();
        $stmtCheck->close();

        if (!$currentBanner) {
            throw new Exception('Không tìm thấy banner cần cập nhật!');
        }

        $title        = trim($_POST['title'] ?? $currentBanner['title']);
        $summary      = trim($_POST['summary'] ?? $currentBanner['summary']);
        $content      = trim($_POST['content'] ?? $currentBanner['content']);
        $category     = trim($_POST['category'] ?? $currentBanner['category']);
        $eventDate    = trim($_POST['event_date'] ?? $currentBanner['event_date']);
        $displayOrder = isset($_POST['display_order']) ? (int)$_POST['display_order'] : $currentBanner['display_order'];
        $status       = in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? $_POST['status'] : $currentBanner['status'];
        $linkUrl      = trim($_POST['link_url'] ?? $currentBanner['link_url']);
        $coverImage   = trim($_POST['cover_image'] ?? '');

        if (empty($title)) {
            throw new Exception('Tiêu đề không được để trống!');
        }

        // Danh sách ảnh giữ lại (từ client gửi qua dạng JSON theo thứ tự người dùng sắp xếp)
        $keepImages = [];
        if (isset($_POST['keep_images'])) {
            $rawKeep = $_POST['keep_images'];
            $keepImages = is_array($rawKeep) ? $rawKeep : (json_decode($rawKeep, true) ?: []);
        } else {
            $keepImages = json_decode($currentBanner['images'] ?? '[]', true) ?: [$currentBanner['image_url']];
        }

        // Kiểm tra quyền xóa ảnh nếu người dùng loại bỏ bớt ảnh
        $oldImages = json_decode($currentBanner['images'] ?? '[]', true) ?: [$currentBanner['image_url']];
        $removedImages = array_diff($oldImages, $keepImages);
        if (!empty($removedImages)) {
            verifyBannerPermission(['BannerActivity.DeleteImage', 'BannerActivity.Edit', 'dashboard.edit']);
        }

        // Xử lý upload ảnh mới bổ sung (yêu cầu BannerActivity.UploadImage)
        $hasNewFiles = (isset($_FILES['images']) && is_array($_FILES['images']['name']) && !empty($_FILES['images']['name'][0]))
                    || (isset($_FILES['image']) && !empty($_FILES['image']['name']));

        if ($hasNewFiles) {
            verifyBannerPermission(['BannerActivity.UploadImage', 'BannerActivity.Edit', 'dashboard.edit']);
        }

        $newImages = [];
        if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
            $count = count($_FILES['images']['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $processed = processUploadedImage(
                        $_FILES['images']['tmp_name'][$i],
                        $_FILES['images']['name'][$i],
                        $uploadDir,
                        $thumbDir
                    );
                    $newImages[] = $processed['url'];
                }
            }
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $processed = processUploadedImage(
                $_FILES['image']['tmp_name'],
                $_FILES['image']['name'],
                $uploadDir,
                $thumbDir
            );
            $newImages[] = $processed['url'];
        }

        // Kết hợp ảnh theo thứ tự đã sắp xếp
        $finalImages = array_values(array_filter(array_merge($keepImages, $newImages)));
        if (empty($finalImages)) {
            $finalImages = [$currentBanner['image_url']];
        }

        // Xác định ảnh đại diện (Cover Image)
        if (!empty($coverImage) && in_array($coverImage, $finalImages, true)) {
            $mainImageUrl = $coverImage;
        } else {
            $mainImageUrl = $finalImages[0];
        }

        $imagesJson = json_encode($finalImages, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $stmtUpdate = $conn->prepare("UPDATE `dashboard_banners` 
            SET `title` = ?, `summary` = ?, `content` = ?, `category` = ?, 
                `image_url` = ?, `images` = ?, `event_date` = ?, 
                `display_order` = ?, `status` = ?, `link_url` = ? 
            WHERE `id` = ?");
        if (!$stmtUpdate) {
            throw new Exception('Lỗi SQL Update: ' . $conn->error);
        }

        $stmtUpdate->bind_param("sssssssissi", 
            $title, 
            $summary, 
            $content, 
            $category, 
            $mainImageUrl, 
            $imagesJson, 
            $eventDate, 
            $displayOrder, 
            $status, 
            $linkUrl, 
            $id
        );
        $stmtUpdate->execute();
        $stmtUpdate->close();

        $response = [
            'success' => true,
            'message' => 'Cập nhật banner hoạt động thành công!'
        ];
    }

    // =========================================================================
    // 6. ACTION: XÓA BANNER (Yêu cầu BannerActivity.Delete)
    // =========================================================================
    elseif ($action === 'delete') {
        verifyBannerPermission(['BannerActivity.Delete', 'dashboard.edit']);

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            throw new Exception('ID banner không hợp lệ!');
        }

        $stmt = $conn->prepare("SELECT `image_url`, `images` FROM `dashboard_banners` WHERE `id` = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $banner = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($banner) {
            $imgs = json_decode($banner['images'] ?? '[]', true) ?: [];
            if (!empty($banner['image_url']) && !in_array($banner['image_url'], $imgs, true)) {
                $imgs[] = $banner['image_url'];
            }
            foreach ($imgs as $imgPath) {
                // Xóa file upload nếu không phải mẫu hệ thống
                if (strpos($imgPath, 'uploads/dashboard_banners/') !== false && strpos($imgPath, 'banner_auto_') !== false) {
                    $fullFile = __DIR__ . '/../' . ltrim($imgPath, '/');
                    if (file_exists($fullFile)) @unlink($fullFile);
                    $thumbFile = str_replace('uploads/dashboard_banners/', 'uploads/dashboard_banners/thumbs/', $fullFile);
                    if (file_exists($thumbFile)) @unlink($thumbFile);
                }
            }
        }

        $stmtDel = $conn->prepare("DELETE FROM `dashboard_banners` WHERE `id` = ?");
        $stmtDel->bind_param("i", $id);
        $stmtDel->execute();
        $stmtDel->close();

        $response = [
            'success' => true,
            'message' => 'Đã xóa banner hoạt động thành công!'
        ];
    }

    // =========================================================================
    // 7. ACTION: BẬT/TẮT TRẠNG THÁI HIỂN THỊ (Yêu cầu BannerActivity.Publish)
    // =========================================================================
    elseif ($action === 'toggle_status') {
        verifyBannerPermission(['BannerActivity.Publish', 'dashboard.edit']);

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            throw new Exception('ID không hợp lệ!');
        }

        $stmt = $conn->prepare("UPDATE `dashboard_banners` 
            SET `status` = IF(`status` = 'active', 'inactive', 'active') 
            WHERE `id` = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $res = $conn->query("SELECT `status` FROM `dashboard_banners` WHERE `id` = $id")->fetch_assoc();
        $newStatus = $res['status'] ?? 'active';

        $response = [
            'success'    => true,
            'new_status' => $newStatus,
            'message'    => "Đã " . ($newStatus === 'active' ? 'bật hiển thị' : 'ẩn') . " banner thành công!"
        ];
    }

    // =========================================================================
    // 8. ACTION: SẮP XẾP THỨ TỰ BANNER (Yêu cầu BannerActivity.Edit)
    // =========================================================================
    elseif ($action === 'reorder') {
        verifyBannerPermission(['BannerActivity.Edit', 'dashboard.edit']);

        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        $orderIds = $data['order_ids'] ?? $_POST['order_ids'] ?? [];

        if (!is_array($orderIds) || empty($orderIds)) {
            throw new Exception('Danh sách ID sắp xếp không hợp lệ!');
        }

        $stmt = $conn->prepare("UPDATE `dashboard_banners` SET `display_order` = ? WHERE `id` = ?");
        $order = 1;
        foreach ($orderIds as $bId) {
            $intId = (int)$bId;
            if ($intId > 0) {
                $stmt->bind_param("ii", $order, $intId);
                $stmt->execute();
                $order++;
            }
        }
        $stmt->close();

        $response = [
            'success' => true,
            'message' => 'Đã lưu thứ tự hiển thị mới thành công!'
        ];
    }

    else {
        throw new Exception("Hành động '$action' không được hỗ trợ!");
    }

} catch (Throwable $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
