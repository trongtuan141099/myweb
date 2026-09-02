<style>
/* Scope hoàn toàn trong trang device_status để không ảnh hưởng layout chung */
.device-monitor-wrapper {
  padding: 20px;
  height: calc(100vh - var(--header-height) - var(--footer-height));
  overflow-y: auto;
  background-color: var(--bg-main);
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* Header Trang Giám Sát */
.device-monitor-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--bg-card);
  padding: 16px 20px;
  border-radius: 10px;
  border: 1px solid var(--border-color);
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.device-monitor-header h1 {
  font-size: 18px;
  font-weight: 700;
  color: var(--text-main);
  display: flex;
  align-items: center;
  gap: 10px;
}

.device-monitor-header h1 .material-icons {
  color: var(--primary);
  font-size: 22px;
}

/* Grid danh sách thiết bị */
.device-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 16px;
}

/* Card Thiết Bị */
.device-card {
  background: var(--bg-card);
  border-radius: 10px;
  padding: 16px;
  border: 1px solid var(--border-color);
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
  display: flex;
  flex-direction: column;
  gap: 12px;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
  position: relative;
}

.device-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

/* Đường viền trạng thái Card */
.device-card.status-ON { border-top: 4px solid var(--success); }
.device-card.status-OFF { border-top: 4px solid var(--danger); }
.device-card.status-ERROR { border-top: 4px solid var(--warning); }
.device-card.status-OFFLINE { border-top: 4px solid var(--text-muted); opacity: 0.85; }

.card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.dev-name {
  font-size: 15px;
  font-weight: 700;
  color: var(--text-main);
}

.settings-btn {
  background: transparent;
  border: none;
  color: var(--text-muted);
  font-size: 18px;
  cursor: pointer;
  border-radius: 4px;
  padding: 4px;
  display: flex;
  align-items: center;
  transition: all 0.15s ease;
}

.settings-btn:hover {
  background-color: var(--bg-hover);
  color: var(--primary);
}

.info-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
  background-color: var(--bg-main);
  padding: 10px 12px;
  border-radius: 6px;
  border: 1px solid var(--border-color);
}

