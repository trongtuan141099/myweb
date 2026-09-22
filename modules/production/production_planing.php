<style>
/* CSS RIÊNG CỦA MODULE KẾ HOẠCH SẢN XUẤT */
.dash-grid-4 { display: grid; grid-template-columns: repeat(1, 1fr); gap: 16px; margin-bottom: 16px; }
@media (min-width: 1280px) { .dash-grid-4 { grid-template-columns: 3fr 1fr; } }
.dash-grid-sub { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }

/* Table Design cho Ma Trận & Tỷ Lệ */
.dash-table { width: 100%; border-collapse: collapse; font-size: 12px; text-align: center; }
.dash-table th { background-color: var(--dx-bg-subtle, #f8fafc); color: var(--dx-text-muted, #475569); font-weight: 700; padding: 8px 4px; border: 1px solid var(--dx-border, #e2e8f0); position: sticky; top: 0; z-index: 10; }
.dash-table td { border: 1px solid var(--dx-border, #e2e8f0); padding: 0; color: var(--dx-text-main); }
.dash-table tr:hover { background-color: var(--dx-bg-hover, #f1f5f9); }

/* Modal Ma Trận Kế Hoạch */
.dash-modal { position: fixed; inset: 0; background: rgba(0, 0, 0, 0.65); backdrop-filter: blur(4px); z-index: 1050; display: flex; align-items: center; justify-content: center; padding: 20px; }
.dash-modal-content { background: var(--dx-bg-card, #ffffff); color: var(--dx-text-main); border: 1px solid var(--dx-border); border-radius: 12px; width: 100%; max-width: 1280px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); overflow: hidden; }
</style>

<div class="app-page-wrapper">

    <!-- 1. BAR LỌC VÀ THAO TÁC MAIN -->
    <div class="app-page-header">
        <div>
            <h1 class="app-page-title">
                <span class="material-icons">precision_manufacturing</span>
                Báo Cáo Tiến Độ Sản Xuất (押出)
            </h1>
            <p class="app-page-subtitle">Theo dõi kế hoạch, thực tích và tỷ lệ hoàn thành hằng ngày</p>
        </div>

        <div class="app-page-actions">
            <select id="filterMode" class="app-form-select" onchange="toggleFilterMode()">
                <option value="month">Theo Tháng</option>
                <option value="range">Theo Khoảng Ngày</option>
            </select>

            <div id="monthFilterContainer">
                <input type="month" id="reportMonth" value="<?= date('Y-m') ?>" class="app-form-control" onchange="loadReportData()">
            </div>

            <div id="rangeFilterContainer" style="display:none;" class="d-flex align-items-center gap-1">
                <input type="date" id="startDate" class="app-form-control" onchange="loadReportData()">
                <span class="text-muted">-</span>
                <input type="date" id="endDate" class="app-form-control" onchange="loadReportData()">
            </div>

            <select id="pipeSizeSelect" class="app-form-select" onchange="loadReportData()">
                <option value="ALL">Tất cả Size (全サイズ)</option>
                <option value="TU04">TU04</option><option value="TU06">TU06</option>
                <option value="TU08">TU08</option><option value="TU10">TU10</option>
                <option value="TU12">TU12</option><option value="TU16">TU16</option>
                <option value="TIUB01">TIUB01</option><option value="TIUB05">TIUB05</option>
                <option value="TIUB07">TIUB07</option><option value="TIUB11">TIUB11</option>
                <option value="TIUB13">TIUB13</option>
            </select>

            <button onclick="openPlanModal()" class="app-btn app-btn-warning">
                <span class="material-icons">edit_calendar</span> Kế Hoạch Tháng
            </button>
        </div>
    </div>

    <!-- 2. KHỐI BIỂU ĐỒ TỔNG CHÍNH VÀ BẢNG TỶ LỆ HOÀN THÀNH -->
    <div class="dash-grid-4">
        <div class="app-card">
            <div class="app-card-header">
                <span class="d-flex align-items-center gap-2">
                    <span class="material-icons text-primary">analytics</span>
                    Biểu Đồ Tiến Độ Sản Xuất Tổng Thể
                </span>
                <span class="small text-muted font-normal">Đơn vị: kM</span>
            </div>
            <div class="app-card-body p-2">
                <div id="mainProductionChart" style="height:380px;"></div>
            </div>
        </div>
        <div class="app-card">
            <div class="app-card-header">
                <span class="d-flex align-items-center gap-2">
                    <span class="material-icons text-primary">pie_chart</span>
                    Tỷ Lệ Hoàn Thành
                </span>
            </div>
            <div class="app-card-body p-0" style="max-height:380px; overflow-y:auto;">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th style="padding:8px 4px;">Size</th>
                            <th style="padding:8px 4px;">TT Lũy Kế</th>
                            <th style="padding:8px 4px;">KH Lũy Kế</th>
                            <th style="padding:8px 4px;">TT% / KH%</th>
                            <th style="padding:8px 4px;">Chênh Lệch</th>
                        </tr>
                    </thead>
                    <tbody id="summaryTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 3. KHỐI BIỂU ĐỒ CON TỪNG SIZE ỐNG -->
    <div class="app-card mb-3">
        <div class="app-card-header">
            <span class="d-flex align-items-center gap-2">
                <span class="material-icons text-primary">show_chart</span>
                Tiến Độ Chi Tiết Theo Từng Size Ống
            </span>
        </div>
        <div class="app-card-body">
            <div id="subChartsContainer" class="dash-grid-sub"></div>
        </div>
    </div>

    <!-- 4. MODAL ĐIỀU CHỈNH KẾ HOẠCH MA TRẬN NÂNG CẤP -->
    <div id="planModal" class="dash-modal" style="display:none;">
        <div class="dash-modal-content">
            <div style="padding:15px 20px; border-bottom:1px solid var(--dx-border); display:flex; justify-content:space-between; align-items:center; background:var(--dx-bg-subtle);">
                <div style="display:flex; align-items:center; gap:16px;">
                    <h3 style="margin:0; font-size:16px; font-weight:700; color:var(--dx-text-main); display:flex; align-items:center; gap:6px;">
                        <span class="material-icons text-warning">edit_calendar</span>
                        Điều Chỉnh Kế Hoạch Sản Xuất
                    </h3>
                    
                    <div style="display:flex; align-items:center; gap:8px; background:var(--dx-bg-card); border:1px solid var(--dx-border); padding:4px 10px; border-radius:8px; box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                        <span style="font-size:12px; font-weight:600; color:var(--dx-text-muted);">Tháng kế hoạch:</span>
                        <input type="month" id="modalPlanMonth" value="<?= date('Y-m') ?>" class="app-form-control" style="padding:3px 8px; font-weight:700; color:var(--dx-primary); border:none; background:transparent;" onchange="loadMatrixByMonth()">
                    </div>
                </div>

                <button onclick="closePlanModal()" style="border:none; background:none; font-size:24px; cursor:pointer; color:var(--dx-text-muted); line-height:1;">&times;</button>
            </div>

            <div style="padding:10px 20px; background:var(--dx-bg-app); border-bottom:1px solid var(--dx-border); display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:10px;">
                <div style="font-size:12px; color:var(--dx-text-muted);">
                    💡 <b>Thao tác:</b> Nhập số trực tiếp vào ma trận hoặc tải file mẫu Excel/CSV để upload nhanh kế hoạch cả tháng.
                </div>
                <div style="display:flex; gap:8px;">
                    <button onclick="downloadPlanTemplate()" class="app-btn app-btn-secondary app-btn-sm">
                        <span class="material-icons">description</span> Tải File Kế Hoạch (.csv)
                    </button>
                    <label class="app-btn app-btn-success app-btn-sm" style="cursor:pointer; margin-bottom:0;">
                        <span class="material-icons">file_upload</span> Upload Kế Hoạch Excel
                        <input type="file" id="planExcelInput" accept=".xlsx, .xls, .csv" style="display:none;" onchange="handleUploadPlanExcel()">
                    </label>
                </div>
            </div>

            <div style="padding:15px; overflow:auto; flex:1; max-height:calc(90vh - 180px);">
                <table class="dash-table" id="matrixPlanTable"></table>
            </div>

            <div style="padding:12px 20px; border-top:1px solid var(--dx-border); text-align:right; background:var(--dx-bg-subtle);">
                <button onclick="closePlanModal()" class="app-btn app-btn-secondary" style="margin-right:8px;">Hủy</button>
                <button onclick="savePlanMatrix()" class="app-btn app-btn-primary">Lưu Cập Nhật KH</button>
            </div>
        </div>
    </div>

</div>

<!-- 5. TOÀN BỘ SCRIPT XỬ LÝ LOGIC MAIN & MODAL -->
<script>
let mainChartInstance = null;
let subChartInstances = {};
let currentPlanMatrix = [];

document.addEventListener("DOMContentLoaded", () => {
    loadReportData();
});

function toggleFilterMode() {
    const mode = document.getElementById("filterMode").value;
    document.getElementById("monthFilterContainer").style.display = (mode === "month") ? "block" : "none";
    document.getElementById("rangeFilterContainer").style.display = (mode === "range") ? "flex" : "none";
    loadReportData();
}

// Tải báo cáo và truyền thông tin điểm Annotations
async function loadReportData() {
    const mode = document.getElementById("filterMode").value;
    let month = document.getElementById("reportMonth").value;
    if (!month) {
        month = new Date().toISOString().slice(0, 7);
        document.getElementById("reportMonth").value = month;
    }
    const startDate = document.getElementById("startDate").value;
    const endDate = document.getElementById("endDate").value;
    const pipeSize = document.getElementById("pipeSizeSelect").value;

    let url = `api/get_production_report.php?mode=${mode}&pipe_size=${pipeSize}`;
    if (mode === 'month') url += `&month=${month}`;
    else url += `&start_date=${startDate}&end_date=${endDate}`;

    try {
        const res = await fetch(url);
        const data = await res.json();

        if (data.success) {
            currentPlanMatrix = data.plan_matrix || [];
            renderMainChart(data.days, data.plan_daily, data.actual_daily, data.plan_cum, data.actual_cum, data.point_plan, data.point_actual);
            renderSummaryTable(data.summary_table);
            renderSubCharts(data.days, data.sizes_data);
        } else {
            alert("Lỗi từ Server: " + data.message);
        }
    } catch (err) {
        console.error("Lỗi tải dữ liệu Dashboard:", err);
    }
}

// Render Biểu đồ chính kèm 2 Nhãn Điểm Lũy Tích Kế Hoạch & Thực Tế
function renderMainChart(categories, planDaily, actualDaily, planCum, actualCum, pointPlan, pointActual) {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const annotationsPoints = [];

    // Nhãn điểm Lũy tích Kế hoạch
    if (pointPlan && pointPlan.index >= 0) {
        const planX = categories[pointPlan.index];
        annotationsPoints.push({
            x: planX,
            y: pointPlan.value,
            seriesIndex: 2,
            marker: { size: 5, fillColor: '#64748b', strokeColor: isDark ? '#1e293b' : '#fff', strokeWidth: 2 },
            label: {
                borderColor: '#64748b',
                style: { color: '#fff', background: '#64748b', fontSize: '11px', fontWeight: 'bold' },
                text: Number(pointPlan.value).toLocaleString()
            }
        });
    }

    // Nhãn điểm Lũy tích Thực tế
    if (pointActual && pointActual.index >= 0) {
        const actualX = categories[pointActual.index];
        annotationsPoints.push({
            x: actualX,
            y: pointActual.value,
            seriesIndex: 3,
            marker: { size: 5, fillColor: '#f59e0b', strokeColor: isDark ? '#1e293b' : '#fff', strokeWidth: 2 },
            label: {
                borderColor: '#f59e0b',
                style: { color: '#fff', background: '#f59e0b', fontSize: '11px', fontWeight: 'bold' },
                text: Number(pointActual.value).toLocaleString()
            }
        });
    }

    const options = {
        chart: { height: 380, type: 'line', toolbar: { show: true }, background: 'transparent' },
        theme: { mode: isDark ? 'dark' : 'light' },
        series: [
            { name: 'Thực tích/ngày', type: 'column', data: actualDaily },
            { name: 'Kế hoạch/ngày', type: 'line', data: planDaily },
            { name: 'Lũy tích kế hoạch', type: 'line', data: planCum },
            { name: 'Lũy tích thực tế', type: 'line', data: actualCum }
        ],
        stroke: { 
            width: [0, 2, 3, 3], 
            curve: 'smooth',
            dashArray: [0, 4, 0, 0]
        },
        annotations: {
            points: annotationsPoints
        },
        colors: ['#3b82f6', '#94a3b8', '#64748b', '#f59e0b'],
        xaxis: { 
            categories: categories,
            labels: { style: { colors: isDark ? '#94a3b8' : '#475569' } }
        },
        yaxis: {
            title: { text: 'Sản lượng (kM)', style: { color: isDark ? '#94a3b8' : '#475569' } },
            labels: {
                style: { colors: isDark ? '#94a3b8' : '#475569' },
                formatter: function (val) {
                    return Number(val).toLocaleString() + " kM";
                }
            }
        },
        legend: { 
            position: 'top',
            labels: { colors: isDark ? '#e2e8f0' : '#334155' }
        },
        tooltip: {
            theme: isDark ? 'dark' : 'light',
            shared: true,
            intersect: false,
            y: {
                formatter: function (val) {
                    return Number(val).toLocaleString() + " kM";
                }
            }
        }
    };

    if (mainChartInstance) mainChartInstance.destroy();
    mainChartInstance = new ApexCharts(document.querySelector("#mainProductionChart"), options);
    mainChartInstance.render();
}

// Render Bảng Tỷ Lệ Hoàn Thành linh hoạt theo mốc ngày
// Render Bảng Tỷ Lệ Hoàn Thành (Bổ sung Chênh lệch %, Hàng TOTAL)
function renderSummaryTable(summaryData) {
    const tbody = document.getElementById("summaryTableBody");
    tbody.innerHTML = "";
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';

    summaryData.forEach(row => {
        const rateText = `${row.actual_rate}% / ${row.plan_rate_effective}%`;

        let diffColor = isDark ? '#34d399' : '#059669';
        let diffText = `+${row.diff_rate}%`;

        if (row.diff_rate < 0) {
            diffColor = isDark ? '#f87171' : '#dc2626';
            diffText = `${row.diff_rate}%`;
        } else if (row.diff_rate === 0) {
            diffColor = isDark ? '#94a3b8' : '#64748b';
            diffText = '0%';
        }

        const isTotal = row.is_total ? 'background:var(--dx-bg-subtle); font-weight:700;' : '';

        tbody.innerHTML += `
            <tr style="height:36px; ${isTotal}">
                <td style="padding:6px 4px;"><b>${row.size}</b></td>
                <td style="padding:6px 4px;">${Number(row.actual_cum).toLocaleString()}</td>
                <td style="padding:6px 4px;">${Number(row.plan_cum).toLocaleString()}</td>
                <td style="padding:6px 4px; font-weight:600; color:var(--dx-text-main);">${rateText}</td>
                <td style="padding:6px 4px; font-weight:700; color:${diffColor};">${diffText}</td>
            </tr>
        `;
    });
}
function renderSubCharts(categories, sizesData) {
    const container = document.getElementById("subChartsContainer");
    container.innerHTML = "";
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';

    Object.keys(subChartInstances).forEach(key => subChartInstances[key].destroy());
    subChartInstances = {};

    Object.keys(sizesData).forEach(size => {
        const cardId = `chart_sub_${size}`;
        container.innerHTML += `
            <div style="border:1px solid var(--dx-border); border-radius:8px; padding:12px; background:var(--dx-bg-card);">
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--dx-border); padding-bottom:6px; margin-bottom:8px;">
                    <span style="font-weight:700; font-size:13px; color:var(--dx-text-main);">Size: ${size}</span>
                    <span style="font-size:11px; background:var(--dx-primary-bg, #eff6ff); color:var(--dx-primary, #2563eb); border:1px solid var(--dx-primary-border, rgba(37,99,235,0.2)); padding:2px 6px; border-radius:4px; font-weight:600;">${sizesData[size].result_rate}%</span>
                </div>
                <div id="${cardId}" style="height:180px;"></div>
            </div>
        `;
    });

    setTimeout(() => {
        Object.keys(sizesData).forEach(size => {
            const sData = sizesData[size];
            const options = {
                chart: { height: 170, type: 'line', toolbar: { show: false }, background: 'transparent' },
                theme: { mode: isDark ? 'dark' : 'light' },
                series: [
                    { name: 'TT ngày', type: 'column', data: sData.actual_daily },
                    { name: 'KH ngày', type: 'line', data: sData.plan_daily },
                    { name: 'Lũy tích KH', type: 'line', data: sData.plan_cum },
                    { name: 'Lũy tích TT', type: 'line', data: sData.actual_cum }
                ],
                stroke: { width: [0, 1.5, 2, 2], curve: 'smooth', dashArray: [0, 3, 0, 0] },
                colors: ['#60a5fa', '#cbd5e1', '#94a3b8', '#f59e0b'],
                xaxis: { categories: categories, labels: { show: false } },
                yaxis: { labels: { show: true, style: { colors: isDark ? '#94a3b8' : '#475569' } } },
                legend: { show: false }
            };
            const chart = new ApexCharts(document.querySelector(`#chart_sub_${size}`), options);
            chart.render();
            subChartInstances[size] = chart;
        });
    }, 100);
}

function openPlanModal() {
    const mainMonthInput = document.getElementById("reportMonth");
    const currentMonth = (mainMonthInput && mainMonthInput.value) ? mainMonthInput.value : "2026-08";
    
    document.getElementById("modalPlanMonth").value = currentMonth;
    loadMatrixByMonth();
    document.getElementById("planModal").style.display = "flex";
}

function closePlanModal() { 
    document.getElementById("planModal").style.display = "none"; 
}

async function loadMatrixByMonth() {
    const selectedMonth = document.getElementById("modalPlanMonth").value;
    try {
        const res = await fetch(`api/get_production_report.php?mode=month&month=${selectedMonth}&pipe_size=ALL`);
        const data = await res.json();

        if (data.success) {
            currentPlanMatrix = data.plan_matrix || [];
            renderMatrixTable();
        } else {
            alert("Không thể tải ma trận kế hoạch: " + data.message);
        }
    } catch (err) {
        console.error("Lỗi kết nối tải ma trận kế hoạch:", err);
    }
}

function renderMatrixTable() {
    const table = document.getElementById("matrixPlanTable");
    if (!table) return;

    let html = '<thead><tr><th style="min-width:80px; position:sticky; left:0; z-index:20; background:var(--dx-bg-subtle); color:var(--dx-text-muted);">Size</th>';
    for (let d = 1; d <= 31; d++) {
        html += `<th style="min-width:45px; background:var(--dx-bg-subtle); color:var(--dx-text-muted);">${d}</th>`;
    }
    html += '</tr></thead><tbody>';

    if (currentPlanMatrix.length === 0) {
        html += `<tr><td colspan="32" style="padding:20px; color:var(--dx-text-muted);">Không có dữ liệu kế hoạch.</td></tr>`;
    } else {
        currentPlanMatrix.forEach((row, sIdx) => {
            html += `<tr><td style="font-weight:700; background:var(--dx-bg-subtle); position:sticky; left:0; z-index:10; color:var(--dx-text-main);">${row.pipe_size}</td>`;
            for (let d = 1; d <= 31; d++) {
                const val = (row.days && row.days[d] !== undefined) ? row.days[d] : 0;
                html += `<td style="padding:0;">
                    <input type="number" 
                           step="any"
                           min="0"
                           style="width:100%; height:32px; text-align:center; border:none; outline:none; font-size:12px; background:transparent; color:var(--dx-text-main);" 
                           value="${val}" 
                           data-size-idx="${sIdx}" 
                           data-day="${d}"
                           onfocus="this.select()"
                           onchange="updateMatrixVal(this, ${sIdx}, ${d})">
                </td>`;
            }
            html += '</tr>';
        });
    }

    html += '</tbody>';
    table.innerHTML = html;
}

function updateMatrixVal(input, sIdx, day) {
    const val = parseFloat(input.value) || 0;
    if (currentPlanMatrix[sIdx] && currentPlanMatrix[sIdx].days) {
        currentPlanMatrix[sIdx].days[day] = val;
    }
}

async function savePlanMatrix() {
    const selectedMonth = document.getElementById("modalPlanMonth").value;
    const inputs = document.querySelectorAll("#matrixPlanTable input");
    
    inputs.forEach(inp => {
        const sIdx = inp.getAttribute("data-size-idx");
        const day  = inp.getAttribute("data-day");
        if (currentPlanMatrix[sIdx] && currentPlanMatrix[sIdx].days) {
            currentPlanMatrix[sIdx].pipe_size = currentPlanMatrix[sIdx].pipe_size.toUpperCase().trim();
            currentPlanMatrix[sIdx].days[day] = parseFloat(inp.value) || 0;
        }
    });

    try {
        const res = await fetch('api/save_production_plan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ month: selectedMonth, matrix: currentPlanMatrix })
        });

        const result = await res.json();
        if (result.success) {
            alert(result.message);
            closePlanModal();
            
            const mainMonthInput = document.getElementById("reportMonth");
            if (mainMonthInput) {
                mainMonthInput.value = selectedMonth;
            }
            loadReportData();
        } else {
            alert("Lỗi từ Server: " + result.message);
        }
    } catch (err) {
        console.error("Lỗi khi lưu kế hoạch:", err);
        alert("Lỗi kết nối Server khi lưu kế hoạch!");
    }
}

function downloadPlanTemplate() {
    const selectedMonth = document.getElementById("modalPlanMonth").value;
    
    if (!currentPlanMatrix || currentPlanMatrix.length === 0) {
        alert("Không có dữ liệu kế hoạch tháng " + selectedMonth);
        return;
    }

    let csvContent = "\uFEFFSize";
    for (let d = 1; d <= 31; d++) csvContent += `,${d}`;
    csvContent += "\n";

    currentPlanMatrix.forEach(row => {
        csvContent += row.pipe_size;
        for (let d = 1; d <= 31; d++) {
            const val = (row.days && row.days[d] !== undefined) ? row.days[d] : 0;
            csvContent += `,${val}`;
        }
        csvContent += "\n";
    });

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    link.setAttribute("href", url);
    link.setAttribute("download", `KHSX_Thang_${selectedMonth}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

async function handleUploadPlanExcel() {
    const fileInput = document.getElementById("planExcelInput");
    if (!fileInput || !fileInput.files.length) return;

    const selectedMonth = document.getElementById("modalPlanMonth").value;
    const formData = new FormData();
    formData.append("plan_month", selectedMonth);
    formData.append("plan_excel", fileInput.files[0]);

    try {
        const res = await fetch('api/upload_production_plan.php', {
            method: 'POST',
            body: formData
        });
        const result = await res.json();

        if (result.success) {
            alert(result.message);
            loadMatrixByMonth();
            fileInput.value = "";
        } else {
            alert("Lỗi Upload Kế Hoạch: " + result.message);
        }
    } catch (err) {
        console.error("Lỗi upload file kế hoạch:", err);
        alert("Không thể upload file kế hoạch!");
    }
}

// Lắng nghe sự kiện đổi chế độ Sáng / Tối để vẽ lại biểu đồ tương thích
window.addEventListener('dxThemeChanged', () => {
    loadReportData();
    if (document.getElementById("planModal") && document.getElementById("planModal").style.display === "flex") {
        renderMatrixTable();
    }
});
</script>