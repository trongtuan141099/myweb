<?php
// config/permission.php
return [
    // Định nghĩa danh sách quyền tương ứng từng Role
    'roles_map' => [
        'admin'  => [
            'dashboard.view',
            'document.view', 'document.upload', 'document.edit', 'document.delete',
            'materials.view', 'materials.edit',
            'production.view', 'production.plan', 'production.data',
            'mixer.view', 'mixer.edit',
            'five_s.view', 'five_s.audit', 'five_s.settings', 'five_s.proposals',
            'device.view', 'device.edit', 'device.history',
            'hrm.view', 'hrm.manage',
            'role.manage'
        ],
        'editor' => [
            'dashboard.view',
            'document.view', 'document.upload', 'document.edit',
            'materials.view', 'materials.edit',
            'production.view', 'production.plan', 'production.data',
            'mixer.view', 'mixer.edit',
            'five_s.view', 'five_s.audit', 'five_s.proposals',
            'device.view', 'device.history'
        ],
        'viewer' => [
            'dashboard.view',
            'document.view',
            'materials.view',
            'production.view',
            'mixer.view',
            'five_s.view', 'five_s.audit',
            'device.view'
        ]
    ],
    // Danh mục chi tiết các nhóm quyền hiển thị trên UI Quản trị
    'permission_catalog' => [
        'dashboard' => [
            'name' => 'Bảng điều khiển (Dashboard)',
            'icon' => 'dashboard',
            'permissions' => [
                'dashboard.view' => 'Xem tổng quan năng suất, sản lượng, máy chạy'
            ]
        ],
        'document' => [
            'name' => 'Tài liệu SOP / Tiêu chuẩn',
            'icon' => 'description',
            'permissions' => [
                'document.view'   => 'Xem và đọc tài liệu PDF',
                'document.upload' => 'Tải lên tài liệu mới',
                'document.edit'   => 'Chỉnh sửa thông tin tài liệu',
                'document.delete' => 'Xóa tài liệu khỏi hệ thống'
            ]
        ],
        'materials' => [
            'name' => 'Kho & Nguyên vật liệu',
            'icon' => 'science',
            'permissions' => [
                'materials.view' => 'Tra cứu độ nhớt, chứng chỉ COA',
                'materials.edit' => 'Cập nhật chỉ số kỹ thuật nguyên liệu'
            ]
        ],
        'production' => [
            'name' => 'Kế hoạch & Sản xuất',
            'icon' => 'precision_manufacturing',
            'permissions' => [
                'production.view' => 'Xem kế hoạch đùn, cuộn, in, cắt',
                'production.plan' => 'Tạo & phân bổ kế hoạch sản xuất',
                'production.data' => 'Cập nhật dữ liệu thực tế sản xuất'
            ]
        ],
        'mixer' => [
            'name' => 'Phối trộn hạt màu',
            'icon' => 'palette',
            'permissions' => [
                'mixer.view' => 'Xem công thức & ma trận phối màu',
                'mixer.edit' => 'Thêm mới, nhập kho mẻ phối màu'
            ]
        ],
        'five_s' => [
            'name' => 'Hệ thống Quản trị 5S',
            'icon' => 'verified',
            'permissions' => [
                'five_s.view'      => 'Xem bản đồ & thống kê 5S nhà máy',
                'five_s.audit'     => 'Thực hiện tuần tra & kiểm tra QR/NFC',
                'five_s.proposals' => 'Gửi & xét duyệt ý kiến đề xuất cải tiến',
                'five_s.settings'  => 'Cấu hình khu vực, ảnh chuẩn & lịch tuần tra'
            ]
        ],
        'device' => [
            'name' => 'Giám sát Thiết bị IoT',
            'icon' => 'sensors',
            'permissions' => [
                'device.view'    => 'Xem trạng thái Online/Offline máy móc',
                'device.edit'    => 'Điều khiển hoặc cập nhật thiết bị',
                'device.history' => 'Xem lịch sử nhật ký vận hành IoT'
            ]
        ],
        'hrm' => [
            'name' => 'Nhân sự & Lao động',
            'icon' => 'badge',
            'permissions' => [
                'hrm.view'   => 'Xem danh sách nhân sự & ca làm việc',
                'hrm.manage' => 'Thêm mới, sửa thông tin nhân viên'
            ]
        ],
        'system' => [
            'name' => 'Hệ thống & Phân quyền',
            'icon' => 'admin_panel_settings',
            'permissions' => [
                'role.manage' => 'Quản trị và cấp quyền người dùng'
            ]
        ]
    ]
];