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
                <!-- CARD BẢNG BIỂU ĐỒ THỰC TÍCH SẢN XUẤT -->
        <div class="data-card">
            <div class="filter-bar">
                <div style="font-weight: 700; font-size: 15px; color: #173b73;">
                生産進捗（押出） - Tiến độ sản xuất đùn
                </div>
                <div style="margin-left: auto; display: flex; gap: 8px;">
                <select class="form-control" id="chart-month-select">
                    <option value="8">Tháng 8 / 8月</option>
                    <option value="9">Tháng 9 / 9月</option>
                </select>
                </div>
            </div>

            <!-- Khung chứa Canvas vẽ đồ thị -->
            <div style="position: relative; height: 380px; width: 100%;">
                <canvas id="productionChart"></canvas>
            </div>
        </div>

        <!-- CARD BIỂU ĐỒ 2: TIẾN ĐỘ SẢN XUẤT CUỘN NHỎ (小巻) -->
        <div class="data-card mt-3">
            <div class="filter-bar mb-2">
                <div style="font-weight: 700; font-size: 15px; color: #173b73;">
                生産進捗（小巻） - Tiến độ sản xuất cuộn
                </div>
            </div>

            <!-- Khung chứa Canvas vẽ biểu đồ thứ 2 -->
            <div style="position: relative; height: 380px; width: 100%;">
                <canvas id="smallRollProgressChart"></canvas>
            </div>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function () {
            const ctx = document.getElementById('productionChart');
            if (!ctx) return;

            const daysLabel = Array.from({length: 31}, (_, i) => i + 1);

            // Dữ liệu mẫu theo thực tế
            const planDaily = [0, 0, 300, 400, 380, 420, 400, 0, 0, 450, 500, 520, 500, 650, 300, 0, 500, 680, 600, 550, 500, 400, 0, 450, 500, 300, 250, 200, 0, 0, 0];
            const actualDaily = [0, 0, 280, 380, 350, 400, 380, 400, 0, 0, 350, 420, 400, 450, 500, 0, 320, 400, 550, 520, 620, 480, 0, 420, null, null, null, null, null, null, null];

            // Tính lũy kế
            let planSum = 0;
            const planCum = planDaily.map(v => (planSum += v));

            let actualSum = 0;
            const actualCum = actualDaily.map(v => (v === null ? null : (actualSum += v)));

            // Xác định vị trí điểm dữ liệu lũy tích mới nhất
            const lastActualIndex = actualCum.reduce((lastIdx, val, idx) => (val !== null ? idx : lastIdx), -1);

            // Kích hoạt Plugin DataLabels
            Chart.register(ChartDataLabels);

            new Chart(ctx, {
                type: 'bar',
                data: {
                labels: daysLabel,
                datasets: [
                    {
                    label: '生産実績/日 (Thực tích/ngày)',
                    data: actualDaily,
                    type: 'bar',
                    backgroundColor: '#f59e0b',
                    barPercentage: 0.5,
                    datalabels: { display: false },
                    order: 4
                    },
                    {
                    label: '生産計画/日 (Kế hoạch/ngày)',
                    data: planDaily,
                    type: 'line',
                    borderColor: '#2563eb',
                    borderWidth: 1.5,
                    pointRadius: 0,
                    datalabels: { display: false },
                    order: 3
                    },
                    {
                    label: '実績累積/月 (Lũy kế thực tích)',
                    data: actualCum,
                    type: 'line',
                    borderColor: '#64748b',
                    backgroundColor: '#64748b',
                    borderWidth: 2,
                    pointRadius: 3,
                    // 📌 CẤU HÌNH Ô THỂ HIỆN 8,336
                    datalabels: {
                        display: function(context) {
                        return context.dataIndex === lastActualIndex; // Chỉ hiển thị tại ngày có thực tích cuối cùng
                        },
                        align: 'top',
                        anchor: 'center',
                        offset: 8,
                        backgroundColor: '#ffffff',
                        borderColor: '#94a3b8',
                        borderWidth: 1,
                        borderRadius: 4,
                        padding: { top: 4, bottom: 4, left: 8, right: 8 },
                        color: '#334155',
                        font: { weight: 'bold', size: 12 },
                        formatter: function(value) {
                        return value ? value.toLocaleString() : '';
                        }
                    },
                    order: 2
                    },
                    {
                    label: '計画累積/月 (Lũy kế kế hoạch)',
                    data: planCum,
                    type: 'line',
                    borderColor: '#eab308',
                    backgroundColor: '#eab308',
                    borderWidth: 3,
                    pointRadius: 3,
                    // 📌 CẤU HÌNH Ô THỂ HIỆN 10,416
                    datalabels: {
                        display: function(context) {
                        return context.dataIndex === planCum.length - 1; // Chỉ hiển thị tại điểm ngày 31 (cuối tháng)
                        },
                        align: 'top',
                        anchor: 'center',
                        offset: 8,
                        backgroundColor: '#ffffff',
                        borderColor: '#eab308',
                        borderWidth: 1,
                        borderRadius: 4,
                        padding: { top: 4, bottom: 4, left: 8, right: 8 },
                        color: '#1e293b',
                        font: { weight: 'bold', size: 12 },
                        formatter: function(value) {
                        return value ? value.toLocaleString() : '';
                        }
                    },
                    order: 1
                    }
                ]
                },
                options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', align: 'start' }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { min: 0, max: 12000, ticks: { stepSize: 1000 } }
                }
                }
            });
            });



           // 1. Biểu đồ cuộn


            document.addEventListener("DOMContentLoaded", function () {
            const ctx2 = document.getElementById('smallRollProgressChart');
            if (!ctx2) return;

            // 1. Nhãn Ngày từ 01 đến 31
            const days = Array.from({length: 31}, (_, i) => String(i + 1).padStart(2, '0'));

            // 2. Dữ liệu Ngày: Thực tích (Xanh dương) & Kế hoạch (Cam)
            const actualDaily = [0, 0, 361, 455, 502, 454, 414, 434, 0, 437, 432, 355, 439, 462, 513, 0, 438, 404, 432, 422, 422, 492, 0, 460, 0, 0, 0, 0, 0, 0, 0];
            const planDaily   = [0, 0, 421, 390, 413, 372, 453, 432, 0, 442, 474, 501, 516, 577, 490, 0, 496, 521, 517, 530, 607, 567, 0, 551, 553, 582, 557, 468, 0, 0, 0];

            // 3. Tính Lũy kế
            let pSum = 0;
            const planCum = planDaily.map(v => (pSum += v));

            let aSum = 0;
            let hasActual = true;
            const actualCum = actualDaily.map(v => {
                if (v === 0 && aSum > 0 && hasActual) {
                // Giữ nguyên mức lũy tích khi dừng sản xuất
                }
                aSum += v;
                return aSum;
            });

            // Tải Plugin DataLabels để hiện số lên Cột & Đường
            Chart.register(ChartDataLabels);

            new Chart(ctx2, {
                type: 'bar',
                data: {
                labels: days,
                datasets: [
                    {
                    label: '実績日 (Thực tích/ngày)',
                    data: actualDaily,
                    backgroundColor: '#2563eb', // Xanh dương
                    yAxisID: 'yLeft',
                    barPercentage: 0.7,
                    categoryPercentage: 0.6,
                    datalabels: {
                        align: 'top',
                        anchor: 'end',
                        color: '#1e3a8a',
                        font: { size: 9, weight: 'bold' },
                        formatter: v => (v > 0 ? v : '0')
                    },
                    order: 3
                    },
                    {
                    label: '計画日 (Kế hoạch/ngày)',
                    data: planDaily,
                    backgroundColor: '#f97316', // Cam
                    yAxisID: 'yLeft',
                    barPercentage: 0.7,
                    categoryPercentage: 0.6,
                    datalabels: {
                        align: 'top',
                        anchor: 'end',
                        color: '#c2410c',
                        font: { size: 9, weight: 'bold' },
                        formatter: v => (v > 0 ? v : '')
                    },
                    order: 4
                    },
                    {
                    label: '計画累計 (Lũy kế kế hoạch)',
                    data: planCum,
                    type: 'line',
                    borderColor: '#94a3b8', // Xám
                    backgroundColor: '#94a3b8',
                    borderWidth: 2,
                    pointRadius: 2,
                    yAxisID: 'yRight',
                    datalabels: {
                        align: 'top',
                        anchor: 'center',
                        color: '#475569',
                        font: { size: 9, weight: 'bold' },
                        formatter: (v, ctx) => ([7, 13, 19, 23, 27, 30].includes(ctx.dataIndex) ? v : '')
                    },
                    order: 1
                    },
                    {
                    label: '実績累計 (Lũy kế thực tích)',
                    data: actualCum,
                    type: 'line',
                    borderColor: '#eab308', // Vàng cam
                    backgroundColor: '#eab308',
                    borderWidth: 3,
                    pointRadius: 2,
                    yAxisID: 'yRight',
                    datalabels: {
                        align: 'bottom',
                        anchor: 'center',
                        color: '#a16207',
                        font: { size: 9, weight: 'bold' },
                        formatter: (v, ctx) => ([2, 4, 6, 7, 9, 11, 13, 14, 17, 19, 21, 23].includes(ctx.dataIndex) ? v : '')
                    },
                    order: 2
                    }
                ]
                },
                options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { size: 11 } }
                    },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    x: {
                    grid: { display: false },
                    ticks: { font: { size: 10 } }
                    },
                    // Trục Y Trái: Sản lượng ngày (0 - 700)
                    yLeft: {
                    type: 'linear',
                    position: 'left',
                    min: 0,
                    max: 700,
                    ticks: { stepSize: 100 },
                    grid: { color: '#e2e8f0' }
                    },
                    // Trục Y Phải: Lũy kế tháng (0 - 14,000)
                    yRight: {
                    type: 'linear',
                    position: 'right',
                    min: 0,
                    max: 14000,
                    ticks: { stepSize: 2000 },
                    grid: { display: false }
                    }
                }
                }
            });
            });













        </script>

      </main>

      <!-- 4. Ghép Footer -->
      <?php include '../includes/footer.php'; ?>
    </div> <!-- Thẻ đóng cho .main-wrapper -->
  </div> <!-- Thẻ đóng cho .app-container -->

</body>
</html>