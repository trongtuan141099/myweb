<style>
/* CSS RIÊNG CỦA MODULE GIÁM SÁT THIẾT BỊ IOT */
.device-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
  gap: 16px;
}

.device-card {
  background: var(--dx-bg-card);
  border-radius: var(--dx-radius-md);
  padding: 16px;
  border: 1px solid var(--dx-border);
  box-shadow: var(--dx-shadow-sm);
  display: flex;
  flex-direction: column;
  gap: 12px;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
  position: relative;
}
.device-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--dx-shadow-md);
}

.device-card.status-ON { border-top: 4px solid var(--dx-success); }
.device-card.status-OFF { border-top: 4px solid var(--dx-danger); }
.device-card.status-ERROR { border-top: 4px solid var(--dx-warning); }
.device-card.status-OFFLINE { border-top: 4px solid var(--dx-border-strong); opacity: 0.85; }

.card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.dev-name {
  font-size: 15px;
  font-weight: 700;
  color: var(--dx-text-main);
}

.settings-btn {
  background: transparent;
  border: none;
  color: var(--dx-text-muted);
  cursor: pointer;
  border-radius: 4px;
  padding: 4px;
  display: flex;
  align-items: center;
}
.settings-btn:hover {
  background-color: var(--dx-bg-subtle);
  color: var(--dx-primary);
}

.info-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
  background-color: var(--dx-bg-app);
  padding: 10px 12px;
  border-radius: 6px;
  border: 1px solid var(--dx-border);
}

.info-row {
  font-size: 12px;
  color: var(--dx-text-muted);
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.info-row span {
  color: var(--dx-text-main);
  font-weight: 600;
  font-family: monospace;
}

.status-badge {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px;
  border-radius: 6px;
  font-weight: 700;
  font-size: 12px;
  text-align: center;
}

.badge-ON { background: var(--dx-success-bg); color: var(--dx-success); border: 1px solid var(--dx-success-border); }
.badge-OFF { background: var(--dx-danger-bg); color: var(--dx-danger); border: 1px solid var(--dx-danger-border); }
.badge-ERROR { background: var(--dx-warning-bg); color: var(--dx-warning); border: 1px solid var(--dx-warning-border); }
.badge-OFFLINE { background: var(--dx-bg-subtle); color: var(--dx-text-muted); border: 1px solid var(--dx-border); }

.note-box {
  font-size: 11px;
  color: var(--dx-text-muted);
  text-align: center;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
}

/* Modal Cấu Hình */
.custom-modal {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.4);
  backdrop-filter: blur(2px);
  justify-content: center;
  align-items: center;
  z-index: 1050;
}

.custom-modal-content {
  background: var(--dx-bg-card);
  padding: 24px;
  border-radius: var(--dx-radius-md);
  width: 90%;
  max-width: 420px;
  border: 1px solid var(--dx-border);
  box-shadow: var(--dx-shadow-lg);
}

.custom-modal-content h3 {
  font-size: 16px;
  font-weight: 700;
  color: var(--dx-text-main);
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
}
</style>

<!-- Nội dung trang -->
<div class="app-page-wrapper">
  <div class="app-page-header">
    <div>
      <h1 class="app-page-title">
        <span class="material-icons">sensors</span>
        Hệ Thống Giám Sát Thiết Bị Real-Time
      </h1>
      <p class="app-page-subtitle">Theo dõi trạng thái kết nối và sự kiện từ thiết bị IoT dây chuyền nhà máy</p>
    </div>
    <div class="app-page-actions">
      <a href="index.php?mainpage=iot&subpage=device_history" class="app-btn app-btn-primary">
        <span class="material-icons">history</span> Xem Lịch Sử Hoạt Động
      </a>
    </div>
  </div>

  <div class="device-grid" id="deviceGrid">
    <!-- Dữ liệu được tải tự động qua AJAX -->
  </div>
</div>

