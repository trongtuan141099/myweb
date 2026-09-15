<?php
if (!defined('INDEX_AUTH')) { define('INDEX_AUTH', true); }
?>

<div class="container-fluid py-3" style="max-width: 760px; margin: 0 auto;">
    <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-clipboard-check me-2 text-primary"></i>Công việc kiểm tra 5S</h4>
            <p class="text-muted small mb-0">Chọn lịch hôm nay, xác thực vị trí rồi gửi kết quả.</p>
        </div>
        <button class="btn btn-outline-primary btn-sm" type="button" onclick="loadSchedules()"><i class="bi bi-arrow-clockwise"></i></button>
    </div>

    <div id="notification-box" class="alert alert-warning d-none"></div>
    <div id="schedule-list" class="list-group shadow-sm"></div>
    <div id="empty-state" class="text-center text-muted py-5 d-none">Hôm nay không có lịch tuần tra được phân công.</div>
</div>

<div class="modal fade" id="auditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div><h5 class="modal-title fw-bold">Kiểm tra <span id="audit-zone-name"></span></h5><small class="text-muted">Bắt buộc xác thực QR/NFC tại khu vực</small></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="audit-form" onsubmit="submitAudit(event)">
                <input type="hidden" name="schedule_id" id="schedule_id"><input type="hidden" name="zone_id" id="zone_id"><input type="hidden" name="qr_code" id="qr_code">
                <div class="modal-body">
                    <div id="verify-panel" class="p-3 bg-light border rounded mb-3">
                        <label for="scan-code" class="form-label fw-bold">Mã QR/NFC khu vực</label>
                        <div class="input-group"><input id="scan-code" class="form-control" autocomplete="off" required><button class="btn btn-primary" type="button" onclick="verifyLocation()">Xác nhận</button></div>
                        <div id="verify-message" class="small mt-2"></div>
                    </div>
                    <div id="audit-panel" class="d-none">
                        <div class="row g-2 text-center mb-3">
                            <div class="col-6"><div class="border rounded p-2 bg-success-subtle"><b class="text-success">Chuẩn OK</b><div class="small">Khu vực sạch, đúng vị trí, đủ checklist</div></div></div>
                            <div class="col-6"><div class="border rounded p-2 bg-danger-subtle"><b class="text-danger">Chuẩn NG</b><div class="small">Có lỗi cần ghi nhận và xử lý</div></div></div>
                        </div>
                        <div class="border rounded p-3 mb-3">
                            <div class="fw-bold mb-2">Checklist hạng mục</div>
                            <label class="d-block mb-2"><input type="checkbox" class="check-item me-2" value="S1"> Sàng lọc: không có vật dụng không cần thiết</label>
                            <label class="d-block mb-2"><input type="checkbox" class="check-item me-2" value="S2"> Sắp xếp: vật dụng đúng vị trí</label>
                            <label class="d-block mb-2"><input type="checkbox" class="check-item me-2" value="S3"> Sạch sẽ: khu vực không có rác/bẩn</label>
                            <label class="d-block"><input type="checkbox" class="check-item me-2" value="S4"> Săn sóc: tiêu chuẩn được duy trì</label>
                        </div>
                        <div class="mb-3 text-center"><div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="audit_result" id="result-ok" value="OK" checked onchange="toggleResult()"><label class="btn btn-outline-success" for="result-ok">ĐẠT (OK)</label>
                            <input type="radio" class="btn-check" name="audit_result" id="result-ng" value="NG" onchange="toggleResult()"><label class="btn btn-outline-danger" for="result-ng">VI PHẠM (NG)</label>
                        </div></div>
                        <div id="ng-fields" class="d-none border-top pt-3 mb-3"><div class="row g-2">
                            <div class="col-md-5"><label class="form-label">Hạng mục lỗi</label><select name="s_category" class="form-select"><option>S1</option><option>S2</option><option>S3</option><option>S4</option><option>S5</option></select></div>
                            <div class="col-md-7"><label class="form-label">Mô tả vi phạm</label><textarea name="description" id="description" class="form-control" rows="2"></textarea></div>
                        </div></div>
                        <label class="form-label fw-bold" for="audit-image">Ảnh thực tế <span class="text-danger">*</span></label>
                        <input type="file" name="actual_image" id="audit-image" class="form-control" accept="image/*" capture="environment" required>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" id="submit-button" class="btn btn-primary d-none">Gửi kết quả</button></div>
            </form>
        </div>
    </div>
