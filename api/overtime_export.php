<?php
/**
 * API Xuất 9 Loại Báo Cáo Excel (Export Center)
 * DX Plastic Group - Overtime Management System
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';

global $conn;
if (!isset($conn) || !($conn instanceof mysqli)) {
    if (!defined('DB_HOST')) {
        require_once __DIR__ . '/../config/db.php';
    } else {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset("utf8");
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
requireApiPermission('api.overtime.export');

$type = $_GET['type'] ?? 'plan';
$year = !empty($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$month = !empty($_GET['month']) ? intval($_GET['month']) : 0;
$department = trim($_GET['department'] ?? '');

$filterMonthSql = $month > 0 ? " AND MONTH(ot_date) = {$month}" : "";
$filterYearSql = " AND YEAR(ot_date) = {$year}";

function sendCsvHeaders($filename) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    // Xuất UTF-8 BOM để Excel hiển thị đúng tiếng Việt
    echo "\xEF\xBB\xBF";
}

$out = fopen('php://output', 'w');

switch ($type) {
    // 1. Danh sách tăng ca kế hoạch
    case 'plan':
        sendCsvHeaders("DanhSachTangCaKeHoach_{$year}_" . ($month ?: 'All') . ".csv");
        fputcsv($out, ['STT', 'Mã Nhân Viên', 'Họ và Tên', 'Nhóm', 'Tổ Đội', 'Ngày Tăng Ca', 'Bắt Đầu KH', 'Kết Thúc KH', 'Số Phút', 'Số Giờ', 'Cấp Trên Trực Tiếp', 'Cấp Trên Gián Tiếp', 'Lý Do Tăng Ca', 'Trạng Thái']);
        $sql = "SELECT * FROM ot_plans WHERE 1=1 {$filterYearSql} {$filterMonthSql} ORDER BY ot_date ASC, employee_code ASC";
        $res = $conn->query($sql);
        $stt = 1;
        while ($r = $res->fetch_assoc()) {
            fputcsv($out, [
                $stt++, $r['employee_code'], $r['full_name'], $r['group_name'], $r['team_name'],
                $r['ot_date'], $r['start_time'], $r['end_time'], $r['total_minutes'],
                $r['total_hours'], $r['direct_manager'], $r['indirect_manager'], $r['reason'], $r['approval_status']
            ]);
        }
        break;

    // 2. Danh sách tăng ca thực tế
    case 'actual':
        sendCsvHeaders("DanhSachTangCaThucTe_{$year}_" . ($month ?: 'All') . ".csv");
        fputcsv($out, ['STT', 'Mã Nhân Viên', 'Họ và Tên', 'Nhóm', 'Tổ Đội', 'Ngày Tăng Ca', 'Bắt Đầu TT', 'Kết Thúc TT', 'Số Phút TT', 'Số Giờ TT', 'Chênh Lệch (Phút)', 'Trạng Thái Lệch', 'Cấp Trên Trực Tiếp', 'Lý Do', 'Trạng Thái Duyệt']);
        $sql = "SELECT * FROM ot_actuals WHERE 1=1 {$filterYearSql} {$filterMonthSql} ORDER BY ot_date ASC, employee_code ASC";
        $res = $conn->query($sql);
        $stt = 1;
        while ($r = $res->fetch_assoc()) {
            fputcsv($out, [
                $stt++, $r['employee_code'], $r['full_name'], $r['group_name'], $r['team_name'],
                $r['ot_date'], $r['start_time_actual'], $r['end_time_actual'], $r['total_minutes_actual'],
                $r['total_hours_actual'], $r['diff_minutes'], $r['diff_status'], $r['direct_manager'], $r['reason'], $r['approval_status']
            ]);
        }
        break;

    // 3. Bảng đối soát tăng ca tổng hợp
    case 'reconciliation':
        sendCsvHeaders("BangDoiSoatTangCa_{$year}_" . ($month ?: 'All') . ".csv");
        fputcsv($out, ['STT', 'Mã Nhân Viên', 'Họ và Tên', 'Bộ Phận', 'Ngày Tăng Ca', 'Bắt Đầu KH', 'Kết Thúc KH', 'Số Phút KH', 'Bắt Đầu TT', 'Kết Thúc TT', 'Số Phút TT', 'Chênh Lệch', 'Trạng Thái Đối Soát', 'Cần Giải Trình']);
        $sql = "
            SELECT 
                r.*,
                COALESCE(p.full_name, a.full_name, e.full_name) AS full_name,
                COALESCE(p.group_name, a.group_name, e.cost_center) AS group_name,
                p.start_time AS p_start, p.end_time AS p_end,
                a.start_time_actual AS a_start, a.end_time_actual AS a_end
            FROM ot_reconciliations r
            LEFT JOIN ot_plans p ON r.plan_id = p.id
            LEFT JOIN ot_actuals a ON r.actual_id = a.id
            LEFT JOIN employees e ON r.employee_code = e.employee_code
            WHERE 1=1 " . ($month > 0 ? "AND MONTH(r.ot_date) = {$month}" : "") . " AND YEAR(r.ot_date) = {$year}
            ORDER BY r.ot_date ASC, r.employee_code ASC
        ";
        $res = $conn->query($sql);
        $stt = 1;
        while ($r = $res->fetch_assoc()) {
            $statusLabel = [
                'matched' => 'Hợp lệ (Khớp)',
                'plan_only' => 'Có KH thiếu TT',
                'actual_only' => 'Có TT thiếu KH',
                'time_diff' => 'Lệch giờ/phút',
                'overdue' => 'Quá hạn duyệt 3 ngày'
            ][$r['reconcile_status']] ?? $r['reconcile_status'];

            fputcsv($out, [
                $stt++, $r['employee_code'], $r['full_name'], $r['group_name'], $r['ot_date'],
                $r['p_start'] ?? '-', $r['p_end'] ?? '-', $r['plan_minutes'],
                $r['a_start'] ?? '-', $r['a_end'] ?? '-', $r['actual_minutes'],
                $r['diff_minutes'], $statusLabel, $r['needs_explanation'] ? 'BẮT BUỘC' : 'Không'
            ]);
        }
        break;

    // 4. Danh sách các ca cần giải trình
    case 'need_explanation':
        sendCsvHeaders("DanhSachCanGiaiTrinh_{$year}_" . ($month ?: 'All') . ".csv");
        fputcsv($out, ['STT', 'Mã Nhân Viên', 'Họ và Tên', 'Ngày Tăng Ca', 'Loại Vi Phạm / Sai Lệch', 'Trạng Thái Giải Trình']);
        $sql = "
            SELECT 
                exp.*,
                COALESCE(e.full_name, exp.employee_code) AS full_name
            FROM ot_explanations exp
            LEFT JOIN employees e ON exp.employee_code = e.employee_code
            WHERE 1=1 " . ($month > 0 ? "AND MONTH(exp.ot_date) = {$month}" : "") . " AND YEAR(exp.ot_date) = {$year}
            ORDER BY exp.ot_date ASC
        ";
        $res = $conn->query($sql);
        $stt = 1;
        while ($r = $res->fetch_assoc()) {
            $stLabel = [
                'pending' => 'Chưa giải trình',
                'submitted' => 'Đã gửi - Chờ duyệt',
                'approved' => 'Đã duyệt',
                'rejected' => 'Từ chối'
            ][$r['approval_status']] ?? $r['approval_status'];

            fputcsv($out, [$stt++, $r['employee_code'], $r['full_name'], $r['ot_date'], $r['violation_type'], $stLabel]);
        }
        break;

    // 5. Báo cáo giải trình chi tiết
    case 'explanation_report':
        sendCsvHeaders("BaoCaoGiaiTrinhChiTiet_{$year}.csv");
        fputcsv($out, ['STT', 'Mã NV', 'Họ Tên', 'Ngày OT', 'Loại Vi Phạm', 'Nội Dung Giải Trình', 'Người Gửi', 'Ngày Gửi', 'Người Phê Duyệt', 'Trạng Thái', 'Ý Kiến Thẩm Định', 'Ngày Duyệt']);
        $sql = "
            SELECT 
                exp.*,
                COALESCE(e.full_name, exp.employee_code) AS full_name
            FROM ot_explanations exp
            LEFT JOIN employees e ON exp.employee_code = e.employee_code
            WHERE YEAR(exp.ot_date) = {$year}
            ORDER BY exp.ot_date DESC
        ";
        $res = $conn->query($sql);
        $stt = 1;
        while ($r = $res->fetch_assoc()) {
            fputcsv($out, [
                $stt++, $r['employee_code'], $r['full_name'], $r['ot_date'], $r['violation_type'],
                $r['explanation_content'], $r['submitted_by'], $r['submitted_at'],
                $r['approver_username'], $r['approval_status'], $r['approver_notes'], $r['approved_at']
            ]);
        }
        break;

    // 6. Tổng hợp giờ tăng ca theo tháng (Pivot T1 - T12)
    case 'monthly_summary':
        sendCsvHeaders("TongHopTangCaTheoThang_{$year}.csv");
        fputcsv($out, ['STT', 'Mã NV', 'Họ và Tên', 'Phòng Ban / Cost Center', 'T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12', 'Tổng Năm (Giờ)', 'Mức Cảnh Báo']);
        $sql = "
            SELECT 
                y.*,
                COALESCE(e.full_name, y.employee_code) AS full_name,
                COALESCE(e.cost_center, '-') AS cost_center
            FROM ot_yearly_accumulations y
            LEFT JOIN employees e ON y.employee_code = e.employee_code
            WHERE y.year = {$year}
            ORDER BY y.total_hours_year DESC
        ";
        $res = $conn->query($sql);
        $stt = 1;
        while ($r = $res->fetch_assoc()) {
            $wLabel = ['green' => 'An toàn (<160h)', 'yellow' => 'Cảnh báo (160-199h)', 'red' => 'VƯỢT QUY ĐỊNH (>=200h)'][$r['warning_level']] ?? $r['warning_level'];
            fputcsv($out, [
                $stt++, $r['employee_code'], $r['full_name'], $r['cost_center'],
                $r['total_hours_m1'], $r['total_hours_m2'], $r['total_hours_m3'], $r['total_hours_m4'],
                $r['total_hours_m5'], $r['total_hours_m6'], $r['total_hours_m7'], $r['total_hours_m8'],
                $r['total_hours_m9'], $r['total_hours_m10'], $r['total_hours_m11'], $r['total_hours_m12'],
                $r['total_hours_year'], $wLabel
            ]);
        }
        break;

    // 7. Tổng hợp giờ tăng ca theo năm
    case 'yearly_summary':
        sendCsvHeaders("TongHopTangCaNam_{$year}.csv");
        fputcsv($out, ['STT', 'Mã NV', 'Họ và Tên', 'Phòng Ban', 'Cấp Bậc', 'Tổng Giờ Năm', 'Giờ Còn Lại Đến 200h', '% Sử Dụng Giới Hạn', 'Mức Cảnh Báo']);
        $sql = "
            SELECT 
                y.*,
                COALESCE(e.full_name, y.employee_code) AS full_name,
                COALESCE(e.cost_center, '-') AS cost_center,
                COALESCE(e.job_level, '-') AS job_level
            FROM ot_yearly_accumulations y
            LEFT JOIN employees e ON y.employee_code = e.employee_code
            WHERE y.year = {$year}
            ORDER BY y.total_hours_year DESC
        ";
        $res = $conn->query($sql);
        $stt = 1;
        while ($r = $res->fetch_assoc()) {
            fputcsv($out, [
                $stt++, $r['employee_code'], $r['full_name'], $r['cost_center'], $r['job_level'],
                $r['total_hours_year'], $r['remaining_hours'], $r['usage_percent'] . '%', strtoupper($r['warning_level'])
            ]);
        }
        break;

    // 8. Danh sách cảnh báo nhân viên vượt hoặc sắp vượt ngưỡng 200h
    case 'warning_200h':
        sendCsvHeaders("DanhSachCanhBaoVuot200H_{$year}.csv");
        fputcsv($out, ['STT', 'Mã NV', 'Họ và Tên', 'Phòng Ban', 'Tổng Giờ OT Đã Làm', 'Số Giờ Vượt Quá 200h', '% Giới Hạn', 'Mức Cảnh Báo']);
        $sql = "
            SELECT 
                y.*,
                COALESCE(e.full_name, y.employee_code) AS full_name,
                COALESCE(e.cost_center, '-') AS cost_center
            FROM ot_yearly_accumulations y
            LEFT JOIN employees e ON y.employee_code = e.employee_code
            WHERE y.year = {$year} AND y.warning_level IN ('yellow', 'red')
            ORDER BY y.total_hours_year DESC
        ";
        $res = $conn->query($sql);
        $stt = 1;
        while ($r = $res->fetch_assoc()) {
            $overHours = max(0, $r['total_hours_year'] - 200.00);
            $wText = ($r['warning_level'] === 'red') ? 'ĐÃ VƯỢT QUY ĐỊNH (>=200H)' : 'SẮP VƯỢT NGƯỠNG (160-199H)';
            fputcsv($out, [
                $stt++, $r['employee_code'], $r['full_name'], $r['cost_center'],
                $r['total_hours_year'], $overHours > 0 ? $overHours : 0, $r['usage_percent'] . '%', $wText
            ]);
        }
        break;

    // 9. Dashboard Summary
    default:
        sendCsvHeaders("BaoCaoTongQuanDashboard_{$year}.csv");
        fputcsv($out, ['BÁO CÁO TỔNG QUAN TĂNG CA NHÀ MÁY DX PLASTIC GROUP']);
        fputcsv($out, ['Năm', $year]);
        fputcsv($out, ['Thời gian xuất', date('d/m/Y H:i:s')]);
        fputcsv($out, []);
        fputcsv($out, ['Chỉ Số KPI', 'Giá Trị']);

        $resTot = $conn->query("SELECT COUNT(DISTINCT employee_code) as total_emp, SUM(total_hours_actual) as total_h FROM ot_actuals WHERE YEAR(ot_date) = {$year}");
        $kpi = $resTot ? $resTot->fetch_assoc() : [];
        fputcsv($out, ['Tổng số nhân viên có OT', $kpi['total_emp'] ?? 0]);
        fputcsv($out, ['Tổng số giờ OT năm', $kpi['total_h'] ?? 0]);

        $resW = $conn->query("SELECT SUM(warning_level='yellow') as y_cnt, SUM(warning_level='red') as r_cnt FROM ot_yearly_accumulations WHERE year = {$year}");
        $wStats = $resW ? $resW->fetch_assoc() : [];
        fputcsv($out, ['Số nhân sự mức cảnh báo Vàng (160-199h)', $wStats['y_cnt'] ?? 0]);
        fputcsv($out, ['Số nhân sự mức cảnh báo Đỏ (>=200h)', $wStats['r_cnt'] ?? 0]);
        break;
}

fclose($out);
exit;
?>