.info-row {
  font-size: 12px;
  color: var(--text-muted);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.info-row span {
  color: var(--text-main);
  font-weight: 600;
  font-family: monospace;
}

/* Badge trạng thái */
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

.badge-ON { background: #f0fdf4; color: var(--success); border: 1px solid #bbf7d0; }
.badge-OFF { background: #fef2f2; color: var(--danger); border: 1px solid #fecaca; }
.badge-ERROR { background: #fffbeb; color: var(--warning); border: 1px solid #fde68a; }
.badge-OFFLINE { background: #f8fafc; color: var(--text-muted); border: 1px solid var(--border-color); }

.note-box {
  font-size: 11px;
  color: var(--text-muted);
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
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(15, 23, 42, 0.4);
  backdrop-filter: blur(2px);
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.custom-modal-content {
  background: var(--bg-card);
  padding: 24px;
  border-radius: 12px;
  width: 90%;
  max-width: 420px;
  border: 1px solid var(--border-color);
  box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.custom-modal-content h3 {
  font-size: 16px;
  font-weight: 700;
  color: var(--text-main);
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.form-group {
  margin-bottom: 14px;
  text-align: left;
}

.form-group label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: var(--text-muted);
  margin-bottom: 6px;
}

.form-group input {
  width: 100%;
  padding: 8px 12px;
  background: var(--bg-main);
  border: 1px solid var(--border-color);
  color: var(--text-main);
  border-radius: 6px;
  font-size: 13px;
  outline: none;
  transition: border 0.15s ease;
}

.form-group input:focus {
  border-color: var(--primary);
}

.modal-btns {
  display: flex;
  gap: 10px;
  margin-top: 20px;
}

.btn-save {
  flex: 1;
  background-color: var(--primary);
  color: #fff;
  border: none;
  padding: 8px 14px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}

.btn-cancel {
  flex: 1;
  background-color: var(--bg-main);
  color: var(--text-main);
  border: 1px solid var(--border-color);
  padding: 8px 14px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}

.btn-save:hover { background-color: var(--primary-hover); }
.btn-cancel:hover { background-color: var(--bg-hover); }
</style>

<!-- Nội dung trang -->
<div class="device-monitor-wrapper">
  <div class="device-monitor-header">
    <h1><span class="material-icons">sensors</span> HỆ THỐNG GIÁM SÁT THIẾT BỊ REAL-TIME</h1>
    <a href="device_history.php" class="btn btn-primary">
      <span class="material-icons">history</span> Xem Lịch Sử Hoạt Động
    </a>
  </div>

  <div class="device-grid" id="deviceGrid">
    <!-- Dữ liệu được tải tự động qua AJAX -->
  </div>
</div>

<!-- Modal Thiết Lập Thiết Bị -->
<div class="custom-modal" id="configModal">
  <div class="custom-modal-content">
    <h3><span class="material-icons">settings</span> Thiết Lập Thiết Bị</h3>
    <input type="hidden" id="edit_id">
    <div class="form-group">
      <label>Tên Thiết Bị:</label>
      <input type="text" id="edit_name">
    </div>
    <div class="form-group">
      <label>Mã Kết Nối IoT (Device Code):</label>
      <input type="text" id="edit_code">
    </div>
    <div class="form-group">
      <label>Địa Chỉ IP Đang Liên Kết:</label>
      <input type="text" id="edit_ip">
    </div>
    <div class="modal-btns">
      <button class="btn-save" onclick="saveConfig()">Lưu Cấu Hình</button>
      <button class="btn-cancel" onclick="closeModal()">Hủy</button>
    </div>
  </div>
</div>

<script>
function loadDevices() {
    fetch(`/myweb/api/iot_status.php?action=get_devices`)
        .then(res => res.json())
        .then(data => {
            let html = '';
            data.forEach(dev => {
                let statusText = 'MẤT KẾT NỐI (OFFLINE)';
                let displayStatus = dev.display_status;

                if (displayStatus === 'ON') statusText = '🟢 ĐANG HOẠT ĐỘNG';
                else if (displayStatus === 'OFF') statusText = '🔴 ĐANG TẠM DỪNG';
                else if (displayStatus === 'ERROR') statusText = '🟡 GẶP LỖI SYSTEM';
                else if (displayStatus === 'OFFLINE') statusText = '⚪ MẤT KẾT NỐI (OFFLINE)';

                html += `
                <div class="device-card status-${displayStatus}">
                    <div class="card-top">
                        <div class="dev-name">${dev.device_name}</div>
                        <button class="settings-btn" onclick="openModal(${dev.id}, '${dev.device_name}', '${dev.device_code}', '${dev.ip_address}')">
                            <span class="material-icons">settings</span>
                        </button>
                    </div>
                    <div class="info-group">
                        <div class="info-row">Mã IoT: <span>${dev.device_code}</span></div>
                        <div class="info-row">Địa chỉ IP: <span>${dev.ip_address || 'N/A'}</span></div>
                    </div>
                    <div class="status-badge badge-${displayStatus}">${statusText}</div>
                    <div class="note-box"><span class="material-icons" style="font-size:12px;">description</span> ${dev.note}</div>
                </div>`;
            });
            document.getElementById('deviceGrid').innerHTML = html;
        });
}

setInterval(loadDevices, 1000);
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

    fetch(`/myweb/api/iot_status.php?action=save_config&id=${id}&device_name=${name}&device_code=${code}&ip_address=${ip}`)
        .then(res => res.json())
        .then(res => {
            if(res.status === 'success') {
                closeModal();
                loadDevices();
            } else {
                alert(res.message);
            }
        });
}
</script>