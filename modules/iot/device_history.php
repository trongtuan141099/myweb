<style>
/* Module-specific styles for Device History */
.code-badge {
  background-color: var(--dx-bg-main);
  color: var(--dx-text-main);
  padding: 3px 8px;
  border-radius: 4px;
  font-family: monospace;
  font-size: 12px;
  border: 1px solid var(--dx-border);
}

.id-col { color: var(--dx-text-muted); font-family: monospace; font-weight: 600; }
.dev-name-col { font-weight: 600; color: var(--dx-text-main); }
.time-col { color: var(--dx-text-muted); font-size: 12px; }

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 10px;
  border-radius: 12px;
  font-weight: 700;
  font-size: 11px;
}

.badge-ON { background-color: var(--dx-success-bg); color: var(--dx-success); border: 1px solid var(--dx-success-border); }
.badge-OFF { background-color: var(--dx-danger-bg); color: var(--dx-danger); border: 1px solid var(--dx-danger-border); }
.badge-ERROR { background-color: var(--dx-warning-bg); color: var(--dx-warning); border: 1px solid var(--dx-warning-border); }
.badge-OFFLINE { background-color: var(--dx-bg-subtle); color: var(--dx-text-muted); border: 1px solid var(--dx-border); }
</style>

<div class="app-page-wrapper">
  <!-- Page Header -->
  <div class="app-page-header">
    <div>
      <h1 class="app-page-title">
        <span class="material-icons">history</span>
        Lịch Sử Hoạt Động Thiết Bị IoT
      </h1>
      <p class="app-page-subtitle">Nhật ký sự kiện kết nối, thay đổi trạng thái và cảnh báo từ các cảm biến dây chuyền</p>
    </div>
    <div class="app-page-actions">
      <a href="index.php?mainpage=iot&subpage=device_status" class="app-btn app-btn-secondary">
        <span class="material-icons">arrow_back</span> Dashboard Trạng Thái
      </a>
    </div>
  </div>

  <!-- Filter Card -->
  <div class="app-filter-card mb-3">
    <div class="row g-3 align-items-center w-100">
      <div class="col-md-7">
        <div class="input-group">
          <span class="input-group-text bg-transparent border-end-0">
            <span class="material-icons fs-6 text-muted">search</span>
          </span>
          <input type="text" id="searchInput" class="app-form-control border-start-0" placeholder="Nhập tên thiết bị hoặc mã IoT..." oninput="loadHistory()">
        </div>
      </div>
      <div class="col-md-5">
        <div class="input-group">
          <span class="input-group-text bg-transparent border-end-0">
            <span class="material-icons fs-6 text-muted">filter_alt</span>
          </span>
          <select id="statusFilter" class="app-form-control border-start-0" onchange="loadHistory()">
            <option value="">-- Tất cả trạng thái --</option>
            <option value="ON">ON (Đang chạy)</option>
            <option value="OFF">OFF (Tạm dừng)</option>
            <option value="ERROR">ERROR (Sự cố)</option>
            <option value="OFFLINE">OFFLINE (Mất kết nối)</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- Table Card -->
  <div class="app-card">
    <div class="app-table-responsive" style="max-height: calc(100vh - 280px); overflow-y: auto;">
      <table class="app-table table-sticky-header">
        <thead>
          <tr>
            <th style="width: 80px;">ID</th>
            <th>Tên Thiết Bị</th>
            <th style="width: 160px;">Mã Kết Nối IoT</th>
            <th style="width: 160px;">Trạng Thái</th>
            <th>Ghi Chú</th>
            <th style="width: 180px;">Thời Gian Ghi Nhận</th>
          </tr>
        </thead>
        <tbody id="historyTbody">
          <!-- Dynamic AJAX Data -->
        </tbody>
      </table>
    </div>
    <div class="app-card-footer">
      <div id="iotHistoryPagination" class="w-100"></div>
    </div>
  </div>
</div>

<script>
let iotPagination = null;
let lastDataJson = '';

function loadHistory() {
    const search = encodeURIComponent(document.getElementById('searchInput').value);
    const status = encodeURIComponent(document.getElementById('statusFilter').value);

    fetch(`api/iot_status.php?action=get_history&search=${search}&status=${status}`)
        .then(res => res.json())
        .then(data => {
            const currentJson = JSON.stringify(data);
            if (currentJson === lastDataJson && iotPagination) return;
            lastDataJson = currentJson;

            if (!iotPagination) {
                iotPagination = createClientTablePagination({
                    tbodyId: 'historyTbody',
                    paginationId: 'iotHistoryPagination',
                    data: data,
                    colSpan: 6,
                    defaultPageSize: 15,
                    pageSizeOptions: [10, 15, 25, 50, 100],
                    emptyMessage: 'Không tìm thấy lịch sử phù hợp',
                    renderRow: (row) => {
                        let statusText = row.status;
                        if (row.status === 'ON') statusText = 'ON (Đang chạy)';
                        else if (row.status === 'OFF') statusText = 'OFF (Tạm dừng)';
                        else if (row.status === 'ERROR') statusText = 'ERROR (Sự cố)';
                        else if (row.status === 'OFFLINE') statusText = 'OFFLINE (Mất kết nối)';

                        return `
                        <tr>
                            <td class="id-col">#${row.id}</td>
                            <td class="dev-name-col">${row.device_name}</td>
                            <td><span class="code-badge">${row.device_code}</span></td>
                            <td><span class="status-badge badge-${row.status}">${statusText}</span></td>
                            <td>${row.note || ''}</td>
                            <td class="time-col">${row.timestamp}</td>
                        </tr>`;
                    }
                });
            } else {
                iotPagination.setData(data);
            }
        })
        .catch(err => {
            console.error('Error fetching history:', err);
        });
}

// Cập nhật tự động mỗi 5 giây
setInterval(loadHistory, 5000);
loadHistory();
</script>