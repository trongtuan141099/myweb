
<style>
/* Scope giao diện trong device-history-wrapper */
.device-history-wrapper {
  padding: 20px;
  height: calc(100vh - var(--header-height) - var(--footer-height));
  overflow-y: auto;
  background-color: var(--bg-main);
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Header trang Lịch sử */
.history-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--bg-card);
  padding: 14px 20px;
  border-radius: 10px;
  border: 1px solid var(--border-color);
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
  flex-shrink: 0;
}

.history-header h1 {
  font-size: 18px;
  font-weight: 700;
  color: var(--text-main);
  display: flex;
  align-items: center;
  gap: 10px;
}

.history-header h1 .material-icons {
  color: var(--primary);
  font-size: 22px;
}

/* Thanh bộ lọc (Filter Bar) */
.filter-card {
  background: var(--bg-card);
  padding: 12px 16px;
  border-radius: 10px;
  border: 1px solid var(--border-color);
  display: flex;
  gap: 12px;
  align-items: center;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 8px;
  flex: 1;
  min-width: 200px;
}

.filter-group label {
  font-size: 12px;
  font-weight: 600;
  color: var(--text-muted);
  white-space: nowrap;
}

.filter-input, .filter-select {
  width: 100%;
  padding: 8px 12px;
  background: var(--bg-main);
  border: 1px solid var(--border-color);
  color: var(--text-main);
  border-radius: 6px;
  font-size: 13px;
  outline: none;
}

.filter-input:focus, .filter-select:focus {
  border-color: var(--primary);
}

/* Card chứa bảng lịch sử */
.table-card {
  background: var(--bg-card);
  border-radius: 10px;
  border: 1px solid var(--border-color);
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
  overflow: hidden;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.table-responsive {
  width: 100%;
  overflow-x: auto;
  overflow-y: auto;
  flex: 1;
}

/* Style Bảng Dữ Liệu */
.custom-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 13px;
}

.custom-table th {
  background-color: var(--bg-main);
  color: var(--text-muted);
  font-weight: 700;
  padding: 12px 16px;
  border-bottom: 1px solid var(--border-color);
  position: sticky;
  top: 0;
  z-index: 10;
  white-space: nowrap;
}

.custom-table td {
  padding: 12px 16px;
  border-bottom: 1px solid var(--border-color);
  color: var(--text-main);
  white-space: nowrap;
}

.custom-table tbody tr:hover {
  background-color: var(--bg-hover);
}

.id-col { color: var(--text-muted); font-family: monospace; font-weight: 600; }
.dev-name-col { font-weight: 600; color: var(--text-main); }
.code-badge {
  background-color: var(--bg-main);
  color: var(--text-main);
  padding: 3px 8px;
  border-radius: 4px;
  font-family: monospace;
  font-size: 12px;
  border: 1px solid var(--border-color);
}
.time-col { color: var(--text-muted); font-size: 12px; }

/* Badge Trạng Thái */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 10px;
  border-radius: 12px;
  font-weight: 700;
  font-size: 11px;
}

.badge-ON { background-color: #f0fdf4; color: var(--success); border: 1px solid #bbf7d0; }
.badge-OFF { background-color: #fef2f2; color: var(--danger); border: 1px solid #fecaca; }
.badge-ERROR { background-color: #fffbeb; color: var(--warning); border: 1px solid #fde68a; }
.badge-OFFLINE { background-color: #f8fafc; color: var(--text-muted); border: 1px solid var(--border-color); }
</style>

<!-- Bố cục HTML chính -->
<div class="device-history-wrapper">
  <div class="history-header">
    <h1><span class="material-icons">history</span> LỊCH SỬ HOẠT ĐỘNG THIẾT BỊ</h1>
    <a href="device_status.php" class="btn btn-secondary">
      <span class="material-icons">arrow_back</span> Quay Lại Dashboard
    </a>
  </div>

  <!-- Thanh lọc thông tin -->
  <div class="filter-card">
    <div class="filter-group">
      <label><span class="material-icons" style="font-size:16px;">search</span> Tìm kiếm:</label>
      <input type="text" id="searchInput" class="filter-input" placeholder="Nhập tên thiết bị hoặc mã IoT..." oninput="loadHistory()">
    </div>
    <div class="filter-group" style="max-width: 250px;">
      <label><span class="material-icons" style="font-size:16px;">filter_alt</span> Trạng thái:</label>
      <select id="statusFilter" class="filter-select" onchange="loadHistory()">
        <option value="">-- Tất cả trạng thái --</option>
        <option value="ON">ON (Đang chạy)</option>
        <option value="OFF">OFF (Tạm dừng)</option>
        <option value="ERROR">ERROR (Sự cố)</option>
        <option value="OFFLINE">OFFLINE (Mất kết nối)</option>
      </select>
    </div>
  </div>

  <div class="table-card">
    <div class="table-responsive">
      <table class="custom-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Tên Thiết Bị</th>
            <th>Mã Kết Nối IoT</th>
            <th>Trạng Thái</th>
            <th>Ghi Chú</th>
            <th>Thời Gian Ghi Nhận</th>
          </tr>
        </thead>
        <tbody id="historyTbody">
          <!-- Dữ liệu nạp động bằng AJAX -->
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function loadHistory() {
    const search = encodeURIComponent(document.getElementById('searchInput').value);
    const status = encodeURIComponent(document.getElementById('statusFilter').value);

    fetch(`/myweb/api/iot_status.php?action=get_history&search=${search}&status=${status}`)
        .then(res => res.json())
        .then(data => {
            let html = '';
            if (data.length > 0) {
                data.forEach(row => {
                    let statusText = row.status;
                    if (row.status === 'ON') statusText = 'ON (Đang chạy)';
                    else if (row.status === 'OFF') statusText = 'OFF (Tạm dừng)';
                    else if (row.status === 'ERROR') statusText = 'ERROR (Sự cố)';
                    else if (row.status === 'OFFLINE') statusText = 'OFFLINE (Mất kết nối)';

                    html += `
                    <tr>
                        <td class="id-col">#${row.id}</td>
                        <td class="dev-name-col">${row.device_name}</td>
                        <td><span class="code-badge">${row.device_code}</span></td>
                        <td><span class="status-badge badge-${row.status}">${statusText}</span></td>
                        <td>${row.note}</td>
                        <td class="time-col">${row.timestamp}</td>
                    </tr>`;
                });
            } else {
                html = `<tr><td colspan="6" style="text-align:center; color:var(--text-muted); padding:20px;">Không tìm thấy lịch sử phù hợp</td></tr>`;
            }
            document.getElementById('historyTbody').innerHTML = html;
        });
}

// Cập nhật tự động mỗi 2 giây
setInterval(loadHistory, 2000);
loadHistory();
</script>