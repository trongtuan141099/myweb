<?php
/**
 * Database Migration & Data Standardization
 * Module: Quản lý sản xuất đùn ép (Extrusion Management)
 * 1. Đồng bộ và chuẩn hóa bảng extrusion_actual_logs làm Single Source of Truth (SSOT)
 * 2. Cập nhật Size ống theo quy chuẩn mới (HF2B, TIUB, TIA, TU, T)
 * 3. Đồng bộ hóa extrusion_productions từ extrusion_actual_logs
 * 4. Chuẩn hóa mã Size trong bảng kế hoạch production_plans
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/extrusion_service.php';

echo "=== BẮT ĐẦU QUÁ TRÌNH CHUẨN HÓA & ĐỒNG BỘ DỮ LIỆU ĐÙN ÉP ===" . PHP_EOL;

// 1. Chuyển đổi collation về đồng nhất utf8mb4_general_ci
echo "1. Chuẩn hóa Collation..." . PHP_EOL;
$conn->query("ALTER TABLE extrusion_productions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
$conn->query("ALTER TABLE extrusion_import_batches CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
echo "   -> Collation đã được đồng nhất về utf8mb4_general_ci." . PHP_EOL;

// 2. Xóa các bản ghi dòng tiêu đề bị sót trong extrusion_actual_logs
echo "2. Dọn dẹp dòng tiêu đề không hợp lệ trong extrusion_actual_logs..." . PHP_EOL;
$conn->query("DELETE FROM extrusion_actual_logs WHERE id IN (4202, 5260) OR product_code IN ('Product Code', '品番') OR product_code NOT REGEXP '^[A-Za-z0-9]'");
echo "   -> Đã dọn dẹp các dòng tiêu đề rác." . PHP_EOL;

// 3. Chuẩn hóa cột pipe_size trong extrusion_actual_logs theo đúng quy tắc mới
echo "3. Cập nhật Size ống (pipe_size) trong extrusion_actual_logs theo quy tắc chuẩn..." . PHP_EOL;
$sqlUpdateSizes = "
    UPDATE extrusion_actual_logs
    SET pipe_size = CASE
        WHEN UPPER(TRIM(product_code)) LIKE 'HF2B%' THEN SUBSTRING(UPPER(TRIM(product_code)), 6, 6)
        WHEN UPPER(TRIM(product_code)) LIKE 'TIUB%' THEN LEFT(UPPER(TRIM(product_code)), 6)
        WHEN UPPER(TRIM(product_code)) LIKE 'TIA%'  THEN LEFT(UPPER(TRIM(product_code)), 5)
        WHEN UPPER(TRIM(product_code)) LIKE 'TU%'   THEN LEFT(UPPER(TRIM(product_code)), 6)
        WHEN UPPER(TRIM(product_code)) LIKE 'T%'    THEN LEFT(UPPER(TRIM(product_code)), 5)
        ELSE 'OTHER'
    END
    WHERE id > 0
";
$conn->query($sqlUpdateSizes);
echo "   -> Đã chuẩn hóa pipe_size cho toàn bộ bản ghi extrusion_actual_logs." . PHP_EOL;

// 4. Bổ sung các bản ghi lịch sử từ extrusion_productions chưa có trong extrusion_actual_logs
echo "4. Đồng bộ dữ liệu lịch sử vào bảng chuẩn extrusion_actual_logs (SSOT)..." . PHP_EOL;
$sqlBackfill = "
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
    )
    SELECT 
        p.input_date, p.production_date, p.employee_code, p.employee_name, p.shift,
        p.directive_code, p.product_code, 
        CASE
            WHEN UPPER(TRIM(p.product_code)) LIKE 'HF2B%' THEN SUBSTRING(UPPER(TRIM(p.product_code)), 6, 6)
            WHEN UPPER(TRIM(p.product_code)) LIKE 'TIUB%' THEN LEFT(UPPER(TRIM(p.product_code)), 6)
            WHEN UPPER(TRIM(p.product_code)) LIKE 'TIA%'  THEN LEFT(UPPER(TRIM(p.product_code)), 5)
            WHEN UPPER(TRIM(p.product_code)) LIKE 'TU%'   THEN LEFT(UPPER(TRIM(p.product_code)), 6)
            WHEN UPPER(TRIM(p.product_code)) LIKE 'T%'    THEN LEFT(UPPER(TRIM(p.product_code)), 5)
            ELSE 'OTHER'
        END,
        p.cost_center, p.stage,
        p.machine_code, p.finished_length, p.finished_weight, p.ng_weight, p.hard_weight,
        p.total_weight, p.material_code, p.grind_num, p.grind_package_code, p.lot_in,
        p.stop_time_total, p.run_time, p.cycle_time, p.availability_rate, p.mold_code,
        p.spider_code, p.production_code, p.is_trial, p.material_type, 
        CAST(p.ng_material AS DECIMAL(10,2)), p.ng_material_lot, 
        CAST(p.hdpe_material AS DECIMAL(10,2)), CAST(p.lio_clean AS DECIMAL(10,2)), CAST(p.ti_clean AS DECIMAL(10,2)),
        p.coils_pl7, p.length_pl7, p.coils_pl4, p.length_pl4, p.printer_type, p.ink_type,
        p.standby_machines, 'EXCEL',
        MD5(CONCAT(p.production_date, '|', p.shift, '|', p.directive_code, '|', p.product_code, '|', p.machine_code, '|', p.production_code))
    FROM extrusion_productions p
    LEFT JOIN extrusion_actual_logs l ON p.production_code = l.production_order_code
    WHERE l.id IS NULL AND p.production_code IS NOT NULL AND p.production_code != ''
    ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP
";
$bfRes = $conn->query($sqlBackfill);
if (!$bfRes) {
    echo "   Lỗi backfill: " . $conn->error . PHP_EOL;
} else {
    echo "   -> Số bản ghi lịch sử được bổ sung vào extrusion_actual_logs: " . $conn->affected_rows . PHP_EOL;
}

// 5. Đồng bộ toàn bộ dữ liệu từ extrusion_actual_logs (SSOT) sang extrusion_productions
echo "5. Đồng bộ bảng extrusion_actual_logs (SSOT) -> extrusion_productions..." . PHP_EOL;
$syncOk = syncExtrusionLogsToProductions($conn);
if ($syncOk) {
    echo "   -> Đồng bộ thành công sang extrusion_productions! Affected rows: " . $conn->affected_rows . PHP_EOL;
} else {
    echo "   Lỗi đồng bộ: " . $conn->error . PHP_EOL;
}

// 6. Chuẩn hóa mã Size trong bảng kế hoạch production_plans
echo "6. Chuẩn hóa mã Size trong bảng production_plans..." . PHP_EOL;
$plansUpdates = [
    'TU04' => 'TU0425',
    'TU06' => 'TU0604',
    'TU08' => 'TU0805',
    'TU10' => 'TU1065',
    'TU12' => 'TU1208',
    'TU16' => 'TU1610',
];
foreach ($plansUpdates as $oldSize => $newSize) {
    $stmtPlan = $conn->prepare("UPDATE `production_plans` SET `pipe_size` = ? WHERE `pipe_size` = ?");
    $stmtPlan->bind_param("ss", $newSize, $oldSize);
    $stmtPlan->execute();
    if ($stmtPlan->affected_rows > 0) {
        echo "   -> Đã chuyển đổi {$stmtPlan->affected_rows} dòng kế hoạch: {$oldSize} => {$newSize}" . PHP_EOL;
    }
}

// 7. Thống kê kiểm tra sau đồng bộ
$cntLogs = $conn->query("SELECT COUNT(*) as c FROM extrusion_actual_logs")->fetch_assoc()['c'];
$cntProd = $conn->query("SELECT COUNT(*) as c FROM extrusion_productions")->fetch_assoc()['c'];
echo PHP_EOL . "=== KẾT QUẢ KIỂM TRA ĐỒNG BỘ ===" . PHP_EOL;
echo "Tổng số bản ghi trong extrusion_actual_logs (SSOT): {$cntLogs}" . PHP_EOL;
echo "Tổng số bản ghi trong extrusion_productions:       {$cntProd}" . PHP_EOL;

echo "=== HOÀN TẤT DI TRÚ & ĐỒNG BỘ ===" . PHP_EOL;