<!-- Modal Thiết Lập Thiết Bị -->
<div class="custom-modal" id="configModal">
  <div class="custom-modal-content">
    <h3><span class="material-icons text-primary">settings</span> Thiết Lập Thiết Bị</h3>
    <input type="hidden" id="edit_id">
    <div class="mb-3">
      <label class="app-form-label">Tên Thiết Bị:</label>
      <input type="text" id="edit_name" class="app-form-control w-100">
    </div>
    <div class="mb-3">
      <label class="app-form-label">Mã Kết Nối IoT (Device Code):</label>
      <input type="text" id="edit_code" class="app-form-control w-100">
    </div>
    <div class="mb-3">
      <label class="app-form-label">Địa Chỉ IP Đang Liên Kết:</label>
      <input type="text" id="edit_ip" class="app-form-control w-100">
    </div>
    <div class="d-flex justify-content-end gap-2 mt-4">
      <button class="app-btn app-btn-secondary" onclick="closeModal()">Hủy</button>
      <button class="app-btn app-btn-primary" onclick="saveConfig()">Lưu Cấu Hình</button>
    </div>
  </div>
</div>

<script>
function loadDevices() {
  fetch('api/iot_status.php?action=get_devices')
    .then(res => res.json())
    .then(data => {
      let html = '';
      data.forEach(dev => {
        let statusText = 'MẤT KẾT NỐI (OFFLINE)';
        let displayStatus = dev.display_status;

        if (displayStatus === 'ON') statusText = 'ĐANG HOẠT ĐỘNG';
        else if (displayStatus === 'OFF') statusText = 'ĐANG TẠM DỪNG';
        else if (displayStatus === 'ERROR') statusText = 'GẶP LỖI HỆ THỐNG';
        else if (displayStatus === 'OFFLINE') statusText = 'MẤT KẾT NỐI (OFFLINE)';

        html += `
        <div class="device-card status-${displayStatus}">
          <div class="card-top">
            <div class="dev-name">${escapeHtml(dev.device_name)}</div>
            <button class="settings-btn" onclick="openModal(${dev.id}, '${escapeHtml(dev.device_name)}', '${escapeHtml(dev.device_code)}', '${escapeHtml(dev.ip_address || '')}')" title="Cài đặt">
              <span class="material-icons">settings</span>
            </button>
          </div>
          <div class="info-group">
            <div class="info-row">Mã IoT: <span>${escapeHtml(dev.device_code)}</span></div>
            <div class="info-row">Địa chỉ IP: <span>${escapeHtml(dev.ip_address || 'N/A')}</span></div>
          </div>
          <div class="status-badge badge-${displayStatus}">
            <span class="material-icons" style="font-size:16px;">${displayStatus === 'ON' ? 'check_circle' : (displayStatus === 'OFF' ? 'pause_circle' : (displayStatus === 'ERROR' ? 'error' : 'cloud_off'))}</span>
            ${statusText}
          </div>
          <div class="note-box"><span class="material-icons" style="font-size:13px;">info</span> ${escapeHtml(dev.note || 'Không có ghi chú')}</div>
        </div>`;
      });
      const grid = document.getElementById('deviceGrid');
      if (grid) grid.innerHTML = html;
    })
    .catch(err => console.error("Lỗi khi tải trạng thái thiết bị:", err));
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str || '';
  return div.innerHTML;
}

let deviceInterval = setInterval(loadDevices, 2000);
loadDevices();

function openModal(id, name, code, ip) {
  document.getElementById('edit_id').value = id;
  document.getElementById('edit_name').value = name;
  document.getElementById('edit_code').value = code;
  document.getElementById('edit_ip').value = ip;
  document.getElementById('configModal').style.display = 'flex';
}

function closeModal() {
  document.getElementById('configModal').style.display = 'none';
}

function saveConfig() {
  const id = document.getElementById('edit_id').value;
  const name = encodeURIComponent(document.getElementById('edit_name').value);
  const code = encodeURIComponent(document.getElementById('edit_code').value);
  const ip = encodeURIComponent(document.getElementById('edit_ip').value);

  fetch(`api/iot_status.php?action=save_config&id=${id}&device_name=${name}&device_code=${code}&ip_address=${ip}`)
    .then(res => res.json())
    .then(res => {
      if(res.status === 'success') {
        closeModal();
        loadDevices();
      } else {
        alert(res.message);
      }
    })
    .catch(err => alert("Lỗi khi lưu cấu hình"));
}
</script>