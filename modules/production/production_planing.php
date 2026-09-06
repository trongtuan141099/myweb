<div class="data-card">
    <div class="filter-bar" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
        <div style="font-weight:700; font-size:16px; color:#173b73;">
            生産進捗（押出） - Tiến độ sản xuất đùn
        </div>
        <div style="display:flex; gap:10px;">
            <input type="month" id="reportMonth" class="form-control" value="2026-08" onchange="loadReportData()">
            <select id="pipeSizeSelect" class="form-control" onchange="loadReportData()">
                <option value="ALL">Tất cả Size / 全サイズ</option>
                <option value="TU04">TU04</option><option value="TU06">TU06</option>
                <option value="TU08">TU08</option><option value="TU10">TU10</option>
                <option value="TU12">TU12</option><option value="TU16">TU16</option>
                <option value="TIUB01">TIUB01</option><option value="TIUB05">TIUB05</option>
                <option value="TIUB07">TIUB07</option><option value="TIUB11">TIUB11</option>
                <option value="TIUB13">TIUB13</option>
            </select>
            <button class="btn btn-warning" onclick="openPlanModal()">✏️ Chỉnh Sửa Kế Hoạch</button>
            <button class="btn btn-success" onclick="openUploadModal()">📥 Upload Thực Tích Excel</button>
        </div>
    </div>

    <div style="display:flex; gap:15px;">
        <div style="flex:1; height:400px;">
            <div id="productionReportChart"></div>
        </div>

        <div style="width:280px;">
            <table border="1" style="width:100%; text-align:center; border-collapse:collapse; font-size:12px;">
                <thead>
                    <tr style="background:#f1f5f9;">
                        <th>Size</th>
                        <th>計画累積率</th>
                        <th>生産実績率</th>
                        <th>結果</th>
                    </tr>
                </thead>
                <tbody id="summaryTableBody"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL CHỈNH SỬA BẢNG KẾ HOẠCH MA TRẬN -->
<div id="planModal" class="modal" style="display:none; position:fixed; z-index:9999; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5);">
    <div style="background:#fff; width:96%; margin:20px auto; padding:20px; border-radius:8px; max-height:90vh; overflow:auto;">
        <h3>Chỉnh Sửa Kế Hoạch Sản Xuất</h3>
        <div style="overflow-x:auto;">
            <table border="1" style="width:100%; border-collapse:collapse; font-size:11px; text-align:center;" id="matrixPlanTable"></table>
        </div>
        <div style="margin-top:15px; text-align:right;">
            <button class="btn btn-secondary" onclick="closePlanModal()">Hủy</button>
            <button class="btn btn-primary" onclick="savePlanMatrix()">Lưu Cập Nhật</button>
        </div>
    </div>
</div>

<!-- MODAL UPLOAD EXCEL -->
<div id="uploadModal" class="modal" style="display:none; position:fixed; z-index:9999; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5);">
    <div style="background:#fff; width:400px; margin:100px auto; padding:20px; border-radius:8px;">
        <h3>Upload Báo Cáo Thực Tích</h3>
        <input type="file" id="excelFileInput" accept=".xlsx, .xls" class="form-control" style="margin:15px 0;">
        <div style="text-align:right;">
            <button class="btn btn-secondary" onclick="closeUploadModal()">Hủy</button>
            <button class="btn btn-success" onclick="handleUploadExcel()">Bắt Đầu Upload</button>
        </div>
    </div>
</div>

<script>
let chartInstance = null;
let currentPlanMatrix = [];

document.addEventListener("DOMContentLoaded", () => {
    loadReportData();
});

async function loadReportData() {
    const month = document.getElementById("reportMonth").value;
    const pipeSize = document.getElementById("pipeSizeSelect").value;

    const res = await fetch(`/myweb/api/get_production_report.php?month=${month}&pipe_size=${pipeSize}`);
    const data = await res.json();

    if (data.success) {
        currentPlanMatrix = data.plan_matrix;
        renderChart(data.days, data.actual_daily, data.plan_daily, data.actual_cum, data.plan_cum);
        renderSummaryTable(data.summary_table);
    }
}

