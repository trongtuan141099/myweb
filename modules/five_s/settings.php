<?php
// modules/five_s/settings.php
if (!defined('INDEX_AUTH')) { define('INDEX_AUTH', true); }
$currentMonth = date('Y-m');
?>

<style>
/* Module-specific styles for 5S Settings */
.assignment-timeline { display: grid; gap: .75rem; }
.assignment-timeline-item { 
    position: relative; 
    border-left: 3px solid var(--dx-primary); 
    padding: .75rem .75rem .75rem 1rem; 
    background: var(--dx-bg-main);
    border-radius: 0 var(--dx-radius-sm) var(--dx-radius-sm) 0;
    border: 1px solid var(--dx-border);
    border-left: 3px solid var(--dx-primary);
}
.assignment-timeline-item::before { 
    content: ''; 
    position: absolute; 
    left: -7px; 
    top: 1rem; 
    width: 11px; 
    height: 11px; 
    border-radius: 50%; 
    background: var(--dx-primary); 
    border: 2px solid #fff; 
}
.assignment-progress { height: 6px; }

.zone-list-btn {
    width: 100%;
    text-align: left;
    padding: 12px 16px;
    border: none;
    border-bottom: 1px solid var(--dx-border);
    background: var(--dx-bg-card);
    color: var(--dx-text-main);
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background-color 0.15s ease;
}
.zone-list-btn:hover {
    background-color: var(--dx-bg-hover);
}
.zone-list-btn:last-child {
    border-bottom: none;
}
</style>

<div class="app-page-wrapper">
    <!-- Header -->
    <div class="app-page-header">
        <div>
            <h1 class="app-page-title">
                <span class="material-icons">tune</span>
                Thiết Lập Hệ Thống 5S
            </h1>
            <p class="app-page-subtitle">Quản lý danh mục khu vực, ảnh chuẩn đối sánh và phân công kiểm tra định kỳ</p>
        </div>
        <div class="app-page-actions">
            <button class="app-btn app-btn-primary" type="button" onclick="newZone()">
                <span class="material-icons">add</span> Thêm khu vực
            </button>
        </div>
    </div>

    <!-- Danh sách khu vực & Form -->
    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-7">
            <div class="app-card h-100">
                <div class="p-3 border-bottom fw-bold d-flex align-items-center gap-2">
                    <span class="material-icons text-primary fs-5">domain</span> Danh sách khu vực
                </div>
                <div id="zone-list" style="max-height: 420px; overflow-y: auto;"></div>
            </div>
        </div>
        <div class="col-12 col-xl-5">
            <div class="app-card">
                <div class="p-3 border-bottom fw-bold d-flex align-items-center gap-2" id="zone-form-title">
                    <span class="material-icons text-primary fs-5">edit_location</span> Khu vực mới
                </div>
                <div class="p-3">
                    <form id="zone-form" onsubmit="saveZone(event)">
                        <input type="hidden" name="zone_id" id="zone_id">
                        <div class="row g-2 mb-2">
                            <div class="col-5">
                                <label class="form-label small fw-bold">Mã khu vực</label>
                                <input name="zone_code" id="zone_code" class="app-form-control" placeholder="VD: ZONE-01" required>
                            </div>
                            <div class="col-7">
                                <label class="form-label small fw-bold">Tên khu vực</label>
                                <input name="zone_name" id="zone_name" class="app-form-control" placeholder="VD: Xưởng Đùn 1" required>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Mã QR/NFC</label>
                            <input name="qr_code" id="qr_code" class="app-form-control" placeholder="Mặc định dùng mã khu vực">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Ảnh layout</label>
                            <input name="layout_image" id="layout_image" class="app-form-control" value="resources/images/factory_layout.png">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Mô tả</label>
                            <textarea name="description" id="description" class="app-form-control" rows="2" placeholder="Ghi chú vị trí hoặc quy định cụ thể..."></textarea>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold text-success">Ảnh mẫu OK</label>
                                <input type="file" name="ok_reference_image" class="app-form-control" accept="image/*" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-danger">Ảnh mẫu NG</label>
                                <input type="file" name="ng_reference_image" class="app-form-control" accept="image/*" required>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="app-btn app-btn-primary" type="submit">
                                <span class="material-icons">save</span> Lưu khu vực
                            </button>
                            <button class="app-btn app-btn-secondary" type="button" onclick="newZone()">Hủy</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Phân công kiểm tra -->
    <div class="app-card mb-3">
        <div class="p-3 border-bottom fw-bold d-flex align-items-center gap-2">
            <span class="material-icons text-primary fs-5">event_available</span> Phân công kiểm tra theo tháng
        </div>
        <div class="p-3">
            <form id="assignment-form" class="row g-2 align-items-end" onsubmit="saveAssignment(event)">
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-bold">Tháng</label>
                    <input class="app-form-control" type="month" name="month_year" id="assignment-month" value="<?php echo $currentMonth; ?>" onchange="loadAssignmentTimeline()" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-bold">Khu vực</label>
                    <select class="app-form-control" name="zone_id" id="assign-zone" required></select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-bold">Người kiểm tra</label>
                    <select class="app-form-control" name="inspector_id" id="assign-inspector" required></select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-bold">Người chịu trách nhiệm</label>
                    <select class="app-form-control" name="assignee_id" id="assign-assignee" required></select>
                </div>
                <div class="col-12 mt-3">
                    <button class="app-btn app-btn-primary" type="submit">
                        <span class="material-icons">assignment_turned_in</span> Lưu phân công
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Timeline phân công -->
    <div class="app-card">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
            <span class="fw-bold d-flex align-items-center gap-2">
                <span class="material-icons text-primary fs-5">history</span> Timeline nhân viên đã phân công
            </span>
            <span class="small text-muted" id="timeline-month"></span>
        </div>
        <div class="p-3">
            <div id="assignment-timeline" class="assignment-timeline">
                <div class="text-muted p-2">Đang tải dữ liệu phân công...</div>
            </div>
        </div>
    </div>
