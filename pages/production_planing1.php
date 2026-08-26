<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kế hoạch sản xuất</title>
  <!-- <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"> -->
  <link rel="stylesheet" href="../resources/icon.css">
  <!-- <link rel="stylesheet" href="../css/dashboard1.css"> -->
  <link rel="stylesheet" href="../css/main.css">
  <link rel="stylesheet" href="../css/sidebar.css">
  <link rel="stylesheet" href="../css/header.css">
  <link rel="stylesheet" href="../css/dashboard-body.css">
  <link rel="stylesheet" href="../css/footer.css">
  <!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> -->
  <!-- <script src="../resources/chart.umd.min.js"></script>
  <script src="../resources/chartjs-plugin-datalabels.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script> -->
  <script src="../resources\apexcharts.js"></script>
  <script>
console.log(ApexCharts);
</script>

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
                <!-- <canvas id="productionChart"></canvas> -->
                <div id="productionChart"></div>
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
                <!-- <canvas id="smallRollProgressChart"></canvas> -->
                <div id="smallRollProgressChart"></div>
            </div>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function () {

            const daysLabel = Array.from({length: 31}, (_, i) => i + 1);

            // const planDaily = [
            //     0,0,300,400,380,420,400,0,0,450,500,520,500,
            //     650,300,0,500,680,600,550,500,400,0,450,
            //     500,300,250,200,0,0,0
            // ];

            const planDaily = [
                0, 0, 286, 411, 401, 415, 377, 177, 0, 473,
                569, 618, 669, 642, 300, 0, 510, 691, 673, 617,
                521, 358, 0, 458, 507, 276, 250, 217, 0, 0, 0
            ];

            

            // const actualDaily = [
            //     0,0,280,380,350,400,380,400,0,0,350,420,
            //     400,450,500,0,320,400,550,520,620,480,0,
            //     420,null,null,null,null,null,null,null
            // ];

            const actualDaily = [
                57, 0, 306, 361, 385, 382, 421, 410, 0, 253,
                388, 437, 430, 454, 516, 0, 326, 407, 585, 621,
                668, 487, 0, 441, 569, 0, 0, 0, 0, 0, 0
            ];

            let planCum = [];
            let actualCum = [];

            let planSum = 0;
            let actualSum = 0;

            planDaily.forEach(v => {
                planSum += v;
                planCum.push(planSum);
            });

            actualDaily.forEach(v => {
                if(v === null){
                    actualCum.push(null);
                } else {
                    actualSum += v;
                    actualCum.push(actualSum);
                }
            });

            var options = {

                chart: {
                    height: 380,
                    type: 'line',
                    toolbar: {
                        show: true
                    }
                },

                series: [
                    {
                        name: 'Thực tích/ngày',
                        type: 'column',
                        data: actualDaily
                    },
                    {
                        name: 'Kế hoạch/ngày',
                        type: 'line',
                        data: planDaily
                    },
                    {
                        name: 'Lũy kế thực tích',
                        type: 'line',
                        data: actualCum
                    },
                    {
                        name: 'Lũy kế kế hoạch',
                        type: 'line',
                        data: planCum
                    }
                ],

                stroke: {
                    width: [0,2,3,3]
                },

                colors: [
                    '#f59e0b',
                    '#2563eb',
                    '#64748b',
                    '#eab308'
                ],

                xaxis: {
                    categories: daysLabel
                },

                yaxis: [
                    {
                        title: {
                            text: 'Sản lượng'
                        }
                    }
                ],

                dataLabels: {
                    enabled: false
                }
            };

            new ApexCharts(
                document.querySelector("#productionChart"),
                options
            ).render();

        });


        //Biểu đồ cuộn
        document.addEventListener("DOMContentLoaded", function () {

            const days = Array.from(
                { length: 31 },
                (_, i) => String(i + 1).padStart(2, '0')
            );

            const actualDaily = [
                0,0,361,455,502,454,414,434,0,437,432,355,
                439,462,513,0,438,404,432,422,422,492,0,
                460,510,0,0,0,0,0,0
            ];

            const planDaily = [
                0,0,421,390,413,372,453,432,0,442,474,501,
                516,577,490,0,496,521,517,530,607,567,0,
                551,553,582,557,468,0,0,0
            ];

            let pSum = 0;
            const planCum = planDaily.map(v => pSum += v);

            let aSum = 0;
            const actualCum = actualDaily.map(v => aSum += v);

            const options = {
                chart: {
                    height: 380,
                    type: 'line',
                    toolbar: {
                        show: true
                    }
                },

                series: [
                    {
                        name: 'Thực tích/ngày',
                        type: 'column',
                        data: actualDaily
                    },
                    {
                        name: 'Kế hoạch/ngày',
                        type: 'column',
                        data: planDaily
                    },
                    {
                        name: 'Lũy kế kế hoạch',
                        type: 'line',
                        data: planCum
                    },
                    {
                        name: 'Lũy kế thực tích',
                        type: 'line',
                        data: actualCum
                    }
                ],

                colors: [
                    '#2563eb',
                    '#f97316',
                    '#94a3b8',
                    '#eab308'
                ],

                stroke: {
                    width: [0,0,2,3]
                },

                xaxis: {
                    categories: days
                },

                dataLabels: {
                    enabled: false
                },

                yaxis: [
                    {
                        title: {
                            text: 'Sản lượng ngày'
                        }
                    }
                ]
            };

            new ApexCharts(
                document.querySelector("#smallRollProgressChart"),
                options
            ).render();

        });














        </script>

      </main>

      <!-- 4. Ghép Footer -->
      <?php include '../includes/footer.php'; ?>
    </div> <!-- Thẻ đóng cho .main-wrapper -->
  </div> <!-- Thẻ đóng cho .app-container -->

</body>
</html>