</div>

<script>
let auditModal;
document.addEventListener('DOMContentLoaded', loadSchedules);

function loadSchedules() {
    fetch('api/five_s_get_schedules.php').then(response => response.json()).then(data => {
        const list = document.getElementById('schedule-list'); const empty = document.getElementById('empty-state'); const notice = document.getElementById('notification-box');
        list.innerHTML = ''; notice.classList.toggle('d-none', !data.notifications || data.notifications.length === 0);
        if (data.notifications && data.notifications.length) notice.textContent = data.notifications[0].message;
        empty.classList.toggle('d-none', data.schedules && data.schedules.length > 0);
        (data.schedules || []).forEach(schedule => {
            const row = document.createElement('div'); row.className = 'list-group-item d-flex justify-content-between align-items-center gap-3';
            row.innerHTML = `<div><strong>${escapeHtml(schedule.zone_name)}</strong><div class="small text-muted">${escapeHtml(schedule.zone_code)} · ${schedule.schedule_date}</div></div>`;
            if (schedule.status === 'completed') row.innerHTML += '<span class="badge bg-success">Đã hoàn thành</span>'; else { const button = document.createElement('button'); button.className = 'btn btn-primary btn-sm'; button.textContent = 'Kiểm tra ngay'; button.onclick = () => openAudit(schedule); row.appendChild(button); }
            list.appendChild(row);
        });
    });
}

function openAudit(schedule) {
    document.getElementById('audit-form').reset(); document.getElementById('schedule_id').value = schedule.id; document.getElementById('zone_id').value = schedule.zone_id; document.getElementById('audit-zone-name').textContent = schedule.zone_name;
    document.getElementById('verify-panel').classList.remove('d-none'); document.getElementById('audit-panel').classList.add('d-none'); document.getElementById('submit-button').classList.add('d-none'); document.getElementById('verify-message').textContent = '';
    auditModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('auditModal')); auditModal.show();
}

function verifyLocation() {
    const code = document.getElementById('scan-code').value.trim(); const formData = new FormData(); formData.append('zone_id', document.getElementById('zone_id').value); formData.append('qr_code', code);
    fetch('api/five_s_verify_location.php', { method: 'POST', body: formData }).then(response => response.json()).then(data => {
        const message = document.getElementById('verify-message'); message.textContent = data.message; message.className = `small mt-2 ${data.success ? 'text-success' : 'text-danger'}`;
        if (data.success) { document.getElementById('qr_code').value = code; document.getElementById('verify-panel').classList.add('d-none'); document.getElementById('audit-panel').classList.remove('d-none'); document.getElementById('submit-button').classList.remove('d-none'); }
    });
}

function toggleResult() { const isNg = document.getElementById('result-ng').checked; document.getElementById('ng-fields').classList.toggle('d-none', !isNg); document.getElementById('audit-image').name = isNg ? 'before_image' : 'actual_image'; document.getElementById('description').required = isNg; }
function submitAudit(event) {
    event.preventDefault(); const form = document.getElementById('audit-form'); const checked = [...document.querySelectorAll('.check-item:checked')].map(item => item.value);
    if (!checked.length) { alert('Vui lòng hoàn thành ít nhất một mục checklist'); return; }
    const formData = new FormData(form); formData.append('checklist_json', JSON.stringify(checked));
    fetch('api/five_s_save_audit_full.php', { method: 'POST', body: formData }).then(response => response.json()).then(data => { if (data.success) { auditModal.hide(); loadSchedules(); } else alert(data.message); });
}
function escapeHtml(value) { const div = document.createElement('div'); div.textContent = value; return div.innerHTML; }
</script>
