<?php
// modules/five_s/mobile_audit.php
if (!defined('INDEX_AUTH')) { define('INDEX_AUTH', true); }
?>

<style>
/* Module-specific styles for Mobile 5S Audit */
.audit-max-width {
    max-width: 820px;
    margin: 0 auto;
    width: 100%;
}
.schedule-toolbar { position: sticky; top: 0; z-index: 2; background: var(--dx-bg-main); }
.schedule-section-title { font-size: .78rem; letter-spacing: .04em; text-transform: uppercase; }
.schedule-locked { background: #f8fafc; color: var(--dx-text-muted); opacity: 0.85; }
.reference-image { display: block; width: 100%; max-height: 180px; object-fit: contain; border-radius: 4px; background: var(--dx-bg-main); }
.schedule-filter-slider { display: flex; gap: .5rem; overflow-x: auto; padding-bottom: .25rem; scrollbar-width: thin; }
.schedule-filter-slider .app-btn { flex: 0 0 auto; white-space: nowrap; font-size: 12px; }

.schedule-list-card {
    background: var(--dx-card-bg);
    border: 1px solid var(--dx-border);
    border-radius: var(--dx-radius-md);
    overflow: hidden;
}
.schedule-item-row {
    padding: 14px 16px;
    border-bottom: 1px solid var(--dx-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    transition: background-color 0.15s ease;
}
.schedule-item-row:last-child {
    border-bottom: none;
}
.schedule-item-row:hover {
    background-color: var(--dx-bg-hover);
}

@media (max-width: 575.98px) { 
    .schedule-item-row { align-items: flex-start !important; flex-direction: column; } 
    .schedule-item-row .app-btn, .schedule-item-row .app-badge { align-self: stretch; text-align: center; justify-content: center; } 
}
</style>

<div class="app-page-wrapper">
    <div class="audit-max-width">
        <!-- Header -->
        <div class="app-page-header">
            <div class="app-page-title">
                <span class="material-icons text-primary">fact_check</span>
                <div>
                    <h1 style="font-size: 18px; margin: 0;">CÔNG VIỆC KIỂM TRA 5S</h1>
                    <p class="text-muted small mb-0">Chọn lịch hôm nay, xác thực vị trí QR/NFC và nộp kết quả</p>
                </div>
            </div>
            <div class="app-page-actions">
                <button class="app-btn app-btn-secondary" type="button" onclick="loadSchedules()" title="Tải lại">
                    <span class="material-icons">refresh</span>
                </button>
            </div>
        </div>

        <!-- KPI Counts -->
        <div class="row g-2 mb-3">
            <div class="col-6 col-sm-3">
                <div class="app-stat-card py-2 px-3">
                    <div class="app-stat-label">Cần làm hôm nay</div>
                    <div class="h4 mb-0 text-primary" id="today-count">0</div>
                </div>
            </div>
            <div class="col-6 col-sm-3">
                <div class="app-stat-card py-2 px-3">
                    <div class="app-stat-label">Chờ thực hiện</div>
                    <div class="h4 mb-0 text-warning" id="upcoming-count">0</div>
                </div>
            </div>
            <div class="col-6 col-sm-3">
                <div class="app-stat-card py-2 px-3">
                    <div class="app-stat-label">Đã hoàn thành</div>
                    <div class="h4 mb-0 text-success" id="completed-count">0</div>
                </div>
            </div>
            <div class="col-6 col-sm-3">
                <div class="app-stat-card py-2 px-3">
                    <div class="app-stat-label">Quá hạn</div>
                    <div class="h4 mb-0 text-muted" id="expired-count">0</div>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="schedule-toolbar pb-2">
            <div id="notification-box" class="alert alert-warning d-none"></div>
            <div class="row g-2">
                <div class="col-12 col-sm-7">
                    <input id="schedule-search" class="app-form-control" type="search" placeholder="Tìm khu vực, mã thiết bị..." oninput="renderSchedules()">
                </div>
                <div class="col-12 col-sm-5">
                    <input id="schedule-date-filter" class="app-form-control" type="date" onchange="renderSchedules()">
                </div>
            </div>
            <div class="schedule-filter-slider mt-2" role="tablist" aria-label="Lọc nhanh công việc">
                <button type="button" class="app-btn app-btn-primary" data-filter="today" onclick="setQuickFilter(this)">Hôm nay</button>
                <button type="button" class="app-btn app-btn-secondary" data-filter="upcoming" onclick="setQuickFilter(this)">Vài ngày tới</button>
                <button type="button" class="app-btn app-btn-secondary" data-filter="completed" onclick="setQuickFilter(this)">Đã hoàn thành</button>
                <button type="button" class="app-btn app-btn-secondary" data-filter="expired" onclick="setQuickFilter(this)">Quá hạn</button>
                <button type="button" class="app-btn app-btn-secondary" data-filter="all" onclick="setQuickFilter(this)">Tất cả</button>
            </div>
        </div>

        <!-- Schedule Items -->
        <div id="schedule-list" class="schedule-list-card mt-2"></div>
        <div id="empty-state" class="text-center text-muted py-5 d-none">Chưa có công việc kiểm tra 5S được phân công.</div>
    </div>
</div>

<!-- Modal Audit -->
<div class="modal fade" id="auditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                        <span class="material-icons text-primary">qr_code_scanner</span> Kiểm tra <span id="audit-zone-name"></span>
                    </h5>
                    <small class="text-muted">Bắt buộc xác thực mã QR/NFC tại khu vực thực tế</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="audit-form" onsubmit="submitAudit(event)">
                <input type="hidden" name="schedule_id" id="schedule_id">
                <input type="hidden" name="zone_id" id="zone_id">
                <input type="hidden" name="qr_code" id="qr_code">
                
                <div class="modal-body">
                    <div id="verify-panel" class="p-3 bg-light border rounded mb-3">
                        <label for="scan-code" class="form-label fw-bold">Mã QR/NFC khu vực</label>
                        <div class="input-group">
                            <input id="scan-code" class="app-form-control border-end-0" autocomplete="off" required placeholder="Quét hoặc nhập mã khu vực...">
                            <button class="app-btn app-btn-primary" type="button" onclick="verifyLocation()">Xác nhận</button>
                        </div>
                        <div id="verify-message" class="small mt-2"></div>
                    </div>
                    
                    <div id="audit-panel" class="d-none">
                        <div class="row g-3 mb-3" id="reference-images">
                            <div class="col-md-6">
                                <div class="border rounded p-2 h-100">
                                    <div class="fw-bold text-success mb-2 d-flex align-items-center gap-1">
                                        <span class="material-icons fs-6">check_circle</span> Hình mẫu OK
                                    </div>
                                    <img id="ok-reference-image" class="reference-image d-none" alt="Hình mẫu khu vực đạt chuẩn OK">
                                    <div id="ok-reference-empty" class="small text-muted">Chưa cấu hình hình mẫu OK cho khu vực này.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-2 h-100">
                                    <div class="fw-bold text-danger mb-2 d-flex align-items-center gap-1">
                                        <span class="material-icons fs-6">cancel</span> Hình mẫu NG
                                    </div>
                                    <img id="ng-reference-image" class="reference-image d-none" alt="Hình mẫu khu vực có lỗi NG">
                                    <div id="ng-reference-empty" class="small text-muted">Chưa cấu hình hình mẫu NG cho khu vực này.</div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-2 text-center mb-3">
                            <div class="col-6"><div class="border rounded p-2" style="background: #f0fdf4;"><b class="text-success">OK</b><div class="small text-muted">Khu vực sạch, đúng vị trí, đủ checklist</div></div></div>
                            <div class="col-6"><div class="border rounded p-2" style="background: #fef2f2;"><b class="text-danger">NG</b><div class="small text-muted">Có lỗi cần ghi nhận và xử lý</div></div></div>
                        </div>
                        
                        <div class="border rounded p-3 mb-3 bg-white">
                            <div class="fw-bold mb-2">Checklist hạng mục</div>
                            <label class="d-block mb-2"><input type="checkbox" class="check-item me-2" value="S1"> Sàng lọc: không có vật dụng không cần thiết</label>
                            <label class="d-block mb-2"><input type="checkbox" class="check-item me-2" value="S2"> Sắp xếp: vật dụng đúng vị trí</label>
                            <label class="d-block mb-2"><input type="checkbox" class="check-item me-2" value="S3"> Sạch sẽ: khu vực không có rác/bẩn</label>
                            <label class="d-block"><input type="checkbox" class="check-item me-2" value="S4"> Săn sóc: tiêu chuẩn được duy trì</label>
                        </div>
                        
                        <div class="mb-3 text-center">
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="audit_result" id="result-ok" value="OK" checked onchange="toggleResult()">
                                <label class="btn btn-outline-success px-4" for="result-ok">ĐẠT (OK)</label>
                                
                                <input type="radio" class="btn-check" name="audit_result" id="result-ng" value="NG" onchange="toggleResult()">
                                <label class="btn btn-outline-danger px-4" for="result-ng">VI PHẠM (NG)</label>
                            </div>
                        </div>
                        
                        <div id="ng-fields" class="d-none border-top pt-3 mb-3">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Hạng mục lỗi</label>
                                    <select name="s_category" class="app-form-control">
                                        <option value="S1">S1 - Sàng lọc</option>
                                        <option value="S2">S2 - Sắp xếp</option>
                                        <option value="S3">S3 - Sạch sẽ</option>
                                        <option value="S4">S4 - Săn sóc</option>
                                        <option value="S5">S5 - Sẵn sàng</option>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-bold">Mô tả vi phạm <span class="text-danger">*</span></label>
                                    <textarea name="description" id="description" class="app-form-control" rows="2" placeholder="Ghi nhận cụ thể tình trạng không đạt..."></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <label class="form-label fw-bold" for="audit-image">Ảnh thực tế <span class="text-danger">*</span></label>
                        <input type="file" name="actual_image" id="audit-image" class="app-form-control" accept="image/*" capture="environment" required>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="submit" id="submit-button" class="app-btn app-btn-primary d-none">
                        <span class="material-icons">send</span> Gửi kết quả
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let auditModal;
let schedules = [];
let quickFilter = 'today';
document.addEventListener('DOMContentLoaded', loadSchedules);

function loadSchedules() {
    fetch('api/five_s_get_schedules.php')
        .then(response => response.json())
        .then(data => {
            const notice = document.getElementById('notification-box');
            schedules = data.schedules || [];
            notice.classList.toggle('d-none', !data.notifications || data.notifications.length === 0);
            
            if (data.notifications && data.notifications.length) {
                notice.textContent = data.notifications[0].message;
            }
            updateScheduleCounts();
            renderSchedules();
        })
        .catch(err => console.error('Lỗi nạp lịch tuần tra:', err));
}

function renderSchedules() {
    const list = document.getElementById('schedule-list');
    const empty = document.getElementById('empty-state');
    const dateFilter = document.getElementById('schedule-date-filter').value;
    const search = document.getElementById('schedule-search').value.trim().toLowerCase();
    list.innerHTML = '';
    const filtered = schedules.filter(schedule => {
        const state = schedule.work_state;
        const matchesFilter = quickFilter === 'all' || (quickFilter === 'today' && Number(schedule.can_audit) === 1) || state === quickFilter;
        const matchesDate = !dateFilter || schedule.schedule_date === dateFilter;
        const haystack = `${schedule.zone_name} ${schedule.zone_code}`.toLowerCase();
        return matchesFilter && matchesDate && haystack.includes(search);
    });
    empty.classList.toggle('d-none', filtered.length > 0);
    filtered.forEach(schedule => {
        const row = document.createElement('div'); 
        const canAudit = Number(schedule.can_audit) === 1;
        const statusLabel = schedule.work_state === 'completed' ? 'Đã hoàn thành' : schedule.work_state === 'upcoming' ? 'Chờ thực hiện' : schedule.work_state === 'expired' ? 'Đã khóa quá hạn' : 'Cần làm hôm nay';
        const statusBadgeClass = schedule.work_state === 'completed' ? 'badge-success' : schedule.work_state === 'expired' ? 'badge-danger' : canAudit ? 'badge-warning' : 'badge-info';
        row.className = `schedule-item-row ${canAudit || schedule.work_state === 'completed' ? '' : 'schedule-locked'}`;
        row.innerHTML = `<div><strong>${escapeHtml(schedule.zone_name)}</strong><div class="small text-muted">${escapeHtml(schedule.zone_code)} · ${formatScheduleDate(schedule.schedule_date)}</div></div>`;
        
        if (canAudit) { 
            const button = document.createElement('button'); 
            button.className = 'app-btn app-btn-primary py-1 px-3'; 
            button.innerHTML = '<span class="material-icons fs-6">qr_code</span> Kiểm tra ngay'; 
            button.onclick = () => openAudit(schedule); 
            row.appendChild(button); 
        } else {
            const badge = document.createElement('span');
            badge.className = `app-badge ${statusBadgeClass}`;
            badge.textContent = statusLabel;
            row.appendChild(badge);
        }
        list.appendChild(row);
    });
}

function setQuickFilter(button) {
    quickFilter = button.dataset.filter;
    document.querySelectorAll('.schedule-filter-slider .app-btn').forEach(item => {
        if (item === button) {
            item.className = 'app-btn app-btn-primary';
        } else {
            item.className = 'app-btn app-btn-secondary';
        }
    });
    renderSchedules();
}

function updateScheduleCounts() {
    const counts = schedules.reduce((result, schedule) => {
        const state = schedule.work_state;
        if (Number(schedule.can_audit) === 1) result.today += 1;
        if (state === 'upcoming') result.upcoming += 1;
        if (state === 'completed') result.completed += 1;
        if (state === 'expired') result.expired += 1;
        return result;
    }, { today: 0, upcoming: 0, completed: 0, expired: 0 });
    document.getElementById('today-count').textContent = counts.today;
    document.getElementById('upcoming-count').textContent = counts.upcoming;
    document.getElementById('completed-count').textContent = counts.completed;
    document.getElementById('expired-count').textContent = counts.expired;
}

function openAudit(schedule) {
    document.getElementById('audit-form').reset(); 
    document.getElementById('schedule_id').value = schedule.id; 
    document.getElementById('zone_id').value = schedule.zone_id; 
    document.getElementById('audit-zone-name').textContent = schedule.zone_name;
    setReferenceImage('ok-reference-image', 'ok-reference-empty', schedule.ok_reference_image);
    setReferenceImage('ng-reference-image', 'ng-reference-empty', schedule.ng_reference_image);
    
    document.getElementById('verify-panel').classList.remove('d-none'); 
    document.getElementById('audit-panel').classList.add('d-none'); 
    document.getElementById('submit-button').classList.add('d-none'); 
    document.getElementById('verify-message').textContent = '';
    
    toggleResult();
    
    auditModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('auditModal')); 
    auditModal.show();
}

function verifyLocation() {
    const code = document.getElementById('scan-code').value.trim(); 
    if (!code) {
        alert('Vui lòng nhập hoặc quét mã QR/NFC');
        return;
    }
    
    const formData = new FormData(); 
    formData.append('zone_id', document.getElementById('zone_id').value); 
    formData.append('qr_code', code);
    
    fetch('api/five_s_verify_location.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            const message = document.getElementById('verify-message'); 
            message.textContent = data.message; 
            message.className = `small mt-2 ${data.success ? 'text-success' : 'text-danger'}`;
            
            if (data.success) { 
                document.getElementById('qr_code').value = code; 
                document.getElementById('verify-panel').classList.add('d-none'); 
                document.getElementById('audit-panel').classList.remove('d-none'); 
                document.getElementById('submit-button').classList.remove('d-none'); 
            }
        });
}

