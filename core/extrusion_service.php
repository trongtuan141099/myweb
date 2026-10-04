<?php
/**
 * Core Service: Extrusion Production Management
 * Xử lý chuẩn hóa Size ống, đồng bộ dữ liệu giữa extrusion_actual_logs và extrusion_productions
 * Lấy extrusion_actual_logs làm bảng chuẩn (Single Source of Truth)
 */

if (!function_exists('calculateExtrusionPipeSize')) {
    /**
     * Chuẩn hóa quy tắc bóc tách Size ống từ mã sản phẩm (Product Code):
     * 1. Tiền tố HF2B: Cắt từ vị trí thứ 6, lấy 6 ký tự tiếp theo (Ví dụ: HF2B-TU1208... -> TU1208)
     * 2. Tiền tố TIUB: Lấy 6 ký tự đầu tiên (Ví dụ: TIUB07C... -> TIUB07)
     * 3. Tiền tố TIA:  Lấy 5 ký tự đầu tiên (Ví dụ: TIA07B... -> TIA07)
     * 4. Tiền tố TU:   Lấy 6 ký tự đầu tiên (Ví dụ: TU0604C... -> TU0604)
     * 5. Tiền tố T:    Lấy 5 ký tự đầu tiên (Ví dụ: T0604B... -> T0604)
     */
    function calculateExtrusionPipeSize($productCode) {
        $code = strtoupper(trim((string)$productCode));
        if ($code === '' || $code === 'PRODUCT CODE' || $code === '品番') {
            return 'OTHER';
        }

        // 1. Tiền tố HF2B: Cắt từ vị trí thứ 6 (1-based index 6 => 0-based offset 5), lấy 6 ký tự
        if (substr($code, 0, 4) === 'HF2B') {
            return substr($code, 5, 6);
        }

        // 2. Tiền tố TIUB: Lấy 6 ký tự đầu tiên
        if (substr($code, 0, 4) === 'TIUB') {
            return substr($code, 0, 6);
        }

        // 3. Tiền tố TIA: Lấy 5 ký tự đầu tiên
        if (substr($code, 0, 3) === 'TIA') {
            return substr($code, 0, 5);
        }

        // 4. Tiền tố TU: Lấy 6 ký tự đầu tiên
        if (substr($code, 0, 2) === 'TU') {
            return substr($code, 0, 6);
        }

        // 5. Tiền tố T: Lấy 5 ký tự đầu tiên
        if (substr($code, 0, 1) === 'T') {
            return substr($code, 0, 5);
        }

        return 'OTHER';
    }
}

if (!function_exists('extractPipeSize')) {
    /**
     * Alias đồng bộ cho calculateExtrusionPipeSize để tương thích với các module cũ
     */
    function extractPipeSize($productCode) {
        return calculateExtrusionPipeSize($productCode);
    }
}

if (!function_exists('getExtrusionStandardSizes')) {
    /**
     * Danh sách các size ống quy chuẩn phổ biến
     */
    function getExtrusionStandardSizes() {
        return [
            'TU0425', 'TU0604', 'TU0805', 'TU1065', 'TU1208', 'TU1610',
            'TIUB01', 'TIUB05', 'TIUB07', 'TIUB11', 'TIUB13'
        ];
    }
}

if (!function_exists('normalizePlanPipeSize')) {
    /**
     * Chuẩn hóa mã size kế hoạch (hỗ trợ chuyển đổi mã cũ TU04->TU0425, TU06->TU0604...)
     */
    function normalizePlanPipeSize($rawSize) {
        $s = strtoupper(trim((string)$rawSize));
        $legacyMap = [
            'TU04' => 'TU0425',
            'TU06' => 'TU0604',
            'TU08' => 'TU0805',
            'TU10' => 'TU1065',
            'TU12' => 'TU1208',
            'TU16' => 'TU1610',
        ];
        if (isset($legacyMap[$s])) {
            return $legacyMap[$s];
        }
        // Nếu truyền vào nguyên mã sản phẩm, bóc tách theo quy tắc
        if (strlen($s) > 6) {
            $parsed = calculateExtrusionPipeSize($s);
            if ($parsed !== 'OTHER') return $parsed;
        }
        return $s;
    }
}

