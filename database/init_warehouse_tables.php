<?php
// database/init_warehouse_tables.php
require_once __DIR__ . '/../config/db.php';
global $conn;

echo "=== INITIALIZING WAREHOUSE TABLES ===\n";

// 1. warehouse_materials
$sql1 = "CREATE TABLE IF NOT EXISTS warehouse_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_code VARCHAR(100) NOT NULL UNIQUE,
    item_name_vn VARCHAR(255) NOT NULL,
    item_name_en VARCHAR(255) NULL,
    group_name VARCHAR(100) NOT NULL,
    unit VARCHAR(50) NOT NULL DEFAULT 'Ea',
    packaging_spec VARCHAR(100) NULL,
    pack_quantity DECIMAL(10,2) DEFAULT 1.00,
    category_type ENUM('consumable', 'irregular') DEFAULT 'consumable',
    warehouse_type VARCHAR(50) DEFAULT 'NON SAP',
    bin_location VARCHAR(100) NULL,
    image_url VARCHAR(255) NULL,
    norm_per_use DECIMAL(10,4) DEFAULT 1.0000,
    default_machines DECIMAL(10,2) DEFAULT 1.00,
    default_uses_per_machine DECIMAL(10,2) DEFAULT 1.00,
    stock_initial DECIMAL(12,2) DEFAULT 0.00,
    stock_current DECIMAL(12,2) DEFAULT 0.00,
    reorder_point DECIMAL(12,2) DEFAULT 0.00,
    reorder_qty DECIMAL(12,2) DEFAULT 0.00,
    delivery_lead_time_days INT DEFAULT 7,
    avg_monthly_consumption DECIMAL(12,2) DEFAULT 0.00,
    notes TEXT NULL,
    frequency_text VARCHAR(100) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_group (group_name),
    INDEX idx_category (category_type),
    INDEX idx_code (item_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
if ($conn->query($sql1)) {
    echo "  [OK] Table warehouse_materials created or exists.\n";
} else {
    echo "  [ERROR] Table warehouse_materials: " . $conn->error . "\n";
}

// 2. warehouse_material_history
$sql2 = "CREATE TABLE IF NOT EXISTS warehouse_material_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    material_id INT NOT NULL,
    item_code VARCHAR(100) NOT NULL,
    year INT NOT NULL,
    month INT NOT NULL,
    issued_qty DECIMAL(12,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_mat_month (material_id, year, month),
    INDEX idx_code_year_month (item_code, year, month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
if ($conn->query($sql2)) {
    echo "  [OK] Table warehouse_material_history created or exists.\n";
} else {
    echo "  [ERROR] Table warehouse_material_history: " . $conn->error . "\n";
}

// 3. warehouse_issues
$sql3 = "CREATE TABLE IF NOT EXISTS warehouse_issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_code VARCHAR(50) NOT NULL UNIQUE,
    group_name VARCHAR(100) NOT NULL,
    issue_type ENUM('consumable', 'irregular', 'mixed') DEFAULT 'consumable',
    month INT NOT NULL,
    year INT NOT NULL,
    status ENUM('pending_checker', 'pending_manager', 'pending_admin_issue', 'pending_handover', 'completed', 'rejected', 'cancelled') DEFAULT 'pending_checker',
    creator_id INT NULL,
    creator_name VARCHAR(100) NOT NULL,
    creator_code VARCHAR(50) NULL,
    purpose TEXT NULL,
    reason_for_irregular TEXT NULL,
    total_items INT DEFAULT 0,
    total_theoretical_qty DECIMAL(12,2) DEFAULT 0.00,
    total_actual_qty DECIMAL(12,2) DEFAULT 0.00,
    notes TEXT NULL,
    has_rop_warning TINYINT(1) DEFAULT 0,
    rop_admin_confirmed TINYINT(1) DEFAULT 0,
    rop_admin_note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_group (group_name),
    INDEX idx_period (year, month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
if ($conn->query($sql3)) {
    echo "  [OK] Table warehouse_issues created or exists.\n";
} else {
    echo "  [ERROR] Table warehouse_issues: " . $conn->error . "\n";
}

// 4. warehouse_issue_items
$sql4 = "CREATE TABLE IF NOT EXISTS warehouse_issue_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_id INT NOT NULL,
    material_id INT NOT NULL,
    item_code VARCHAR(100) NOT NULL,
    item_name_vn VARCHAR(255) NOT NULL,
    item_name_en VARCHAR(255) NULL,
    group_name VARCHAR(100) NULL,
    unit VARCHAR(50) NOT NULL,
    packaging_spec VARCHAR(100) NULL,
    pack_quantity DECIMAL(10,2) DEFAULT 1.00,
    category_type ENUM('consumable', 'irregular') DEFAULT 'consumable',
    bin_location VARCHAR(100) NULL,
    image_url VARCHAR(255) NULL,
    machines_count DECIMAL(10,2) DEFAULT 0.00,
    uses_per_machine DECIMAL(10,2) DEFAULT 0.00,
    total_uses DECIMAL(10,2) DEFAULT 0.00,
    norm_per_use DECIMAL(10,4) DEFAULT 0.0000,
    theoretical_qty DECIMAL(12,2) DEFAULT 0.00,
    field_stock DECIMAL(12,2) DEFAULT 0.00,
    reusable_stock DECIMAL(12,2) DEFAULT 0.00,
    net_theoretical_qty DECIMAL(12,2) DEFAULT 0.00,
    actual_qty DECIMAL(12,2) DEFAULT 0.00,
    stock_before_issue DECIMAL(12,2) DEFAULT 0.00,
    stock_after_issue DECIMAL(12,2) DEFAULT 0.00,
    reorder_point DECIMAL(12,2) DEFAULT 0.00,
    is_below_rop TINYINT(1) DEFAULT 0,
    avg_3months_consumption DECIMAL(12,2) DEFAULT 0.00,
    runway_months DECIMAL(10,2) DEFAULT 0.00,
    history_m1 DECIMAL(12,2) DEFAULT 0.00,
    history_m2 DECIMAL(12,2) DEFAULT 0.00,
    history_m3 DECIMAL(12,2) DEFAULT 0.00,
    irregular_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_issue_id (issue_id),
    INDEX idx_material_id (material_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
if ($conn->query($sql4)) {
    echo "  [OK] Table warehouse_issue_items created or exists.\n";
} else {
    echo "  [ERROR] Table warehouse_issue_items: " . $conn->error . "\n";
}

// 5. warehouse_workflow_logs
$sql5 = "CREATE TABLE IF NOT EXISTS warehouse_workflow_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_id INT NOT NULL,
    step VARCHAR(50) NOT NULL,
    actor_id INT NULL,
    actor_name VARCHAR(100) NOT NULL,
    actor_role VARCHAR(50) NOT NULL,
    action VARCHAR(50) NOT NULL,
    comment TEXT NULL,
    handover_receiver_name VARCHAR(100) NULL,
    handover_receiver_code VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_issue_log (issue_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
if ($conn->query($sql5)) {
    echo "  [OK] Table warehouse_workflow_logs created or exists.\n";
} else {
    echo "  [ERROR] Table warehouse_workflow_logs: " . $conn->error . "\n";
}

// 6. warehouse_approvers
$sql6 = "CREATE TABLE IF NOT EXISTS warehouse_approvers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_type ENUM('checker', 'manager', 'admin_warehouse', 'receiver') NOT NULL,
    group_name VARCHAR(100) DEFAULT 'ALL',
    user_id INT NULL,
    username VARCHAR(100) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_role_type (role_type),
    INDEX idx_approver_group (group_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
if ($conn->query($sql6)) {
    echo "  [OK] Table warehouse_approvers created or exists.\n";
} else {
    echo "  [ERROR] Table warehouse_approvers: " . $conn->error . "\n";
}

// 7. warehouse_reorder_alerts
$sql7 = "CREATE TABLE IF NOT EXISTS warehouse_reorder_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_id INT NULL,
    material_id INT NOT NULL,
    item_code VARCHAR(100) NOT NULL,
    item_name_vn VARCHAR(255) NOT NULL,
    stock_remain DECIMAL(12,2) NOT NULL,
    reorder_point DECIMAL(12,2) NOT NULL,
    reorder_qty DECIMAL(12,2) DEFAULT 0.00,
    status ENUM('pending', 'ordered', 'delivered', 'dismissed') DEFAULT 'pending',
    admin_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_alert_status (status),
    INDEX idx_alert_code (item_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
if ($conn->query($sql7)) {
    echo "  [OK] Table warehouse_reorder_alerts created or exists.\n";
} else {
    echo "  [ERROR] Table warehouse_reorder_alerts: " . $conn->error . "\n";
}

echo "=== TABLES INITIALIZATION FINISHED ===\n";