function renderChart(days, actualDaily, planDaily, actualCum, planCum) {
    const options = {
        chart: { height: 380, type: 'line', toolbar: { show: true } },
        series: [
            { name: '生産実績/日 (Thực tích/ngày)', type: 'column', data: actualDaily },
            { name: '生産計画/日 (Kế hoạch/ngày)', type: 'line', data: planDaily },
            { name: '実績累積/月 (Lũy kế thực tích)', type: 'line', data: actualCum },
            { name: '計画累積/月 (Lũy kế kế hoạch)', type: 'line', data: planCum }
        ],
        stroke: { width: [0, 2, 3, 3], curve: 'smooth' },
        colors: ['#f59e0b', '#2563eb', '#64748b', '#eab308'],
        xaxis: { categories: days },
        yaxis: { title: { text: 'Sản lượng' } }
    };

    if (chartInstance) chartInstance.destroy();
    chartInstance = new ApexCharts(document.querySelector("#productionReportChart"), options);
    chartInstance.render();
}

function renderSummaryTable(summaryData) {
    const tbody = document.getElementById("summaryTableBody");
    tbody.innerHTML = "";
    summaryData.forEach(row => {
        tbody.innerHTML += `
            <tr>
                <td><b>${row.size}</b></td>
                <td>${row.plan_rate}%</td>
                <td>${row.actual_rate}%</td>
                <td style="background:#e2e8f0;"><b>${row.result_rate}%</b></td>
            </tr>
        `;
    });
}

// Modal Ma Trận Chỉnh Sửa Kế Hoạch
function openPlanModal() {
    const table = document.getElementById("matrixPlanTable");
    let html = '<thead><tr style="background:#f1f5f9;"><th>Size</th>';
    for (let d = 1; d <= 31; d++) html += `<th>${d}</th>`;
    html += '</tr></thead><tbody>';

    currentPlanMatrix.forEach((row, sIdx) => {
        html += `<tr><td><b>${row.pipe_size}</b></td>`;
        for (let d = 1; d <= 31; d++) {
            const val = row.days[d] || 0;
            html += `<td><input type="number" style="width:45px;" value="${val}" data-size-idx="${sIdx}" data-day="${d}"></td>`;
        }
        html += '</tr>';
    });

    html += '</tbody>';
    table.innerHTML = html;
    document.getElementById("planModal").style.display = "block";
}

function closePlanModal() { document.getElementById("planModal").style.display = "none"; }

async function savePlanMatrix() {
    const month = document.getElementById("reportMonth").value;
    const inputs = document.querySelectorAll("#matrixPlanTable input");
    
    inputs.forEach(inp => {
        const sIdx = inp.getAttribute("data-size-idx");
        const day  = inp.getAttribute("data-day");
        currentPlanMatrix[sIdx].days[day] = parseFloat(inp.value) || 0;
    });

    const res = await fetch('/myweb/api/save_production_plan.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ month: month, matrix: currentPlanMatrix })
    });

    const result = await res.json();
    if (result.success) {
        alert("Lưu thành công!");
        closePlanModal();
        loadReportData();
    }
}

// Upload Excel
function openUploadModal() { document.getElementById("uploadModal").style.display = "block"; }
function closeUploadModal() { document.getElementById("uploadModal").style.display = "none"; }

async function handleUploadExcel() {
    const fileInput = document.getElementById("excelFileInput");
    if (!fileInput.files.length) return alert("Vui lòng chọn file Excel!");

    const formData = new FormData();
    formData.append("excel_file", fileInput.files[0]);

    const res = await fetch('/myweb/api/upload_extrusion_actuals.php', { method: 'POST', body: formData });
    const result = await res.json();

    if (result.success) {
        alert(result.message);
        closeUploadModal();
        loadReportData();
    } else {
        alert(result.message);
    }
}
</script>