</div>

<script>
let zoneData = [], userData = [];
document.addEventListener('DOMContentLoaded', loadSettings);

function loadSettings() { 
    fetch('api/five_s_get_dashboard.php')
        .then(r => r.json())
        .then(data => { 
            zoneData = data.zones || []; 
            userData = data.users || []; 
            renderZones(); 
            fillOptions(); 
            loadAssignmentTimeline(); 
        })
        .catch(err => console.error('Lỗi load settings:', err)); 
}

function renderZones() { 
    const list = document.getElementById('zone-list'); 
    list.innerHTML = zoneData.length ? '' : '<div class="p-3 text-muted">Chưa có khu vực nào.</div>'; 
    zoneData.forEach(zone => { 
        const row = document.createElement('button'); 
        row.type = 'button'; 
        row.className = 'zone-list-btn'; 
        row.innerHTML = `<span><strong>${escapeText(zone.zone_name)}</strong><small class="d-block text-muted font-monospace">${escapeText(zone.zone_code)}</small></span><span class="material-icons text-muted fs-6">edit</span>`; 
        row.onclick = () => editZone(zone); 
        list.appendChild(row); 
    }); 
}

function fillOptions() { 
    document.getElementById('assign-zone').innerHTML = zoneData.map(z => `<option value="${z.id}">${escapeText(z.zone_name)} (${escapeText(z.zone_code)})</option>`).join(''); 
    const options = userData.map(u => `<option value="${u.id}">${escapeText(u.fullname || u.username)}</option>`).join(''); 
    document.getElementById('assign-inspector').innerHTML = options; 
    document.getElementById('assign-assignee').innerHTML = options; 
}

