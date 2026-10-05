<?php
/**
 * ShiftMasterService: Dịch vụ Quản lý & Xác định Ca Làm Việc Tự Động (Shift Master Engine)
 * DX Plastic Group - Overtime Management System
 */

require_once __DIR__ . '/../config/db.php';

class ShiftMasterService {
    private static $cachedShifts = null;

    /**
     * Nạp toàn bộ cấu hình ca làm việc từ bảng shift_master
     * Tự động cache trong request để tối ưu hiệu năng
     */
    public static function getActiveShifts($conn = null, $forceReload = false) {
        if (self::$cachedShifts !== null && !$forceReload) {
            return self::$cachedShifts;
        }

        global $conn;
        $db = $conn;
        if (!isset($db) || !($db instanceof mysqli)) {
            if (!defined('DB_HOST')) {
                require_once __DIR__ . '/../config/db.php';
            }
            $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $db->set_charset("utf8mb4");
        }

        $shifts = [];
        $check = $db->query("SHOW TABLES LIKE 'shift_master'");
        if ($check && $check->num_rows > 0) {
            $sql = "SELECT * FROM shift_master WHERE active_flag = 1 ORDER BY priority ASC, id ASC";
            $res = $db->query($sql);
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $shifts[] = $row;
                }
            }
        }

        // Dự phòng (fallback) cấu hình tiêu chuẩn nếu bảng chưa có dữ liệu
        if (empty($shifts)) {
            $shifts = [
                [
                    'shift_code' => '1',
                    'shift_name' => 'Ca 1',
                    'standard_start_time' => '06:00:00',
                    'standard_end_time' => '14:00:00',
                    'ot_before_hours' => 2.00,
                    'ot_after_hours' => 2.00,
                    'detect_start_time' => '04:00:00',
                    'detect_end_time' => '16:00:00',
                    'priority' => 10,
                    'active_flag' => 1,
                    'is_cross_day' => 0
                ],
                [
                    'shift_code' => 'HC',
                    'shift_name' => 'Hành Chính',
                    'standard_start_time' => '07:45:00',
                    'standard_end_time' => '16:30:00',
                    'ot_before_hours' => 2.00,
                    'ot_after_hours' => 4.00,
                    'detect_start_time' => '05:45:00',
                    'detect_end_time' => '20:00:00',
                    'priority' => 15,
                    'active_flag' => 1,
                    'is_cross_day' => 0
                ],
                [
                    'shift_code' => '2',
                    'shift_name' => 'Ca 2',
                    'standard_start_time' => '14:00:00',
                    'standard_end_time' => '22:00:00',
                    'ot_before_hours' => 2.00,
                    'ot_after_hours' => 2.00,
                    'detect_start_time' => '12:00:00',
                    'detect_end_time' => '24:00:00',
                    'priority' => 20,
                    'active_flag' => 1,
                    'is_cross_day' => 0
                ],
                [
                    'shift_code' => '3',
                    'shift_name' => 'Ca 3',
                    'standard_start_time' => '22:00:00',
                    'standard_end_time' => '06:00:00',
                    'ot_before_hours' => 2.00,
                    'ot_after_hours' => 2.00,
                    'detect_start_time' => '20:00:00',
                    'detect_end_time' => '08:00:00',
                    'priority' => 30,
                    'active_flag' => 1,
                    'is_cross_day' => 1
                ]
            ];
        }

        self::$cachedShifts = $shifts;
        return self::$cachedShifts;
    }

    /**
     * Chuyển đổi chuỗi giờ HH:mm:ss thành số phút từ 0:00 (0..1440)
     */
    public static function timeToMinutes($timeStr) {
        if (empty($timeStr)) return 0;
        if (strlen($timeStr) > 8 && strpos($timeStr, ' ') !== false) {
            $parts = explode(' ', $timeStr);
            $timeStr = $parts[1];
        }
        $p = explode(':', $timeStr);
        $h = intval($p[0] ?? 0);
        $m = intval($p[1] ?? 0);
        return $h * 60 + $m;
    }

    /**
     * Thuật toán tự động xác định ca làm việc theo cấu hình Shift_Master
     * 
     * @param string $startTimeStr Thời gian bắt đầu OT (VD: '2026-09-18 12:30:00' hoặc '12:30:00')
     * @param string $endTimeStr   Thời gian kết thúc OT (VD: '2026-09-18 14:00:00' hoặc '14:00:00')
     * @param array  $context      Thông tin ngữ cảnh (work_group, cost_center, job_level, reason...)
     * @param mysqli $conn         Database connection
     * @return string Shift code ('1', '2', '3', 'HC')
     */
    public static function determineShift($startTimeStr, $endTimeStr, $context = [], $conn = null) {
        $shifts = self::getActiveShifts($conn);

        $sMin = self::timeToMinutes($startTimeStr);
        $eMin = self::timeToMinutes($endTimeStr);
        $crossesMidnight = ($eMin <= $sMin);

        $bestShift = null;
        $bestScore = -999999;

        foreach ($shifts as $shift) {
            $code = trim($shift['shift_code'] ?? $shift['ShiftCode']);
            $stdStart = self::timeToMinutes($shift['standard_start_time'] ?? $shift['StandardStartTime']);
            $stdEnd = self::timeToMinutes($shift['standard_end_time'] ?? $shift['StandardEndTime']);
            $detStart = self::timeToMinutes($shift['detect_start_time'] ?? $shift['DetectStartTime']);
            
            $detEndRaw = trim($shift['detect_end_time'] ?? $shift['DetectEndTime']);
            $detEnd = ($detEndRaw === '24:00:00' || $detEndRaw === '24:00') ? 1440 : self::timeToMinutes($detEndRaw);
            
            $isCrossDay = intval($shift['is_cross_day'] ?? 0) === 1 || ($stdStart > $stdEnd) || ($detStart > $detEnd);

            $score = 0;
            $inWindow = false;

            if ($isCrossDay) {
                // Ca qua đêm (VD Ca 3: standard 22:00 ~ 06:00, detect 20:00 ~ 08:00)
                if ($crossesMidnight) {
                    if ($sMin >= $detStart && $eMin <= $detEnd) {
                        $inWindow = true;
                    } elseif ($sMin >= 1080) { // Bắt đầu từ 18:00 trở đi xuyên đêm
                        $inWindow = true;
                    }
                } else {
                    // Tăng ca không xuyên đêm (VD đi sớm trước ca 3: 20:30 ~ 22:00, 20:30 ~ 22:30)
                    if ($sMin >= $detStart && $sMin <= $stdStart + 30) {
                        $inWindow = true;
                    } elseif ($sMin <= $detEnd && $eMin <= $detEnd) {
                        $inWindow = true;
                    }
                }
            } else {
                // Ca trong ngày (Ca 1, Ca 2, HC)
                if (!$crossesMidnight) {
                    if ($sMin >= $detStart && $eMin <= $detEnd) {
                        // Ca 2 (14:00 ~ 22:00): Tăng ca trước ca chỉ từ 12:00 ~ 14:00.
                        // Nếu bắt đầu >= 20:00 là tăng ca trước ca của Ca 3, không thuộc Ca 2
                        if ($code === '2' && $sMin >= 1200) {
                            $inWindow = false;
                        } else {
                            $inWindow = true;
                        }
                    }
                }
            }

            if (!$inWindow) {
                $score -= 1000;
            } else {
                $score += 100;

                // 1. Điểm mốc chuẩn (Anchor bonuses):
                // a. Trùng mốc bắt đầu nhận diện (VD 05:45 của HC, 04:00 của Ca 1, 12:00 của Ca 2)
                if (abs($sMin - $detStart) <= 5) {
                    $score += 90;
                }

                // b. NGUYÊN CA: Trùng mốc bắt đầu & kết thúc ca tiêu chuẩn (VD 06:00~14:00 của Ca 1, 07:45~16:30 của HC)
                if (abs($sMin - $stdStart) <= 15 && abs($eMin - $stdEnd) <= 15) {
                    $score += 300;
                }

                // c. TĂNG CA SAU CA (Post-shift OT): Bắt đầu ngay sau khi ca tiêu chuẩn kết thúc
                // VD: Ca 1 kết thúc lúc 14:00 -> OT 14:00~15:30, 14:00~16:00
                //     HC kết thúc lúc 16:30 -> OT 16:30~18:00, 16:30~19:30
                //     Ca 2 kết thúc lúc 22:00 -> OT 22:00~23:30
                if (abs($sMin - $stdEnd) <= 15) {
                    $score += 250;
                }

                // d. TĂNG CA TRƯỚC CA (Pre-shift OT): Kết thúc ngay khi ca tiêu chuẩn bắt đầu
                // VD: Ca 2 bắt đầu lúc 14:00 -> OT 12:30~14:00
                //     Ca 3 bắt đầu lúc 22:00 -> OT 20:30~22:00
                //     HC bắt đầu lúc 07:45 -> OT 05:45~07:45
                //     Ca 1 bắt đầu lúc 06:00 -> OT 04:30~06:00
                if (abs($eMin - $stdStart) <= 15) {
                    $score += 250;
                }

                // e. Gần mốc tiêu chuẩn
                if (abs($sMin - $stdStart) <= 15) {
                    $score += 60;
                }
                if (abs($eMin - $stdEnd) <= 15) {
                    $score += 60;
                }

                // 2. Độ dài giao thoa với giờ làm việc tiêu chuẩn
                if (!$isCrossDay) {
                    $overlapStart = max($sMin, $stdStart);
                    $overlapEnd = min($eMin, $stdEnd);
                    if ($overlapEnd > $overlapStart) {
                        $score += ($overlapEnd - $overlapStart) / 10;
                    }
                } else {
                    if ($crossesMidnight) {
                        $score += 50;
                    }
                }

                // 3. Ưu tiên theo Ngữ cảnh (Nhóm HC vs Nhóm Sản Xuất & Lý do)
                $reason = strtolower($context['reason'] ?? '');
                $wg = strtolower($context['work_group'] ?? '');
                $jl = strtolower($context['job_level'] ?? '');

                if ($code === 'HC') {
                    if (strpos($reason, 'hc') !== false || strpos($reason, 'hành chính') !== false) $score += 80;
                    if ($wg === 'hành chính' || $wg === 'văn phòng' || $jl === 'm1') $score += 60;
                } elseif ($code === '1') {
                    if (strpos($reason, 'ca 1') !== false || strpos($reason, 'ca1') !== false) $score += 80;
                    if (in_array($wg, ['đùn tu', 'đùn t', 'nghiền nhựa', 'shotblast', 'thiết bị'])) $score += 10;
                } elseif ($code === '2') {
                    if (strpos($reason, 'ca 2') !== false || strpos($reason, 'ca2') !== false) $score += 80;
                } elseif ($code === '3') {
                    if (strpos($reason, 'ca 3') !== false || strpos($reason, 'ca3') !== false) $score += 80;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestShift = $code;
            }
        }

        // Mặc định an toàn nếu không khớp (fallback)
        if (empty($bestShift)) {
            if ($sMin >= 240 && $sMin < 720) return '1';
            if ($sMin >= 720 && $sMin < 1200) return '2';
            return '3';
        }

        return $bestShift;
    }
}
