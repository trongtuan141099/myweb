<?php
/**
 * API Quản lý & Cung cấp dữ liệu Thông Báo Mới Nhất (Dashboard Announcements)
 * DX Plastic Group - Industrial Enterprise Design System
 * Tích hợp hệ thống phân quyền chi tiết Announcement.*, đính kèm đa hình ảnh, đa tài liệu và theo dõi lượt xem
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

// Nếu là action download_attachment thì không gửi header JSON ngay lập tức
if ($action !== 'download_attachment') {
    header('Content-Type: application/json; charset=utf-8');
}
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

    $conn->set_charset("utf8mb4");

    // Thư mục lưu trữ hình ảnh và tệp đính kèm
    $baseUploadDir = __DIR__ . '/../uploads/announcements';
    $imgUploadDir  = $baseUploadDir . '/images';
    $thumbDir      = $imgUploadDir . '/thumbs';
    $fileUploadDir = $baseUploadDir . '/files';

    foreach ([$baseUploadDir, $imgUploadDir, $thumbDir, $fileUploadDir] as $d) {
        if (!file_exists($d)) {
            mkdir($d, 0777, true);
        }
    }

    // Hàm lấy IP người dùng
    function getClientIpAddress() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    $clientIp = getClientIpAddress();

    // Nhận thông tin user hiện hành từ Session
    $currentUsername = $_SESSION['username'] ?? ($_SESSION['user']['username'] ?? '');
    $currentFullname = $_SESSION['fullname'] ?? ($_SESSION['user']['fullname'] ?? '');
    $currentUserId   = $_SESSION['user_id']  ?? ($_SESSION['user']['id'] ?? null);
    $currentUserRole = $_SESSION['user']['role'] ?? ($_SESSION['role'] ?? 'viewer');

    if (empty($currentUsername)) {
        $currentUsername = 'guest_' . substr(md5($clientIp), 0, 8);
        $currentFullname = 'Khách truy cập (' . substr($clientIp, 0, 7) . '...)';
    }

    // =========================================================================
    // HÀM KIỂM TRA PHÂN QUYỀN CHẶT CHẼ THEO ANNOUNCEMENT.*
    // =========================================================================
    function verifyAnnouncementPermission($requiredPerm) {
        $userRole = $_SESSION['user']['role'] ?? ($_SESSION['role'] ?? '');
        if ($userRole === 'admin') return true;

        $permsToCheck = is_array($requiredPerm) ? $requiredPerm : [$requiredPerm];
        if (hasPermission($permsToCheck)) {
            return true;
        }

        http_response_code(403);
        $reqStr = implode(', ', $permsToCheck);
        throw new Exception("Bạn không có quyền thực hiện thao tác này! (Yêu cầu quyền: $reqStr)");
    }

    // =========================================================================
    // HÀM TIỆN ÍCH ĐỊNH DẠNG DUNG LƯỢNG FILE
    // =========================================================================
    function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 1) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 0) . ' KB';
        } elseif ($bytes > 0) {
            return $bytes . ' B';
        }
        return '0 B';
    }

    // =========================================================================
    // HÀM XỬ LÝ HÌNH ẢNH (RESIZE, TẠO THUMBNAIL, NÉN TỐI ƯU DUNG LƯỢNG)
    // =========================================================================
    function processUploadedAnnouncementImage($fileTmp, $originalName, $uploadDir, $thumbDir) {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
        if (!in_array($ext, $allowedExts, true)) {
            throw new Exception("Định dạng ảnh '{$ext}' không được hỗ trợ! Chấp nhận JPG, PNG, WEBP, GIF, SVG.");
        }

        $baseName = 'ann_img_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4));
        $destFile = $baseName . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
        $destPath = $uploadDir . '/' . $destFile;
        $thumbPath = $thumbDir . '/' . $destFile;

        if ($ext === 'svg') {
            if (!move_uploaded_file($fileTmp, $destPath)) {
                throw new Exception("Không thể lưu file SVG tải lên!");
            }
            copy($destPath, $thumbPath);
            return [
                'url'   => 'uploads/announcements/images/' . $destFile,
                'thumb' => 'uploads/announcements/images/thumbs/' . $destFile,
                'name'  => $originalName,
                'size'  => filesize($destPath),
                'ext'   => 'svg'
            ];
        }

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
                case 'gif':
                    $srcImg = @imagecreatefromgif($fileTmp);
                    break;
            }

            if ($srcImg) {
                $srcW = imagesx($srcImg);
                $srcH = imagesy($srcImg);

                // 1. Resize ảnh lớn (Max width 1600, max height 1200)
                $maxW = 1600;
                $maxH = 1200;
                $ratio = min($maxW / $srcW, $maxH / $srcH, 1.0);
                $dstW = (int)round($srcW * $ratio);
                $dstH = (int)round($srcH * $ratio);

                $largeCanvas = imagecreatetruecolor($dstW, $dstH);
                if ($ext === 'png' || $ext === 'webp') {
                    imagealphablending($largeCanvas, false);
                    imagesavealpha($largeCanvas, true);
                    $transparent = imagecolorallocatealpha($largeCanvas, 255, 255, 255, 127);
                    imagefilledrectangle($largeCanvas, 0, 0, $dstW, $dstH, $transparent);
                }
                imagecopyresampled($largeCanvas, $srcImg, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

                // 2. Tạo thumbnail (Max width 420, max height 280)
                $thumbMaxW = 420;
                $thumbMaxH = 280;
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

                // Lưu ảnh lớn và thumbnail
                if ($ext === 'png') {
                    imagepng($largeCanvas, $destPath, 8);
                    imagepng($thumbCanvas, $thumbPath, 8);
                } elseif ($ext === 'webp') {
                    imagewebp($largeCanvas, $destPath, 85);
                    imagewebp($thumbCanvas, $thumbPath, 80);
                } else {
                    imagejpeg($largeCanvas, $destPath, 85);
                    imagejpeg($thumbCanvas, $thumbPath, 80);
                }

                imagedestroy($srcImg);
                imagedestroy($largeCanvas);
                imagedestroy($thumbCanvas);

                return [
                    'url'   => 'uploads/announcements/images/' . $destFile,
                    'thumb' => 'uploads/announcements/images/thumbs/' . $destFile,
                    'name'  => $originalName,
                    'size'  => filesize($destPath),
                    'ext'   => $ext
                ];
            }
        }

        // Fallback copy trực tiếp nếu GD không hỗ trợ
        if (!move_uploaded_file($fileTmp, $destPath)) {
            throw new Exception("Không thể lưu ảnh tải lên!");
        }
        copy($destPath, $thumbPath);

        return [
            'url'   => 'uploads/announcements/images/' . $destFile,
            'thumb' => 'uploads/announcements/images/thumbs/' . $destFile,
            'name'  => $originalName,
            'size'  => filesize($destPath),
            'ext'   => $ext
        ];
    }

    // =========================================================================
    // HÀM TIỆN ÍCH DỌN DẸP FILE VẬT LÝ AN TOÀN
    // =========================================================================
    function safelyDeletePhysicalFile($relativeUrl) {
        if (empty($relativeUrl)) return;
        $cleanRel = ltrim($relativeUrl, '/\\');
        // Chỉ cho phép xóa file trong thư mục uploads/announcements/
        if (strpos($cleanRel, 'uploads/announcements/') === 0) {
            $absPath = realpath(__DIR__ . '/../' . $cleanRel);
            if ($absPath && file_exists($absPath)) {
                @unlink($absPath);
            }
        }
    }

    // Đọc payload JSON nếu có
    $jsonInput = [];
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        $decoded = json_decode($rawInput, true);
        if (is_array($decoded)) {
            $jsonInput = $decoded;
            if (isset($jsonInput['action']) && empty($_GET['action']) && empty($_POST['action'])) {
                $action = $jsonInput['action'];
            }
        }
    }

    switch ($action) {
        // =====================================================================
        // 1. DANH SÁCH THÔNG BÁO HIỆU LỰC CHO DASHBOARD (PUBLIC / USER)
        // =====================================================================
        case 'list': {
            $today = date('Y-m-d');

            $sql = "SELECT 
                        a.id, 
                        a.title, 
                        a.summary, 
                        a.content, 
                        a.images,
                        a.attachments,
                        a.priority, 
                        a.is_important, 
                        a.status, 
                        a.valid_from, 
                        a.valid_to, 
                        a.created_by, 
                        a.created_at, 
                        a.updated_at,
                        COALESCE(a.total_views, 0) AS total_views,
                        (SELECT COUNT(DISTINCT v.username) FROM `announcement_views` v WHERE v.announcement_id = a.id) AS unique_viewers_count,
                        CASE 
                            WHEN ? != '' AND EXISTS (
                                SELECT 1 FROM `announcement_views` uv 
                                WHERE uv.announcement_id = a.id AND uv.username = ?
                            ) THEN 1 
                            ELSE 0 
                        END AS user_viewed
                    FROM `announcements` a
                    WHERE a.status = 'active'
                      AND a.valid_from <= ?
                      AND (a.valid_to IS NULL OR a.valid_to >= ?)
                    ORDER BY a.is_important DESC, a.valid_from DESC, a.id DESC";

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Lỗi truy vấn danh sách thông báo: " . $conn->error);
            }

            $stmt->bind_param("ssss", $currentUsername, $currentUsername, $today, $today);
            $stmt->execute();
            $result = $stmt->get_result();

            $announcements = [];
            $unreadCount = 0;
            $importantCount = 0;

            while ($row = $result->fetch_assoc()) {
                $row['id']                   = (int)$row['id'];
                $row['is_important']         = (int)$row['is_important'];
                $row['total_views']          = (int)$row['total_views'];
                $row['unique_viewers_count'] = (int)$row['unique_viewers_count'];
                $row['user_viewed']          = (int)$row['user_viewed'];

                // Giải mã mảng hình ảnh và tài liệu
                $imgs = json_decode($row['images'] ?? '[]', true);
                if (!is_array($imgs)) $imgs = [];
                // Sắp xếp ảnh theo thứ tự order
                usort($imgs, function($a, $b) {
                    return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
                });
                $row['images_list'] = $imgs;
                $row['images_count'] = count($imgs);

                $attaches = json_decode($row['attachments'] ?? '[]', true);
                if (!is_array($attaches)) $attaches = [];
                $row['attachments_list'] = $attaches;
                $row['attachments_count'] = count($attaches);

                // Format ngày hiển thị
                $row['valid_from_formatted'] = $row['valid_from'] ? date('d/m/Y', strtotime($row['valid_from'])) : '';
                $row['valid_to_formatted']   = $row['valid_to'] ? date('d/m/Y', strtotime($row['valid_to'])) : 'Vô thời hạn';
                $row['created_at_formatted'] = $row['created_at'] ? date('d/m/Y H:i', strtotime($row['created_at'])) : '';

                if ($row['user_viewed'] === 0) {
                    $unreadCount++;
                }
                if ($row['is_important'] === 1) {
                    $importantCount++;
                }

                $announcements[] = $row;
            }
            $stmt->close();

            $response = [
                'success' => true,
                'data' => $announcements,
                'stats' => [
                    'total' => count($announcements),
                    'unread' => $unreadCount,
                    'important' => $importantCount
                ]
            ];
            break;
        }

        // =====================================================================
        // 2. CHI TIẾT THÔNG BÁO & TỰ ĐỘNG GHI NHẬN LƯỢT XEM
        // =====================================================================
        case 'get_detail': {
            $id = (int)($_GET['id'] ?? $jsonInput['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID thông báo không hợp lệ!');
            }

            $stmt = $conn->prepare("SELECT * FROM `announcements` WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $announcement = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$announcement) {
                throw new Exception('Không tìm thấy thông báo hoặc đã bị xóa!');
            }

            // Ghi nhận lượt xem nếu user hợp lệ
            if (!empty($currentUsername)) {
                $sqlLogView = "INSERT INTO `announcement_views` 
                               (`announcement_id`, `user_id`, `username`, `fullname`, `view_count`, `first_viewed_at`, `last_viewed_at`, `ip_address`)
                               VALUES (?, ?, ?, ?, 1, NOW(), NOW(), ?)
                               ON DUPLICATE KEY UPDATE 
                                   view_count = view_count + 1,
                                   last_viewed_at = NOW(),
                                   fullname = VALUES(fullname),
                                   ip_address = VALUES(ip_address)";
                
                $logStmt = $conn->prepare($sqlLogView);
                if ($logStmt) {
                    $logStmt->bind_param("iisss", $id, $currentUserId, $currentUsername, $currentFullname, $clientIp);
                    $logStmt->execute();
                    $logStmt->close();
                }

                $conn->query("UPDATE `announcements` SET total_views = total_views + 1 WHERE id = {$id}");
                $announcement['total_views'] = (int)$announcement['total_views'] + 1;
            }

            // Thống kê người xem
            $viewersCountQuery = $conn->query("SELECT COUNT(DISTINCT username) AS unique_count, SUM(view_count) AS sum_views FROM `announcement_views` WHERE announcement_id = {$id}");
            $vStats = $viewersCountQuery ? $viewersCountQuery->fetch_assoc() : ['unique_count' => 0, 'sum_views' => 0];

            $announcement['id']                   = (int)$announcement['id'];
            $announcement['is_important']         = (int)$announcement['is_important'];
            $announcement['total_views']          = (int)($vStats['sum_views'] ?? $announcement['total_views']);
            $announcement['unique_viewers_count'] = (int)($vStats['unique_count'] ?? 0);
            $announcement['user_viewed']          = 1;

            // Giải mã hình ảnh và tài liệu
            $imgs = json_decode($announcement['images'] ?? '[]', true);
            if (!is_array($imgs)) $imgs = [];
            usort($imgs, function($a, $b) {
                return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
            });
            $announcement['images_list'] = $imgs;

            $attaches = json_decode($announcement['attachments'] ?? '[]', true);
            if (!is_array($attaches)) $attaches = [];
            $announcement['attachments_list'] = $attaches;

            $announcement['valid_from_formatted'] = $announcement['valid_from'] ? date('d/m/Y', strtotime($announcement['valid_from'])) : '';
            $announcement['valid_to_formatted']   = $announcement['valid_to'] ? date('d/m/Y', strtotime($announcement['valid_to'])) : 'Vô thời hạn';
            $announcement['created_at_formatted'] = $announcement['created_at'] ? date('d/m/Y H:i', strtotime($announcement['created_at'])) : '';

            $response = [
                'success' => true,
                'data' => $announcement
            ];
            break;
        }

        // =====================================================================
        // 3. DANH SÁCH DÀNH CHO ADMIN / QUẢN TRỊ VIÊN (ADMIN_LIST)
        // =====================================================================
        case 'admin_list': {
            verifyAnnouncementPermission(['Announcement.View', 'Announcement.Create', 'Announcement.Edit']);

            $keyword   = trim($_GET['keyword'] ?? '');
            $priority  = trim($_GET['priority'] ?? '');
            $status    = trim($_GET['status'] ?? '');
            $important = isset($_GET['is_important']) && $_GET['is_important'] !== '' ? (int)$_GET['is_important'] : null;

            $whereParts = ["1=1"];
            $params = [];
            $types  = "";

            if ($keyword !== '') {
                $whereParts[] = "(a.title LIKE ? OR a.summary LIKE ? OR a.content LIKE ?)";
                $kw = "%{$keyword}%";
                $params[] = $kw;
                $params[] = $kw;
                $params[] = $kw;
                $types .= "sss";
            }

            if ($priority !== '' && in_array($priority, ['normal', 'high', 'urgent'], true)) {
                $whereParts[] = "a.priority = ?";
                $params[] = $priority;
                $types .= "s";
            }

            if ($status !== '' && in_array($status, ['active', 'inactive'], true)) {
                $whereParts[] = "a.status = ?";
                $params[] = $status;
                $types .= "s";
            }

            if ($important !== null) {
                $whereParts[] = "a.is_important = ?";
                $params[] = $important;
                $types .= "i";
            }

            $whereSql = implode(" AND ", $whereParts);

            $sql = "SELECT 
                        a.*,
                        COALESCE(a.total_views, 0) AS total_views,
                        (SELECT COUNT(DISTINCT v.username) FROM `announcement_views` v WHERE v.announcement_id = a.id) AS unique_viewers_count
                    FROM `announcements` a
                    WHERE {$whereSql}
                    ORDER BY a.is_important DESC, a.valid_from DESC, a.id DESC";

            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();

            $list = [];
            $today = date('Y-m-d');
            while ($row = $result->fetch_assoc()) {
                $row['id']                   = (int)$row['id'];
                $row['is_important']         = (int)$row['is_important'];
                $row['total_views']          = (int)$row['total_views'];
                $row['unique_viewers_count'] = (int)$row['unique_viewers_count'];

                $imgs = json_decode($row['images'] ?? '[]', true);
                if (!is_array($imgs)) $imgs = [];
                $row['images_list'] = $imgs;
                $row['images_count'] = count($imgs);

                $attaches = json_decode($row['attachments'] ?? '[]', true);
                if (!is_array($attaches)) $attaches = [];
                $row['attachments_list'] = $attaches;
                $row['attachments_count'] = count($attaches);

                $isCurrentlyValid = ($row['status'] === 'active' && 
                                     $row['valid_from'] <= $today && 
                                     (!$row['valid_to'] || $row['valid_to'] >= $today));
                $row['is_currently_valid']   = $isCurrentlyValid;

                $row['valid_from_formatted'] = $row['valid_from'] ? date('d/m/Y', strtotime($row['valid_from'])) : '';
                $row['valid_to_formatted']   = $row['valid_to'] ? date('d/m/Y', strtotime($row['valid_to'])) : 'Vô thời hạn';
                $row['created_at_formatted'] = $row['created_at'] ? date('d/m/Y H:i', strtotime($row['created_at'])) : '';

                $list[] = $row;
            }
            $stmt->close();

            $response = [
                'success' => true,
                'data' => $list,
                'total' => count($list)
            ];
            break;
        }

        // =====================================================================
        // 4. ACTION: TẢI LÊN NHIỀU HÌNH ẢNH (UPLOAD_IMAGES)
        // =====================================================================
        case 'upload_images': {
            verifyAnnouncementPermission(['Announcement.UploadImage', 'Announcement.Create', 'Announcement.Edit']);

            if (empty($_FILES['images']) && empty($_FILES['image'])) {
                throw new Exception('Không tìm thấy file hình ảnh tải lên!');
            }

            $rawFiles = !empty($_FILES['images']) ? $_FILES['images'] : $_FILES['image'];
            $uploadedList = [];

            // Chuyển format single/multiple thành mảng chuẩn
            $filesToProcess = [];
            if (is_array($rawFiles['name'])) {
                $count = count($rawFiles['name']);
                for ($i = 0; $i < $count; $i++) {
                    if ($rawFiles['error'][$i] === UPLOAD_ERR_OK) {
                        $filesToProcess[] = [
                            'name'     => $rawFiles['name'][$i],
                            'type'     => $rawFiles['type'][$i],
                            'tmp_name' => $rawFiles['tmp_name'][$i],
                            'size'     => $rawFiles['size'][$i]
                        ];
                    }
                }
            } else {
                if ($rawFiles['error'] === UPLOAD_ERR_OK) {
                    $filesToProcess[] = $rawFiles;
                }
            }

            if (empty($filesToProcess)) {
                throw new Exception('Không có file ảnh hợp lệ để tải lên hoặc lỗi truyền file!');
            }

            foreach ($filesToProcess as $idx => $f) {
                // Giới hạn 15MB
                if ($f['size'] > 15 * 1024 * 1024) {
                    throw new Exception("File ảnh '{$f['name']}' vượt quá kích thước cho phép (tối đa 15MB)!");
                }
                $processed = processUploadedAnnouncementImage($f['tmp_name'], $f['name'], $imgUploadDir, $thumbDir);
                $processed['order'] = $idx;
                $uploadedList[] = $processed;
            }

            $response = [
                'success' => true,
                'message' => 'Tải lên hình ảnh thành công!',
                'data'    => $uploadedList
            ];
            break;
        }

        // =====================================================================
        // 5. ACTION: TẢI LÊN NHIỀU TÀI LIỆU ĐÍNH KÈM (UPLOAD_ATTACHMENTS)
        // =====================================================================
        case 'upload_attachments': {
            verifyAnnouncementPermission(['Announcement.UploadAttachment', 'Announcement.Create', 'Announcement.Edit']);

            if (empty($_FILES['attachments']) && empty($_FILES['files']) && empty($_FILES['file'])) {
                throw new Exception('Không tìm thấy file tài liệu tải lên!');
            }

            $rawFiles = !empty($_FILES['attachments']) ? $_FILES['attachments'] : (!empty($_FILES['files']) ? $_FILES['files'] : $_FILES['file']);
            $uploadedAttachments = [];

            $filesToProcess = [];
            if (is_array($rawFiles['name'])) {
                $count = count($rawFiles['name']);
                for ($i = 0; $i < $count; $i++) {
                    if ($rawFiles['error'][$i] === UPLOAD_ERR_OK) {
                        $filesToProcess[] = [
                            'name'     => $rawFiles['name'][$i],
                            'tmp_name' => $rawFiles['tmp_name'][$i],
                            'size'     => $rawFiles['size'][$i]
                        ];
                    }
                }
            } else {
                if ($rawFiles['error'] === UPLOAD_ERR_OK) {
                    $filesToProcess[] = $rawFiles;
                }
            }

            if (empty($filesToProcess)) {
                throw new Exception('Không có tài liệu hợp lệ để tải lên!');
            }

            $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv', 'txt', 'zip', 'rar', '7z'];

            foreach ($filesToProcess as $f) {
                // Giới hạn 50MB cho mỗi tài liệu
                if ($f['size'] > 50 * 1024 * 1024) {
                    throw new Exception("File '{$f['name']}' vượt quá kích thước cho phép (tối đa 50MB)!");
                }

                $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExtensions, true)) {
                    throw new Exception("Định dạng file '{$ext}' không được hỗ trợ! Chấp nhận PDF, Word (DOC/DOCX), Excel (XLS/XLSX), PPT/PPTX, TXT, ZIP, RAR.");
                }

                $baseName = 'ann_doc_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4));
                $storedFile = $baseName . '.' . $ext;
                $destPath = $fileUploadDir . '/' . $storedFile;

                if (!move_uploaded_file($f['tmp_name'], $destPath)) {
                    throw new Exception("Không thể lưu file '{$f['name']}' vào hệ thống!");
                }

                $uploadedAttachments[] = [
                    'url'            => 'uploads/announcements/files/' . $storedFile,
                    'name'           => $f['name'],
                    'ext'            => $ext,
                    'size'           => $f['size'],
                    'size_formatted' => formatFileSize($f['size']),
                    'uploaded_at'    => date('Y-m-d H:i:s')
                ];
            }

            $response = [
                'success' => true,
                'message' => 'Tải lên tài liệu đính kèm thành công!',
                'data'    => $uploadedAttachments
            ];
            break;
        }

        // =====================================================================
        // 6. ACTION: TẢI XUỐNG TÀI LIỆU ĐÍNH KÈM (DOWNLOAD_ATTACHMENT)
        // =====================================================================
        case 'download_attachment': {
            verifyAnnouncementPermission('Announcement.View');

            $fileUrl = $_GET['file_url'] ?? '';
            $customName = $_GET['name'] ?? '';

            if (empty($fileUrl)) {
                throw new Exception('Đường dẫn file không hợp lệ!');
            }

            // Bảo mật: Chặn path traversal
            if (strpos($fileUrl, '..') !== false || strpos($fileUrl, 'uploads/announcements/files/') !== 0) {
                throw new Exception('Đường dẫn tài liệu không an toàn hoặc nằm ngoài thư mục cho phép!');
            }

            $absPath = realpath(__DIR__ . '/../' . $fileUrl);
            if (!$absPath || !file_exists($absPath)) {
                throw new Exception('File tài liệu không tồn tại hoặc đã bị xóa trên máy chủ!');
            }

            $downloadName = !empty($customName) ? basename($customName) : basename($absPath);

            // Gửi header tải file
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . rawurlencode($downloadName) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($absPath));
            readfile($absPath);
            exit;
        }

        // =====================================================================
        // 7. THÊM MỚI THÔNG BÁO (CREATE)
        // =====================================================================
        case 'create': {
            verifyAnnouncementPermission('Announcement.Create');

            $data = !empty($jsonInput) ? $jsonInput : $_POST;

            $title       = trim($data['title'] ?? '');
            $summary     = trim($data['summary'] ?? '');
            $content     = trim($data['content'] ?? '');
            $priority    = trim($data['priority'] ?? 'normal');
            $isImportant = !empty($data['is_important']) ? 1 : 0;
            $status      = trim($data['status'] ?? 'active');
            $validFrom   = trim($data['valid_from'] ?? date('Y-m-d'));
            $validTo     = !empty($data['valid_to']) ? trim($data['valid_to']) : null;

            if (empty($title)) {
                throw new Exception('Tiêu đề thông báo không được để trống!');
            }

            if (!in_array($priority, ['normal', 'high', 'urgent'], true)) {
                $priority = 'normal';
            }

            if (!in_array($status, ['active', 'inactive'], true)) {
                $status = 'active';
            }

            if (!empty($validTo) && $validTo < $validFrom) {
                throw new Exception('Ngày kết thúc hiệu lực không được nhỏ hơn ngày bắt đầu!');
            }

            if (empty($summary)) {
                $plainContent = strip_tags($content);
                $summary = mb_substr($plainContent, 0, 160, 'UTF-8');
                if (mb_strlen($plainContent, 'UTF-8') > 160) {
                    $summary .= '...';
                }
            }

            // Xử lý danh sách hình ảnh
            $imagesData = $data['images'] ?? [];
            if (is_string($imagesData)) {
                $imagesData = json_decode($imagesData, true) ?: [];
            }
            if (!is_array($imagesData)) $imagesData = [];
            // Đảm bảo thứ tự
            foreach ($imagesData as $idx => &$img) {
                $img['order'] = $idx;
            }
            $imagesJson = json_encode($imagesData, JSON_UNESCAPED_UNICODE);

            // Xử lý danh sách tài liệu đính kèm
            $attachmentsData = $data['attachments'] ?? [];
            if (is_string($attachmentsData)) {
                $attachmentsData = json_decode($attachmentsData, true) ?: [];
            }
            if (!is_array($attachmentsData)) $attachmentsData = [];
            $attachmentsJson = json_encode($attachmentsData, JSON_UNESCAPED_UNICODE);

            $createdBy = !empty($currentUsername) ? $currentUsername : 'Admin';

            $stmt = $conn->prepare("INSERT INTO `announcements` 
                (`title`, `summary`, `content`, `images`, `attachments`, `priority`, `is_important`, `status`, `valid_from`, `valid_to`, `created_by`, `total_views`, `created_at`, `updated_at`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW())");

            if (!$stmt) {
                throw new Exception("Lỗi khởi tạo truy vấn: " . $conn->error);
            }

            $stmt->bind_param("ssssssissss", $title, $summary, $content, $imagesJson, $attachmentsJson, $priority, $isImportant, $status, $validFrom, $validTo, $createdBy);
            if (!$stmt->execute()) {
                throw new Exception("Lỗi lưu thông báo vào CSDL: " . $stmt->error);
            }

            $newId = $stmt->insert_id;
            $stmt->close();

            $response = [
                'success' => true,
                'message' => 'Tạo mới thông báo thành công!',
                'data' => [
                    'id' => $newId,
                    'title' => $title
                ]
            ];
            break;
        }

        // =====================================================================
        // 8. CẬP NHẬT THÔNG BÁO (UPDATE)
        // =====================================================================
        case 'update': {
            verifyAnnouncementPermission('Announcement.Edit');

            $data = !empty($jsonInput) ? $jsonInput : $_POST;

            $id = (int)($data['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID thông báo cần chỉnh sửa không hợp lệ!');
            }

            $stmtCheck = $conn->prepare("SELECT id, images, attachments FROM `announcements` WHERE id = ? LIMIT 1");
            $stmtCheck->bind_param("i", $id);
            $stmtCheck->execute();
            $oldRow = $stmtCheck->get_result()->fetch_assoc();
            $stmtCheck->close();

            if (!$oldRow) {
                throw new Exception('Thông báo không tồn tại hoặc đã bị xóa trước đó!');
            }

            $title       = trim($data['title'] ?? '');
            $summary     = trim($data['summary'] ?? '');
            $content     = trim($data['content'] ?? '');
            $priority    = trim($data['priority'] ?? 'normal');
            $isImportant = !empty($data['is_important']) ? 1 : 0;
            $status      = trim($data['status'] ?? 'active');
            $validFrom   = trim($data['valid_from'] ?? date('Y-m-d'));
            $validTo     = !empty($data['valid_to']) ? trim($data['valid_to']) : null;

            if (empty($title)) {
                throw new Exception('Tiêu đề thông báo không được để trống!');
            }

            if (!in_array($priority, ['normal', 'high', 'urgent'], true)) {
                $priority = 'normal';
            }

            if (!in_array($status, ['active', 'inactive'], true)) {
                $status = 'active';
            }

            if (!empty($validTo) && $validTo < $validFrom) {
                throw new Exception('Ngày kết thúc hiệu lực không được nhỏ hơn ngày bắt đầu!');
            }

            if (empty($summary)) {
                $plainContent = strip_tags($content);
                $summary = mb_substr($plainContent, 0, 160, 'UTF-8');
                if (mb_strlen($plainContent, 'UTF-8') > 160) {
                    $summary .= '...';
                }
            }

            // Xử lý mảng hình ảnh
            $imagesData = $data['images'] ?? [];
            if (is_string($imagesData)) {
                $imagesData = json_decode($imagesData, true) ?: [];
            }
            if (!is_array($imagesData)) $imagesData = [];
            foreach ($imagesData as $idx => &$img) {
                $img['order'] = $idx;
            }
            $imagesJson = json_encode($imagesData, JSON_UNESCAPED_UNICODE);

            // Dọn dẹp ảnh cũ bị xóa khỏi danh sách
            $oldImages = json_decode($oldRow['images'] ?? '[]', true) ?: [];
            $newImageUrls = array_column($imagesData, 'url');
            foreach ($oldImages as $oi) {
                if (!empty($oi['url']) && !in_array($oi['url'], $newImageUrls, true)) {
                    safelyDeletePhysicalFile($oi['url']);
                    if (!empty($oi['thumb'])) {
                        safelyDeletePhysicalFile($oi['thumb']);
                    }
                }
            }

            // Xử lý mảng tài liệu đính kèm
            $attachmentsData = $data['attachments'] ?? [];
            if (is_string($attachmentsData)) {
                $attachmentsData = json_decode($attachmentsData, true) ?: [];
            }
            if (!is_array($attachmentsData)) $attachmentsData = [];
            $attachmentsJson = json_encode($attachmentsData, JSON_UNESCAPED_UNICODE);

            // Dọn dẹp tài liệu cũ bị xóa khỏi danh sách
            $oldAttaches = json_decode($oldRow['attachments'] ?? '[]', true) ?: [];
            $newAttachUrls = array_column($attachmentsData, 'url');
            foreach ($oldAttaches as $oa) {
                if (!empty($oa['url']) && !in_array($oa['url'], $newAttachUrls, true)) {
                    safelyDeletePhysicalFile($oa['url']);
                }
            }

            $stmt = $conn->prepare("UPDATE `announcements` SET 
                `title` = ?, 
                `summary` = ?, 
                `content` = ?, 
                `images` = ?,
                `attachments` = ?,
                `priority` = ?, 
                `is_important` = ?, 
                `status` = ?, 
                `valid_from` = ?, 
                `valid_to` = ?, 
                `updated_at` = NOW() 
                WHERE `id` = ?");

            if (!$stmt) {
                throw new Exception("Lỗi khởi tạo truy vấn: " . $conn->error);
            }

            $stmt->bind_param("ssssssisssi", $title, $summary, $content, $imagesJson, $attachmentsJson, $priority, $isImportant, $status, $validFrom, $validTo, $id);
            if (!$stmt->execute()) {
                throw new Exception("Lỗi cập nhật thông báo: " . $stmt->error);
            }
            $stmt->close();

            $response = [
                'success' => true,
                'message' => 'Cập nhật thông báo thành công!',
                'data' => [
                    'id' => $id,
                    'title' => $title
                ]
            ];
            break;
        }

        // =====================================================================
        // 9. XÓA THÔNG BÁO (DELETE) - DỌN DẸP SẠCH FILE TRÊN ĐĨA
        // =====================================================================
        case 'delete': {
            verifyAnnouncementPermission('Announcement.Delete');

            $data = !empty($jsonInput) ? $jsonInput : $_POST;
            $id = (int)($data['id'] ?? $_GET['id'] ?? 0);

            if ($id <= 0) {
                throw new Exception('ID thông báo cần xóa không hợp lệ!');
            }

            // Lấy thông tin file ảnh và tài liệu để dọn dẹp vật lý
            $res = $conn->query("SELECT images, attachments FROM `announcements` WHERE id = {$id}");
            if ($res && $row = $res->fetch_assoc()) {
                $imgs = json_decode($row['images'] ?? '[]', true) ?: [];
                foreach ($imgs as $i) {
                    safelyDeletePhysicalFile($i['url'] ?? '');
                    safelyDeletePhysicalFile($i['thumb'] ?? '');
                }

                $attaches = json_decode($row['attachments'] ?? '[]', true) ?: [];
                foreach ($attaches as $a) {
                    safelyDeletePhysicalFile($a['url'] ?? '');
                }
            }

            $stmt = $conn->prepare("DELETE FROM `announcements` WHERE id = ?");
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) {
                throw new Exception("Lỗi xóa thông báo: " . $stmt->error);
            }
            $stmt->close();

            $response = [
                'success' => true,
                'message' => 'Đã xóa thông báo và toàn bộ file đính kèm liên quan!'
            ];
            break;
        }

        // =====================================================================
        // 10. ACTION: XÓA 1 TÀI LIỆU ĐÍNH KÈM (DELETE_SINGLE_ATTACHMENT)
        // =====================================================================
        case 'delete_single_attachment': {
            verifyAnnouncementPermission(['Announcement.DeleteAttachment', 'Announcement.Edit']);

            $data = !empty($jsonInput) ? $jsonInput : $_POST;
            $id = (int)($data['id'] ?? 0);
            $fileUrl = trim($data['file_url'] ?? '');

            if ($id <= 0 || empty($fileUrl)) {
                throw new Exception('Dữ liệu yêu cầu xóa không hợp lệ!');
            }

            $res = $conn->query("SELECT attachments FROM `announcements` WHERE id = {$id}");
            if (!$res || !($row = $res->fetch_assoc())) {
                throw new Exception('Không tìm thấy thông báo!');
            }

            $attaches = json_decode($row['attachments'] ?? '[]', true) ?: [];
            $newAttaches = [];
            foreach ($attaches as $a) {
                if (($a['url'] ?? '') === $fileUrl) {
                    safelyDeletePhysicalFile($fileUrl);
                } else {
                    $newAttaches[] = $a;
                }
            }

            $newJson = json_encode($newAttaches, JSON_UNESCAPED_UNICODE);
            $stmt = $conn->prepare("UPDATE `announcements` SET `attachments` = ?, `updated_at` = NOW() WHERE id = ?");
            $stmt->bind_param("si", $newJson, $id);
            $stmt->execute();
            $stmt->close();

            $response = [
                'success' => true,
                'message' => 'Đã xóa tài liệu đính kèm!',
                'data' => $newAttaches
            ];
            break;
        }

        // =====================================================================
        // 11. ACTION: XÓA 1 HÌNH ẢNH (DELETE_SINGLE_IMAGE)
        // =====================================================================
        case 'delete_single_image': {
            verifyAnnouncementPermission(['Announcement.DeleteImage', 'Announcement.Edit']);

            $data = !empty($jsonInput) ? $jsonInput : $_POST;
            $id = (int)($data['id'] ?? 0);
            $imageUrl = trim($data['image_url'] ?? '');

            if ($id <= 0 || empty($imageUrl)) {
                throw new Exception('Dữ liệu yêu cầu xóa không hợp lệ!');
            }

            $res = $conn->query("SELECT images FROM `announcements` WHERE id = {$id}");
            if (!$res || !($row = $res->fetch_assoc())) {
                throw new Exception('Không tìm thấy thông báo!');
            }

            $images = json_decode($row['images'] ?? '[]', true) ?: [];
            $newImages = [];
            foreach ($images as $img) {
                if (($img['url'] ?? '') === $imageUrl) {
                    safelyDeletePhysicalFile($imageUrl);
                    if (!empty($img['thumb'])) {
                        safelyDeletePhysicalFile($img['thumb']);
                    }
                } else {
                    $newImages[] = $img;
                }
            }

            // Đánh lại order
            foreach ($newImages as $idx => &$img) {
                $img['order'] = $idx;
            }

            $newJson = json_encode($newImages, JSON_UNESCAPED_UNICODE);
            $stmt = $conn->prepare("UPDATE `announcements` SET `images` = ?, `updated_at` = NOW() WHERE id = ?");
            $stmt->bind_param("si", $newJson, $id);
            $stmt->execute();
            $stmt->close();

            $response = [
                'success' => true,
                'message' => 'Đã xóa hình ảnh!',
                'data' => $newImages
            ];
            break;
        }

        // =====================================================================
        // 12. BẬT/TẮT TRẠNG THÁI ẨN / HIỆN (TOGGLE_STATUS)
        // =====================================================================
        case 'toggle_status': {
            verifyAnnouncementPermission('Announcement.Publish');

            $data = !empty($jsonInput) ? $jsonInput : $_POST;
            $id = (int)($data['id'] ?? 0);

            if ($id <= 0) {
                throw new Exception('ID thông báo không hợp lệ!');
            }

            $stmt = $conn->prepare("UPDATE `announcements` SET 
                `status` = CASE WHEN `status` = 'active' THEN 'inactive' ELSE 'active' END,
                `updated_at` = NOW()
                WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $res = $conn->query("SELECT status FROM `announcements` WHERE id = {$id}");
            $newStatus = $res ? $res->fetch_assoc()['status'] : 'active';

            $response = [
                'success' => true,
                'message' => $newStatus === 'active' ? 'Đã hiển thị thông báo!' : 'Đã ẩn thông báo!',
                'new_status' => $newStatus
            ];
            break;
        }

        // =====================================================================
        // 13. BẬT/TẮT ĐÁNH DẤU QUAN TRỌNG (TOGGLE_IMPORTANT)
        // =====================================================================
        case 'toggle_important': {
            verifyAnnouncementPermission('Announcement.Edit');

            $data = !empty($jsonInput) ? $jsonInput : $_POST;
            $id = (int)($data['id'] ?? 0);

            if ($id <= 0) {
                throw new Exception('ID thông báo không hợp lệ!');
            }

            $stmt = $conn->prepare("UPDATE `announcements` SET 
                `is_important` = CASE WHEN `is_important` = 1 THEN 0 ELSE 1 END,
                `updated_at` = NOW()
                WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $res = $conn->query("SELECT is_important FROM `announcements` WHERE id = {$id}");
            $newImportant = $res ? (int)$res->fetch_assoc()['is_important'] : 0;

            $response = [
                'success' => true,
                'message' => $newImportant === 1 ? 'Đã ghim thông báo quan trọng lên đầu!' : 'Đã hủy ghim thông báo quan trọng!',
                'is_important' => $newImportant
            ];
            break;
        }

        // =====================================================================
        // 14. DANH SÁCH NGƯỜI ĐÃ XEM THÔNG BÁO (VIEWERS_LIST - THỐNG KÊ)
        // =====================================================================
        case 'viewers_list': {
            verifyAnnouncementPermission('Announcement.ViewStatistics');

            $id = (int)($_GET['id'] ?? $jsonInput['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID thông báo không hợp lệ!');
            }

            $stmtAnn = $conn->prepare("SELECT id, title, total_views, created_at FROM `announcements` WHERE id = ? LIMIT 1");
            $stmtAnn->bind_param("i", $id);
            $stmtAnn->execute();
            $ann = $stmtAnn->get_result()->fetch_assoc();
            $stmtAnn->close();

            if (!$ann) {
                throw new Exception('Không tìm thấy thông báo!');
            }

            $stmtViews = $conn->prepare("SELECT 
                                            id, 
                                            user_id, 
                                            username, 
                                            fullname, 
                                            view_count, 
                                            first_viewed_at, 
                                            last_viewed_at, 
                                            ip_address 
                                         FROM `announcement_views` 
                                         WHERE announcement_id = ? 
                                         ORDER BY last_viewed_at DESC");
            $stmtViews->bind_param("i", $id);
            $stmtViews->execute();
            $result = $stmtViews->get_result();

            $viewers = [];
            $totalViewsSum = 0;
            while ($v = $result->fetch_assoc()) {
                $v['id']                      = (int)$v['id'];
                $v['view_count']              = (int)$v['view_count'];
                $totalViewsSum               += $v['view_count'];
                $v['first_viewed_formatted']  = $v['first_viewed_at'] ? date('d/m/Y H:i:s', strtotime($v['first_viewed_at'])) : '';
                $v['last_viewed_formatted']   = $v['last_viewed_at'] ? date('d/m/Y H:i:s', strtotime($v['last_viewed_at'])) : '';
                $viewers[] = $v;
            }
            $stmtViews->close();

            $response = [
                'success' => true,
                'data' => [
                    'announcement' => [
                        'id' => (int)$ann['id'],
                        'title' => $ann['title'],
                        'total_views' => (int)$ann['total_views'],
                        'unique_viewers' => count($viewers),
                        'sum_view_counts' => $totalViewsSum
                    ],
                    'viewers' => $viewers
                ]
            ];
            break;
        }

        default:
            throw new Exception("Hành động '{$action}' không được hỗ trợ!");
    }

} catch (Exception $e) {
    if (!isset($response['message']) || $response['message'] === 'Lỗi không xác định!') {
        $response = [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