function toggleResult() {
    const isNg = document.getElementById('result-ng').checked;
    const ngFields = document.getElementById('ng-fields');
    const auditImage = document.getElementById('audit-image');
    const description = document.getElementById('description');

    ngFields.classList.toggle('d-none', !isNg);
    auditImage.name = isNg ? 'before_image' : 'actual_image';
    description.required = isNg;
}

function setReferenceImage(imageId, emptyId, source) {
    const image = document.getElementById(imageId);
    const empty = document.getElementById(emptyId);
    const hasImage = typeof source === 'string' && source.trim() !== '';
    image.classList.toggle('d-none', !hasImage);
    empty.classList.toggle('d-none', hasImage);
    if (hasImage) {
        image.src = source;
    } else {
        image.removeAttribute('src');
    }
}

function formatScheduleDate(value) {
    if (!value) return '';
    const parts = value.split('-');
    return parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : value;
}

function submitAudit(event) {
    event.preventDefault();
    const form = document.getElementById('audit-form'); 
    const checked = [...document.querySelectorAll('.check-item:checked')].map(item => item.value);
    
    if (!checked.length) { 
        alert('Vui lòng hoàn thành ít nhất một mục checklist'); 
        return; 
    }
    
    const formData = new FormData(form); 
    formData.append('checklist_json', JSON.stringify(checked));

    fetch('api/five_s_save_audit_full.php', { method: 'POST', body: formData })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) { 
                auditModal.hide(); 
                loadSchedules(); 
            } else {
                alert(data.message); 
            }
        } catch (e) {
            console.error('Lỗi trả về từ PHP:', text);
            alert('Lỗi Server: ' + text.replace(/<[^>]*>?/gm, '').substring(0, 150));
        }
    })
    .catch(err => {
        console.error('Fetch error:', err);
        alert('Lỗi kết nối máy chủ!');
    });
}

function escapeHtml(value) { 
    const div = document.createElement('div'); 
    div.textContent = value; 
    return div.innerHTML; 
}
</script>