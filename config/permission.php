<?php
// config/permission.php
// Cấu hình phân quyền hệ thống DX Plastic Group

return array (
  'roles_map' => 
  array (
    'admin' => 
    array (
      0 => 'dashboard.view',
      1 => 'document.view',
      2 => 'document.upload',
      3 => 'document.edit',
      4 => 'document.delete',
      5 => 'materials.view',
      6 => 'materials.edit',
      7 => 'production.view',
      8 => 'production.plan',
      9 => 'production.data',
      10 => 'mixer.view',
      11 => 'mixer.edit',
      12 => 'five_s.view',
      13 => 'five_s.audit',
      14 => 'five_s.settings',
      15 => 'five_s.proposals',
      16 => 'device.view',
      17 => 'device.edit',
      18 => 'device.history',
      19 => 'hrm.view',
      20 => 'hrm.manage',
      21 => 'role.manage',
      22 => 'api.document.get',
      23 => 'api.document.upload',
      24 => 'api.document.edit',
      25 => 'api.document.delete',
      26 => 'api.mixer.get',
      27 => 'api.mixer.save',
      28 => 'api.mixer.delete',
      29 => 'api.mixer.import',
      30 => 'api.mixer.export',
      31 => 'api.mixer.summary',
      32 => 'api.production.report',
      33 => 'api.production.plan_save',
      34 => 'api.production.plan_upload',
      35 => 'api.production.extrusion_get',
      36 => 'api.production.extrusion_save_row',
      37 => 'api.production.extrusion_upload',
      38 => 'api.production.extrusion_export',
      39 => 'api.production.extrusion_delete',
      40 => 'api.five_s.dashboard',
      41 => 'api.five_s.schedules',
      42 => 'api.five_s.assignments',
      43 => 'api.five_s.save_assignment',
      44 => 'api.five_s.save_audit',
      45 => 'api.five_s.save_audit_full',
      46 => 'api.five_s.save_zone',
      47 => 'api.five_s.verify_location',
      48 => 'api.five_s.resolve_issue',
      49 => 'api.five_s.delete_issue',
      50 => 'api.five_s.get_proposals',
      51 => 'api.five_s.save_proposal',
      52 => 'api.materials.get_viscosity',
      53 => 'api.materials.upload_viscosity',
      54 => 'api.device.iot_status',
      55 => 'api.hrm.add_employee',
      56 => 'api.hrm.inventory_org_chart',
      57 => 'api.system.save_role_permissions',
      58 => 'api.system.check_auth',
    ),
    'editor' => 
    array (
      0 => 'dashboard.view',
      1 => 'document.view',
      2 => 'document.upload',
      3 => 'document.edit',
      4 => 'materials.view',
      5 => 'materials.edit',
      6 => 'production.view',
      7 => 'production.plan',
      8 => 'production.data',
      9 => 'mixer.view',
      10 => 'mixer.edit',
      11 => 'five_s.view',
      12 => 'five_s.audit',
      13 => 'five_s.proposals',
      14 => 'device.view',
      15 => 'device.history',
      16 => 'hrm.view',
      17 => 'hrm.manage',
      18 => 'api.document.get',
      19 => 'api.document.upload',
      20 => 'api.document.edit',
      21 => 'api.mixer.get',
      22 => 'api.mixer.save',
      23 => 'api.mixer.import',
      24 => 'api.mixer.export',
      25 => 'api.mixer.summary',
      26 => 'api.production.report',
      27 => 'api.production.plan_save',
      28 => 'api.production.plan_upload',
      29 => 'api.production.extrusion_get',
      30 => 'api.production.extrusion_save_row',
      31 => 'api.production.extrusion_upload',
      32 => 'api.production.extrusion_export',
      33 => 'api.five_s.dashboard',
      34 => 'api.five_s.schedules',
      35 => 'api.five_s.assignments',
      36 => 'api.five_s.save_assignment',
      37 => 'api.five_s.save_audit',
      38 => 'api.five_s.save_audit_full',
      39 => 'api.five_s.verify_location',
      40 => 'api.five_s.resolve_issue',
      41 => 'api.five_s.get_proposals',
      42 => 'api.five_s.save_proposal',
      43 => 'api.materials.get_viscosity',
      44 => 'api.materials.upload_viscosity',
      45 => 'api.device.iot_status',
      46 => 'api.hrm.add_employee',
      47 => 'api.hrm.inventory_org_chart',
      48 => 'api.system.check_auth',
    ),
    'viewer' => 
    array (
      0 => 'dashboard.view',
      1 => 'document.view',
      2 => 'materials.view',
      3 => 'production.view',
      4 => 'production.plan',
      5 => 'production.data',
      6 => 'mixer.view',
      7 => 'five_s.view',
      8 => 'five_s.audit',
      9 => 'hrm.view',
      10 => 'api.document.get',
      11 => 'api.mixer.get',
      12 => 'api.mixer.export',
      13 => 'api.mixer.summary',
      14 => 'api.production.report',
      15 => 'api.production.extrusion_get',
      16 => 'api.production.extrusion_export',
      17 => 'api.five_s.dashboard',
      18 => 'api.five_s.schedules',
      19 => 'api.five_s.assignments',
      20 => 'api.five_s.save_audit',
      21 => 'api.five_s.get_proposals',
      22 => 'api.materials.get_viscosity',
      23 => 'api.hrm.inventory_org_chart',
      24 => 'api.system.check_auth',
    ),
  ),
  'permission_catalog' => 
  array (
    'dashboard' => 
    array (
      'name' => 'Bảng điều khiển (Dashboard)',
      'icon' => 'dashboard',
      'permissions' => 
      array (
        'dashboard.view' => 'Xem tổng quan năng suất, sản lượng, máy chạy',
      ),
    ),
    'document' => 
    array (
      'name' => 'Tài liệu SOP / Tiêu chuẩn',
      'icon' => 'description',
      'permissions' => 
      array (
        'document.view' => 'Xem và đọc tài liệu PDF',
        'document.upload' => 'Tải lên tài liệu mới',
        'document.edit' => 'Chỉnh sửa thông tin tài liệu',
        'document.delete' => 'Xóa tài liệu khỏi hệ thống',
      ),
    ),
    'materials' => 
    array (
      'name' => 'Kho & Nguyên vật liệu',
      'icon' => 'science',
      'permissions' => 
      array (
        'materials.view' => 'Tra cứu độ nhớt, chứng chỉ COA',
        'materials.edit' => 'Cập nhật chỉ số kỹ thuật nguyên liệu',
      ),
    ),
    'production' => 
    array (
      'name' => 'Kế hoạch & Sản xuất',
      'icon' => 'precision_manufacturing',
      'permissions' => 
      array (
        'production.view' => 'Xem kế hoạch đùn, cuộn, in, cắt',
        'production.plan' => 'Tạo & phân bổ kế hoạch sản xuất',
        'production.data' => 'Cập nhật dữ liệu thực tế sản xuất',
      ),
    ),
    'mixer' => 
    array (
      'name' => 'Phối trộn hạt màu',
      'icon' => 'palette',
      'permissions' => 
      array (
        'mixer.view' => 'Xem công thức & ma trận phối màu',
        'mixer.edit' => 'Thêm mới, nhập kho mẻ phối màu',
      ),
    ),
    'five_s' => 
    array (
      'name' => 'Hệ thống Quản trị 5S',
      'icon' => 'verified',
      'permissions' => 
      array (
        'five_s.view' => 'Xem bản đồ & thống kê 5S nhà máy',
        'five_s.audit' => 'Thực hiện tuần tra & kiểm tra QR/NFC',
        'five_s.proposals' => 'Gửi & xét duyệt ý kiến đề xuất cải tiến',
        'five_s.settings' => 'Cấu hình khu vực, ảnh chuẩn & lịch tuần tra',
      ),
    ),
    'device' => 
    array (
      'name' => 'Giám sát Thiết bị IoT',
      'icon' => 'sensors',
      'permissions' => 
      array (
        'device.view' => 'Xem trạng thái Online/Offline máy móc',
        'device.edit' => 'Điều khiển hoặc cập nhật thiết bị',
        'device.history' => 'Xem lịch sử nhật ký vận hành IoT',
      ),
    ),
    'hrm' => 
    array (
      'name' => 'Nhân sự & Kiểm kê',
      'icon' => 'badge',
      'permissions' => 
      array (
        'hrm.view' => 'Xem danh sách nhân sự & sơ đồ kiểm kê',
        'hrm.manage' => 'Thêm mới nhân sự & cập nhật sơ đồ kiểm kê',
      ),
    ),
    'system' => 
    array (
      'name' => 'Hệ thống & Phân quyền',
      'icon' => 'admin_panel_settings',
      'permissions' => 
      array (
        'role.manage' => 'Quản trị và cấp quyền người dùng',
      ),
    ),
  ),
  'api_catalog' => 
  array (
    'document' => 
    array (
      'name' => 'Tài liệu SOP / Tiêu chuẩn (Document API)',
      'icon' => 'description',
      'apis' => 
      array (
        'api.document.get' => 
        array (
          'name' => 'Tra cứu danh sách tài liệu SOP/Kỹ thuật',
          'endpoint' => 'api/get_documents.php',
          'method' => 'GET',
        ),
        'api.document.upload' => 
        array (
          'name' => 'Tải lên tài liệu kỹ thuật / SOP mới',
          'endpoint' => 'api/upload_document.php',
          'method' => 'POST',
        ),
        'api.document.edit' => 
        array (
          'name' => 'Cập nhật phân loại & thông tin tài liệu',
          'endpoint' => 'api/edit_document.php',
          'method' => 'POST',
        ),
        'api.document.delete' => 
        array (
          'name' => 'Xóa tài liệu khỏi kho lưu trữ',
          'endpoint' => 'api/delete_document.php',
          'method' => 'POST',
        ),
      ),
    ),
    'mixer' => 
    array (
      'name' => 'Phối trộn hạt màu (Color Mixer API)',
      'icon' => 'palette',
      'apis' => 
      array (
        'api.mixer.get' => 
        array (
          'name' => 'Truy vấn dữ liệu công thức & mẻ phối trộn',
          'endpoint' => 'api/color_mixer_get.php',
          'method' => 'GET',
        ),
        'api.mixer.save' => 
        array (
          'name' => 'Thêm mới hoặc cập nhật mẻ phối trộn hạt màu',
          'endpoint' => 'api/color_mixer_save.php',
          'method' => 'POST',
        ),
        'api.mixer.delete' => 
        array (
          'name' => 'Xóa bản ghi mẻ phối màu',
          'endpoint' => 'api/color_mixer_delete.php',
          'method' => 'POST',
        ),
        'api.mixer.import' => 
        array (
          'name' => 'Import danh sách phối màu từ file Excel',
          'endpoint' => 'api/color_mixer_import.php',
          'method' => 'POST',
        ),
        'api.mixer.export' => 
        array (
          'name' => 'Xuất báo cáo dữ liệu phối màu sang Excel',
          'endpoint' => 'api/color_mixer_export.php',
          'method' => 'GET',
        ),
        'api.mixer.summary' => 
        array (
          'name' => 'Thống kê tổng hợp số mẻ, size & tốc độ',
          'endpoint' => 'api/color_mixer_summary_get.php',
          'method' => 'GET',
        ),
      ),
    ),
    'production' => 
    array (
      'name' => 'Kế hoạch & Sản xuất (Production API)',
      'icon' => 'precision_manufacturing',
      'apis' => 
      array (
        'api.production.report' => 
        array (
          'name' => 'Báo cáo tổng hợp sản lượng & OEE máy',
          'endpoint' => 'api/get_production_report.php',
          'method' => 'GET',
        ),
        'api.production.plan_save' => 
        array (
          'name' => 'Lưu kế hoạch sản xuất ca / ngày',
          'endpoint' => 'api/save_production_plan.php',
          'method' => 'POST',
        ),
        'api.production.plan_upload' => 
        array (
          'name' => 'Upload kế hoạch sản xuất từ file Excel',
          'endpoint' => 'api/upload_production_plan.php',
          'method' => 'POST',
        ),
        'api.production.extrusion_get' => 
        array (
          'name' => 'Tra cứu dữ liệu sản lượng đùn thực tế',
          'endpoint' => 'api/get_extrusion_actuals.php',
          'method' => 'GET',
        ),
        'api.production.extrusion_save_row' => 
        array (
          'name' => 'Cập nhật từng dòng sản lượng đùn thực tế',
          'endpoint' => 'api/save_extrusion_actual_row.php',
          'method' => 'POST',
        ),
        'api.production.extrusion_upload' => 
        array (
          'name' => 'Upload file sản lượng đùn thực tế theo ca',
          'endpoint' => 'api/upload_extrusion_actuals.php',
          'method' => 'POST',
        ),
        'api.production.extrusion_export' => 
        array (
          'name' => 'Xuất dữ liệu sản lượng đùn ra Excel',
          'endpoint' => 'api/export_extrusion_actuals.php',
          'method' => 'GET',
        ),
        'api.production.extrusion_delete' => 
        array (
          'name' => 'Xóa dữ liệu sản lượng đùn theo ca',
          'endpoint' => 'api/delete_extrusion_actuals.php',
          'method' => 'POST',
        ),
      ),
    ),
    'five_s' => 
    array (
      'name' => 'Quản lý 5S & Kaizen (5S System API)',
      'icon' => 'verified',
      'apis' => 
      array (
        'api.five_s.dashboard' => 
        array (
          'name' => 'Lấy chỉ số KPI, điểm số & lỗi vi phạm 5S',
          'endpoint' => 'api/five_s_get_dashboard.php',
          'method' => 'GET',
        ),
        'api.five_s.schedules' => 
        array (
          'name' => 'Tra cứu lịch tuần tra & đánh giá 5S',
          'endpoint' => 'api/five_s_get_schedules.php',
          'method' => 'GET',
        ),
        'api.five_s.assignments' => 
        array (
          'name' => 'Tra cứu danh sách phân công tuần tra',
          'endpoint' => 'api/five_s_get_assignments.php',
          'method' => 'GET',
        ),
        'api.five_s.save_assignment' => 
        array (
          'name' => 'Phân công ca / lịch tuần tra cho nhân sự',
          'endpoint' => 'api/five_s_save_assignment.php',
          'method' => 'POST',
        ),
        'api.five_s.save_audit' => 
        array (
          'name' => 'Lưu kết quả chấm điểm tuần tra 5S nhanh',
          'endpoint' => 'api/five_s_save_audit.php',
          'method' => 'POST',
        ),
        'api.five_s.save_audit_full' => 
        array (
          'name' => 'Lưu biên bản đánh giá 5S chi tiết',
          'endpoint' => 'api/five_s_save_audit_full.php',
          'method' => 'POST',
        ),
        'api.five_s.save_zone' => 
        array (
          'name' => 'Cấu hình khu vực, tiêu chuẩn & mã QR/NFC 5S',
          'endpoint' => 'api/five_s_save_zone.php',
          'method' => 'POST',
        ),
        'api.five_s.verify_location' => 
        array (
          'name' => 'Xác thực tọa độ GPS / NFC vị trí tuần tra',
          'endpoint' => 'api/five_s_verify_location.php',
          'method' => 'POST',
        ),
        'api.five_s.resolve_issue' => 
        array (
          'name' => 'Xác nhận khắc phục hoàn tất điểm lỗi 5S',
          'endpoint' => 'api/five_s_resolve_issue.php',
          'method' => 'POST',
        ),
        'api.five_s.delete_issue' => 
        array (
          'name' => 'Xóa biên bản vi phạm / lỗi 5S',
          'endpoint' => 'api/five_s_delete_issue.php',
          'method' => 'POST',
        ),
        'api.five_s.get_proposals' => 
        array (
          'name' => 'Tra cứu danh sách sáng kiến cải tiến Kaizen',
          'endpoint' => 'api/five_s_get_proposals.php',
          'method' => 'GET',
        ),
        'api.five_s.save_proposal' => 
        array (
          'name' => 'Gửi hoặc xét duyệt sáng kiến Kaizen 5S',
          'endpoint' => 'api/five_s_save_proposal.php',
          'method' => 'POST',
        ),
      ),
    ),
    'materials' => 
    array (
      'name' => 'Kho & Nguyên vật liệu (Materials API)',
      'icon' => 'science',
      'apis' => 
      array (
        'api.materials.get_viscosity' => 
        array (
          'name' => 'Tra cứu nhật ký đo kiểm độ nhớt nguyên liệu',
          'endpoint' => 'api/get_viscosity_logs.php',
          'method' => 'GET',
        ),
        'api.materials.upload_viscosity' => 
        array (
          'name' => 'Upload dữ liệu đo độ nhớt nguyên liệu',
          'endpoint' => 'api/upload_viscosity_logs.php',
          'method' => 'POST',
        ),
      ),
    ),
    'device' => 
    array (
      'name' => 'Giám sát Thiết bị IoT (IoT API)',
      'icon' => 'sensors',
      'apis' => 
      array (
        'api.device.iot_status' => 
        array (
          'name' => 'Đọc trạng thái Online/Offline cảm biến & máy móc',
          'endpoint' => 'api/iot_status.php',
          'method' => 'GET',
        ),
      ),
    ),
    'hrm' => 
    array (
      'name' => 'Nhân sự & Kiểm kê (HRM & Inventory API)',
      'icon' => 'badge',
      'apis' => 
      array (
        'api.hrm.add_employee' => 
        array (
          'name' => 'Thêm mới hồ sơ nhân viên nhà máy',
          'endpoint' => 'api/add_employee.php',
          'method' => 'POST',
        ),
        'api.hrm.inventory_org_chart' => 
        array (
          'name' => 'Quản lý cây sơ đồ tổ chức kiểm kê & phân công đa khu vực',
          'endpoint' => 'api/inventory_org_chart.php',
          'method' => 'POST',
        ),
      ),
    ),
    'system' => 
    array (
      'name' => 'Quản trị Hệ thống & Quyền (System API)',
      'icon' => 'admin_panel_settings',
      'apis' => 
      array (
        'api.system.save_role_permissions' => 
        array (
          'name' => 'Lưu cấu hình phân quyền ma trận vai trò người dùng',
          'endpoint' => 'api/save_role_permissions.php',
          'method' => 'POST',
        ),
        'api.system.check_auth' => 
        array (
          'name' => 'Kiểm tra trạng thái xác thực phiên làm việc',
          'endpoint' => 'api/check_auth.php',
          'method' => 'GET',
        ),
      ),
    ),
  ),
);
