<?php
/**
 * API Xuất / Nhập Dữ Liệu Chất Lượng (Excel & Báo Cáo PDF)
 * DX Plastic Group - Factory Management System
 */
if (!headers_sent()) {
    header('Cache-Control: no-cache, must-revalidate');
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../core/check_permission.php';
require_once __DIR__ . '/../core/quality_service.php';

global $conn;
if (!isset($conn) || !($conn instanceof mysqli)) {
    if (!defined('DB_HOST')) {
        require_once __DIR__ . '/../config/db.php';
    } else {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset("utf8mb4");
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!hasPermission(['quality.view', 'quality.manage', 'quality.investigate', 'admin'])) {
    http_response_code(403);
    die('Bạn không có quyền truy cập chức năng xuất/nhập dữ liệu chất lượng.');
}

$curUser = $_SESSION['username'] ?? ($_SESSION['user']['username'] ?? 'USER');
$action = $_GET['action'] ?? ($_POST['action'] ?? 'export_excel');

// =====================================================================
// 1. IMPORT FILE EXCEL (.xlsx / .xlsm)
// =====================================================================
if ($action === 'import_excel') {
    header('Content-Type: application/json; charset=utf-8');
    if (!hasPermission(['quality.manage', 'admin'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền import dữ liệu.']);
        exit;
    }

    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng chọn file Excel hợp lệ để tải lên!']);
        exit;
    }

    $uploadedFile = $_FILES['excel_file']['tmp_name'];
    $fileName = $_FILES['excel_file']['name'];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($ext, ['xlsx', 'xlsm', 'xls', 'csv'])) {
        echo json_encode(['success' => false, 'message' => 'Định dạng file không được hỗ trợ. Vui lòng tải lên file .xlsx, .xlsm hoặc .csv!']);
        exit;
    }

    $res = processYieldExcelImport($conn, $uploadedFile, $curUser);
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;
}

// =====================================================================
// 2. XUẤT DỮ LIỆU EXCEL / CSV (KHỚP CHÍNH XÁC SHEET データ FILE MẪU)
// =====================================================================
if ($action === 'export_excel') {
    $format = strtolower(trim($_GET['format'] ?? 'xlsx'));
    if (!in_array($format, ['xlsx', 'csv'])) $format = 'xlsx';

    $filters = [
        'date_type'         => trim($_GET['date_type'] ?? 'komaki'),
        'period_mode'       => trim($_GET['period_mode'] ?? 'day'),
        'date_from'         => trim($_GET['date_from'] ?? ''),
        'date_to'           => trim($_GET['date_to'] ?? ''),
        'year'              => intval($_GET['year'] ?? date('Y')),
        'month'             => intval($_GET['month'] ?? 0),
        'extrusion_machine' => trim($_GET['extrusion_machine'] ?? ''),
        'komaki_machine'    => trim($_GET['komaki_machine'] ?? ''),
        'size'              => trim($_GET['size'] ?? ''),
        'material_type'     => trim($_GET['material_type'] ?? ''),
        'material_group'    => trim($_GET['material_group'] ?? ''),
        'quality_status'    => trim($_GET['quality_status'] ?? ''),
        'search'            => trim($_GET['search'] ?? '')
    ];

    list($where, $dateCol) = buildQualityWhereClause($conn, $filters);

    $sql = "
        SELECT r.* FROM quality_yield_records r
        {$where}
        ORDER BY {$dateCol} DESC, r.id DESC
    ";
    $result = $conn->query($sql);
    $records = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
    }

    $timestamp = date('Ymd_His');

    // =========================================================================
    // XUẤT FILE .XLSX (CHUẨN SHEET データ TRONG 26年09月生産進捗(TU).xlsm)
    // =========================================================================
    if ($format === 'xlsx') {
        $filename = "26年09月生産進捗(TU)_Data_{$timestamp}.xlsx";
        $tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'yield_export_' . uniqid() . '.xlsx';
        
        $ok = createQualityXlsxExport($tmpFile, $records, 'データ');
        if ($ok && file_exists($tmpFile)) {
            if (!headers_sent()) {
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Length: ' . filesize($tmpFile));
                header('Cache-Control: max-age=0');
                header('Pragma: public');
            }
            readfile($tmpFile);
            @unlink($tmpFile);
            exit;
        }
        // Fallback sang CSV nếu lỗi tạo xlsx
        $format = 'csv';
    }

    // =========================================================================
    // XUẤT FILE .CSV (UTF-8 BOM MỞ TRỰC TIẾP TRÊN EXCEL)
    // =========================================================================
    $filename = "26年09月生産進捗(TU)_Data_{$timestamp}.csv";

    if (!headers_sent()) {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    // Ghi UTF-8 BOM
    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');

    // Dòng 1: Tiêu đề
    fputcsv($output, ['BẢNG THEO DÕI SẢN XUẤT VÀ TỈ LỆ THÀNH PHẨM (26年09月生産進捗 - 良品率)']);
    // Dòng 2: Trống
    fputcsv($output, []);
    // Dòng 3: Khớp chính xác các cột sheet データ từ A đến BA
    fputcsv($output, [
        '小巻日', 'Máy cuộn', 'サイズ', 'LOT', '製品品番', '成形日/Ngay dun',
        '良品数', '設定数', '良品率', '不良数 No.1-5',
        '外観(A1)', 'A2-A5', 'A2 上限異常', 'A3 下限異常', 'A4 偏平異常', 'A5 その他異常',
        'こすれ Trầy', '滞留物 Vón cục', '異物 Dị Vật', 'メヤニ Dính gèn', '傷 Xước', 'ゲル Gel', 'ゆがみ Chấm trắng', '変形 Biến dạng', '縦すじ Vết lõm kéo dài', '気泡 Bọt khí', '割れ Vỡ', '圧痕転写 Vết in hằn', '印字高さ Độ cao chữ', '印字太さ Độ rộng chữ', '印字内容 Nội dung in', '文字かすれ Thiếu nét', 'MAU SAC',
        'GIỜ ĐÙN', 'CHIỀU DÀI BOBIN', 'CHIỀU DÀI CHẠY TAY', 'CA CUỘN', 'Vật liệu', 'Vật liệu lần', 'Column1',
        'Tieu chuan', 'Máy'
    ]);

    foreach ($records as $r) {
        $sub = !empty($r['defect_details']) ? json_decode($r['defect_details'], true) : [];
        $sumA2A5 = intval($r['defect_a2'] ?? 0) + intval($r['defect_a3'] ?? 0) + intval($r['defect_a4'] ?? 0) + intval($r['defect_a5'] ?? 0);
        fputcsv($output, [
            $r['komaki_date'] ?? '',
            $r['komaki_machine'] ?? '',
            $r['size'] ?? '',
            $r['lot_no'] ?? '',
            $r['product_code'] ?? '',
            $r['extrusion_date'] ?? '',
            intval($r['good_qty'] ?? 0),
            intval($r['total_qty'] ?? 0),
            round(floatval($r['yield_rate'] ?? 0) / 100, 4),
            intval($r['defect_qty'] ?? 0),
            intval($r['defect_a1'] ?? 0),
            $sumA2A5,
            intval($r['defect_a2'] ?? 0),
            intval($r['defect_a3'] ?? 0),
            intval($r['defect_a4'] ?? 0),
            intval($r['defect_a5'] ?? 0),
            intval($sub['tray'] ?? 0),
            intval($sub['von_cuc'] ?? 0),
            intval($sub['di_vat'] ?? 0),
            intval($sub['dinh_gen'] ?? 0),
            intval($sub['xuoc'] ?? 0),
            intval($sub['gel'] ?? 0),
            intval($sub['cham_trang'] ?? 0),
            intval($sub['bien_dang'] ?? 0),
            intval($sub['vet_lom'] ?? 0),
            intval($sub['bot_khi'] ?? 0),
            intval($sub['vo'] ?? 0),
            intval($sub['in_han'] ?? 0),
            intval($sub['cao_chu'] ?? 0),
            intval($sub['rong_chu'] ?? 0),
            intval($sub['nd_in'] ?? 0),
            intval($sub['thieu_net'] ?? 0),
            intval($sub['mau_sac'] ?? 0),
            $r['bobbin_time'] ?? '00:00',
            intval($r['bobbin_length'] ?? 0),
            0,
            str_replace('Ca ', '', $r['shift'] ?? '1'),
            0,
            substr($r['lot_no'] ?? '', 1, 1),
            $r['material_type'] ?? 'zin',
            floatval($r['benchmark_rate'] ?? 98.00),
            $r['extrusion_machine'] ?? 'PL08'
        ]);
    }

    fclose($output);
    exit;
}

// =====================================================================
// 3. XUẤT BÁO CÁO PDF / BẢN IN A4 CHUẨN CÔNG NGHIỆP
// =====================================================================
if ($action === 'export_pdf' || $action === 'render_pdf_html') {
    $filters = [
        'date_type'         => trim($_GET['date_type'] ?? 'komaki'),
        'period_mode'       => trim($_GET['period_mode'] ?? 'day'),
        'date_from'         => trim($_GET['date_from'] ?? ''),
        'date_to'           => trim($_GET['date_to'] ?? ''),
        'year'              => intval($_GET['year'] ?? date('Y')),
        'month'             => intval($_GET['month'] ?? date('m')),
        'extrusion_machine' => trim($_GET['extrusion_machine'] ?? ''),
        'komaki_machine'    => trim($_GET['komaki_machine'] ?? ''),
        'size'              => trim($_GET['size'] ?? ''),
        'material_type'     => trim($_GET['material_type'] ?? ''),
        'quality_status'    => trim($_GET['quality_status'] ?? ''),
        'search'            => trim($_GET['search'] ?? '')
    ];

    $stats = getYieldDashboardStats($conn, $filters);
    $kpi = $stats['kpi'];
    $pareto = $stats['pareto_defects'];
    $matrix = $stats['matrix'];
    $extData = $stats['extrusion_comparison'];

    // Lấy top 15 lô bất thường có tỉ lệ thấp nhất
    list($where, $dateCol) = buildQualityWhereClause($conn, $filters);
    $sqlAbnormal = "
        SELECT * FROM quality_yield_records r
        {$where}
        ORDER BY r.yield_rate ASC, r.defect_qty DESC
        LIMIT 15
    ";
    $resAb = $conn->query($sqlAbnormal);
    $abnormalList = [];
    if ($resAb) while ($r = $resAb->fetch_assoc()) $abnormalList[] = $r;

    // Lấy danh sách phiếu điều tra liên quan
    $invList = getQualityInvestigationsList($conn, ['size' => $filters['size']]);
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
      <meta charset="UTF-8">
      <title>BÁO CÁO THEO DÕI TỈ LỆ THÀNH PHẨM (良品率) - DX PLASTIC GROUP</title>
      <style>
        @page {
          size: A4 landscape;
          margin: 8mm 10mm 10mm 10mm;
        }
        body {
          font-family: 'Times New Roman', 'Arial', sans-serif;
          color: #0f172a;
          background: #ffffff;
          margin: 0;
          padding: 10px;
          font-size: 11pt;
          line-height: 1.35;
        }
        .report-header {
          display: flex;
          justify-content: space-between;
          align-items: flex-start;
          border-bottom: 2px solid #0f172a;
          padding-bottom: 8px;
          margin-bottom: 12px;
        }
        .company-brand h2 { margin: 0; font-size: 15pt; font-weight: bold; color: #1e3a8a; text-transform: uppercase; }
        .company-brand p { margin: 2px 0 0; font-size: 9pt; color: #475569; }
        .report-meta { text-align: right; font-size: 8.5pt; color: #475569; }
        .report-title-box { text-align: center; margin: 10px 0 14px; }
        .report-title-box h1 { margin: 0; font-size: 16pt; font-weight: 800; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px; }
        .report-title-box .sub-title { font-size: 10pt; color: #64748b; margin-top: 3px; font-style: italic; }

        /* KPI Cards in Print */
        .kpi-row {
          display: grid;
          grid-template-columns: repeat(5, 1fr);
          gap: 10px;
          margin-bottom: 14px;
        }
        .kpi-box {
          border: 1px solid #cbd5e1;
          border-radius: 4px;
          padding: 8px 10px;
          text-align: center;
          background: #f8fafc;
        }
        .kpi-box .kpi-val { font-size: 14pt; font-weight: bold; color: #1e3a8a; font-family: monospace; }
        .kpi-box .kpi-lbl { font-size: 8pt; color: #475569; text-transform: uppercase; font-weight: 600; margin-top: 2px; }
        .kpi-box.success .kpi-val { color: #15803d; }
        .kpi-box.danger .kpi-val { color: #b91c1c; }

        /* Tables */
        .report-section-title {
          font-size: 11pt;
          font-weight: bold;
          color: #1e3a8a;
          border-left: 4px solid #1e3a8a;
          padding-left: 6px;
          margin: 12px 0 6px 0;
          text-transform: uppercase;
        }
        table.report-table {
          width: 100%;
          border-collapse: collapse;
          font-size: 8.5pt;
          margin-bottom: 12px;
        }
        table.report-table th, table.report-table td {
          border: 1px solid #94a3b8;
          padding: 4px 6px;
        }
        table.report-table th {
          background-color: #f1f5f9;
          font-weight: bold;
          text-align: center;
          color: #0f172a;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .bg-pass { background-color: rgba(34, 197, 94, 0.12); color: #166534; font-weight: bold; }
        .bg-warn { background-color: rgba(245, 158, 11, 0.15); color: #92400e; font-weight: bold; }
        .bg-danger { background-color: rgba(239, 68, 68, 0.15); color: #991b1b; font-weight: bold; }

        /* Signatures */
        .signatures {
          margin-top: 20px;
          display: grid;
          grid-template-columns: repeat(3, 1fr);
          text-align: center;
          page-break-inside: avoid;
        }
        .sig-box { padding: 10px; }
        .sig-title { font-weight: bold; font-size: 9.5pt; text-transform: uppercase; }
        .sig-sub { font-size: 8pt; color: #64748b; font-style: italic; margin-bottom: 45px; }

        @media print {
          .no-print { display: none !important; }
          body { padding: 0; }
        }
      </style>
    </head>
    <body>
      <!-- Thanh nút thao tác khi xem trên trình duyệt -->
      <div class="no-print" style="background: #1e293b; color: #fff; padding: 10px 16px; margin: -10px -10px 16px; display: flex; justify-content: space-between; align-items: center; border-radius: 4px;">
        <div style="font-weight: bold; font-size: 13px;">
          📄 BẢN XEM TRƯỚC BÁO CÁO PDF (KHỔ A4 LANDSCAPE - CHUẨN NHÀ MÁY)
        </div>
        <div style="display: flex; gap: 8px;">
          <button onclick="window.print()" style="background: #0284c7; color: #fff; border: none; padding: 6px 14px; border-radius: 4px; font-weight: bold; cursor: pointer;">
            🖨️ In Báo Cáo / Lưu PDF
          </button>
          <button onclick="window.close()" style="background: #475569; color: #fff; border: none; padding: 6px 14px; border-radius: 4px; cursor: pointer;">
            Đóng
          </button>
        </div>
      </div>

      <!-- Header Báo Cáo -->
      <div class="report-header">
        <div class="company-brand">
          <h2>DX PLASTIC GROUP VIỆT NAM</h2>
          <p>Nhà máy Sản xuất Ống Nhựa Công Nghiệp & Đùn Ép Chính Xác - ISO 9001:2015</p>
        </div>
        <div class="report-meta">
          <div><strong>Mã biểu mẫu:</strong> BM-QC-YIELD-02</div>
          <div><strong>Ngày in báo cáo:</strong> <?= date('d/m/Y H:i') ?></div>
          <div><strong>Người lập:</strong> <?= htmlspecialchars($curUser) ?></div>
        </div>
      </div>

      <!-- Tiêu đề -->
      <div class="report-title-box">
        <h1>BÁO CÁO THEO DÕI TỈ LỆ THÀNH PHẨM (良品率)</h1>
        <div class="sub-title">
          Kỳ theo dõi: <?= !empty($filters['date_from']) ? "Từ {$filters['date_from']} đến {$filters['date_to']}" : "Tháng {$filters['month']}/{$filters['year']}" ?> 
          | Lọc theo: <?= !empty($filters['extrusion_machine']) ? "Máy đùn {$filters['extrusion_machine']}" : "Toàn xưởng" ?>
          <?= !empty($filters['size']) ? " - Cỡ ống: {$filters['size']}" : "" ?>
        </div>
      </div>

      <!-- KPI Summary -->
      <div class="kpi-row">
        <div class="kpi-box">
          <div class="kpi-val"><?= number_format($kpi['total_produced']) ?></div>
          <div class="kpi-lbl">Tổng Sản Lượng (Cuộn)</div>
        </div>
        <div class="kpi-box success">
          <div class="kpi-val"><?= number_format($kpi['total_good']) ?></div>
          <div class="kpi-lbl">Thành Phẩm Đạt (Cuộn)</div>
        </div>
        <div class="kpi-box <?= ($kpi['yield_rate'] >= $kpi['benchmark_rate']) ? 'success' : 'danger' ?>">
          <div class="kpi-val"><?= $kpi['yield_rate'] ?>%</div>
          <div class="kpi-lbl">Tỉ Lệ Thành Phẩm (Target: <?= $kpi['benchmark_rate'] ?>%)</div>
        </div>
        <div class="kpi-box danger">
          <div class="kpi-val"><?= $kpi['defect_rate'] ?>%</div>
          <div class="kpi-lbl">Tỉ Lệ Phế Phẩm (<?= number_format($kpi['total_defect']) ?> cuộn)</div>
        </div>
        <div class="kpi-box">
          <div class="kpi-val" style="color: #d97706;"><?= $kpi['count_danger'] ?></div>
          <div class="kpi-lbl">Số Lô Bất Thường (&lt; 95%)</div>
        </div>
      </div>

      <!-- 1. Bảng phân tích nguyên nhân lỗi (Pareto) & So sánh máy -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
        <div>
          <div class="report-section-title">1. Phân Loại Nhóm Lỗi (Pareto Defect Breakdown)</div>
          <table class="report-table">
            <thead>
              <tr>
                <th>Mã</th>
                <th>Tên Nhóm Lỗi Phát Sinh</th>
                <th>Số Lỗi (Cuộn)</th>
                <th>Tỉ Lệ %</th>
                <th>Tích Lũy %</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pareto as $p): ?>
              <tr>
                <td class="text-center font-monospace fw-bold"><?= $p['code'] ?></td>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td class="text-end fw-bold"><?= number_format($p['count']) ?></td>
                <td class="text-end"><?= $p['percent'] ?>%</td>
                <td class="text-end fw-bold" style="color: #0284c7;"><?= $p['cum_percent'] ?>%</td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div>
          <div class="report-section-title">2. Hiệu Suất Tỉ Lệ Thành Phẩm Theo Máy Đùn</div>
          <table class="report-table">
            <thead>
              <tr>
                <th>Máy Đùn</th>
                <th>Tổng Số (Cuộn)</th>
                <th>Đạt (Cuộn)</th>
                <th>Tỉ Lệ Đạt %</th>
                <th>Mục Tiêu %</th>
                <th>Đánh Giá</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($extData as $e): 
                $isPass = $e['yield_rate'] >= $e['benchmark_rate'];
              ?>
              <tr>
                <td class="text-center fw-bold"><?= htmlspecialchars($e['machine']) ?></td>
                <td class="text-end"><?= number_format($e['produced']) ?></td>
                <td class="text-end"><?= number_format($e['good']) ?></td>
                <td class="text-end fw-bold <?= $isPass ? 'bg-pass' : 'bg-danger' ?>"><?= $e['yield_rate'] ?>%</td>
                <td class="text-end"><?= $e['benchmark_rate'] ?>%</td>
                <td class="text-center"><?= $isPass ? '✓ Đạt' : '⚠️ Dưới chuẩn' ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- 2. Danh Sách Lô Bất Thường Có Tỉ Lệ Thành Phẩm Thấp Cần Chú Ý -->
      <div class="report-section-title">3. Danh Sách Các Lô Sản Phẩm Có Tỉ Lệ Thấp (Cần Theo Dõi & Đối Ứng)</div>
      <table class="report-table">
        <thead>
          <tr>
            <th style="width: 30px;">STT</th>
            <th>Ngày Cuộn</th>
            <th>Máy Cuộn</th>
            <th>Kích Cỡ</th>
            <th>Mã LOT</th>
            <th>Mã Sản Phẩm</th>
            <th>Máy Đùn</th>
            <th>Ngày Đùn</th>
            <th>Sản Lượng</th>
            <th>Đạt</th>
            <th>Tỉ Lệ TP %</th>
            <th>Lỗi A1</th>
            <th>Lỗi A2</th>
            <th>Lỗi A3</th>
            <th>Lỗi A4</th>
            <th>Lỗi A5</th>
            <th>Tình Trạng</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($abnormalList)): ?>
          <tr><td colspan="17" class="text-center py-2 text-muted">Không phát sinh lô hàng bất thường nào trong kỳ báo cáo.</td></tr>
          <?php else: ?>
            <?php foreach ($abnormalList as $idx => $ab): 
              $rRate = floatval($ab['yield_rate']);
              $cl = ($rRate >= $ab['benchmark_rate']) ? 'bg-pass' : (($rRate >= 95.0) ? 'bg-warn' : 'bg-danger');
            ?>
            <tr>
              <td class="text-center"><?= $idx + 1 ?></td>
              <td class="text-center"><?= $ab['komaki_date'] ?></td>
              <td class="text-center"><?= $ab['komaki_machine'] ?></td>
              <td class="text-center fw-bold"><?= $ab['size'] ?></td>
              <td class="text-center font-monospace fw-bold"><?= $ab['lot_no'] ?></td>
              <td><?= htmlspecialchars($ab['product_code']) ?></td>
              <td class="text-center"><?= $ab['extrusion_machine'] ?></td>
              <td class="text-center"><?= $ab['extrusion_date'] ?? '-' ?></td>
              <td class="text-end"><?= $ab['total_qty'] ?></td>
              <td class="text-end"><?= $ab['good_qty'] ?></td>
              <td class="text-end fw-bold <?= $cl ?>"><?= $ab['yield_rate'] ?>%</td>
              <td class="text-end"><?= $ab['defect_a1'] ?></td>
              <td class="text-end"><?= $ab['defect_a2'] ?></td>
              <td class="text-end"><?= $ab['defect_a3'] ?></td>
              <td class="text-end"><?= $ab['defect_a4'] ?></td>
              <td class="text-end"><?= $ab['defect_a5'] ?></td>
              <td class="text-center small"><?= ($rRate < 95) ? '🔴 Lỗi lớn' : '🟡 Cảnh báo' ?></td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>

      <!-- 3. Phiếu Điều Tra & Biện Pháp Đối Ứng -->
      <?php if (!empty($invList)): ?>
      <div class="report-section-title">4. Tiến Độ Xử Lý Phiếu Yêu Cầu Điều Tra & Biện Pháp Đối Sách (Đối Ứng)</div>
      <table class="report-table">
        <thead>
          <tr>
            <th style="width: 80px;">Mã Phiếu</th>
            <th>Ngày Y/C</th>
            <th>Size / Máy</th>
            <th>Mã LOT</th>
            <th>TLTP %</th>
            <th>Tình Trạng Phát Sinh</th>
            <th>Nguyên Nhân Điều Tra</th>
            <th>Biện Pháp Đối Sách</th>
            <th>Người Đối Ứng</th>
            <th>Kết Quả</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (array_slice($invList, 0, 8) as $inv): ?>
          <tr>
            <td class="text-center font-monospace fw-bold"><?= $inv['investigation_code'] ?></td>
            <td class="text-center"><?= $inv['investigation_date'] ?></td>
            <td class="text-center fw-bold"><?= $inv['size'] ?> (<?= $inv['extrusion_machine'] ?>)</td>
            <td class="text-center font-monospace"><?= $inv['lot_no'] ?></td>
            <td class="text-end fw-bold text-danger"><?= $inv['yield_rate'] ?>%</td>
            <td><small><?= htmlspecialchars($inv['status_description']) ?></small></td>
            <td><small><?= htmlspecialchars($inv['root_cause'] ?: '-') ?></small></td>
            <td><small><?= htmlspecialchars($inv['countermeasure'] ?: '-') ?></small></td>
            <td class="text-center"><?= htmlspecialchars($inv['assigned_to'] ?: '-') ?></td>
            <td class="text-center fw-bold"><?= htmlspecialchars($inv['result_status']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>

      <!-- Chữ ký xác nhận -->
      <div class="signatures">
        <div class="sig-box">
          <div class="sig-title">Người Lập Báo Cáo</div>
          <div class="sig-sub">(Ký & ghi rõ họ tên)</div>
          <div style="font-weight: bold;"><?= htmlspecialchars($curUser) ?></div>
        </div>
        <div class="sig-box">
          <div class="sig-title">Trưởng Bộ Phận QC</div>
          <div class="sig-sub">(Ký & ghi rõ họ tên)</div>
          <div style="font-weight: bold;">...................................................</div>
        </div>
        <div class="sig-box">
          <div class="sig-title">Giám Đốc / Quản Lý Xưởng</div>
          <div class="sig-sub">(Ký & duyệt)</div>
          <div style="font-weight: bold;">...................................................</div>
        </div>
      </div>
    </body>
    </html>
    <?php
    exit;
}