if (!function_exists('syncExtrusionLogsToProductions')) {
    /**
     * Đồng bộ dữ liệu từ bảng chuẩn extrusion_actual_logs sang extrusion_productions
     * extrusion_actual_logs là Single Source of Truth
     */
    function syncExtrusionLogsToProductions($conn, $logIds = null) {
        if (!$conn) return false;

        $whereLog = "WHERE l.product_code NOT IN ('Product Code', '品番') AND l.production_order_code IS NOT NULL AND l.production_order_code != ''";
        if (!empty($logIds)) {
            if (is_array($logIds)) {
                $idList = implode(',', array_map('intval', $logIds));
                $whereLog .= " AND l.id IN ({$idList})";
            } else {
                $idVal = intval($logIds);
                $whereLog .= " AND l.id = {$idVal}";
            }
        }

        $sql = "
            INSERT INTO extrusion_productions (
                production_code, input_date, production_date, production_month, production_year, shift,
                employee_code, employee_name, directive_code, product_code, size_original, size_calculated,
                cost_center, stage, workshop, machine_code, mold_code, spider_code,
                finished_length, finished_weight, ng_weight, hard_weight, total_weight, total_coils,
                coils_pl7, length_pl7, coils_pl4, length_pl4, stop_time_total, run_time, cycle_time, availability_rate,
                material_code, grind_num, grind_package_code, lot_in, is_trial, material_type,
                ng_material, ng_material_lot, hdpe_material, lio_clean, ti_clean, printer_type, ink_type, standby_machines
            )
            SELECT 
                l.production_order_code, 
                l.import_date, 
                l.production_date, 
                DATE_FORMAT(l.production_date, '%Y-%m'), 
                YEAR(l.production_date), 
                l.shift,
                l.employee_code, 
                l.employee_name, 
                l.mfg_order_code, 
                l.product_code, 
                l.pipe_size, 
                l.pipe_size,
                l.cost_center, 
                COALESCE(l.process_name, 'Extrusion'), 
                'Xưởng Đùn Nhựa V61', 
                l.device_code, 
                l.mold_code, 
                l.spider_code,
                l.finished_qty_m, 
                l.finished_qty_kg, 
                l.ng_qty_kg, 
                l.hard_waste_qty_kg, 
                l.total_weight_kg, 
                (l.bobbin_pl7_3_count + l.bobbin_pl4_7_count),
                l.bobbin_pl7_3_count, 
                l.bobbin_pl7_3_meters, 
                l.bobbin_pl4_7_count, 
                l.bobbin_pl4_7_meters, 
                l.total_downtime, 
                l.total_runtime, 
                l.cycle_time, 
                l.machine_efficiency,
                l.material_code, 
                l.regrind_count, 
                l.regrind_package_code, 
                l.lot_in, 
                l.is_test, 
                l.material_type,
                l.material_ng_qty, 
                l.lot_material_ng, 
                l.hdpe_qty, 
                l.lio_clean_qty, 
                l.ti_clean_qty, 
                l.printer_type, 
                l.ink_type, 
                l.waiting_machine_count
            FROM extrusion_actual_logs l
            {$whereLog}
            ON DUPLICATE KEY UPDATE
                input_date = VALUES(input_date),
                production_date = VALUES(production_date),
                production_month = VALUES(production_month),
                production_year = VALUES(production_year),
                shift = VALUES(shift),
                employee_code = VALUES(employee_code),
                employee_name = VALUES(employee_name),
                directive_code = VALUES(directive_code),
                product_code = VALUES(product_code),
                size_original = VALUES(size_original),
                size_calculated = VALUES(size_calculated),
                cost_center = VALUES(cost_center),
                stage = VALUES(stage),
                workshop = VALUES(workshop),
                machine_code = VALUES(machine_code),
                mold_code = VALUES(mold_code),
                spider_code = VALUES(spider_code),
                finished_length = VALUES(finished_length),
                finished_weight = VALUES(finished_weight),
                ng_weight = VALUES(ng_weight),
                hard_weight = VALUES(hard_weight),
                total_weight = VALUES(total_weight),
                total_coils = VALUES(total_coils),
                coils_pl7 = VALUES(coils_pl7),
                length_pl7 = VALUES(length_pl7),
                coils_pl4 = VALUES(coils_pl4),
                length_pl4 = VALUES(length_pl4),
                stop_time_total = VALUES(stop_time_total),
                run_time = VALUES(run_time),
                cycle_time = VALUES(cycle_time),
                availability_rate = VALUES(availability_rate),
                material_code = VALUES(material_code),
                grind_num = VALUES(grind_num),
                grind_package_code = VALUES(grind_package_code),
                lot_in = VALUES(lot_in),
                is_trial = VALUES(is_trial),
                material_type = VALUES(material_type),
                ng_material = VALUES(ng_material),
                ng_material_lot = VALUES(ng_material_lot),
                hdpe_material = VALUES(hdpe_material),
                lio_clean = VALUES(lio_clean),
                ti_clean = VALUES(ti_clean),
                printer_type = VALUES(printer_type),
                ink_type = VALUES(ink_type),
                standby_machines = VALUES(standby_machines)
        ";

        return $conn->query($sql);
    }
}

if (!function_exists('syncDeleteExtrusionActualToProduction')) {
    /**
     * Đồng bộ thao tác xóa từ extrusion_actual_logs sang extrusion_productions
     */
    function syncDeleteExtrusionActualToProduction($conn, $productionCodes) {
        if (!$conn || empty($productionCodes)) return false;
        if (!is_array($productionCodes)) $productionCodes = [$productionCodes];
        
        $escaped = array_map(function($c) use ($conn) {
            return "'" . $conn->real_escape_string($c) . "'";
        }, $productionCodes);

        $inList = implode(',', $escaped);
        return $conn->query("DELETE FROM extrusion_productions WHERE production_code IN ({$inList})");
    }
}
