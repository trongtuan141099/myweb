<?php
/**
 * API Quản Lý Nhập Báo Cáo Sản Xuất Đùn Thủ Công & Tra Cứu / Xuất Báo Cáo Chuẩn (DATA_PLASTIC & DATA_TIME)
 * Bám sát cấu trúc file mẫu: Format_Báo Cáo Đùn 7.2026.xlsm
 * Tương thích 100% với bảng thực tích: extrusion_actual_logs, extrusion_productions, production_actuals
 */
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Ho_Chi_Minh');
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';
require_once __DIR__ . '/../core/extrusion_service.php';

// Đồng bộ thông tin đăng nhập nếu có phiên làm việc (hỗ trợ cả truy cập nhanh từ màn hình Login)
if (isset($_SESSION['user_id']) || isset($_SESSION['user'])) {
    syncUserAuth();
}

/**
 * Danh mục chuẩn từ Sheet "List" và "Input" của file Format_Báo Cáo Đùn 7.2026.xlsm
 */
function getExtrusionFormatCatalog() {
    // 1. Danh mục Size & BOM (kg/m) & Chiều dài chuẩn mỗi cuộn (m) từ Sheet List (G3:H14 & P3:Q14)
    $sizeBom = [
        'TU0425' => ['bom_kg_per_m' => 0.0087, 'std_length_m' => 3210, 'ref_note' => 'TU04 (3140m) = 27 Kg'],
        'TU0604' => ['bom_kg_per_m' => 0.0179, 'std_length_m' => 1510, 'ref_note' => 'TU06 (1475m) = 26.5 Kg'],
        'TU0805' => ['bom_kg_per_m' => 0.0349, 'std_length_m' => 930,  'ref_note' => 'TU08 (850m) = 30 Kg'],
        'TU1065' => ['bom_kg_per_m' => 0.0516, 'std_length_m' => 615,  'ref_note' => 'TU10 (520m) = 27 Kg'],
        'TU1208' => ['bom_kg_per_m' => 0.0715, 'std_length_m' => 510,  'ref_note' => 'TU12 (420m) = 30.5 Kg'],
        'TU1610' => ['bom_kg_per_m' => 0.1394, 'std_length_m' => 220,  'ref_note' => 'TU16 (220m) = 30.67 Kg'],
        'TU1611' => ['bom_kg_per_m' => 0.1206, 'std_length_m' => 220,  'ref_note' => 'TU1611 (220m) = 26.53 Kg'],
        'TIUB01' => ['bom_kg_per_m' => 0.0055, 'std_length_m' => 3210, 'ref_note' => 'TIUB01 (3140m) = 17.5 Kg'],
        'TIUB05' => ['bom_kg_per_m' => 0.0112, 'std_length_m' => 1510, 'ref_note' => 'TIUB05 (1475m) = 17 Kg'],
        'TIUB07' => ['bom_kg_per_m' => 0.0201, 'std_length_m' => 1510, 'ref_note' => 'TIUB07 (1475m) = 30 Kg'],
        'TIUB11' => ['bom_kg_per_m' => 0.0451, 'std_length_m' => 615,  'ref_note' => 'TIUB11 (520m) = 24 Kg'],
        'TIUB13' => ['bom_kg_per_m' => 0.0802, 'std_length_m' => 420,  'ref_note' => 'TIUB13 (320m) = 26 Kg'],
    ];

    // 2. Danh mục Mã màu ống -> Màu mực in từ Sheet List (J3:K31)
    $colorInkMap = [
        'YR2' => 'Đen', 'YR1' => 'Đen', 'Y2'  => 'Đen', 'Y3'  => 'Đen', 'Y1'  => 'Đen',
        'S1'  => 'Đen', 'R2'  => 'Đen', 'PU2' => 'Đen', 'R1'  => 'Đen', 'PU1' => 'Trắng',
        'P1'  => 'Đen', 'GR2' => 'Đen', 'GR1' => 'Đen', 'G4'  => 'Trắng', 'G3' => 'Đen',
        'G2'  => 'Đen', 'G1'  => 'Đen', 'BU3' => 'Đen', 'BR1' => 'Trắng', 'YR' => 'Đen',
        'BU2' => 'Đen', 'BU1' => 'Trắng', 'C' => 'Đen', 'Y'   => 'Đen', 'G'   => 'Đen',
        'BU'  => 'Đen', 'R'   => 'Đen', 'W'   => 'Đen', 'B'   => 'Trắng'
    ];

    // 3. Danh sách Nhân viên chuẩn từ Sheet List (D3:E51)
    $employeesList = [
        '02114273' => 'Thân Trọng Tuấn',
        '01920589' => 'Nguyễn Ngọc Thiện',
        '01920598' => 'Trịnh Minh Thiện',
        '01920668' => 'Hà Viết Thế Thiện',
        '01920862' => 'Nguyễn Khắc Kiên',
        '01920871' => 'Xa Minh Hiếu',
        '01921551' => 'Trương Quang Nghĩa',
        '01921560' => 'Trần Quang Hiệu',
        '02020390' => 'Võ Thiên Tuế',
        '02020488' => 'Lê Thanh Trúc',
        '02020497' => 'Vũ Nhân Tài',
        '02020947' => 'Nguyễn Hoàng Duy',
        '02020965' => 'Hà Xuân Tiến',
        '02021131' => 'Nguyễn Công Anh',
        '02021159' => 'Trần Vương',
        '02021210' => 'Phan Ngọc Trọng',
        '02021627' => 'Lê Đạt Hồng Phúc',
        '02021742' => 'Nguyễn Ngọc Dũng',
        '02021964' => 'Hồ Nhựt Trường',
        '02022149' => 'Nguyễn Xuân Mạnh',
        '02022158' => 'Bùi Lê Đức Minh',
        '02121705' => 'Vũ Trúc Phi',
        '02220255' => 'Nguyễn Đức Sơn',
        '02221892' => 'Lê Đức Anh Tuấn',
        '02221908' => 'Nguyễn Minh Hoàng',
        '02222785' => 'Nguyễn Anh Duy',
        '02223535' => 'Nguyễn Quốc',
        '02224288' => 'Võ Văn Dương',
        '02224367' => 'Ngô Minh Hiếu',
        '02226596' => 'Đoàn Văn Dương',
        '02227708' => 'Phạm Việt Khải',
        '02227717' => 'Thạch Hoàng',
        '02222332' => 'Lê Chí Khanh',
        '02229885' => 'Châu Nhựt Anh',
        '02229894' => 'Nguyễn Hữu Tài',
        '02241656' => 'Lương Trọng Ân',
        '02243991' => 'Bùi Quang Chưởng',
        '02325295' => 'Lưu Văn Đủ',
        '02325301' => 'Cao Thế Mỹ',
        '02325310' => 'Nguyễn Công Hậu',
        '02325365' => 'Trần Minh Nhật',
        '02325374' => 'Trương Văn Tình',
        '02228947' => 'Phạm Lê Minh Tân',
        '02224358' => 'Hoàng Trung Hiếu',
        '02229876' => 'Bạch Thành Tú',
        '02021645' => 'Trịnh Xuân Tỉnh',
        '02526423' => 'Âu Nguyễn Hưng',
        '02526414' => 'Nguyễn Văn Lực',
        '02544094' => 'Nguyễn Huỳnh Quốc An'
    ];

    // 4. Danh mục Mã sản phẩm -> CTSX chuẩn từ Sheet List (M3:N109)
    $productCtsxMap = [
        'TU0604C-1MZ2' => 'P250201311', 'TU1065B-1MZ2' => 'P250201312', 'TU1208B-1MZ2' => 'P250201313',
        'TU1208BU-1MZ2' => 'P250201314', 'TU0425C-1MZ2' => 'P250201315', 'TU0805C-1MZ2' => 'P250201316',
        'TU1065C-1MZ2' => 'P250201317', 'TU0604R-1MZ2' => 'P250201318', 'TU0805W-1MZ2' => 'P250201319',
        'TU0604Y-1MZ2' => 'P250201320', 'TU1208C-1MZ2' => 'P250201321', 'TU1610B-1MZ2' => 'P250201322',
        'TU0805Y-1MZ2' => 'P250201323', 'TU0604G-1MZ2' => 'P250201324', 'TU1610BU-1MZ2' => 'P250201325',
        'TU0805R-1MZ2' => 'P250201326', 'TU0425Y-1MZ2' => 'P250201327', 'TU0425W-1MZ2' => 'P250201328',
        'TU0805BU3-1MZ2' => 'P250201329', 'TU0425G-1MZ2' => 'P250201330', 'TU1208W-1MZ2' => 'P250201331',
        'TIUB01BU-1MZ2' => 'P250201332', 'TU1065W-1MZ2' => 'P250201333', 'TU0425R-1MZ2' => 'P250201334',
        'TU1208R-1MZ2' => 'P250201335', 'TU0604BU2-1MZ2' => 'P250201336', 'TU1610C-1MZ2' => 'P250201337',
        'TU1208Y-1MZ2' => 'P250201338', 'TIUB07BU-1MZ2' => 'P250201339', 'TU0805G-1MZ2' => 'P250201340',
        'TU1065BU3-1MZ2' => 'P250201341', 'TU1065Y-1MZ2' => 'P250201342', 'TU0604GR1-1MZ2' => 'P250201343',
        'TIUB07B-1MZ2' => 'P250201344', 'TU0805BU1-1MZ2' => 'P250201345', 'TU1065R-1MZ2' => 'P250201346',
        'TIUB01B-1MZ2' => 'P250201347', 'TIUB11B-1MZ2' => 'P250201348', 'TU0425YR-1MZ1' => 'P250201349',
        'TU0604YR-1MZ1' => 'P250201350', 'TU1208BU3-1MZ2' => 'P250201351', 'TIUB11BU-1MZ2' => 'P250201352',
        'TIUB01C-1MZ2' => 'P250201353', 'TU1065BU2-1MZ2' => 'P250201354', 'TU1208BU1-1MZ2' => 'P250201355',
        'TU0805YR-1MZ1' => 'P250201356', 'TU0604S1-1MZ2' => 'P250201357', 'TU0604Y1-1MZ2' => 'P250201358',
        'TU0604G1-1MZ2' => 'P250201359', 'TU0604R1-1MZ2' => 'P250201360', 'TU0805S1-1MZ2' => 'P250201361',
        'TU1065G-1MZ2' => 'P250201362', 'TU1610Y-1MZ2' => 'P250201363', 'TIUB07G-1MZ2' => 'P250201364',
        'TU1065BU1-1MZ2' => 'P250201365', 'TU1208YR-1MZ1' => 'P250201366', 'TU0805R1-1MZ2' => 'P250201367',
        'TU0604R2-1MZ2' => 'P250201368', 'TU0604YR1-1MZ2' => 'P250201369', 'TU1208BU2-1MZ2' => 'P250201370',
        'TU0805G1-1MZ2' => 'P250201371', 'TU0805Y2-1MZ2' => 'P250201372', 'TIUB01W-1MZ2' => 'P250201373',
        'TIUB13B-1MZ2' => 'P250201374', 'TIUB11C-1MZ2' => 'P250201375', 'TU0604Y2-1MZ2' => 'P250201376',
        'TIUB13BU-1MZ2' => 'P250201377', 'TIUB13C-1MZ2' => 'P250201378', 'TU0425G1-1MZ2' => 'P250201379',
        'TU0425G2-1MZ2' => 'P250201380', 'TU1065S1-1MZ2' => 'P250201381', 'TU0604P1-1MZ2' => 'P250201382',
        'TIUB11R-1MZ2' => 'P250201383', 'TU1610BU3-1MZ2' => 'P250201384', 'TU1610R-1MZ2' => 'P250201385',
        'TU0805YR2-1MZ2' => 'P250201386', 'TU1065R1-1MZ2' => 'P250201387', 'TU1208Y1-1MZ2' => 'P250201388',
        'TU1610YR-1MZ1' => 'P250201389', 'TU1610BU2-1MZ2' => 'P250201390', 'TU1610GR1-1MZ2' => 'P250201391',
        'TU1065G1-1MZ2' => 'P250201392', 'TIUB07W-1MZ2' => 'P250201393', 'TIUB13R1-1MZ2' => 'P250201394',
        'TU1208R2-1MZ2' => 'P250201395', 'TU0604G2-1MZ2' => 'P250201396', 'TIUB07R1-1MZ2' => 'P250201397',
        'TIUB07GR1-1MZ2' => 'P250201398', 'TIUB07YR1-1MZ2' => 'P250201399', 'TU0604PU1-1MZ2' => 'P250201400',
        'TU1610G1-1MZ2' => 'P250201401', 'TIUB01R2-1MZ2' => 'P250201402', 'TIUB13PU1-1MZ2' => 'P250201403',
        'TU0805R2-1MZ2' => 'P250201404', 'TIUB07GR2-1MZ2' => 'P250201405', 'TU1065G2-1MZ2' => 'P250201406',
        'TU1065R2-1MZ2' => 'P250201407', 'TU0604C-1MX214Z2' => 'P250201408', 'TU0805BU-1MX214Z2' => 'P250201409',
        'TU1065BU-1MX214Z2' => 'P250201410', 'TU0604BU-1MZ2' => 'P250201304', 'TU0604B-1MZ2' => 'P250201305',
        'TU0805BU-1MZ2' => 'P250201306', 'TU0805B-1MZ2' => 'P250201307', 'TU0425B-1MZ2' => 'P250201308',
        'TU0425BU-1MZ2' => 'P250201309', 'TU1065BU-1MZ2' => 'P250201310'
    ];

    // 5. Danh mục 41 hạng mục dừng máy & thao tác (Rows 15-55 của Sheet Input)
    $downtimeCatalog = [
        // Nhóm 1: Lỗi quản lý
        ['row' => 15, 'group' => 'Lỗi quản lý', 'code' => '103', 'name' => 'Chào lễ đầu giờ', 'def_running' => 0, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => null],
        ['row' => 16, 'group' => 'Lỗi quản lý', 'code' => '104', 'name' => 'Kiểm tra máy', 'def_running' => 0, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => null],
        ['row' => 17, 'group' => 'Lỗi quản lý', 'code' => '102', 'name' => 'Khám sức khỏe', 'def_running' => 0, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => null],
        ['row' => 18, 'group' => 'Lỗi quản lý', 'code' => '111', 'name' => 'Kiểm kê', 'def_running' => 0, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => null],
        ['row' => 19, 'group' => 'Lỗi quản lý', 'code' => '114', 'name' => 'Nghỉ giải lao', 'def_running' => 0, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => null],
        ['row' => 20, 'group' => 'Lỗi quản lý', 'code' => '115', 'name' => 'Ghi chép các phiếu sản xuất/ nhập báo cáo', 'def_running' => 0, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => null],

        // Nhóm 2: Lỗi sản xuất
        ['row' => 21, 'group' => 'Lỗi sản xuất', 'code' => '200-1', 'name' => 'Di chuyển vật liệu mới (đùn)', 'def_running' => 0, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => null],
        ['row' => 22, 'group' => 'Lỗi sản xuất', 'code' => '200-2', 'name' => 'Di chuyển máy phế phẩm đến máy nghiền', 'def_running' => 0, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => null],
        ['row' => 23, 'group' => 'Lỗi sản xuất', 'code' => '200-3', 'name' => 'Di chuyển bobin lên giá đỡ', 'def_running' => 0, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => null],
        ['row' => 24, 'group' => 'Lỗi sản xuất', 'code' => '200-4', 'name' => 'Di chuyển hủy cục cứng', 'def_running' => 0, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => null],
        ['row' => 25, 'group' => 'Lỗi sản xuất', 'code' => '206',   'name' => 'Đo độ dày ống trong quá trình sản xuất', 'def_running' => 0, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => null],
        ['row' => 26, 'group' => 'Lỗi sản xuất', 'code' => '202',   'name' => 'Chờ xả/ đổi lot liệu nếu vật liệu lỗi', 'def_running' => 1, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => null],

        // Nhóm 3: Lỗi thiết bị / điều chỉnh
        ['row' => 27, 'group' => 'Lỗi thiết bị/ điều chỉnh', 'code' => '300',   'name' => 'Hư hỏng thiết bị (Tổng nhóm 300)', 'def_running' => 1, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => true,  'parent' => null],
        ['row' => 28, 'group' => 'Lỗi thiết bị/ điều chỉnh', 'code' => '300-1', 'name' => '300-1: Lỗi máy kéo', 'def_running' => 1, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => '300'],
        ['row' => 29, 'group' => 'Lỗi thiết bị/ điều chỉnh', 'code' => '300-2', 'name' => '300-2: Lỗi máy cuộn', 'def_running' => 1, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => '300'],
        ['row' => 30, 'group' => 'Lỗi thiết bị/ điều chỉnh', 'code' => '300-3', 'name' => '300-3: Lỗi máy in', 'def_running' => 1, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => '300'],
        ['row' => 31, 'group' => 'Lỗi thiết bị/ điều chỉnh', 'code' => '300-4', 'name' => '300-4: Sập trục', 'def_running' => 1, 'def_workers' => 0, 'def_support' => 0, 'is_summary' => false, 'parent' => '300'],
        ['row' => 32, 'group' => 'Lỗi thiết bị/ điều chỉnh', 'code' => '300-5', 'name' => '300-5: Xì nhựa', 'def_running' => 1, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => '300'],
        ['row' => 33, 'group' => 'Lỗi thiết bị/ điều chỉnh', 'code' => '300-6', 'name' => '300-6: Máy sấy', 'def_running' => 1, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => '300'],
        ['row' => 34, 'group' => 'Lỗi thiết bị/ điều chỉnh', 'code' => '300-7', 'name' => '300-7: Lỗi chiller', 'def_running' => 1, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => '300'],
        ['row' => 35, 'group' => 'Lỗi thiết bị/ điều chỉnh', 'code' => '300-8', 'name' => '300-8: Thiết bị khác', 'def_running' => 1, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => '300'],
        ['row' => 36, 'group' => 'Lỗi thiết bị/ điều chỉnh', 'code' => '303',   'name' => 'Cúp điện', 'def_running' => 1, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => null],
        ['row' => 37, 'group' => 'Lỗi thiết bị/ điều chỉnh', 'code' => '308-1', 'name' => 'Điều chỉnh setup máy (khi bắt đầu sản xuất)', 'def_running' => 1, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => null],
        ['row' => 38, 'group' => 'Lỗi thiết bị/ điều chỉnh', 'code' => '308-2', 'name' => 'Thay đổi điều kiện nhiệt độ', 'def_running' => 1, 'def_workers' => 0, 'def_support' => 0, 'is_summary' => false, 'parent' => null],
        ['row' => 39, 'group' => 'Lỗi thiết bị/ điều chỉnh', 'code' => '308',   'name' => 'Điều chỉnh trong quá trình sản xuất (Tổng nhóm 308)', 'def_running' => 1, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => true,  'parent' => null],
        ['row' => 40, 'group' => 'Lỗi thiết bị/ điều chỉnh', 'code' => '308-3', 'name' => '308-3: Lấy gân', 'def_running' => 1, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => '308'],
        ['row' => 41, 'group' => 'Lỗi thiết bị/ điều chỉnh', 'code' => '308-4', 'name' => '308-4: Chân không không ổn định', 'def_running' => 1, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => '308'],
        ['row' => 42, 'group' => 'Lỗi thiết bị/ điều chỉnh', 'code' => '308-5', 'name' => '308-5: Điều chỉnh con lăn', 'def_running' => 1, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => '308'],
        ['row' => 43, 'group' => 'Lỗi thiết bị/ điều chỉnh', 'code' => '308-6', 'name' => '308-6: Thay vòng ring', 'def_running' => 1, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => '308'],
        ['row' => 44, 'group' => 'Lỗi thiết bị/ điều chỉnh', 'code' => '308-7', 'name' => '308-7: Bất định', 'def_running' => 1, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => '308'],
        ['row' => 45, 'group' => 'Lỗi thiết bị/ điều chỉnh', 'code' => '308-8', 'name' => '308-8: Biểu đồ xấu', 'def_running' => 1, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => '308'],
        ['row' => 46, 'group' => 'Lỗi thiết bị/ điều chỉnh', 'code' => '308-9', 'name' => '308-9: Gel', 'def_running' => 1, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => '308'],
        ['row' => 47, 'group' => 'Lỗi thiết bị/ điều chỉnh', 'code' => '308-10', 'name' => '308-10: Dị vật', 'def_running' => 1, 'def_workers' => 0, 'def_support' => 0, 'is_summary' => false, 'parent' => '308'],
        ['row' => 48, 'group' => 'Lỗi thiết bị/ điều chỉnh', 'code' => '308-11', 'name' => '308-11: Thử nghiệm/ test', 'def_running' => 1, 'def_workers' => 0, 'def_support' => 0, 'is_summary' => false, 'parent' => '308'],

        // Nhóm 4: Đổi màu / vệ sinh
        ['row' => 49, 'group' => 'Đổi màu/ vệ sinh', 'code' => '307',   'name' => 'Đổi màu, đổi mã sản xuất', 'def_running' => 1, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => null],
        ['row' => 50, 'group' => 'Đổi màu/ vệ sinh', 'code' => '307-1', 'name' => '307-1: Đổi màu', 'def_running' => 1, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => null],
        ['row' => 51, 'group' => 'Đổi màu/ vệ sinh', 'code' => '307-2', 'name' => '307-2: Đổi size', 'def_running' => 1, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => null],
        ['row' => 52, 'group' => 'Đổi màu/ vệ sinh', 'code' => '309-1', 'name' => 'Vệ sinh máy (nhập khối lượng chất vệ sinh nếu vệ sinh máy)', 'def_running' => 1, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => null],
        ['row' => 53, 'group' => 'Đổi màu/ vệ sinh', 'code' => '309-2', 'name' => 'Lắp đặt máy', 'def_running' => 1, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => null],
        ['row' => 54, 'group' => 'Đổi màu/ vệ sinh', 'code' => '309-3', 'name' => 'Thay gear/ vệ sinh gear', 'def_running' => 1, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => null],
        ['row' => 55, 'group' => 'Đổi màu/ vệ sinh', 'code' => '999',   'name' => 'Nguyên nhân khác/ công việc khác', 'def_running' => 1, 'def_workers' => 1, 'def_support' => 0, 'is_summary' => false, 'parent' => null],
    ];

    $machines = [];
    for ($i = 1; $i <= 24; $i++) {
        $machines[] = sprintf('PL%02d', $i);
    }

    return [
        'size_bom'          => $sizeBom,
        'color_ink_map'     => $colorInkMap,
        'employees_list'    => $employeesList,
        'product_ctsx_map'  => $productCtsxMap,
        'downtime_catalog'  => $downtimeCatalog,
        'machines'          => $machines,
        'material_types'    => ['BASF', 'DIC', 'HUNSTMAN', 'COVESTRO', 'VLN'],
        'shifts'            => ['Ca 1', 'Ca 2', 'Ca 3', 'Ca HC'],
        'production_types'  => ['Hàng sản xuất', 'Hàng bù', 'Hàng điều chỉnh'],
        'printer_types'     => ['A200', 'AX300i'],
        'ink_types'         => ['Đen', 'Trắng']
    ];
}

