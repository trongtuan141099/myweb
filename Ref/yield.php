<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Báo cáo Sản xuất - MES Dashboard v7</title>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <!-- <link rel="stylesheet" href="../css/dashboard1.css"> -->
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/sidebar.css">
  <link rel="stylesheet" href="../css/header.css">
  <link rel="stylesheet" href="../css/dashboard-body.css">
  <link rel="stylesheet" href="../css/footer.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
</head>
<body>
  <div class="app-container">
    <!-- 1. Ghép Sidebar -->
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-wrapper">
      <!-- 2. Ghép Header -->
      <?php 
        include '../includes/header.php'; 
      ?>

      <!-- 3. Nội dung chính của trang (Main Content) -->
      <main class="dashboard-body">
        <div class="document-page-wrapper">
        <!-- Banner Header -->
        <div class="document-hero">
            <div>
            <!-- <h2>Phân tích Tỷ lệ thành phẩm (良品率)</h2> -->
            <p>Theo dõi tỷ lệ thành phẩm và cơ cấu lỗi phế phẩm theo mã sản phẩm</p>
            </div>
            <div class="document-hero-icon">
            <!-- <span class="material-icons">donut_large</span> -->
            </div>
        </div>

        <!-- Card chứa Biểu đồ & Bảng số liệu -->
        <div class="document-card">
            <div class="section-heading-bar mb-3">
            <!-- <h4 class="section-heading">
                <span class="material-icons">bar_chart</span>Báo cáo phế phẩm & Tỷ lệ thành phẩm
            </h4> -->
            <!-- Bộ lọc mã sản phẩm / Tháng -->
            <div class="search-filter-box">
                <select class="form-select form-select-sm" style="width: 160px;">
                <option value="thang8">Tháng 08/2026</option>
                <option value="thang7">Tháng 07/2026</option>
                </select>
            </div>
            </div>

            <!-- 1. Vùng vẽ Biểu đồ Cột Chồng -->
            <div style="position: relative; height: 360px; width: 100%;">
            <canvas id="yieldRateChart"></canvas>
            </div>


            <!-- 2. Vùng Bảng Dữ Liệu Tổng Hợp bên dưới Biểu đồ -->
            <!-- <div class="table-container-custom mt-3">
            <table class="table table-sm table-bordered align-middle document-table text-center" id="yieldDataTable">
                <thead>
                <tr style="background-color: #f1f5f9;">
                    <th style="text-align: left; width: 160px;">Mã sản phẩm</th>
                    <th>TU0425</th>
                    <th>TU0604</th>
                    <th>TU0805</th>
                    <th>TU1065</th>
                    <th>TU1208</th>
                    <th>TU1610</th>
                    <th>TIUB01</th>
                    <th>TIUB05</th>
                    <th>TIUB07</th>
                    <th>TIUB11</th>
                    <th>TIUB13</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td style="text-align: left;"><span style="color:#2563eb;">■</span> その他異常(A5)</td>
                    <td>0.2%</td><td>0.1%</td><td>0.1%</td><td>0.0%</td><td>0.0%</td><td>0.0%</td><td>0.0%</td><td>-</td><td>0.1%</td><td>0.0%</td><td>-</td>
                </tr>
                <tr>
                    <td style="text-align: left;"><span style="color:#38bdf8;">■</span> 偏平異常(A4)</td>
                    <td>0.0%</td><td>0.0%</td><td>0.0%</td><td>0.0%</td><td>0.0%</td><td>0.0%</td><td>0.0%</td><td>-</td><td>0.0%</td><td>0.0%</td><td>-</td>
                </tr>
                <tr>
                    <td style="text-align: left;"><span style="color:#f59e0b;">■</span> 下限異常(A3)</td>
                    <td>0.9%</td><td>0.5%</td><td>0.4%</td><td>0.1%</td><td>0.1%</td><td>0.0%</td><td>0.3%</td><td>-</td><td>0.3%</td><td>0.5%</td><td>-</td>
                </tr>
                <tr>
                    <td style="text-align: left;"><span style="color:#94a3b8;">■</span> 上限異常(A2)</td>
                    <td>0.3%</td><td>0.9%</td><td>1.4%</td><td>1.1%</td><td>1.1%</td><td>1.7%</td><td>0.0%</td><td>-</td><td>4.2%</td><td>0.9%</td><td>-</td>
                </tr>
                <tr>
                    <td style="text-align: left;"><span style="color:#ea580c;">■</span> 外観(A1)</td>
                    <td>0.4%</td><td>0.8%</td><td>1.4%</td><td>0.8%</td><td>0.6%</td><td>0.2%</td><td>0.3%</td><td>-</td><td>0.0%</td><td>0.3%</td><td>-</td>
                </tr>
                <tr style="font-weight: bold; background-color: #f8fafc;">
                    <td style="text-align: left;"><span style="color:#3b82f6;">■</span> 良品率 (Thành phẩm)</td>
                    <td class="text-primary">98.3%</td>
                    <td class="text-primary">97.7%</td>
                    <td class="text-primary">96.6%</td>
                    <td class="text-primary">97.9%</td>
                    <td class="text-primary">98.2%</td>
                    <td class="text-primary">98.1%</td>
                    <td class="text-primary">99.3%</td>
                    <td>-</td>
                    <td class="text-primary">95.1%</td>
                    <td class="text-primary">98.2%</td>
                    <td>-</td>
                </tr>
                </tbody>
            </table>
            </div> -->
        <!-- Vùng Bảng Dữ Liệu Tổng Hợp Nâng Cấp -->
            <div class="table-container-custom mt-3">
            <table class="table table-sm yield-table-custom align-middle text-center" id="yieldDataTable">
                <thead>
                <tr>
                    <th style="text-align: left; width: 220px; padding-left: 16px;">Mã sản phẩm</th>
                    <th>TU0425</th>
                    <th>TU0604</th>
                    <th>TU0805</th>
                    <th>TU1065</th>
                    <th>TU1208</th>
                    <th>TU1610</th>
                    <th>TIUB01</th>
                    <th>TIUB05</th>
                    <th>TIUB07</th>
                    <th>TIUB11</th>
                    <th>TIUB13</th>
                </tr>
                </thead>
                <tbody>
                <!-- Lỗi A5 -->
                <tr>
                    <td style="text-align: left; padding-left: 16px;">
                    <div class="indicator-box">
                        <span class="dot-symbol" style="background-color: #22c55e;"></span>
                        <span>その他異常(A5)</span>
                    </div>
                    </td>
                    <td>0.2%</td><td>0.1%</td><td>0.1%</td><td>0.0%</td><td>0.0%</td><td>0.0%</td><td>0.0%</td>
                    <td class="text-muted">-</td><td>0.1%</td><td>0.0%</td><td class="text-muted">-</td>
                </tr>

                <!-- Lỗi A4 -->
                <tr>
                    <td style="text-align: left; padding-left: 16px;">
                    <div class="indicator-box">
                        <span class="dot-symbol" style="background-color: #38bdf8;"></span>
                        <span>偏平異常(A4)</span>
                    </div>
                    </td>
                    <td>0.0%</td><td>0.0%</td><td>0.0%</td><td>0.0%</td><td>0.0%</td><td>0.0%</td><td>0.0%</td>
                    <td class="text-muted">-</td><td>0.0%</td><td>0.0%</td><td class="text-muted">-</td>
                </tr>

                <!-- Lỗi A3 -->
                <tr>
                    <td style="text-align: left; padding-left: 16px;">
                    <div class="indicator-box">
                        <span class="dot-symbol" style="background-color: #f59e0b;"></span>
                        <span>下限異常(A3)</span>
                    </div>
                    </td>
                    <td>0.9%</td><td>0.5%</td><td>0.4%</td><td>0.1%</td><td>0.1%</td><td>0.0%</td><td>0.3%</td>
                    <td class="text-muted">-</td><td>0.3%</td><td>0.5%</td><td class="text-muted">-</td>
                </tr>

                <!-- Lỗi A2 -->
                <tr>
                    <td style="text-align: left; padding-left: 16px;">
                    <div class="indicator-box">
                        <span class="dot-symbol" style="background-color: #94a3b8;"></span>
                        <span>上限異常(A2)</span>
                    </div>
                    </td>
                    <td>0.3%</td><td>0.9%</td><td>1.4%</td><td>1.1%</td><td>1.1%</td><td>1.7%</td><td>0.0%</td>
                    <td class="text-muted">-</td><td>4.2%</td><td>0.9%</td><td class="text-muted">-</td>
                </tr>

                <!-- Lỗi A1 -->
                <tr>
                    <td style="text-align: left; padding-left: 16px;">
                    <div class="indicator-box">
                        <span class="dot-symbol" style="background-color: #ea580c;"></span>
                        <span>外観(A1)</span>
                    </div>
                    </td>
                    <td>0.4%</td><td>0.8%</td><td>1.4%</td><td>0.8%</td><td>0.6%</td><td>0.2%</td><td>0.3%</td>
                    <td class="text-muted">-</td><td>0.0%</td><td>0.3%</td><td class="text-muted">-</td>
                </tr>

                <!-- DÒNG TỔNG NỔI BẬT: THÀNH PHẨM (良品率) -->
                <tr class="row-highlight-yield">
                    <td style="text-align: left; padding-left: 16px;">
                    <div class="indicator-box">
                        <span class="dot-symbol" style="background-color: #3b82f6;"></span>
                        <span>良品率 (Thành phẩm)</span>
                    </div>
                    </td>
                    <td>98.3%</td>
                    <td>97.7%</td>
                    <td>96.6%</td>
                    <td>97.9%</td>
                    <td>98.2%</td>
                    <td>98.1%</td>
                    <td>99.3%</td>
                    <td class="text-muted">-</td>
                    <td>95.1%</td>
                    <td>98.2%</td>
                    <td class="text-muted">-</td>
                </tr>
                </tbody>
            </table>
            </div>
        
        
        
        </div>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function () {
            const ctx = document.getElementById('yieldRateChart');
            if (!ctx) return;

            // Danh sách các Mã Sản Phẩm
            const labels = ['TU0425', 'TU0604', 'TU0805', 'TU1065', 'TU1208', 'TU1610', 'TIUB01', 'TIUB05', 'TIUB07', 'TIUB11', 'TIUB13'];

            // Dữ liệu % các nhóm phế phẩm & thành phẩm (Null đại diện cho mã không có dữ liệu)
            const dataGood      = [98.3, 97.7, 96.6, 97.9, 98.2, 98.1, 99.3, null, 95.1, 98.2, null];
            const dataA1_Look   = [0.4,  0.8,  1.4,  0.8,  0.6,  0.2,  0.3,  null, 0.0,  0.3,  null];
            const dataA2_Upper  = [0.3,  0.9,  1.4,  1.1,  1.1,  1.7,  0.0,  null, 4.2,  0.9,  null];
            const dataA3_Lower  = [0.9,  0.5,  0.4,  0.1,  0.1,  0.0,  0.3,  null, 0.3,  0.5,  null];
            const dataA4_Flat   = [0.0,  0.0,  0.0,  0.0,  0.0,  0.0,  0.0,  null, 0.0,  0.0,  null];
            const dataA5_Other  = [0.2,  0.1,  0.1,  0.0,  0.0,  0.0,  0.0,  null, 0.1,  0.0,  null];

            Chart.register(ChartDataLabels);

            new Chart(ctx, {
                type: 'bar',
                data: {
                labels: labels,
                datasets: [
                    {
                    label: '良品率 (Tỷ lệ thành phẩm)',
                    data: dataGood,
                    backgroundColor: '#3b82f6', // Xanh dương
                    datalabels: {
                        color: '#ffffff',
                        font: { weight: 'bold', size: 10 },
                        formatter: v => (v ? v + '%' : '')
                    }
                    },
                    {
                    label: '外観(A1)',
                    data: dataA1_Look,
                    backgroundColor: '#ea580c', // Cam
                    datalabels: {
                        color: '#ffffff',
                        font: { size: 9 },
                        formatter: v => (v && v >= 0.3 ? v + '%' : '')
                    }
                    },
                    {
                    label: '上限異常(A2)',
                    data: dataA2_Upper,
                    backgroundColor: '#94a3b8', // Xám
                    datalabels: {
                        color: '#334155',
                        font: { size: 9 },
                        formatter: v => (v && v >= 0.3 ? v + '%' : '')
                    }
                    },
                    {
                    label: '下限異常(A3)',
                    data: dataA3_Lower,
                    backgroundColor: '#f59e0b', // Vàng
                    datalabels: {
                        color: '#ffffff',
                        font: { size: 9 },
                        formatter: v => (v && v >= 0.3 ? v + '%' : '')
                    }
                    },
                    {
                    label: '偏平異常(A4)',
                    data: dataA4_Flat,
                    backgroundColor: '#38bdf8', // Xanh lam nhạt
                    datalabels: { display: false }
                    },
                    {
                    label: 'その他異常(A5)',
                    data: dataA5_Other,
                    backgroundColor: '#22c55e', // Xanh lá
                    datalabels: { display: false }
                    }
                ]
                },
                options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }, // Ẩn Legend mặc định vì đã có bảng chi tiết bên dưới
                    tooltip: {
                    callbacks: {
                        label: (ctx) => `${ctx.dataset.label}: ${ctx.raw}%`
                    }
                    }
                },
                scales: {
                    x: {
                    stacked: true,
                    grid: { display: false }
                    },
                    y: {
                    stacked: true,
                    min: 82.0,
                    max: 100.0,
                    ticks: {
                        stepSize: 3.0,
                        callback: v => v.toFixed(1) + '%'
                    },
                    grid: { color: '#e2e8f0' }
                    }
                }
                }
            });
            });
        </script>

        <style>
            /* ==========================================
            CSS TỐI ƯU BẢNG TỶ LỆ THÀNH PHẨM (YIELD TABLE)
            ========================================== */
            .table-container-custom {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow-x: auto;
            background-color: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            }

            .yield-table-custom {
            width: 100%;
            border-collapse: collapse !important;
            font-size: 12px;
            color: #1e293b;
            margin: 0;
            }

            /* Header bảng */
            .yield-table-custom thead th {
            background-color: #f8fafc !important;
            color: #475569;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            padding: 10px 12px;
            border-bottom: 2px solid #cbd5e1 !important;
            white-space: nowrap;
            }

            /* Căn chỉnh các hàng */
            .yield-table-custom tbody td {
            padding: 8px 12px;
            border-bottom: 1px solid #f1f5f9;
            font-variant-numeric: tabular-nums;
            vertical-align: middle;
            }

            .yield-table-custom tbody tr:hover {
            background-color: #f8fafc;
            }

            /* Chú thích chấm màu */
            .indicator-box {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            }
            .dot-symbol {
            width: 9px;
            height: 9px;
            border-radius: 2px;
            display: inline-block;
            flex-shrink: 0;
            }

            /* Nổi bật dòng Thành phẩm (良品率) */
            .row-highlight-yield {
            background-color: #eff6ff !important;
            border-top: 2px solid #93c5fd !important;
            }
            .row-highlight-yield td {
            color: #1d4ed8 !important;
            font-weight: 700 !important;
            font-size: 12.5px;
            padding: 10px 12px;
            }

        </style>


      </main>

      <!-- 4. Ghép Footer -->
      <?php include '../includes/footer.php'; ?>
    </div> <!-- Thẻ đóng cho .main-wrapper -->
  </div> <!-- Thẻ đóng cho .app-container -->

</body>
</html>