function newZone() { 
    document.getElementById('zone-form').reset(); 
    document.getElementById('zone_id').value = ''; 
    document.getElementById('layout_image').value = 'resources/images/factory_layout.png'; 
    document.getElementById('zone-form-title').innerHTML = '<span class="material-icons text-primary fs-5">edit_location</span> Khu vực mới'; 
    document.querySelectorAll('#zone-form input[type=file]').forEach(input => input.required = true); 
}

function editZone(zone) { 
    document.getElementById('zone_id').value = zone.id; 
    document.getElementById('zone_code').value = zone.zone_code || ''; 
    document.getElementById('zone_name').value = zone.zone_name || ''; 
    document.getElementById('qr_code').value = zone.qr_code_hash || ''; 
    document.getElementById('layout_image').value = zone.layout_image || ''; 
    document.getElementById('description').value = zone.description || ''; 
    document.getElementById('zone-form-title').innerHTML = '<span class="material-icons text-primary fs-5">edit_location</span> Chỉnh sửa khu vực: ' + escapeText(zone.zone_code); 
    document.querySelectorAll('#zone-form input[type=file]').forEach(input => input.required = false); 
}

function saveZone(event) { 
    event.preventDefault(); 
    fetch('api/five_s_save_zone.php', { method: 'POST', body: new FormData(document.getElementById('zone-form')) })
        .then(r => r.json())
        .then(data => { 
            alert(data.message); 
            if (data.success) { 
                loadSettings(); 
                newZone(); 
            } 
        }); 
}

function saveAssignment(event) { 
    event.preventDefault(); 
    fetch('api/five_s_save_assignment.php', { method: 'POST', body: new FormData(document.getElementById('assignment-form')) })
        .then(r => r.json())
        .then(data => { 
            alert(data.message); 
            if (data.success) loadAssignmentTimeline(); 
        }); 
}

function loadAssignmentTimeline() { 
    const month = document.getElementById('assignment-month').value; 
    fetch(`api/five_s_get_assignments.php?month=${encodeURIComponent(month)}`)
        .then(r => r.json())
        .then(data => { 
            document.getElementById('timeline-month').textContent = data.month || month; 
            renderAssignmentTimeline(data.assignments || []); 
        }); 
}

function renderAssignmentTimeline(assignments) { 
    const timeline = document.getElementById('assignment-timeline'); 
    if (!assignments.length) { 
        timeline.innerHTML = '<div class="text-muted p-3">Chưa có phân công trong tháng này.</div>'; 
        return; 
    } 
    timeline.innerHTML = assignments.map(item => { 
        const total = Number(item.total_days) || 0; 
        const completed = Number(item.completed_days) || 0; 
        const percent = total ? Math.round(completed * 100 / total) : 0; 
        return `
        <div class="assignment-timeline-item">
            <div class="d-flex justify-content-between gap-2 flex-wrap mb-1">
                <strong>${escapeText(item.zone_name)} (${escapeText(item.zone_code)})</strong>
                <span class="app-badge badge-info">${escapeText(item.month_year)}</span>
            </div>
            <div class="small text-muted mb-1">
                Kiểm tra: <strong class="text-dark">${escapeText(item.inspector_name || item.inspector_username || 'Chưa gán')}</strong> · 
                Chịu trách nhiệm: <strong class="text-dark">${escapeText(item.assignee_name || item.assignee_username || 'Chưa gán')}</strong>
            </div>
            <div class="progress assignment-progress mt-2" style="background:#e2e8f0; height: 6px; border-radius: 3px;">
                <div class="progress-bar bg-success" style="width: ${percent}%; border-radius: 3px;"></div>
            </div>
            <div class="small text-muted mt-1 font-monospace">${completed}/${total} ngày hoàn thành (${percent}%) · ${escapeText(item.first_date || '')} - ${escapeText(item.last_date || '')}</div>
        </div>`; 
    }).join(''); 
}

function escapeText(value) { 
    const node = document.createElement('span'); 
    node.textContent = value || ''; 
    return node.innerHTML; 
}
</script>