/**
 * Khởi tạo & chuẩn hóa cấu trúc bảng CSDL (an toàn, không phá vỡ dữ liệu cũ)
 */
function ensureExtrusionManualSchema($conn) {
    // 1. Kiểm tra AUTO_INCREMENT trên extrusion_actual_logs.id
    $resCol = $conn->query("SHOW COLUMNS FROM extrusion_actual_logs LIKE 'id'");
    if ($resCol && ($col = $resCol->fetch_assoc())) {
        if (stripos($col['Extra'] ?? '', 'auto_increment') === false) {
            $maxRes = $conn->query("SELECT COALESCE(MAX(id), 0) AS max_id FROM extrusion_actual_logs");
            $maxId = $maxRes ? (int)$maxRes->fetch_assoc()['max_id'] : 0;
            $nextId = $maxId + 1;
            $conn->query("UPDATE extrusion_actual_logs SET id = {$nextId} WHERE id = 0");
            $conn->query("ALTER TABLE extrusion_actual_logs MODIFY `id` int(11) NOT NULL AUTO_INCREMENT");
        }
    }

    // 2. Kiểm tra AUTO_INCREMENT trên extrusion_productions.id
    $resCol2 = $conn->query("SHOW COLUMNS FROM extrusion_productions LIKE 'id'");
    if ($resCol2 && ($col2 = $resCol2->fetch_assoc())) {
        if (stripos($col2['Extra'] ?? '', 'auto_increment') === false) {
            $maxRes2 = $conn->query("SELECT COALESCE(MAX(id), 0) AS max_id FROM extrusion_productions");
            $maxId2 = $maxRes2 ? (int)$maxRes2->fetch_assoc()['max_id'] : 0;
            $nextId2 = $maxId2 + 1;
            $conn->query("UPDATE extrusion_productions SET id = {$nextId2} WHERE id = 0");
            $conn->query("ALTER TABLE extrusion_productions MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT");
        }
    }

    // 3. Kiểm tra UNIQUE KEY trên production_actuals
    $resIdx = $conn->query("SHOW INDEX FROM production_actuals WHERE Key_name = 'uk_ym_size_day'");
    if ($resIdx && $resIdx->num_rows === 0) {
        // Chuẩn hóa bảng production_actuals nếu chưa có khóa
        $conn->query("
            CREATE TEMPORARY TABLE tmp_prod_actuals AS
            SELECT year_month, pipe_size, day, MAX(actual_qty) AS actual_qty
            FROM production_actuals
            GROUP BY year_month, pipe_size, day
        ");
        $conn->query("TRUNCATE TABLE production_actuals");
        @$conn->query("ALTER TABLE production_actuals MODIFY `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY");
        @$conn->query("ALTER TABLE production_actuals ADD UNIQUE KEY `uk_ym_size_day` (`year_month`, `pipe_size`, `day`)");
        $conn->query("
            INSERT IGNORE INTO production_actuals (year_month, pipe_size, day, actual_qty)
            SELECT year_month, pipe_size, day, actual_qty FROM tmp_prod_actuals
        ");
    }

    // 4. Tạo bảng lưu chi tiết Thời gian dừng máy (DATA_TIME & DATA_TIME_BU)
    $conn->query("
        CREATE TABLE IF NOT EXISTS `extrusion_downtime_logs` (
          `id` bigint(20) NOT NULL AUTO_INCREMENT,
          `actual_log_id` int(11) DEFAULT NULL COMMENT 'FK tới extrusion_actual_logs.id',
          `production_order_code` varchar(100) NOT NULL COMMENT 'Col P: Mã SX (P_NT_...)',
          `import_datetime` datetime NOT NULL COMMENT 'Col A: Ngày Nhập',
          `production_date` date NOT NULL COMMENT 'Col B: Ngày SX',
          `employee_code` varchar(50) DEFAULT NULL COMMENT 'Col C: Mã nhân viên',
          `employee_name` varchar(100) DEFAULT NULL COMMENT 'Col D: Tên nhân viên',
          `shift` varchar(20) DEFAULT NULL COMMENT 'Col E: Ca',
          `mfg_order_code` varchar(50) DEFAULT NULL COMMENT 'Col F: Mã CTSX',
          `product_code` varchar(50) NOT NULL COMMENT 'Col G: Mã sản phẩm',
          `process_name` varchar(50) DEFAULT 'Extrusion' COMMENT 'Col H: Công đoạn',
          `device_code` varchar(50) DEFAULT NULL COMMENT 'Col I: Mã thiết bị',
          `downtime_code` varchar(20) NOT NULL COMMENT 'Col J: Mã lỗi',
          `downtime_name` varchar(255) DEFAULT NULL COMMENT 'Mô tả hạng mục dừng máy',
          `occurrences` int(11) DEFAULT 0 COMMENT 'Col K: Số lần',
          `duration_hours` decimal(10,4) DEFAULT 0.0000 COMMENT 'Col L: Thời gian (h)',
          `duration_hm` varchar(20) DEFAULT NULL COMMENT 'Thời gian hiển thị HH:MM',
          `is_machine_running` tinyint(4) DEFAULT 0 COMMENT 'Col M: Máy đang chạy (1/0)',
          `worker_count` int(11) DEFAULT 0 COMMENT 'Col N: Số người',
          `support_worker_count` int(11) DEFAULT 0 COMMENT 'Col O: Số người hỗ trợ',
          `doc_code` varchar(100) DEFAULT NULL COMMENT 'Mã tài liệu (Col AO)',
          `sheet_type` enum('DATA_TIME','DATA_TIME_BU') NOT NULL DEFAULT 'DATA_TIME',
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `idx_actual_log_id` (`actual_log_id`),
          KEY `idx_prod_order_code` (`production_order_code`),
          KEY `idx_prod_date` (`production_date`),
          KEY `idx_downtime_code` (`downtime_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    // 5. Tạo bảng lưu metadata mở rộng của phiếu nhập thủ công (phục vụ Edit 100% trường Input)
    $conn->query("
        CREATE TABLE IF NOT EXISTS `extrusion_manual_ext_meta` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `actual_log_id` int(11) NOT NULL COMMENT 'FK tới extrusion_actual_logs.id',
          `production_order_code` varchar(100) NOT NULL COMMENT 'Mã SX (P_NT_...)',
          `import_datetime` datetime NOT NULL COMMENT 'Ngày giờ nhập chính xác',
          `production_type` varchar(50) NOT NULL DEFAULT 'Hàng sản xuất' COMMENT 'Hàng sản xuất / Hàng bù / Hàng điều chỉnh',
          `sheet_type` enum('DATA_PLASTIC','DATA_BU') NOT NULL DEFAULT 'DATA_PLASTIC',
          `adj_meters` decimal(12,2) DEFAULT 0.00 COMMENT 'V10: Số mét ĐC',
          `adj_weight_kg` decimal(12,2) DEFAULT 0.00 COMMENT 'F54: Khối lượng ĐC (kg)',
          `adj_log_id` int(11) DEFAULT NULL COMMENT 'ID bản ghi Hàng ĐC tương ứng nếu có',
          `start_time` varchar(20) DEFAULT NULL COMMENT 'AE10: Giờ bắt đầu',
          `end_time` varchar(20) DEFAULT NULL COMMENT 'AH10: Giờ kết thúc',
          `actual_machine_time` decimal(10,2) DEFAULT 0.00 COMMENT 'V6: TG máy thực tế (h)',
          `human_time` decimal(10,2) DEFAULT 0.00 COMMENT 'AA6: Thời gian con người (h)',
          `total_bobbin_calc` decimal(10,2) DEFAULT 0.00 COMMENT 'AN: Số bobin tính theo chuẩn',
          `defect_items_json` text DEFAULT NULL COMMENT 'Chi tiết phế phẩm P19:Q28',
          `downtime_items_json` mediumtext DEFAULT NULL COMMENT 'Toàn bộ dữ liệu bảng dừng máy Rows 15-55',
          `created_by` varchar(100) DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `uk_actual_log_id` (`actual_log_id`),
          KEY `idx_prod_order_code` (`production_order_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    // 6. Đồng bộ dữ liệu mẫu ban đầu từ file Format_Báo Cáo Đùn 7.2026.xlsm nếu bảng extrusion_downtime_logs đang trống
    $cntDt = $conn->query("SELECT COUNT(*) AS c FROM extrusion_downtime_logs");
    if ($cntDt && (int)$cntDt->fetch_assoc()['c'] === 0) {
        seedFromSampleExcelIfAvailable($conn);
    }
}

/**
 * Nạp dữ liệu lịch sử ban đầu từ file Format_Báo Cáo Đùn 7.2026.xlsm (Sheet DATA_PLASTIC & DATA_TIME)
 */
function seedFromSampleExcelIfAvailable($conn) {
    $xlsxLib = __DIR__ . '/../vendor/SimpleXLSX.php';
    if (!file_exists($xlsxLib)) return;
    require_once $xlsxLib;

    $files = glob(__DIR__ . '/../data/*.xlsm');
    $targetFile = null;
    foreach ($files as $f) {
        if (strpos(basename($f), '~$') === false) {
            $targetFile = $f;
            break;
        }
    }
    if (!$targetFile || !($xlsx = \Shuchkin\SimpleXLSX::parse($targetFile))) return;

    $catalog = getExtrusionFormatCatalog();
    $downtimeNameMap = [];
    foreach ($catalog['downtime_catalog'] as $item) {
        $downtimeNameMap[(string)$item['code']] = $item['name'];
    }

    // Map production_order_code -> actual_log_id
    $codeToLogId = [];

    // 1. Nạp Sheet 2 (DATA_PLASTIC) vào extrusion_actual_logs (nếu chưa có)
    $rowsPlastic = $xlsx->rows(2);
    foreach ($rowsPlastic as $idx => $r) {
        if ($idx < 4) continue; // Bỏ qua 4 dòng tiêu đề
        $prodDateRaw = trim((string)($r[1] ?? ''));
        $productCode = trim((string)($r[6] ?? ''));
        if ($prodDateRaw === '' || $productCode === '') continue;

        $importDtStr = trim((string)($r[0] ?? ''));
        $importDate  = !empty($importDtStr) ? date('Y-m-d', strtotime($importDtStr)) : date('Y-m-d');
        $importDt    = !empty($importDtStr) ? date('Y-m-d H:i:s', strtotime($importDtStr)) : ($importDate . ' 08:00:00');
        $prodDate    = date('Y-m-d', strtotime($prodDateRaw));
        $empCode     = trim((string)($r[2] ?? ''));
        if ($empCode !== '' && strlen($empCode) === 7 && ctype_digit($empCode)) {
            $empCode = '0' . $empCode;
        }
        $empName     = trim((string)($r[3] ?? ''));
        $shift       = trim((string)($r[4] ?? 'Ca 1'));
        $mfgOrder    = trim((string)($r[5] ?? ''));
        $pipeSize    = calculateExtrusionPipeSize($productCode);
        $processName = trim((string)($r[7] ?? 'Extrusion'));
        $deviceCode  = trim((string)($r[8] ?? ''));
        $finM        = (float)($r[9] ?? 0);
        $finKg       = (float)($r[10] ?? 0);
        $ngKg        = (float)($r[11] ?? 0);
        $hardKg      = (float)($r[12] ?? 0);
        $totKg       = (float)($r[13] ?? ($finKg + $ngKg + $hardKg));
        $matCode     = trim((string)($r[14] ?? ''));
        $regrindCnt  = (int)($r[15] ?? 0);
        $regrindPkg  = trim((string)($r[16] ?? $matCode));
        $lotIn       = trim((string)($r[17] ?? ''));
        $downtime    = round((float)($r[18] ?? 0), 2);
        $runtime     = round((float)($r[19] ?? 0), 2);
        $cycleTime   = (int)round((float)($r[20] ?? 0));
        $effRaw      = (float)($r[21] ?? 0);
        // Trong Excel DATA_PLASTIC hiệu suất lưu dạng 0.9907 (= 99.07%), quy đổi về % cho extrusion_actual_logs
        $effPct      = ($effRaw > 0 && $effRaw <= 3.0) ? round($effRaw * 100, 2) : round($effRaw, 2);
        $moldCode    = trim((string)($r[22] ?? ''));
        $spiderCode  = trim((string)($r[23] ?? ''));
        $prodOrder   = trim((string)($r[24] ?? ''));
        if ($prodOrder === '') {
            $prodOrder = 'P_NT_' . date('YmdHis', strtotime($importDt)) . '_' . $idx;
        }
        $isTest      = !empty($r[25]) ? 1 : 0;
        $matType     = trim((string)($r[26] ?? ''));
        $matNgQty    = (float)($r[27] ?? 0);
        $lotMatNg    = trim((string)($r[28] ?? ''));
        $hdpeQty     = (float)($r[29] ?? 0);
        $lioQty      = (float)($r[30] ?? 0);
        $tiQty       = (float)($r[31] ?? 0);
        $pl73Cnt     = (int)($r[32] ?? 0);
        $pl73M       = (float)($r[33] ?? 0);
        $pl47Cnt     = (int)($r[34] ?? 0);
        $pl47M       = (float)($r[35] ?? 0);
        $printer     = trim((string)($r[36] ?? 'A200'));
        $ink         = trim((string)($r[37] ?? 'Đen'));
        $waitMch     = (int)($r[38] ?? 0);
        $bobbinCalc  = (float)($r[39] ?? ($pl73Cnt + $pl47Cnt));

        $recordKey   = "{$prodDate}|{$shift}|{$mfgOrder}|{$productCode}|{$deviceCode}|{$prodOrder}";
        $recordHash  = md5($recordKey);
        $costCenter  = 'A00330';
        $dataSource  = 'MANUAL';

        $stmt = $conn->prepare("
            INSERT INTO extrusion_actual_logs (
                import_date, production_date, employee_code, employee_name, shift,
                mfg_order_code, product_code, pipe_size, cost_center, process_name,
                device_code, finished_qty_m, finished_qty_kg, ng_qty_kg, hard_waste_qty_kg,
                total_weight_kg, material_code, regrind_count, regrind_package_code, lot_in,
                total_downtime, total_runtime, cycle_time, machine_efficiency, mold_code,
                spider_code, production_order_code, is_test, material_type, material_ng_qty,
                lot_material_ng, hdpe_qty, lio_clean_qty, ti_clean_qty, bobbin_pl7_3_count,
                bobbin_pl7_3_meters, bobbin_pl4_7_count, bobbin_pl4_7_meters, printer_type, ink_type,
                waiting_machine_count, data_source, record_hash
            ) VALUES (" . implode(',', array_fill(0, 43, '?')) . ")
            ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)
        ");
        if ($stmt) {
            $types = "sssssssssssdddddsissddidsssisdsdddididssiss";
            $stmt->bind_param($types,
                $importDate, $prodDate, $empCode, $empName, $shift,
                $mfgOrder, $productCode, $pipeSize, $costCenter, $processName,
                $deviceCode, $finM, $finKg, $ngKg, $hardKg,
                $totKg, $matCode, $regrindCnt, $regrindPkg, $lotIn,
                $downtime, $runtime, $cycleTime, $effPct, $moldCode,
                $spiderCode, $prodOrder, $isTest, $matType, $matNgQty,
                $lotMatNg, $hdpeQty, $lioQty, $tiQty, $pl73Cnt,
                $pl73M, $pl47Cnt, $pl47M, $printer, $ink,
                $waitMch, $dataSource, $recordHash
            );
            if ($stmt->execute()) {
                $logId = (int)$conn->insert_id;
                $codeToLogId[$prodOrder] = $logId;

                $stmtMeta = $conn->prepare("
                    INSERT IGNORE INTO extrusion_manual_ext_meta (
                        actual_log_id, production_order_code, import_datetime, production_type,
                        sheet_type, total_bobbin_calc, created_by
                    ) VALUES (?, ?, ?, 'Hàng sản xuất', 'DATA_PLASTIC', ?, 'Format_7.2026')
                ");
                if ($stmtMeta) {
                    $stmtMeta->bind_param("issd", $logId, $prodOrder, $importDt, $bobbinCalc);
                    $stmtMeta->execute();
                }
            }
        }
    }

    // 2. Nạp Sheet 1 (DATA_TIME) vào extrusion_downtime_logs
    $rowsTime = $xlsx->rows(1);
    $stmtDt = $conn->prepare("
        INSERT INTO extrusion_downtime_logs (
            actual_log_id, production_order_code, import_datetime, production_date,
            employee_code, employee_name, shift, mfg_order_code, product_code,
            process_name, device_code, downtime_code, downtime_name, occurrences,
            duration_hours, duration_hm, is_machine_running, worker_count,
            support_worker_count, sheet_type
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'DATA_TIME')
    ");
    if ($stmtDt) {
        foreach ($rowsTime as $idx => $r) {
            if ($idx < 4) continue;
            $prodDateRaw = trim((string)($r[1] ?? ''));
            $dtCode      = trim((string)($r[9] ?? ''));
            if ($prodDateRaw === '' || $dtCode === '') continue;

            $importDtStr = trim((string)($r[0] ?? ''));
            $importDt    = !empty($importDtStr) ? date('Y-m-d H:i:s', strtotime($importDtStr)) : date('Y-m-d H:i:s');
            $prodDate    = date('Y-m-d', strtotime($prodDateRaw));
            $empCode     = trim((string)($r[2] ?? ''));
            if ($empCode !== '' && strlen($empCode) === 7 && ctype_digit($empCode)) {
                $empCode = '0' . $empCode;
            }
            $empName     = trim((string)($r[3] ?? ''));
            $shift       = trim((string)($r[4] ?? 'Ca 1'));
            $mfgOrder    = trim((string)($r[5] ?? ''));
            $productCode = trim((string)($r[6] ?? ''));
            $processName = trim((string)($r[7] ?? 'Extrusion'));
            $deviceCode  = trim((string)($r[8] ?? ''));
            $dtName      = $downtimeNameMap[$dtCode] ?? $dtCode;
            $occurrences = (int)($r[10] ?? 0);
            $durHours    = round((float)($r[11] ?? 0), 4);
            $totMins     = (int)round($durHours * 60);
            $durHm       = sprintf('%02d:%02d', intdiv($totMins, 60), $totMins % 60);
            $isRunning   = (int)($r[12] ?? 0);
            $workers     = (int)($r[13] ?? 0);
            $support     = (int)($r[14] ?? 0);
            $prodOrder   = trim((string)($r[15] ?? ''));
            $logId       = $codeToLogId[$prodOrder] ?? null;

            $stmtDt->bind_param("issssssssssssidsiii",
                $logId, $prodOrder, $importDt, $prodDate,
                $empCode, $empName, $shift, $mfgOrder, $productCode,
                $processName, $deviceCode, $dtCode, $dtName, $occurrences,
                $durHours, $durHm, $isRunning, $workers, $support
            );
            $stmtDt->execute();
        }
    }

    // Đồng bộ sang extrusion_productions
    syncExtrusionLogsToProductions($conn);
}

/**
 * Tính toán lại tổng sản lượng theo Ngày & Size cho bảng production_actuals
 */
function recalculateProductionActualsDay($conn, $productionDate, $pipeSize) {
    if (empty($productionDate) || empty($pipeSize)) return;
    $yearMonth = date('Y-m', strtotime($productionDate));
    $day       = (int)date('d', strtotime($productionDate));
    $pipeSize  = strtoupper(trim($pipeSize));

    $stmtRecalc = $conn->prepare("
        SELECT COALESCE(SUM(finished_qty_m), 0) AS total_m
        FROM extrusion_actual_logs
        WHERE DATE_FORMAT(production_date, '%Y-%m') = ?
          AND TRIM(UPPER(pipe_size)) = ?
          AND DAY(production_date) = ?
    ");
    if ($stmtRecalc) {
        $stmtRecalc->bind_param("ssi", $yearMonth, $pipeSize, $day);
        $stmtRecalc->execute();
        $res = $stmtRecalc->get_result()->fetch_assoc();
        $newTotalM = (float)($res['total_m'] ?? 0);
        $stmtRecalc->close();

        $stmtUpd = $conn->prepare("
            INSERT INTO production_actuals (year_month, pipe_size, day, actual_qty)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE actual_qty = VALUES(actual_qty)
        ");
        if ($stmtUpd) {
            $stmtUpd->bind_param("ssid", $yearMonth, $pipeSize, $day, $newTotalM);
            $stmtUpd->execute();
            $stmtUpd->close();
        }
    }
}

/**
 * Bóc tách Mã màu từ Mã sản phẩm (theo đúng công thức ô F51 của Sheet Input)
 * =RIGHT(LEFT(P10, FIND("-",P10,1)-1), LEN(LEFT(P10, FIND("-",P10,1)-1))-6)
 */
function extractProductColorCode($productCode) {
    $code = strtoupper(trim((string)$productCode));
    if (substr($code, 0, 4) === 'HF2B' && strlen($code) > 5) {
        $code = substr($code, 5);
    }
    $dashPos = strpos($code, '-');
    $prefixPart = ($dashPos !== false) ? substr($code, 0, $dashPos) : $code;
    $size = calculateExtrusionPipeSize($productCode);
    if ($size !== 'OTHER' && strpos($prefixPart, $size) === 0) {
        return substr($prefixPart, strlen($size));
    }
    if (strlen($prefixPart) > 6) {
        return substr($prefixPart, 6);
    }
    return '';
}

/**
 * Tạo file Excel .xlsx đa Sheet (OpenXML) bằng ZipArchive thuần PHP
 */
function generateMultiSheetXlsx($sheets, $downloadFilename) {
    $tmpFile = tempnam(sys_get_temp_dir(), 'extrusion_xlsx_');
    $zip = new ZipArchive();
    if ($zip->open($tmpFile, ZipArchive::OVERWRITE) !== TRUE) {
        throw new Exception('Không thể khởi tạo file nén Excel (.xlsx)');
    }

    $xmlEscape = function($str) {
        return htmlspecialchars((string)$str, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    };

    $colNameFromIndex = function($idx) {
        $s = '';
        $n = $idx;
        while ($n >= 0) {
            $s = chr($n % 26 + 65) . $s;
            $n = intdiv($n, 26) - 1;
        }
        return $s;
    };

    // 1. [Content_Types].xml
    $sheetOverrides = '';
    foreach ($sheets as $i => $sh) {
        $idx = $i + 1;
        $sheetOverrides .= '<Override PartName="/xl/worksheets/sheet' . $idx . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
    }
    $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
        . $sheetOverrides
        . '</Types>';
    $zip->addFromString('[Content_Types].xml', $contentTypes);

    // 2. _rels/.rels
    $relsRoot = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '</Relationships>';
    $zip->addFromString('_rels/.rels', $relsRoot);

    // 3. xl/workbook.xml & xl/_rels/workbook.xml.rels
    $sheetsXml = '';
    $wbRelsXml = '';
    foreach ($sheets as $i => $sh) {
        $idx = $i + 1;
        $sName = $xmlEscape($sh['name']);
        $sheetsXml .= '<sheet name="' . $sName . '" sheetId="' . $idx . '" r:id="rId' . $idx . '"/>';
        $wbRelsXml .= '<Relationship Id="rId' . $idx . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $idx . '.xml"/>';
    }
    $styleRelId = count($sheets) + 1;
    $wbRelsXml .= '<Relationship Id="rId' . $styleRelId . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

    $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheets>' . $sheetsXml . '</sheets>'
        . '</workbook>';
    $zip->addFromString('xl/workbook.xml', $workbookXml);

    $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . $wbRelsXml
        . '</Relationships>';
    $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);

    // 4. xl/styles.xml
    // Style indices:
    // 0: Normal
    // 1: Title Banner (Bold 13pt, Blue Text)
    // 2: Header Row 2 - Vietnamese (Bold, Fill #0284C7, White Text, Border, Center)
    // 3: Header Row 3 - Japanese (Fill #E0F2FE, Dark Text, Border, Center)
    // 4: Header Row 4 - English (Bold, Fill #F1F5F9, Muted Text, Border, Center)
    // 5: Data Cell Normal (Border)
    // 6: Data Cell Yellow Highlight (Fill #FEF08A, Bold, Border) - for Col AN (Số bobin)
    // 7: Warning Header Yellow (Bold, Fill #FACC15, Red Text, Border)
    $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<fonts count="6">'
        . '<font><sz val="10"/><name val="Segoe UI"/></font>'
        . '<font><b/><sz val="12"/><color rgb="FF0369A1"/><name val="Segoe UI"/></font>'
        . '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Segoe UI"/></font>'
        . '<font><sz val="9.5"/><color rgb="FF0F172A"/><name val="Segoe UI"/></font>'
        . '<font><b/><sz val="9.5"/><color rgb="FF334155"/><name val="Segoe UI"/></font>'
        . '<font><b/><sz val="10"/><color rgb="FFB91C1C"/><name val="Segoe UI"/></font>'
        . '</fonts>'
        . '<fills count="7">'
        . '<fill><patternFill patternType="none"/></fill>'
        . '<fill><patternFill patternType="gray125"/></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FF0284C7"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFE0F2FE"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFF1F5F9"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFFEF9C3"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFFDE047"/></patternFill></fill>'
        . '</fills>'
        . '<borders count="2">'
        . '<border><left/><right/><top/><bottom/><diagonal/></border>'
        . '<border>'
        . '<left style="thin"><color rgb="FFCBD5E1"/></left>'
        . '<right style="thin"><color rgb="FFCBD5E1"/></right>'
        . '<top style="thin"><color rgb="FFCBD5E1"/></top>'
        . '<bottom style="thin"><color rgb="FFCBD5E1"/></bottom>'
        . '<diagonal/>'
        . '</border>'
        . '</borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="8">'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
        . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
        . '<xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
        . '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="4" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
        . '<xf numFmtId="0" fontId="5" fillId="6" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
        . '</cellXfs>'
        . '</styleSheet>';
    $zip->addFromString('xl/styles.xml', $stylesXml);

    // 5. Worksheets
    foreach ($sheets as $i => $sh) {
        $idx = $i + 1;
        $rows = $sh['rows'] ?? [];
        $yellowColIdx = $sh['yellow_col_idx'] ?? -1;

        $colsXml = '<cols><col min="1" max="45" width="16" customWidth="1"/></cols>';
        $sheetDataXml = '<sheetData>';

        foreach ($rows as $rIdx => $rowCells) {
            $rNum = $rIdx + 1;
            $ht = ($rNum === 2) ? ' ht="28" customHeight="1"' : (($rNum <= 4) ? ' ht="20" customHeight="1"' : '');
            $sheetDataXml .= '<row r="' . $rNum . '"' . $ht . '>';

            foreach ($rowCells as $cIdx => $val) {
                $cellRef = $colNameFromIndex($cIdx) . $rNum;
                // Chọn style
                $styleId = 5;
                if ($rNum === 1) {
                    $styleId = ($cIdx >= 38 && $val !== '') ? 7 : 1;
                } elseif ($rNum === 2) {
                    $styleId = ($cIdx === $yellowColIdx) ? 7 : 2;
                } elseif ($rNum === 3) {
                    $styleId = 3;
                } elseif ($rNum === 4) {
                    $styleId = 4;
                } elseif ($cIdx === $yellowColIdx) {
                    $styleId = 6;
                }

                if ($val === '' || $val === null) {
                    if ($rNum >= 2 && $rNum <= 4) {
                        $sheetDataXml .= '<c r="' . $cellRef . '" s="' . $styleId . '"/>';
                    }
                    continue;
                }

                // Kiểm tra kiểu số (trừ mã nhân viên có số 0 ở đầu như 02020965)
                $isLeadingZeroCode = is_string($val) && strlen($val) > 1 && $val[0] === '0' && ctype_digit($val);
                if (is_numeric($val) && !$isLeadingZeroCode && $rNum > 4) {
                    $sheetDataXml .= '<c r="' . $cellRef . '" s="' . $styleId . '"><v>' . $val . '</v></c>';
                } else {
                    $sheetDataXml .= '<c r="' . $cellRef . '" t="inlineStr" s="' . $styleId . '"><is><t xml:space="preserve">' . $xmlEscape($val) . '</t></is></c>';
                }
            }
            $sheetDataXml .= '</row>';
        }
        $sheetDataXml .= '</sheetData>';

        $wsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="4" topLeftCell="A5" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . $colsXml
            . $sheetDataXml
            . '</worksheet>';

        $zip->addFromString('xl/worksheets/sheet' . $idx . '.xml', $wsXml);
    }

    $zip->close();

    ob_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $downloadFilename . '"');
    header('Content-Length: ' . filesize($tmpFile));
    header('Cache-Control: max-age=0');
    readfile($tmpFile);
    @unlink($tmpFile);
    exit;
}

/**
 * Xây dựng mảng 4 dòng tiêu đề chuẩn của Sheet DATA_PLASTIC (A -> AN: 40 cột)
 */
function getDataPlasticHeaderRows($title = 'DỮ LIỆU BÁO CÁO HÀNG SẢN XUẤT') {
    $row1 = array_fill(0, 40, '');
    $row1[0] = $title;
    $row1[38] = 'KHÔNG XÓA CỘT MÀU VÀNG';

    $row2 = [
        'Ngày Nhập', 'Ngày SX', 'Mã nhân viên', 'Tên  nhân viên', 'Ca',
        'Mã CTSX', 'Mã sản phẩm', 'Công đoạn', 'Mã thiết bị', 'Thành phẩm (M)',
        'Khối lượng thành phẩm (KG)', 'Khối lượng NG (KG)', 'Khối lượng cứng (KG)', 'Tổng KL', 'Mã Vật Liệu',
        'Số lần VL nghiền', 'Mã bì VL Nghiền', 'Lot In', 'Tổng t/g dừng máy', "Thời gian \nchạy máy",
        'Thời gian chu kỳ', 'Hiệu suất máy (%)', 'Mã Khuôn', 'Mã Spider', 'Mã SX',
        'Thủ nghiệm', 'Loại NVL', 'Nguyên liệu NG', 'Lot liệu NG', 'HDPE',
        'LIO-CLEAN', 'TI-CLEAN', 'Số bobin PL7-3', 'Số mét PL7-3', 'Số bobin PL4-7',
        'Số mét PL4-7', 'Loại máy in', 'Loại mực in', 'Số máy chờ', 'Số bobin'
    ];

    $row3 = [
        '入力日', '製造日', '社員番号', '氏名', 'シフト',
        '指令コード', '品番', '工程', '管理№', '完成品',
        '完成品のボリューム', 'NGの総体積', 'HARDの総体積', '総重量', '素材品番',
        '番号 研磨材', '材料包装コード', 'ロットイン', '停止合計', '稼働時間',
        'サイクルタイム', '機械性能', '金型コード', 'スパイダーコード', '生産コード',
        '', '', '', '', '',
        '', '', '', '', '',
        '', '', '', '', ''
    ];

    $row4 = [
        'Input Date', 'Production Date', 'Employee ID', 'Employee Name', 'Shifts',
        'Directive Code', 'Product Code', 'Stages', 'Machine Code', 'Finished Product',
        'Finished Product Weight', 'NG Weight', 'Hard Weight', 'Total Weight', 'Material Code',
        'Number Grind Materials', 'Material Packaging Code', 'Lot In', 'Total Stop Time', 'Machine Time Run',
        'Cycle Time', 'Machine Performance', 'Mold Code', 'Spider Code', 'Production Code',
        'Trial', 'Material Code', 'NG Material', '', '',
        '', '', 'PL7-3', 'PL7-3', 'PL4-7',
        'PL4-7', '', '', '', ''
    ];

    return [$row1, $row2, $row3, $row4];
}

/**
 * Xây dựng mảng 4 dòng tiêu đề chuẩn của Sheet DATA_TIME (A -> P: 16 cột)
 */
function getDataTimeHeaderRows($title = 'Dữ Liệu Thời Gian Dừng Máy') {
    $row1 = array_fill(0, 16, '');
    $row1[0] = $title;

    $row2 = [
        'Ngày Nhập', 'Ngày SX', 'Mã nhân viên', 'Tên  nhân viên', 'Ca',
        'Mã CTSX', 'Mã sản phẩm', 'Công đoạn', 'Mã thiết bị', 'Mã lỗi',
        'Số lần', 'Thời gian', 'Máy đang chạy', 'Số người', 'Số người hỗ trợ',
        'Mã SX'
    ];

    $row3 = [
        '入力日', '製造日', '社員番号', '氏名', 'シフト',
        '指令コード', '品番', '工程', '管理№', '',
        '', '', '', '', '',
        ''
    ];

    $row4 = [
        'Input Date', 'Production Date', 'Employee ID', 'Employee Name', 'Shifts',
        'Directive Code', 'Product Code', 'Stages', 'Machine Code', '',
        '', '', '', '', '',
        ''
    ];

    return [$row1, $row2, $row3, $row4];
}

try {
    ensureExtrusionManualSchema($conn);
    $action = $_GET['action'] ?? ($_POST['action'] ?? '');

    switch ($action) {
        // =====================================================================
        // 1. LẤY DANH MỤC MASTER DATA ĐẦY ĐỦ CHO FORM NHẬP LIỆU & BỘ LỌC
        // =====================================================================
        case 'get_master_data':
            $catalog = getExtrusionFormatCatalog();

            // Gộp thêm danh sách nhân viên từ bảng employees và extrusion_actual_logs
            $employeesMap = $catalog['employees_list'];
            $resEmp = $conn->query("SELECT employee_code, full_name FROM employees WHERE employee_code IS NOT NULL AND employee_code != ''");
            if ($resEmp) {
                while ($r = $resEmp->fetch_assoc()) {
                    $code = trim($r['employee_code']);
                    $name = trim($r['full_name']);
                    if ($code !== '' && $name !== '') {
                        $employeesMap[$code] = $name;
                    }
                }
            }
            $resEmpLog = $conn->query("SELECT DISTINCT employee_code, employee_name FROM extrusion_actual_logs WHERE employee_code IS NOT NULL AND employee_code != '' AND employee_name IS NOT NULL AND employee_name != '' LIMIT 500");
            if ($resEmpLog) {
                while ($r = $resEmpLog->fetch_assoc()) {
                    $code = trim($r['employee_code']);
                    $name = trim($r['employee_name']);
                    if ($code !== '' && $name !== '' && !isset($employeesMap[$code])) {
                        $employeesMap[$code] = $name;
                    }
                }
            }

            // Gộp thêm danh sách Mã sản phẩm -> CTSX & Tốc độ chuẩn từ extrusion_actual_logs
            $productMap = [];
            foreach ($catalog['product_ctsx_map'] as $pCode => $ctsx) {
                $size = calculateExtrusionPipeSize($pCode);
                $color = extractProductColorCode($pCode);
                $ink = $catalog['color_ink_map'][$color] ?? 'Đen';
                $bomInfo = $catalog['size_bom'][$size] ?? ['bom_kg_per_m' => 0, 'std_length_m' => 0];
                $productMap[$pCode] = [
                    'product_code'   => $pCode,
                    'mfg_order_code' => $ctsx,
                    'pipe_size'      => $size,
                    'color_code'     => $color,
                    'ink_type'       => $ink,
                    'bom_kg_per_m'   => $bomInfo['bom_kg_per_m'],
                    'std_length_m'   => $bomInfo['std_length_m'],
                    'cycle_time'     => 0,
                    'mold_code'      => 'DA-5',
                    'spider_code'    => 'SA-5'
                ];
            }

            $resProdLog = $conn->query("
                SELECT product_code, mfg_order_code, pipe_size, cycle_time, mold_code, spider_code, ink_type, printer_type
                FROM extrusion_actual_logs
                WHERE product_code NOT IN ('Product Code', '品番', '')
                ORDER BY production_date DESC, id DESC
                LIMIT 1000
            ");
            if ($resProdLog) {
                while ($r = $resProdLog->fetch_assoc()) {
                    $pCode = strtoupper(trim($r['product_code']));
                    if ($pCode === '') continue;
                    $size = calculateExtrusionPipeSize($pCode);
                    $color = extractProductColorCode($pCode);
                    $ink = !empty($r['ink_type']) ? $r['ink_type'] : ($catalog['color_ink_map'][$color] ?? 'Đen');
                    $bomInfo = $catalog['size_bom'][$size] ?? ['bom_kg_per_m' => 0, 'std_length_m' => 0];

                    if (!isset($productMap[$pCode])) {
                        $productMap[$pCode] = [
                            'product_code'   => $pCode,
                            'mfg_order_code' => trim($r['mfg_order_code'] ?? ''),
                            'pipe_size'      => $size,
                            'color_code'     => $color,
                            'ink_type'       => $ink,
                            'bom_kg_per_m'   => $bomInfo['bom_kg_per_m'],
                            'std_length_m'   => $bomInfo['std_length_m'],
                            'cycle_time'     => (float)($r['cycle_time'] ?? 0),
                            'mold_code'      => !empty($r['mold_code']) ? $r['mold_code'] : 'DA-5',
                            'spider_code'    => !empty($r['spider_code']) ? $r['spider_code'] : 'SA-5'
                        ];
                    } else {
                        if ($productMap[$pCode]['cycle_time'] == 0 && !empty($r['cycle_time'])) {
                            $productMap[$pCode]['cycle_time'] = (float)$r['cycle_time'];
                        }
                    }
                }
            }

            // Lấy danh sách Lot nguyên liệu gần đây để gợi ý nhanh
            $recentLots = [];
            $resLots = $conn->query("SELECT DISTINCT material_code, material_type FROM extrusion_actual_logs WHERE material_code IS NOT NULL AND material_code != '' ORDER BY id DESC LIMIT 100");
            if ($resLots) {
                while ($r = $resLots->fetch_assoc()) {
                    $recentLots[] = [
                        'material_code' => $r['material_code'],
                        'material_type' => $r['material_type']
                    ];
                }
            }

            ob_clean();
            echo json_encode([
                'success'          => true,
                'size_bom'         => $catalog['size_bom'],
                'color_ink_map'    => $catalog['color_ink_map'],
                'employees'        => $employeesMap,
                'products'         => array_values($productMap),
                'downtime_catalog' => $catalog['downtime_catalog'],
                'machines'         => $catalog['machines'],
                'material_types'   => $catalog['material_types'],
                'shifts'           => $catalog['shifts'],
                'production_types' => $catalog['production_types'],
                'printer_types'    => $catalog['printer_types'],
                'ink_types'        => $catalog['ink_types'],
                'recent_lots'      => $recentLots
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 2. LƯU HOẶC CẬP NHẬT BÁO CÁO SẢN XUẤT ĐÙN THỦ CÔNG (CREATE / UPDATE)
        // =====================================================================
        case 'save_report':
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);
            if (!is_array($data)) {
                $data = $_POST;
            }

            $id               = (int)($data['id'] ?? 0);
            $productionDate   = trim($data['production_date'] ?? date('Y-m-d'));
            $productionType   = trim($data['production_type'] ?? 'Hàng sản xuất');
            $shift            = trim($data['shift'] ?? 'Ca 1');
            $deviceCode       = strtoupper(trim($data['device_code'] ?? ''));
            $waitingMachines  = max(0, (int)($data['waiting_machine_count'] ?? 1));
            $employeeCode     = trim($data['employee_code'] ?? '');
            $employeeName     = trim($data['employee_name'] ?? '');
            $productCode      = strtoupper(trim($data['product_code'] ?? ''));
            $mfgOrderCode     = trim($data['mfg_order_code'] ?? '');
            $isTest           = !empty($data['is_test']) ? 1 : 0;

            if (empty($productionDate)) {
                throw new Exception('Vui lòng chọn Ngày sản xuất!');
            }
            if (empty($productCode)) {
                throw new Exception('Vui lòng nhập hoặc chọn Mã sản phẩm!');
            }
            if (empty($deviceCode)) {
                throw new Exception('Vui lòng chọn Mã số thiết bị (Máy đùn)!');
            }
            if (empty($employeeCode) && empty($employeeName)) {
                throw new Exception('Vui lòng nhập Mã số nhân viên (MSNV)!');
            }

            $catalog = getExtrusionFormatCatalog();

            // Tự động chuẩn hóa tên nhân viên nếu để trống hoặc "-"
            if (empty($employeeName) || $employeeName === '-') {
                $paddedCode = (strlen($employeeCode) === 7 && ctype_digit($employeeCode)) ? ('0' . $employeeCode) : $employeeCode;
                $employeeName = $catalog['employees_list'][$employeeCode] ?? ($catalog['employees_list'][$paddedCode] ?? '');
                if (empty($employeeName)) {
                    $stmtEmp = $conn->prepare("SELECT full_name FROM employees WHERE employee_code = ? OR TRIM(LEADING '0' FROM employee_code) = TRIM(LEADING '0' FROM ?) LIMIT 1");
                    if ($stmtEmp) {
                        $stmtEmp->bind_param("ss", $employeeCode, $employeeCode);
                        $stmtEmp->execute();
                        if ($rEmp = $stmtEmp->get_result()->fetch_assoc()) {
                            $employeeName = $rEmp['full_name'];
                        }
                        $stmtEmp->close();
                    }
                }
            }

            // Chuẩn hóa Mã CTSX theo Loại sản xuất (bám sát công thức ô F5)
            if ($productionType === 'Hàng bù' && (empty($mfgOrderCode) || $mfgOrderCode === '-')) {
                $mfgOrderCode = 'Hàng Bù';
            } elseif ($productionType === 'Hàng điều chỉnh' && (empty($mfgOrderCode) || $mfgOrderCode === '-')) {
                $mfgOrderCode = 'Hàng ĐC';
            } elseif (empty($mfgOrderCode) || $mfgOrderCode === 'Nhập mã sản phẩm') {
                $mfgOrderCode = $catalog['product_ctsx_map'][$productCode] ?? '-';
            }

            $pipeSize = calculateExtrusionPipeSize($productCode);
            $bomInfo  = $catalog['size_bom'][$pipeSize] ?? ['bom_kg_per_m' => 0, 'std_length_m' => 0];

            // Thông số Thành phẩm & Bobin
            $bobbinPl73Cnt = max(0, (int)($data['bobbin_pl7_3_count'] ?? 0));
            $bobbinPl73M   = max(0, (float)($data['bobbin_pl7_3_meters'] ?? 0));
            $bobbinPl47Cnt = max(0, (int)($data['bobbin_pl4_7_count'] ?? 0));
            $bobbinPl47M   = max(0, (float)($data['bobbin_pl4_7_meters'] ?? 0));

            $finishedQtyM  = isset($data['finished_qty_m']) && (float)$data['finished_qty_m'] > 0
                ? (float)$data['finished_qty_m']
                : ($bobbinPl73M + $bobbinPl47M);

            // Số mét điều chỉnh (V10)
            $adjMeters     = max(0, (float)($data['adj_meters'] ?? 0));
            $adjWeightKg   = isset($data['adj_weight_kg']) && (float)$data['adj_weight_kg'] > 0
                ? round((float)$data['adj_weight_kg'], 3)
                : round($adjMeters * (float)$bomInfo['bom_kg_per_m'], 3);

            // Khối lượng thành phẩm (K = F48 = W10 * BOM)
            $finishedQtyKg = isset($data['finished_qty_kg']) && $data['finished_qty_kg'] !== ''
                ? round((float)$data['finished_qty_kg'], 3)
                : round($finishedQtyM * (float)$bomInfo['bom_kg_per_m'], 3);

            // B: Phế phẩm điều chỉnh (Q15 -> L: Khối lượng NG) & Lot In (P15 -> R: Lot In)
            $lotIn         = trim($data['lot_in'] ?? '');
            $ngQtyKg       = round((float)($data['ng_qty_kg'] ?? 0), 3);

            // D: Nguyên liệu hủy / Cục cứng (J25 -> M: Khối lượng cứng)
            $hardWasteKg   = round((float)($data['hard_waste_qty_kg'] ?? 0), 3);

            // Tổng trọng lượng nguyên liệu (J15 = A + B + D)
            $totalWeightKg = round($finishedQtyKg + $ngQtyKg + $hardWasteKg, 3);

            // Nguyên liệu sản xuất (B15, F15, G15)
            $materialCode   = trim($data['material_code'] ?? '');
            $materialType   = trim($data['material_type'] ?? 'BASF');
            $regrindCount   = max(0, (int)($data['regrind_count'] ?? 0));
            $regrindPkgCode = trim($data['regrind_package_code'] ?? $materialCode);
            if ($regrindPkgCode === '') $regrindPkgCode = $materialCode;

            // C: Nguyên liệu NG (B20, J20)
            $lotMaterialNg  = trim($data['lot_material_ng'] ?? '');
            $materialNgQty  = round((float)($data['material_ng_qty'] ?? 0), 2);

            // Nguyên liệu vệ sinh (J40: HDPE, J41: LIOCLEAN, J42: TICLEAN)
            $hdpeQty        = round((float)($data['hdpe_qty'] ?? 0), 2);
            $lioCleanQty    = round((float)($data['lio_clean_qty'] ?? 0), 2);
            $tiCleanQty     = round((float)($data['ti_clean_qty'] ?? 0), 2);

            // Thiết bị phụ trợ & Khuôn (F33, F34, F49, F30)
            $moldCode       = trim($data['mold_code'] ?? 'DA-5');
            $spiderCode     = trim($data['spider_code'] ?? 'SA-5');
            $printerType    = trim($data['printer_type'] ?? 'A200');
            $colorCode      = extractProductColorCode($productCode);
            $inkType        = trim($data['ink_type'] ?? ($catalog['color_ink_map'][$colorCode] ?? 'Đen'));

            // Tốc độ thực tế Mpm / Chu kỳ (R11) & Thời gian chạy máy (AK10)
            $cycleTime      = (float)($data['cycle_time'] ?? 0);
            $cycleTimeInt   = (int)round($cycleTime);
            $startTime      = trim($data['start_time'] ?? '');
            $endTime        = trim($data['end_time'] ?? '');
            $totalRuntime   = round((float)($data['total_runtime'] ?? 0), 2);

            // Xử lý danh sách Thời gian dừng máy & thao tác (Rows 15-55)
            $downtimeItemsInput = is_array($data['downtime_items'] ?? null) ? $data['downtime_items'] : [];
            $defectItemsInput   = is_array($data['defect_items'] ?? null) ? $data['defect_items'] : [];

            // Tính toán tổng nhóm 300 (từ 300-1..300-8) và nhóm 308 (từ 308-3..308-11) giống hệt công thức AF27/AI27 & AF39/AI39
            $dtByCode = [];
            foreach ($downtimeItemsInput as $item) {
                $c = trim((string)($item['code'] ?? ''));
                if ($c === '') continue;
                $dtByCode[$c] = [
                    'code'                => $c,
                    'name'                => trim((string)($item['name'] ?? '')),
                    'occurrences'         => max(0, (int)($item['occurrences'] ?? 0)),
                    'duration_hours'      => max(0, round((float)($item['duration_hours'] ?? 0), 4)),
                    'duration_hm'         => trim((string)($item['duration_hm'] ?? '')),
                    'is_machine_running'  => (int)($item['is_machine_running'] ?? 0),
                    'worker_count'        => max(0, (int)($item['worker_count'] ?? 0)),
                    'support_worker_count'=> max(0, (int)($item['support_worker_count'] ?? 0)),
                    'doc_code'            => trim((string)($item['doc_code'] ?? ''))
                ];
            }

            $sum300Occ = 0; $sum300Dur = 0.0;
            $sum308Occ = 0; $sum308Dur = 0.0;
            foreach ($catalog['downtime_catalog'] as $catItem) {
                $c = (string)$catItem['code'];
                if ($catItem['parent'] === '300' && isset($dtByCode[$c])) {
                    $sum300Occ += $dtByCode[$c]['occurrences'];
                    $sum300Dur += $dtByCode[$c]['duration_hours'];
                }
                if ($catItem['parent'] === '308' && isset($dtByCode[$c])) {
                    $sum308Occ += $dtByCode[$c]['occurrences'];
                    $sum308Dur += $dtByCode[$c]['duration_hours'];
                }
            }
            if ($sum300Dur > 0 || $sum300Occ > 0) {
                $dtByCode['300'] = [
                    'code'                 => '300',
                    'name'                 => 'Hư hỏng thiết bị',
                    'occurrences'          => $sum300Occ,
                    'duration_hours'       => round($sum300Dur, 4),
                    'duration_hm'          => '',
                    'is_machine_running'   => 0, // Trong sheet Input AL27 trống để không cộng trùng 2 lần vào AL56
                    'worker_count'         => 0, // AM27 trống để không cộng trùng 2 lần vào AM56
                    'support_worker_count' => 0,
                    'doc_code'             => ''
                ];
            }
            if ($sum308Dur > 0 || $sum308Occ > 0) {
                $dtByCode['308'] = [
                    'code'                 => '308',
                    'name'                 => 'Điều chỉnh trong quá trình sản xuất',
                    'occurrences'          => $sum308Occ,
                    'duration_hours'       => round($sum308Dur, 4),
                    'duration_hm'          => '',
                    'is_machine_running'   => 0, // AL39 trống để không cộng trùng 2 lần vào AL56
                    'worker_count'         => 0, // AM39 trống để không cộng trùng 2 lần vào AM56
                    'support_worker_count' => 0,
                    'doc_code'             => ''
                ];
            }

            // Tính Tổng thời gian dừng máy có AL=1 (AL56) và Thời gian con người có AM>=1 (AM56)
            $calcDowntimeAL = 0.0;
            $calcHumanAM    = 0.0;
            foreach ($catalog['downtime_catalog'] as $catItem) {
                $c = (string)$catItem['code'];
                if ($catItem['is_summary']) continue; // Không cộng trùng dòng tổng 300 & 308
                if (isset($dtByCode[$c]) && $dtByCode[$c]['duration_hours'] > 0) {
                    $dur = $dtByCode[$c]['duration_hours'];
                    if ($dtByCode[$c]['is_machine_running'] == 1) {
                        $calcDowntimeAL += $dur;
                    }
                    if ($dtByCode[$c]['worker_count'] > 0) {
                        $calcHumanAM += $dur * $dtByCode[$c]['worker_count'];
                    }
                }
            }

            $totalDowntime = isset($data['total_downtime']) && $data['total_downtime'] !== '' && $calcDowntimeAL == 0
                ? round((float)$data['total_downtime'], 2)
                : round($calcDowntimeAL, 2);

            $humanTime = round($calcHumanAM, 2);

            // Công thức V6: TG máy thực tế = (W10 / R11) / 60 + AL56
            $extrusionRunHours = ($cycleTime > 0) ? (($finishedQtyM / $cycleTime) / 60.0) : 0.0;
            $actualMachineTime = round($extrusionRunHours + $totalDowntime, 2);

            // Công thức W6: Hiệu suất máy (%) = (W10 / R11) / (AK10 * 60) * 100
            if ($cycleTime > 0 && $totalRuntime > 0) {
                $machineEfficiency = round((($finishedQtyM / $cycleTime) / ($totalRuntime * 60.0)) * 100, 2);
            } else {
                $machineEfficiency = round((float)($data['machine_efficiency'] ?? 0), 2);
            }
            $machineEfficiency = min(999.99, max(0, $machineEfficiency));

            // Số bobin tính theo chuẩn (Cột AN của DATA_PLASTIC)
            $stdLen = (float)($bomInfo['std_length_m'] ?? 0);
            $totalBobbinCalc = ($stdLen > 0 && $finishedQtyM > 0)
                ? round($finishedQtyM / $stdLen, 2)
                : ($bobbinPl73Cnt + $bobbinPl47Cnt);

            $importDate     = date('Y-m-d');
            $importDatetime = date('Y-m-d H:i:s');
            $costCenter     = 'A00330';
            $processName    = 'Extrusion';
            $dataSource     = 'MANUAL';
            $sheetType      = ($productionType === 'Hàng sản xuất') ? 'DATA_PLASTIC' : 'DATA_BU';
            $dtSheetType    = ($productionType === 'Hàng sản xuất') ? 'DATA_TIME' : 'DATA_TIME_BU';

            $conn->begin_transaction();

            $oldProdDate = null;
            $oldPipeSize = null;
            $prodOrderCode = trim($data['production_order_code'] ?? '');

            if ($id > 0) {
                // Kiểm tra bản ghi cũ để lấy production_order_code và ngày/size cũ
                $stmtOld = $conn->prepare("SELECT production_date, pipe_size, production_order_code, import_date FROM extrusion_actual_logs WHERE id = ? LIMIT 1");
                $stmtOld->bind_param("i", $id);
                $stmtOld->execute();
                $oldRow = $stmtOld->get_result()->fetch_assoc();
                $stmtOld->close();

                if (!$oldRow) {
                    throw new Exception("Không tìm thấy bản ghi ID #{$id} để cập nhật!");
                }
                $oldProdDate = $oldRow['production_date'];
                $oldPipeSize = $oldRow['pipe_size'];
                if (empty($prodOrderCode)) {
                    $prodOrderCode = $oldRow['production_order_code'];
                }
                if (!empty($oldRow['import_date'])) {
                    $importDate = $oldRow['import_date'];
                }
            }

            if (empty($prodOrderCode)) {
                // Sinh Mã SX chuẩn theo VBA: "P_NT_" & Year(Now) & Month(Now) & Day(Now) & Hour(Now) & Minute(Now) & Second(Now)
                $now = new DateTime();
                $prodOrderCode = 'P_NT_'
                    . $now->format('Y')
                    . (int)$now->format('m')
                    . (int)$now->format('d')
                    . (int)$now->format('H')
                    . (int)$now->format('i')
                    . (int)$now->format('s');
            }

            $recordKey  = "{$productionDate}|{$shift}|{$mfgOrderCode}|{$productCode}|{$deviceCode}|{$prodOrderCode}";
            $recordHash = md5($recordKey);

            if ($id > 0) {
                // UPDATE bản ghi trong extrusion_actual_logs
                $sqlUpd = "
                    UPDATE extrusion_actual_logs SET
                        production_date = ?, employee_code = ?, employee_name = ?, shift = ?,
                        mfg_order_code = ?, product_code = ?, pipe_size = ?, cost_center = ?, process_name = ?,
                        device_code = ?, finished_qty_m = ?, finished_qty_kg = ?, ng_qty_kg = ?, hard_waste_qty_kg = ?,
                        total_weight_kg = ?, material_code = ?, regrind_count = ?, regrind_package_code = ?, lot_in = ?,
                        total_downtime = ?, total_runtime = ?, cycle_time = ?, machine_efficiency = ?, mold_code = ?,
                        spider_code = ?, production_order_code = ?, is_test = ?, material_type = ?, material_ng_qty = ?,
                        lot_material_ng = ?, hdpe_qty = ?, lio_clean_qty = ?, ti_clean_qty = ?, bobbin_pl7_3_count = ?,
                        bobbin_pl7_3_meters = ?, bobbin_pl4_7_count = ?, bobbin_pl4_7_meters = ?, printer_type = ?, ink_type = ?,
                        waiting_machine_count = ?, record_hash = ?
                    WHERE id = ?
                ";
                $stmtUpd = $conn->prepare($sqlUpd);
                if (!$stmtUpd) throw new Exception('Lỗi SQL Prepare Update: ' . $conn->error);

                $typesUpd = "ssssssssssdddddsissddidsssisdsdddididssisi";
                $stmtUpd->bind_param($typesUpd,
                    $productionDate, $employeeCode, $employeeName, $shift,
                    $mfgOrderCode, $productCode, $pipeSize, $costCenter, $processName,
                    $deviceCode, $finishedQtyM, $finishedQtyKg, $ngQtyKg, $hardWasteKg,
                    $totalWeightKg, $materialCode, $regrindCount, $regrindPkgCode, $lotIn,
                    $totalDowntime, $totalRuntime, $cycleTimeInt, $machineEfficiency, $moldCode,
                    $spiderCode, $prodOrderCode, $isTest, $materialType, $materialNgQty,
                    $lotMaterialNg, $hdpeQty, $lioCleanQty, $tiCleanQty, $bobbinPl73Cnt,
                    $bobbinPl73M, $bobbinPl47Cnt, $bobbinPl47M, $printerType, $inkType,
                    $waitingMachines, $recordHash, $id
                );
                if (!$stmtUpd->execute()) {
                    throw new Exception('Lỗi khi cập nhật báo cáo: ' . $stmtUpd->error);
                }
                $stmtUpd->close();
                $logId = $id;
            } else {
                // INSERT bản ghi mới vào extrusion_actual_logs
                $sqlIns = "
                    INSERT INTO extrusion_actual_logs (
                        import_date, production_date, employee_code, employee_name, shift,
                        mfg_order_code, product_code, pipe_size, cost_center, process_name,
                        device_code, finished_qty_m, finished_qty_kg, ng_qty_kg, hard_waste_qty_kg,
                        total_weight_kg, material_code, regrind_count, regrind_package_code, lot_in,
                        total_downtime, total_runtime, cycle_time, machine_efficiency, mold_code,
                        spider_code, production_order_code, is_test, material_type, material_ng_qty,
                        lot_material_ng, hdpe_qty, lio_clean_qty, ti_clean_qty, bobbin_pl7_3_count,
                        bobbin_pl7_3_meters, bobbin_pl4_7_count, bobbin_pl4_7_meters, printer_type, ink_type,
                        waiting_machine_count, data_source, record_hash
                    ) VALUES (" . implode(',', array_fill(0, 43, '?')) . ")
                    ON DUPLICATE KEY UPDATE
                        import_date = VALUES(import_date),
                        employee_code = VALUES(employee_code),
                        employee_name = VALUES(employee_name),
                        finished_qty_m = VALUES(finished_qty_m),
                        finished_qty_kg = VALUES(finished_qty_kg),
                        ng_qty_kg = VALUES(ng_qty_kg),
                        hard_waste_qty_kg = VALUES(hard_waste_qty_kg),
                        total_weight_kg = VALUES(total_weight_kg),
                        total_downtime = VALUES(total_downtime),
                        total_runtime = VALUES(total_runtime),
                        cycle_time = VALUES(cycle_time),
                        machine_efficiency = VALUES(machine_efficiency),
                        bobbin_pl7_3_count = VALUES(bobbin_pl7_3_count),
                        bobbin_pl7_3_meters = VALUES(bobbin_pl7_3_meters),
                        bobbin_pl4_7_count = VALUES(bobbin_pl4_7_count),
                        bobbin_pl4_7_meters = VALUES(bobbin_pl4_7_meters),
                        data_source = VALUES(data_source),
                        id = LAST_INSERT_ID(id)
                ";
                $stmtIns = $conn->prepare($sqlIns);
                if (!$stmtIns) throw new Exception('Lỗi SQL Prepare Insert: ' . $conn->error);

                $typesIns = "sssssssssssdddddsissddidsssisdsdddididssiss";
                $stmtIns->bind_param($typesIns,
                    $importDate, $productionDate, $employeeCode, $employeeName, $shift,
                    $mfgOrderCode, $productCode, $pipeSize, $costCenter, $processName,
                    $deviceCode, $finishedQtyM, $finishedQtyKg, $ngQtyKg, $hardWasteKg,
                    $totalWeightKg, $materialCode, $regrindCount, $regrindPkgCode, $lotIn,
                    $totalDowntime, $totalRuntime, $cycleTimeInt, $machineEfficiency, $moldCode,
                    $spiderCode, $prodOrderCode, $isTest, $materialType, $materialNgQty,
                    $lotMaterialNg, $hdpeQty, $lioCleanQty, $tiCleanQty, $bobbinPl73Cnt,
                    $bobbinPl73M, $bobbinPl47Cnt, $bobbinPl47M, $printerType, $inkType,
                    $waitingMachines, $dataSource, $recordHash
                );
                if (!$stmtIns->execute()) {
                    throw new Exception('Lỗi khi thêm mới báo cáo: ' . $stmtIns->error);
                }
                $logId = (int)$conn->insert_id;
                $stmtIns->close();
            }

            // Lưu / Cập nhật bảng extrusion_downtime_logs (DATA_TIME)
            $stmtDelDt = $conn->prepare("DELETE FROM extrusion_downtime_logs WHERE actual_log_id = ? OR production_order_code = ?");
            if ($stmtDelDt) {
                $stmtDelDt->bind_param("is", $logId, $prodOrderCode);
                $stmtDelDt->execute();
                $stmtDelDt->close();
            }

            $stmtInsDt = $conn->prepare("
                INSERT INTO extrusion_downtime_logs (
                    actual_log_id, production_order_code, import_datetime, production_date,
                    employee_code, employee_name, shift, mfg_order_code, product_code,
                    process_name, device_code, downtime_code, downtime_name, occurrences,
                    duration_hours, duration_hm, is_machine_running, worker_count,
                    support_worker_count, doc_code, sheet_type
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $savedDowntimeCount = 0;
            if ($stmtInsDt) {
                // Duyệt theo đúng thứ tự Rows 15-55 của Sheet Input
                foreach ($catalog['downtime_catalog'] as $catItem) {
                    $c = (string)$catItem['code'];
                    if (!isset($dtByCode[$c])) continue;
                    $dItem = $dtByCode[$c];
                    if ($dItem['duration_hours'] <= 0 && $dItem['occurrences'] <= 0) continue;

                    $dtName  = $catItem['name'];
                    $occ     = (int)$dItem['occurrences'];
                    $durH    = (float)$dItem['duration_hours'];
                    $durHm   = $dItem['duration_hm'];
                    if ($durHm === '' && $durH > 0) {
                        $mins = (int)round($durH * 60);
                        $durHm = sprintf('%02d:%02d', intdiv($mins, 60), $mins % 60);
                    }
                    $running = (int)$dItem['is_machine_running'];
                    $workers = (int)$dItem['worker_count'];
                    $support = (int)$dItem['support_worker_count'];
                    $docCode = $dItem['doc_code'];

                    $stmtInsDt->bind_param("issssssssssssidsiiiss",
                        $logId, $prodOrderCode, $importDatetime, $productionDate,
                        $employeeCode, $employeeName, $shift, $mfgOrderCode, $productCode,
                        $processName, $deviceCode, $c, $dtName, $occ,
                        $durH, $durHm, $running, $workers,
                        $support, $docCode, $dtSheetType
                    );
                    $stmtInsDt->execute();
                    $savedDowntimeCount++;
                }
                $stmtInsDt->close();
            }

            // Lưu metadata mở rộng vào extrusion_manual_ext_meta
            $defectJson   = json_encode($defectItemsInput, JSON_UNESCAPED_UNICODE);
            $downtimeJson = json_encode($downtimeItemsInput, JSON_UNESCAPED_UNICODE);
            $createdBy    = $_SESSION['user']['fullname'] ?? ($_SESSION['user']['username'] ?? $employeeName);

            $stmtMeta = $conn->prepare("
                INSERT INTO extrusion_manual_ext_meta (
                    actual_log_id, production_order_code, import_datetime, production_type,
                    sheet_type, adj_meters, adj_weight_kg, start_time, end_time,
                    actual_machine_time, human_time, total_bobbin_calc,
                    defect_items_json, downtime_items_json, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    production_order_code = VALUES(production_order_code),
                    production_type = VALUES(production_type),
                    sheet_type = VALUES(sheet_type),
                    adj_meters = VALUES(adj_meters),
                    adj_weight_kg = VALUES(adj_weight_kg),
                    start_time = VALUES(start_time),
                    end_time = VALUES(end_time),
                    actual_machine_time = VALUES(actual_machine_time),
                    human_time = VALUES(human_time),
                    total_bobbin_calc = VALUES(total_bobbin_calc),
                    defect_items_json = VALUES(defect_items_json),
                    downtime_items_json = VALUES(downtime_items_json),
                    created_by = VALUES(created_by)
            ");
            if ($stmtMeta) {
                $stmtMeta->bind_param("issssddssdddsss",
                    $logId, $prodOrderCode, $importDatetime, $productionType,
                    $sheetType, $adjMeters, $adjWeightKg, $startTime, $endTime,
                    $actualMachineTime, $humanTime, $totalBobbinCalc,
                    $defectJson, $downtimeJson, $createdBy
                );
                $stmtMeta->execute();
                $stmtMeta->close();
            }

            // Cập nhật lại bảng tổng hợp production_actuals
            if ($oldProdDate && $oldPipeSize && ($oldProdDate !== $productionDate || $oldPipeSize !== $pipeSize)) {
                recalculateProductionActualsDay($conn, $oldProdDate, $oldPipeSize);
            }
            recalculateProductionActualsDay($conn, $productionDate, $pipeSize);

            $conn->commit();

            // Đồng bộ sang bảng extrusion_productions
            syncExtrusionLogsToProductions($conn, $logId);

            ob_clean();
            echo json_encode([
                'success'               => true,
                'id'                    => $logId,
                'production_order_code' => $prodOrderCode,
                'downtime_rows_saved'   => $savedDowntimeCount,
                'message'               => ($id > 0)
                    ? "Đã cập nhật báo cáo sản xuất [{$prodOrderCode}] thành công!"
                    : "Đã lưu báo cáo sản xuất mới [{$prodOrderCode}] thành công!"
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 3. LẤY CHI TIẾT 1 BÁO CÁO ĐỂ PHỤC VỤ CHỈNH SỬA (EDIT)
        // =====================================================================
        case 'get_report_detail':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID báo cáo không hợp lệ!');
            }

            $stmt = $conn->prepare("
                SELECT l.*,
                       m.import_datetime, m.production_type, m.sheet_type,
                       m.adj_meters, m.adj_weight_kg, m.start_time, m.end_time,
                       m.actual_machine_time, m.human_time, m.total_bobbin_calc,
                       m.defect_items_json, m.downtime_items_json
                FROM extrusion_actual_logs l
                LEFT JOIN extrusion_manual_ext_meta m ON m.actual_log_id = l.id
                WHERE l.id = ?
                LIMIT 1
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$row) {
                throw new Exception("Không tìm thấy bản ghi thực tích ID #{$id}!");
            }

            // Lấy danh sách thời gian dừng máy từ extrusion_downtime_logs
            $downtimeLogs = [];
            $prodOrderCode = $row['production_order_code'] ?? '';
            $stmtDt = $conn->prepare("
                SELECT * FROM extrusion_downtime_logs
                WHERE actual_log_id = ? OR (production_order_code != '' AND production_order_code = ?)
                ORDER BY id ASC
            ");
            if ($stmtDt) {
                $stmtDt->bind_param("is", $id, $prodOrderCode);
                $stmtDt->execute();
                $downtimeLogs = $stmtDt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmtDt->close();
            }

            $row['defect_items']   = !empty($row['defect_items_json']) ? json_decode($row['defect_items_json'], true) : [];
            $row['downtime_items'] = !empty($row['downtime_items_json']) ? json_decode($row['downtime_items_json'], true) : $downtimeLogs;
            $row['downtime_logs']  = $downtimeLogs;
            unset($row['defect_items_json'], $row['downtime_items_json']);

            if (empty($row['production_type'])) {
                if (stripos($row['mfg_order_code'] ?? '', 'Bù') !== false || stripos($row['mfg_order_code'] ?? '', 'Bu') !== false) {
                    $row['production_type'] = 'Hàng bù';
                } elseif (stripos($row['mfg_order_code'] ?? '', 'DC') !== false || stripos($row['mfg_order_code'] ?? '', 'ĐC') !== false) {
                    $row['production_type'] = 'Hàng điều chỉnh';
                } else {
                    $row['production_type'] = 'Hàng sản xuất';
                }
            }

            ob_clean();
            echo json_encode([
                'success' => true,
                'data'    => $row
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 4. TRA CỨU & LỌC DỮ LIỆU THỰC TÍCH (DATA_PLASTIC & DATA_TIME)
        // =====================================================================
        case 'list_reports':
            $viewType   = $_GET['view_type'] ?? 'plastic'; // 'plastic' hoặc 'time'
            $page       = max(1, (int)($_GET['page'] ?? 1));
            $limit      = max(10, min(200, (int)($_GET['limit'] ?? 25)));
            $offset     = ($page - 1) * $limit;

            $mode       = $_GET['mode'] ?? 'month';
            if ($mode === 'month') {
                $month     = $_GET['month'] ?? date('Y-m');
                $startDate = "{$month}-01";
                $endDate   = date('Y-m-t', strtotime($startDate));
            } elseif ($mode === 'all') {
                $startDate = '2020-01-01';
                $endDate   = '2035-12-31';
            } else {
                $startDate = $_GET['start_date'] ?? date('Y-m-01');
                $endDate   = $_GET['end_date'] ?? date('Y-m-t');
            }

            $shift      = trim($_GET['shift'] ?? 'ALL');
            $pipeSize   = trim($_GET['pipe_size'] ?? 'ALL');
            $deviceCode = trim($_GET['device_code'] ?? 'ALL');
            $dataSource = trim($_GET['data_source'] ?? 'ALL');
            $prodType   = trim($_GET['production_type'] ?? 'ALL');
            $search     = trim($_GET['search'] ?? '');

            $catalog = getExtrusionFormatCatalog();

            if ($viewType === 'time') {
                // Tra cứu trên bảng extrusion_downtime_logs (DATA_TIME)
                $where = ["t.production_date BETWEEN ? AND ?"];
                $params = [$startDate, $endDate];
                $types = "ss";

                if ($shift !== 'ALL' && $shift !== '') {
                    $where[] = "t.shift = ?";
                    $params[] = $shift;
                    $types .= "s";
                }
                if ($deviceCode !== 'ALL' && $deviceCode !== '') {
                    $where[] = "(t.device_code = ? OR t.device_code LIKE ?)";
                    $params[] = $deviceCode;
                    $params[] = "%.{$deviceCode}%";
                    $types .= "ss";
                }
                if ($pipeSize !== 'ALL' && $pipeSize !== '') {
                    $where[] = "t.product_code LIKE ?";
                    $params[] = "%{$pipeSize}%";
                    $types .= "s";
                }
                if ($prodType === 'SX') {
                    $where[] = "t.sheet_type = 'DATA_TIME'";
                } elseif ($prodType === 'BU') {
                    $where[] = "t.sheet_type = 'DATA_TIME_BU'";
                }
                if ($search !== '') {
                    $where[] = "(t.employee_code LIKE ? OR t.employee_name LIKE ? OR t.mfg_order_code LIKE ? OR t.product_code LIKE ? OR t.downtime_code LIKE ? OR t.downtime_name LIKE ? OR t.production_order_code LIKE ?)";
                    $sk = "%{$search}%";
                    array_push($params, $sk, $sk, $sk, $sk, $sk, $sk, $sk);
                    $types .= "sssssss";
                }

                $whereSql = implode(" AND ", $where);

                $stmtSum = $conn->prepare("
                    SELECT COUNT(*) AS total_records,
                           COALESCE(SUM(t.occurrences), 0) AS total_occurrences,
                           COALESCE(SUM(CASE WHEN t.downtime_code NOT IN ('300','308') THEN t.duration_hours ELSE 0 END), 0) AS total_downtime_h,
                           COUNT(DISTINCT t.production_order_code) AS total_orders
                    FROM extrusion_downtime_logs t
                    WHERE {$whereSql}
                ");
                $stmtSum->bind_param($types, ...$params);
                $stmtSum->execute();
                $summary = $stmtSum->get_result()->fetch_assoc();
                $stmtSum->close();

                $totalRecords = (int)($summary['total_records'] ?? 0);

                $sqlData = "
                    SELECT t.*
                    FROM extrusion_downtime_logs t
                    WHERE {$whereSql}
                    ORDER BY t.production_date DESC, t.import_datetime DESC, t.id ASC
                    LIMIT ? OFFSET ?
                ";
                $paramsData = array_merge($params, [$limit, $offset]);
                $typesData  = $types . "ii";

                $stmtData = $conn->prepare($sqlData);
                $stmtData->bind_param($typesData, ...$paramsData);
                $stmtData->execute();
                $rows = $stmtData->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmtData->close();

                ob_clean();
                echo json_encode([
                    'success'    => true,
                    'view_type'  => 'time',
                    'data'       => $rows,
                    'summary'    => $summary,
                    'pagination' => [
                        'current_page'  => $page,
                        'limit'         => $limit,
                        'total_records' => $totalRecords,
                        'total_pages'   => max(1, (int)ceil($totalRecords / $limit))
                    ]
                ], JSON_UNESCAPED_UNICODE);
                break;
            }

            // Mặc định: Tra cứu trên bảng extrusion_actual_logs (DATA_PLASTIC)
            $where = [
                "l.production_date BETWEEN ? AND ?",
                "l.product_code NOT IN ('Product Code', '品番')"
            ];
            $params = [$startDate, $endDate];
            $types  = "ss";

            if ($shift !== 'ALL' && $shift !== '') {
                $where[] = "l.shift = ?";
                $params[] = $shift;
                $types .= "s";
            }
            if ($pipeSize !== 'ALL' && $pipeSize !== '') {
                $where[] = "TRIM(UPPER(l.pipe_size)) = ?";
                $params[] = strtoupper($pipeSize);
                $types .= "s";
            }
            if ($deviceCode !== 'ALL' && $deviceCode !== '') {
                $where[] = "(l.device_code = ? OR l.device_code LIKE ?)";
                $params[] = $deviceCode;
                $params[] = "%.{$deviceCode}%";
                $types .= "ss";
            }
            if ($dataSource !== 'ALL' && $dataSource !== '') {
                $where[] = "l.data_source = ?";
                $params[] = $dataSource;
                $types .= "s";
            }
            if ($prodType === 'SX') {
                $where[] = "(m.sheet_type = 'DATA_PLASTIC' OR (m.sheet_type IS NULL AND l.mfg_order_code NOT IN ('Hàng Bù', 'Hang Bu', 'Hang DC', 'Hàng ĐC')))";
            } elseif ($prodType === 'BU') {
                $where[] = "(m.sheet_type = 'DATA_BU' OR l.mfg_order_code IN ('Hàng Bù', 'Hang Bu', 'Hang DC', 'Hàng ĐC'))";
            }
            if ($search !== '') {
                $where[] = "(l.employee_code LIKE ? OR l.employee_name LIKE ? OR l.mfg_order_code LIKE ? OR l.product_code LIKE ? OR l.device_code LIKE ? OR l.material_code LIKE ? OR l.production_order_code LIKE ?)";
                $sk = "%{$search}%";
                array_push($params, $sk, $sk, $sk, $sk, $sk, $sk, $sk);
                $types .= "sssssss";
            }

            $whereSql = implode(" AND ", $where);

            $stmtSum = $conn->prepare("
                SELECT COUNT(*) AS total_records,
                       COALESCE(SUM(l.finished_qty_m), 0) AS total_finished_m,
                       COALESCE(SUM(l.finished_qty_kg), 0) AS total_finished_kg,
                       COALESCE(SUM(l.ng_qty_kg), 0) AS total_ng_kg,
                       COALESCE(SUM(l.hard_waste_qty_kg), 0) AS total_hard_kg,
                       COALESCE(SUM(l.total_weight_kg), 0) AS total_weight_kg,
                       COALESCE(SUM(l.bobbin_pl7_3_count + l.bobbin_pl4_7_count), 0) AS total_bobbins,
                       COALESCE(SUM(l.total_downtime), 0) AS total_downtime_h,
                       COALESCE(SUM(l.total_runtime), 0) AS total_runtime_h,
                       COALESCE(AVG(NULLIF(l.machine_efficiency, 0)), 0) AS avg_efficiency
                FROM extrusion_actual_logs l
                LEFT JOIN extrusion_manual_ext_meta m ON m.actual_log_id = l.id
                WHERE {$whereSql}
            ");
            $stmtSum->bind_param($types, ...$params);
            $stmtSum->execute();
            $summary = $stmtSum->get_result()->fetch_assoc();
            $stmtSum->close();

            $totalRecords = (int)($summary['total_records'] ?? 0);

            $sqlData = "
                SELECT l.*,
                       COALESCE(m.import_datetime, CONCAT(COALESCE(l.import_date, l.production_date), ' 08:00:00')) AS full_import_datetime,
                       COALESCE(m.production_type, CASE WHEN l.mfg_order_code IN ('Hàng Bù','Hang Bu') THEN 'Hàng bù' WHEN l.mfg_order_code IN ('Hang DC','Hàng ĐC') THEN 'Hàng điều chỉnh' ELSE 'Hàng sản xuất' END) AS production_type,
                       COALESCE(m.adj_meters, 0) AS adj_meters,
                       COALESCE(m.adj_weight_kg, 0) AS adj_weight_kg,
                       COALESCE(m.actual_machine_time, 0) AS actual_machine_time,
                       COALESCE(m.human_time, 0) AS human_time,
                       m.total_bobbin_calc
                FROM extrusion_actual_logs l
                LEFT JOIN extrusion_manual_ext_meta m ON m.actual_log_id = l.id
                WHERE {$whereSql}
                ORDER BY l.production_date DESC, l.id DESC
                LIMIT ? OFFSET ?
            ";
            $paramsData = array_merge($params, [$limit, $offset]);
            $typesData  = $types . "ii";

            $stmtData = $conn->prepare($sqlData);
            $stmtData->bind_param($typesData, ...$paramsData);
            $stmtData->execute();
            $rows = $stmtData->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmtData->close();

            // Tính toán cột Số bobin (AN) cho từng dòng theo chuẩn List!P:Q
            foreach ($rows as &$r) {
                $sz = $r['pipe_size'];
                $stdLen = (float)($catalog['size_bom'][$sz]['std_length_m'] ?? 0);
                if ($r['total_bobbin_calc'] !== null && (float)$r['total_bobbin_calc'] > 0) {
                    $r['bobbin_std_calc'] = round((float)$r['total_bobbin_calc'], 2);
                } elseif ($stdLen > 0 && (float)$r['finished_qty_m'] > 0) {
                    $r['bobbin_std_calc'] = round((float)$r['finished_qty_m'] / $stdLen, 2);
                } else {
                    $r['bobbin_std_calc'] = (int)$r['bobbin_pl7_3_count'] + (int)$r['bobbin_pl4_7_count'];
                }
            }
            unset($r);

            ob_clean();
            echo json_encode([
                'success'    => true,
                'view_type'  => 'plastic',
                'data'       => $rows,
                'summary'    => $summary,
                'pagination' => [
                    'current_page'  => $page,
                    'limit'         => $limit,
                    'total_records' => $totalRecords,
                    'total_pages'   => max(1, (int)ceil($totalRecords / $limit))
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 5. XÓA BẢN GHI BÁO CÁO
        // =====================================================================
        case 'delete_report':
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);
            $ids = $input['ids'] ?? [];
            if (!empty($input['id'])) {
                $ids[] = (int)$input['id'];
            }
            $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
            if (empty($ids)) {
                throw new Exception('Vui lòng chọn ít nhất một bản ghi để xóa!');
            }

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $types = str_repeat('i', count($ids));

            $stmtInfo = $conn->prepare("SELECT id, production_date, pipe_size, production_order_code FROM extrusion_actual_logs WHERE id IN ({$placeholders})");
            $stmtInfo->bind_param($types, ...$ids);
            $stmtInfo->execute();
            $infoRows = $stmtInfo->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmtInfo->close();

            $prodCodes = [];
            $recalcPairs = [];
            foreach ($infoRows as $ir) {
                if (!empty($ir['production_order_code'])) {
                    $prodCodes[] = $ir['production_order_code'];
                }
                $recalcPairs[$ir['production_date'] . '|' . $ir['pipe_size']] = [$ir['production_date'], $ir['pipe_size']];
            }

            $conn->begin_transaction();

            // Xóa ở extrusion_downtime_logs và extrusion_manual_ext_meta
            $stmtDelDt = $conn->prepare("DELETE FROM extrusion_downtime_logs WHERE actual_log_id IN ({$placeholders})");
            if ($stmtDelDt) {
                $stmtDelDt->bind_param($types, ...$ids);
                $stmtDelDt->execute();
                $stmtDelDt->close();
            }
            if (!empty($prodCodes)) {
                $escapedCodes = implode(',', array_map(function($c) use ($conn) {
                    return "'" . $conn->real_escape_string($c) . "'";
                }, $prodCodes));
                $conn->query("DELETE FROM extrusion_downtime_logs WHERE production_order_code IN ({$escapedCodes})");
            }

            $stmtDelMeta = $conn->prepare("DELETE FROM extrusion_manual_ext_meta WHERE actual_log_id IN ({$placeholders})");
            if ($stmtDelMeta) {
                $stmtDelMeta->bind_param($types, ...$ids);
                $stmtDelMeta->execute();
                $stmtDelMeta->close();
            }

            // Xóa ở extrusion_actual_logs
            $stmtDelLog = $conn->prepare("DELETE FROM extrusion_actual_logs WHERE id IN ({$placeholders})");
            $stmtDelLog->bind_param($types, ...$ids);
            $stmtDelLog->execute();
            $affected = $stmtDelLog->affected_rows;
            $stmtDelLog->close();

            // Tính lại production_actuals
            foreach ($recalcPairs as $pair) {
                recalculateProductionActualsDay($conn, $pair[0], $pair[1]);
            }

            $conn->commit();

            if (!empty($prodCodes)) {
                syncDeleteExtrusionActualToProduction($conn, $prodCodes);
            }

            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => "Đã xóa thành công {$affected} bản ghi báo cáo sản xuất!"
            ], JSON_UNESCAPED_UNICODE);
            break;

        // =====================================================================
        // 6. XUẤT BÁO CÁO EXCEL THEO MẪU CHUẨN (DATA_PLASTIC & DATA_TIME)
        // =====================================================================
        case 'export_excel':
            $exportFormat = $_GET['format'] ?? 'standard_workbook'; // standard_workbook | data_plastic_xlsx | data_time_xlsx | data_plastic_csv | data_time_csv
            $mode         = $_GET['mode'] ?? 'month';
            if ($mode === 'month') {
                $month     = $_GET['month'] ?? date('Y-m');
                $startDate = "{$month}-01";
                $endDate   = date('Y-m-t', strtotime($startDate));
            } elseif ($mode === 'all') {
                $startDate = '2020-01-01';
                $endDate   = '2035-12-31';
            } else {
                $startDate = $_GET['start_date'] ?? date('Y-m-01');
                $endDate   = $_GET['end_date'] ?? date('Y-m-t');
            }

            $shift      = trim($_GET['shift'] ?? 'ALL');
            $pipeSize   = trim($_GET['pipe_size'] ?? 'ALL');
            $deviceCode = trim($_GET['device_code'] ?? 'ALL');
            $dataSource = trim($_GET['data_source'] ?? 'ALL');
            $prodType   = trim($_GET['production_type'] ?? 'ALL');
            $search     = trim($_GET['search'] ?? '');

            $catalog = getExtrusionFormatCatalog();

            // 1. Truy vấn dữ liệu cho DATA_PLASTIC
            $whereP = [
                "l.production_date BETWEEN ? AND ?",
                "l.product_code NOT IN ('Product Code', '品番')"
            ];
            $paramsP = [$startDate, $endDate];
            $typesP  = "ss";

            if ($shift !== 'ALL' && $shift !== '') {
                $whereP[] = "l.shift = ?";
                $paramsP[] = $shift;
                $typesP .= "s";
            }
            if ($pipeSize !== 'ALL' && $pipeSize !== '') {
                $whereP[] = "TRIM(UPPER(l.pipe_size)) = ?";
                $paramsP[] = strtoupper($pipeSize);
                $typesP .= "s";
            }
            if ($deviceCode !== 'ALL' && $deviceCode !== '') {
                $whereP[] = "(l.device_code = ? OR l.device_code LIKE ?)";
                $paramsP[] = $deviceCode;
                $paramsP[] = "%.{$deviceCode}%";
                $typesP .= "ss";
            }
            if ($dataSource !== 'ALL' && $dataSource !== '') {
                $whereP[] = "l.data_source = ?";
                $paramsP[] = $dataSource;
                $typesP .= "s";
            }
            if ($prodType === 'SX') {
                $whereP[] = "(m.sheet_type = 'DATA_PLASTIC' OR (m.sheet_type IS NULL AND l.mfg_order_code NOT IN ('Hàng Bù', 'Hang Bu', 'Hang DC', 'Hàng ĐC')))";
            } elseif ($prodType === 'BU') {
                $whereP[] = "(m.sheet_type = 'DATA_BU' OR l.mfg_order_code IN ('Hàng Bù', 'Hang Bu', 'Hang DC', 'Hàng ĐC'))";
            }
            if ($search !== '') {
                $whereP[] = "(l.employee_code LIKE ? OR l.employee_name LIKE ? OR l.mfg_order_code LIKE ? OR l.product_code LIKE ? OR l.device_code LIKE ? OR l.material_code LIKE ? OR l.production_order_code LIKE ?)";
                $sk = "%{$search}%";
                array_push($paramsP, $sk, $sk, $sk, $sk, $sk, $sk, $sk);
                $typesP .= "sssssss";
            }

            $whereSqlP = implode(" AND ", $whereP);
            $stmtP = $conn->prepare("
                SELECT l.*,
                       COALESCE(m.import_datetime, CONCAT(COALESCE(l.import_date, l.production_date), ' 08:00:00')) AS full_import_datetime,
                       m.total_bobbin_calc
                FROM extrusion_actual_logs l
                LEFT JOIN extrusion_manual_ext_meta m ON m.actual_log_id = l.id
                WHERE {$whereSqlP}
                ORDER BY l.production_date ASC, l.id ASC
                LIMIT 50000
            ");
            $stmtP->bind_param($typesP, ...$paramsP);
            $stmtP->execute();
            $plasticLogs = $stmtP->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmtP->close();

            $plasticRows = getDataPlasticHeaderRows($prodType === 'BU' ? 'DỮ LIỆU BÁO CÁO HÀNG BÙ' : 'DỮ LIỆU BÁO CÁO HÀNG SẢN XUẤT');
            foreach ($plasticLogs as $r) {
                $sz = $r['pipe_size'];
                $stdLen = (float)($catalog['size_bom'][$sz]['std_length_m'] ?? 0);
                $bobbinVal = ($r['total_bobbin_calc'] !== null && (float)$r['total_bobbin_calc'] > 0)
                    ? round((float)$r['total_bobbin_calc'], 2)
                    : (($stdLen > 0 && (float)$r['finished_qty_m'] > 0) ? round((float)$r['finished_qty_m'] / $stdLen, 2) : ((int)$r['bobbin_pl7_3_count'] + (int)$r['bobbin_pl4_7_count']));

                // Làm sạch mã máy (ví dụ V61-MAYDUN.PL17 -> PL17 để chuẩn hóa với DATA_PLASTIC)
                $machDisplay = $r['device_code'] ?? '';
                if (preg_match('/(PL\d+)/i', $machDisplay, $mm)) {
                    $machDisplay = strtoupper($mm[1]);
                }

                // Tỷ lệ hiệu suất trong DATA_PLASTIC (hệ số thập phân như 0.9822 hoặc %)
                $effRatio = round(((float)($r['machine_efficiency'] ?? 0)) / 100.0, 4);

                $plasticRows[] = [
                    $r['full_import_datetime'],                         // A: Ngày Nhập
                    $r['production_date'],                              // B: Ngày SX
                    $r['employee_code'],                                // C: Mã nhân viên
                    $r['employee_name'],                                // D: Tên nhân viên
                    $r['shift'],                                        // E: Ca
                    $r['mfg_order_code'],                               // F: Mã CTSX
                    $r['product_code'],                                 // G: Mã sản phẩm
                    $r['process_name'] ?: 'Extrusion',                  // H: Công đoạn
                    $machDisplay,                                       // I: Mã thiết bị
                    (float)$r['finished_qty_m'],                        // J: Thành phẩm (M)
                    (float)$r['finished_qty_kg'],                       // K: Khối lượng thành phẩm (KG)
                    (float)$r['ng_qty_kg'],                             // L: Khối lượng NG (KG)
                    (float)$r['hard_waste_qty_kg'],                     // M: Khối lượng cứng (KG)
                    (float)$r['total_weight_kg'],                       // N: Tổng KL
                    $r['material_code'],                                // O: Mã Vật Liệu
                    (int)$r['regrind_count'],                           // P: Số lần VL nghiền
                    $r['regrind_package_code'] ?: $r['material_code'],  // Q: Mã bì VL Nghiền
                    $r['lot_in'],                                       // R: Lot In
                    (float)$r['total_downtime'],                        // S: Tổng t/g dừng máy
                    (float)$r['total_runtime'],                         // T: Thời gian chạy máy
                    (float)$r['cycle_time'],                            // U: Thời gian chu kỳ
                    $effRatio,                                          // V: Hiệu suất máy (%)
                    $r['mold_code'],                                    // W: Mã Khuôn
                    $r['spider_code'],                                  // X: Mã Spider
                    $r['production_order_code'],                        // Y: Mã SX
                    !empty($r['is_test']) ? 'Thử nghiệm' : '',          // Z: Thử nghiệm
                    $r['material_type'],                                // AA: Loại NVL
                    (float)$r['material_ng_qty'],                       // AB: Nguyên liệu NG
                    $r['lot_material_ng'],                              // AC: Lot liệu NG
                    (float)$r['hdpe_qty'] ?: '',                        // AD: HDPE
                    (float)$r['lio_clean_qty'] ?: '',                   // AE: LIO-CLEAN
                    (float)$r['ti_clean_qty'] ?: '',                    // AF: TI-CLEAN
                    (int)$r['bobbin_pl7_3_count'] ?: '',                // AG: Số bobin PL7-3
                    (float)$r['bobbin_pl7_3_meters'] ?: '',             // AH: Số mét PL7-3
                    (int)$r['bobbin_pl4_7_count'] ?: '',                // AI: Số bobin PL4-7
                    (float)$r['bobbin_pl4_7_meters'] ?: '',             // AJ: Số mét PL4-7
                    $r['printer_type'],                                 // AK: Loại máy in
                    $r['ink_type'],                                     // AL: Loại mực in
                    (int)$r['waiting_machine_count'],                   // AM: Số máy chờ
                    $bobbinVal                                          // AN: Số bobin
                ];
            }

            // 2. Truy vấn dữ liệu cho DATA_TIME
            $whereT = ["t.production_date BETWEEN ? AND ?"];
            $paramsT = [$startDate, $endDate];
            $typesT  = "ss";

            if ($shift !== 'ALL' && $shift !== '') {
                $whereT[] = "t.shift = ?";
                $paramsT[] = $shift;
                $typesT .= "s";
            }
            if ($deviceCode !== 'ALL' && $deviceCode !== '') {
                $whereT[] = "(t.device_code = ? OR t.device_code LIKE ?)";
                $paramsT[] = $deviceCode;
                $paramsT[] = "%.{$deviceCode}%";
                $typesT .= "ss";
            }
            if ($pipeSize !== 'ALL' && $pipeSize !== '') {
                $whereT[] = "t.product_code LIKE ?";
                $paramsT[] = "%{$pipeSize}%";
                $typesT .= "s";
            }
            if ($prodType === 'SX') {
                $whereT[] = "t.sheet_type = 'DATA_TIME'";
            } elseif ($prodType === 'BU') {
                $whereT[] = "t.sheet_type = 'DATA_TIME_BU'";
            }
            if ($search !== '') {
                $whereT[] = "(t.employee_code LIKE ? OR t.employee_name LIKE ? OR t.mfg_order_code LIKE ? OR t.product_code LIKE ? OR t.downtime_code LIKE ? OR t.production_order_code LIKE ?)";
                $sk = "%{$search}%";
                array_push($paramsT, $sk, $sk, $sk, $sk, $sk, $sk);
                $typesT .= "ssssss";
            }

            $whereSqlT = implode(" AND ", $whereT);
            $stmtT = $conn->prepare("
                SELECT t.*
                FROM extrusion_downtime_logs t
                WHERE {$whereSqlT}
                ORDER BY t.production_date ASC, t.import_datetime ASC, t.id ASC
                LIMIT 50000
            ");
            $stmtT->bind_param($typesT, ...$paramsT);
            $stmtT->execute();
            $timeLogs = $stmtT->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmtT->close();

            $timeRows = getDataTimeHeaderRows($prodType === 'BU' ? 'Dữ Liệu Thời Gian Dừng Máy BÙ' : 'Dữ Liệu Thời Gian Dừng Máy');
            foreach ($timeLogs as $t) {
                $machDisplay = $t['device_code'] ?? '';
                if (preg_match('/(PL\d+)/i', $machDisplay, $mm)) {
                    $machDisplay = strtoupper($mm[1]);
                }

                $timeRows[] = [
                    $t['import_datetime'],                              // A: Ngày Nhập
                    $t['production_date'],                              // B: Ngày SX
                    $t['employee_code'],                                // C: Mã nhân viên
                    $t['employee_name'],                                // D: Tên nhân viên
                    $t['shift'],                                        // E: Ca
                    $t['mfg_order_code'],                               // F: Mã CTSX
                    $t['product_code'],                                 // G: Mã sản phẩm
                    $t['process_name'] ?: 'Extrusion',                  // H: Công đoạn
                    $machDisplay,                                       // I: Mã thiết bị
                    $t['downtime_code'],                                // J: Mã lỗi
                    (int)$t['occurrences'],                             // K: Số lần
                    round((float)$t['duration_hours'], 4),              // L: Thời gian (h)
                    (int)$t['is_machine_running'] ?: '',                // M: Máy đang chạy
                    (int)$t['worker_count'] ?: '',                      // N: Số người
                    (int)$t['support_worker_count'] ?: '',              // O: Số người hỗ trợ
                    $t['production_order_code']                         // P: Mã SX
                ];
            }

            $tsSuffix = date('Ymd_His');

            // Xuất CSV nếu yêu cầu định dạng CSV
            if ($exportFormat === 'data_plastic_csv' || $exportFormat === 'data_time_csv') {
                $isTimeCsv = ($exportFormat === 'data_time_csv');
                $csvFilename = ($isTimeCsv ? "DATA_TIME_" : "DATA_PLASTIC_") . $tsSuffix . ".csv";
                $rowsToExport = $isTimeCsv ? $timeRows : $plasticRows;

                ob_clean();
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $csvFilename . '"');
                $out = fopen('php://output', 'w');
                fputs($out, "\xEF\xBB\xBF");
                foreach ($rowsToExport as $r) {
                    fputcsv($out, $r);
                }
                fclose($out);
                exit;
            }

            // Xuất .XLSX (1 sheet hoặc 2 sheet chuẩn)
            if ($exportFormat === 'data_plastic_xlsx') {
                generateMultiSheetXlsx([
                    ['name' => 'DATA_PLASTIC', 'rows' => $plasticRows, 'yellow_col_idx' => 39]
                ], "Bao_Cao_Dun_DATA_PLASTIC_{$tsSuffix}.xlsx");
            } elseif ($exportFormat === 'data_time_xlsx') {
                generateMultiSheetXlsx([
                    ['name' => 'DATA_TIME', 'rows' => $timeRows, 'yellow_col_idx' => -1]
                ], "Bao_Cao_Dun_DATA_TIME_{$tsSuffix}.xlsx");
            } else {
                // Mặc định: Xuất Workbook chuẩn gồm cả 2 Sheet DATA_PLASTIC và DATA_TIME
                generateMultiSheetXlsx([
                    ['name' => 'DATA_PLASTIC', 'rows' => $plasticRows, 'yellow_col_idx' => 39],
                    ['name' => 'DATA_TIME',    'rows' => $timeRows,    'yellow_col_idx' => -1]
                ], "Format_Bao_Cao_Dun_DATA_PLASTIC_TIME_{$tsSuffix}.xlsx");
            }
            break;

        default:
            ob_clean();
            echo json_encode([
                'success' => false,
                'message' => 'Action không hợp lệ: ' . htmlspecialchars($action)
            ], JSON_UNESCAPED_UNICODE);
            break;
    }

} catch (Exception $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        @$conn->rollback();
    }
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

