CREATE TABLE IF NOT EXISTS `devices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `device_name` VARCHAR(100) NOT NULL,
    `device_code` VARCHAR(50) NOT NULL UNIQUE,
    `ip_address` VARCHAR(20) DEFAULT '',
    `status` ENUM('ON', 'OFF', 'ERROR', 'OFFLINE') DEFAULT 'OFFLINE',
    `note` VARCHAR(255) DEFAULT 'Chưa kết nối',
    `last_seen` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
INSERT INTO `devices` (`device_name`, `device_code`, `status`, `note`) VALUES
('PL01', 'ESP32_DEV_01', 'OFFLINE', 'Chưa kết nối'),
('PL02', 'ESP32_DEV_02', 'OFFLINE', 'Chưa kết nối'),
('PL03', 'ESP32_DEV_03', 'OFFLINE', 'Chưa kết nối'),
('PL04', 'ESP32_DEV_04', 'OFFLINE', 'Chưa kết nối'),
('PL05', 'ESP32_DEV_05', 'OFFLINE', 'Chưa kết nối'),
('PL06', 'ESP32_DEV_06', 'OFFLINE', 'Chưa kết nối')
ON DUPLICATE KEY UPDATE `id`=`id`;SELECT * FROM `devices` WHERE 1

-- Bảng lưu lịch sử thay đổi trạng thái
CREATE TABLE IF NOT EXISTS `device_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `device_code` VARCHAR(50) NOT NULL,
    `device_name` VARCHAR(100) NOT NULL,
    `status` VARCHAR(20) NOT NULL,
    `note` VARCHAR(255) DEFAULT '',
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);