-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th9 03, 2026 lúc 01:47 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `myweb`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `devices`
--

CREATE TABLE `devices` (
  `id` int(11) NOT NULL,
  `device_name` varchar(100) NOT NULL,
  `device_code` varchar(50) NOT NULL,
  `ip_address` varchar(20) DEFAULT '',
  `status` enum('ON','OFF','ERROR','OFFLINE') DEFAULT 'OFFLINE',
  `note` varchar(255) DEFAULT 'Chưa kết nối',
  `last_seen` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `devices`
--

INSERT INTO `devices` (`id`, `device_name`, `device_code`, `ip_address`, `status`, `note`, `last_seen`) VALUES
(1, 'PL15', 'PL15', '192.168.2.5', 'OFFLINE', 'Mất kết nối IoT', '2026-08-31 15:55:57'),
(2, 'PL15', 'PL16', '192.168.2.5', 'ON', 'Kết nối ổn định 1', '2026-09-02 23:47:34'),
(3, 'PL03', 'ESP32_DEV_03', '192.168.2.5', 'OFFLINE', 'Mất kết nối IoT', '2026-08-31 13:11:29'),
(4, 'PL04', 'ESP32_DEV_04', '', 'OFFLINE', 'Mất kết nối IoT', '2026-08-31 10:47:37'),
(5, 'PL05', 'ESP32_DEV_05', '', 'OFFLINE', 'Mất kết nối IoT', '2026-08-31 10:47:37'),
(6, 'PL06', 'ESP32_DEV_06', '', 'OFFLINE', 'Mất kết nối IoT', '2026-08-31 10:47:37');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `device_history`
--

CREATE TABLE `device_history` (
  `id` int(11) NOT NULL,
  `device_id` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `device_history`
--

INSERT INTO `device_history` (`id`, `device_id`, `status`, `timestamp`) VALUES
(1, 'ESP32_DEV_01', 'OFF', '2026-08-31 08:53:48'),
(2, 'ESP32_DEV_01', 'OFF', '2026-08-31 08:54:44'),
(3, 'ESP32_DEV_01', 'OFF', '2026-08-31 08:55:05'),
(4, 'ESP32_DEV_01', 'OFF', '2026-08-31 08:55:31'),
(5, 'ESP32_DEV_01', 'OFF', '2026-08-31 08:56:59'),
(6, 'ESP32_DEV_01', 'OFF', '2026-08-31 09:00:19'),
(7, 'ESP32_DEV_01', 'OFF', '2026-08-31 09:09:21'),
(8, 'ESP32_DEV_01', 'ON', '2026-08-31 09:09:22'),
(9, 'ESP32_DEV_01', 'OFF', '2026-08-31 09:09:32'),
(10, 'ESP32_DEV_01', 'ON', '2026-08-31 09:09:32'),
(11, 'ESP32_DEV_01', 'OFF', '2026-08-31 09:09:36'),
(12, 'ESP32_DEV_01', 'ON', '2026-08-31 09:09:40'),
(13, 'ESP32_DEV_01', 'OFF', '2026-08-31 09:09:45'),
(14, 'ESP32_DEV_01', 'ON', '2026-08-31 09:09:51'),
(15, 'ESP32_DEV_01', 'OFF', '2026-08-31 09:10:09'),
(16, 'ESP32_DEV_01', 'ON', '2026-08-31 09:10:17'),
(17, 'PL01', 'OFF', '2026-08-31 09:15:01'),
(18, 'PL01', 'ON', '2026-08-31 09:15:06'),
(19, 'PL01', 'OFF', '2026-08-31 09:30:53'),
(20, 'PL01', 'ON', '2026-08-31 09:30:57'),
(21, 'PL01', 'OFF', '2026-08-31 10:04:39'),
(22, 'PL01', 'ON', '2026-08-31 10:04:42'),
(23, 'PL01', 'OFF', '2026-08-31 10:04:50'),
(24, 'PL01', 'ON', '2026-08-31 10:04:57'),
(25, 'PL01', 'OFF', '2026-08-31 10:10:52'),
(26, 'PL01', 'ON', '2026-08-31 10:10:59'),
(27, 'PL15', 'OFF', '2026-08-31 10:18:28'),
(28, 'PL15', 'ON', '2026-08-31 10:18:36');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `device_historys`
--

CREATE TABLE `device_historys` (
  `id` int(11) NOT NULL,
  `device_code` varchar(50) NOT NULL,
  `device_name` varchar(100) NOT NULL,
  `status` varchar(20) NOT NULL,
  `note` varchar(255) DEFAULT '',
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `device_historys`
--

INSERT INTO `device_historys` (`id`, `device_code`, `device_name`, `status`, `note`, `timestamp`) VALUES
(1, 'PL15', 'Máy đùn PL15', 'OFF', 'Kết nối ổn định', '2026-08-31 10:50:22'),
(2, 'PL15', 'Máy đùn PL15', 'ON', 'Kết nối ổn định', '2026-08-31 10:50:23'),
(3, 'PL15', 'Máy đùn PL15', 'OFF', 'Kết nối ổn định', '2026-08-31 10:50:30'),
(4, 'PL15', 'Máy đùn PL15', 'ON', 'Kết nối ổn định', '2026-08-31 10:50:37'),
(5, 'PL15', 'Máy đùn PL15', 'OFF', 'Kết nối ổn định', '2026-08-31 10:51:29'),
(6, 'PL15', 'Máy đùn PL15', 'ON', 'Kết nối ổn định', '2026-08-31 10:51:33'),
(7, 'PL15', 'Máy đùn PL15', 'OFF', 'Kết nối ổn định', '2026-08-31 10:52:12'),
(8, 'PL15', 'Máy đùn PL15', 'ON', 'Kết nối ổn định', '2026-08-31 10:52:17'),
(9, 'PL15', 'Máy đùn PL15', 'OFF', 'Kết nối ổn định', '2026-08-31 10:52:47'),
(10, 'PL15', 'Máy đùn PL15', 'ON', 'Kết nối ổn định', '2026-08-31 10:52:51'),
(11, 'PL15', 'Máy đùn PL15', 'OFF', 'Kết nối ổn định', '2026-08-31 10:54:03'),
(12, 'PL15', 'Máy đùn PL15', 'ON', 'Kết nối ổn định', '2026-08-31 10:54:12'),
(13, 'PL15', 'Máy đùn PL15', 'OFF', 'Kết nối ổn định', '2026-08-31 10:54:25'),
(14, 'PL15', 'Máy đùn PL15', 'ON', 'Kết nối ổn định', '2026-08-31 10:54:28'),
(15, 'PL15', 'Máy đùn PL15', 'OFF', 'Kết nối ổn định', '2026-08-31 13:05:05'),
(16, 'PL15', 'Máy đùn PL15', 'ON', 'Kết nối ổn định', '2026-08-31 13:05:08'),
(17, 'PL15', 'Máy đùn PL15', 'OFF', 'Kết nối ổn định', '2026-08-31 13:12:02'),
(18, 'PL15', 'Máy đùn PL15', 'ON', 'Kết nối ổn định', '2026-08-31 13:12:22'),
(19, 'PL16', 'PL15', 'OFF', 'Thay đổi trạng thái', '2026-09-01 02:06:40'),
(20, 'PL16', 'PL15', 'ON', 'Thay đổi trạng thái', '2026-09-01 02:06:42'),
(21, 'PL16', 'PL15', 'OFF', 'Thay đổi trạng thái', '2026-09-01 02:06:43'),
(22, 'PL16', 'PL15', 'ON', 'Thay đổi trạng thái', '2026-09-01 02:06:44'),
(23, 'PL16', 'PL15', 'OFF', 'Thay đổi trạng thái', '2026-09-01 02:06:46'),
(24, 'PL16', 'PL15', 'ON', 'Thay đổi trạng thái', '2026-09-01 02:06:51'),
(25, 'PL16', 'PL15', 'OFF', 'Thay đổi trạng thái', '2026-09-01 02:06:54'),
(26, 'PL16', 'PL15', 'ON', 'Thay đổi trạng thái', '2026-09-01 02:07:01'),
(27, 'PL16', 'PL15', 'OFF', 'Thay đổi trạng thái', '2026-09-01 02:33:03'),
(28, 'PL16', 'PL15', 'ON', 'Thay đổi trạng thái', '2026-09-01 02:33:08'),
(29, 'PL16', 'PL15', 'ON', 'Thay đổi trạng thái', '2026-09-02 23:43:54'),
(30, 'PL16', 'PL15', 'OFF', 'Thay đổi trạng thái', '2026-09-02 23:44:51'),
(31, 'PL16', 'PL15', 'ON', 'Thay đổi trạng thái', '2026-09-02 23:44:56'),
(32, 'PL16', 'PL15', 'OFF', 'Thay đổi trạng thái', '2026-09-02 23:44:58'),
(33, 'PL16', 'PL15', 'ON', 'Thay đổi trạng thái', '2026-09-02 23:45:01');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `device_status`
--

CREATE TABLE `device_status` (
  `device_id` varchar(50) NOT NULL,
  `ip_address` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `device_status`
--

INSERT INTO `device_status` (`device_id`, `ip_address`, `status`, `last_update`) VALUES
('ESP32_DEV_01', '192.168.2.5', 'ON', '2026-08-31 09:10:17'),
('PL01', '192.168.2.5', 'ON', '2026-08-31 10:10:59'),
('PL15', '192.168.2.5', 'ON', '2026-08-31 10:18:36');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `document_code` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `doc_type` enum('Hướng dẫn công việc','Tiêu chuẩn sản phẩm','Phiếu kiểm tra','Mục tiêu hiện trường','Điều kiện sản xuất','Báo cáo NC','Phiếu đề xuất cải tiến','Sơ đồ chất lượng') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `documents`
--

INSERT INTO `documents` (`id`, `document_code`, `title`, `doc_type`, `file_path`, `uploaded_at`, `updated_at`) VALUES
(36, 'PMW-00243-B', 'HDCV Vận hành máy in đùn nhựa', 'Hướng dẫn công việc', 'documents/36. PMW-00243-B HDCV Vận hành máy in đùn nhựa.pdf', '2026-08-25 12:57:11', '2026-08-25 12:57:11'),
(37, 'FEWPM-00244-A', 'HDCV sử dụng máy laser đo đường kính ống', 'Hướng dẫn công việc', 'documents/37. FEWPM-00244-A HDCV sử dụng máy laser đo đường kính ống.pdf', '2026-08-25 12:57:11', '2026-08-25 12:57:11'),
(41, 'PMA-00264-B', 'ĐKSDTB Máy đùn nhựa', 'Điều kiện sản xuất', 'documents/41. PMA-00264-B ĐKSDTB Máy đùn nhựa.pdf', '2026-08-25 12:57:11', '2026-08-25 12:57:11'),
(42, 'PMP-00264-9.3-E', 'Qui trình thiết lập máy đùn nhựa', 'Hướng dẫn công việc', 'documents/42. PMP-00264-9.3-E Qui trình thiết lập máy đùn nhựa.pdf', '2026-08-25 12:57:11', '2026-08-25 12:57:11'),
(43, 'PMW-00264-9.4-B', 'HDCV Qui trình dừng máy đùn', 'Hướng dẫn công việc', 'documents/43. PMW-00264-9.4-B  HDCV Qui trình dừng máy đùn.pdf', '2026-08-25 12:57:11', '2026-08-25 14:18:08'),
(45, 'PMW-00295-B', 'HDCV Chú ý lắp khuôn', 'Hướng dẫn công việc', 'documents/45. PMW-00295-B HDCV  Chú ý lắp khuôn.pdf', '2026-08-25 12:57:11', '2026-08-25 14:18:48'),
(49, 'PMW-00416_01-B', 'HDCV Máy sấy nguyên liệu trộn màu 30AS', 'Hướng dẫn công việc', 'documents/49. PMW-00416_01-B HDCV Máy sấy nguyên liệu trộn màu 30AS.pdf', '2026-08-25 12:57:11', '2026-08-25 12:57:11'),
(50, 'PMW-00416_02-B', 'HDCV Máy sấy vật liệu trộn màu 100ASE', 'Hướng dẫn công việc', 'documents/50. PMW-00416_02-B HDCV Máy sấy vật liệu trộn màu 100ASE.pdf', '2026-08-25 12:57:11', '2026-08-25 12:57:11'),
(51, 'PMW-00419-B', 'HDCV Cách lấy hạt trộn màu', 'Hướng dẫn công việc', 'documents/51. PMW-00419-B HDCV Cách lấy hạt trộn màu.pdf', '2026-08-25 12:57:11', '2026-08-25 12:57:11'),
(52, 'PMW-00453-B', 'HDCV Quy trình cắt giữ hạt trộn màu', 'Hướng dẫn công việc', 'documents/52. PMW-00453-B HDCV Quy trình cất giữ hạt trộn màu.pdf', '2026-08-25 12:57:11', '2026-08-25 14:19:21'),
(53, 'PEWPM-00455-A', 'Kiểm tra máy kéo', 'Phiếu kiểm tra', 'documents/53. PEWPM-00455-A  Kiểm tra máy kéo.pdf', '2026-08-25 12:57:11', '2026-08-25 14:20:19'),
(54, 'PMW-00642-B', 'HDCV Chú ý vệ sinh xylanh, gear pumb', 'Hướng dẫn công việc', 'documents/54. PMW-00642-B HDCV Chú ý vệ sinh xylanh, gear pumb.pdf', '2026-08-25 12:57:11', '2026-08-25 12:57:11'),
(55, 'PMW-00647-B', 'HDCV Vệ sinh bơm bánh răng', 'Hướng dẫn công việc', 'documents/55. PMW-00647-B  HDCV Vệ sinh bơm bánh răng.pdf', '2026-08-25 12:57:11', '2026-08-25 14:20:43'),
(56, 'PMW-00670-C', 'HDCV Tốc độ hạt màu', 'Hướng dẫn công việc', 'documents/56. PMW-00670-C HDCV Tốc độ hạt màu.pdf', '2026-08-25 12:57:11', '2026-08-25 12:57:11'),
(61, 'PEWPM-00710-A', 'HDCV Vận hành máy Shot Blast', 'Hướng dẫn công việc', 'documents/61. PEWPM-00710-A HDCV Vận hành máy Shot Blast.pdf', '2026-08-25 12:57:11', '2026-08-25 12:57:11'),
(63, 'PEWPM-00713-A', 'HDCV Vận hành máy đo độ ẩm', 'Hướng dẫn công việc', 'documents/63. PEWPM-00713-A  HDCV Vận hành máy đo độ ẩm.pdf', '2026-08-25 12:57:11', '2026-08-25 14:21:12'),
(67, 'PMW-01679-A', 'HDCV thay seal dầu máy đùn', 'Hướng dẫn công việc', 'documents/67. PMW-01679-A HDCV thay seal dầu máy đùn.pdf', '2026-08-25 12:57:11', '2026-08-25 12:57:11'),
(74, 'PMW-02994-A', 'HDCV Xử lí hàng NG', 'Hướng dẫn công việc', 'documents/74. PMW-02994-A HDCV Xử lí hàng NG.pdf', '2026-08-25 12:57:11', '2026-08-25 12:57:11'),
(109, 'PEWPM-00468', 'bản hiệu chuẩn thiết bị đo đường kính ngoài line đùn', 'Hướng dẫn công việc', 'documents/109. PEWPM-00468 bản hiệu chuẩn thiết bị đo đường kính ngoài line đùn.pdf', '2026-08-25 12:57:11', '2026-08-25 12:57:11'),
(128, 'PMW-04304-A', 'HDCV kiểm tra sản phẩm đùn theo check sheet', 'Phiếu kiểm tra', 'documents/128. PMW-04304 -A HDCV kiểm tra sản phẩm đùn theo check sheet.pdf', '2026-08-25 12:59:40', '2026-08-25 14:21:51'),
(129, 'PMW-05703-A', 'Hướng dẫn thao tác lấy bobin sau đùn', 'Hướng dẫn công việc', 'documents/129. PMW-05703-A Hướng dẫn thao tác lấy bobin sau đùn.pdf', '2026-08-25 12:59:40', '2026-08-25 12:59:40'),
(131, 'PMW-05806_A', 'HDCV Thao tác sử dụng jig tháo lắp mandrel', 'Hướng dẫn công việc', 'documents/131. PMW-05806_A HDCV Thao tác sử dụng jig tháo lắp mandrel.pdf', '2026-08-25 12:59:40', '2026-08-25 12:59:40'),
(132, 'PMW-06066-A', 'HDCV setup 3 người chạy máy đùn', 'Hướng dẫn công việc', 'documents/132. PMW-06066- A HDCV setup 3 người chạy máy đùn.pdf', '2026-08-25 12:59:40', '2026-08-25 14:22:33'),
(133, 'PMW-05865-A', 'HDCV LẤY GHEN', 'Hướng dẫn công việc', 'documents/133. PMW-05865-A HDCV LẤY GHEN.pdf', '2026-08-25 12:59:40', '2026-08-25 12:59:40'),
(135, 'PMW-06353-A', 'HDCV Quy trình xử lý sau khi phát sinh ngoại quan', 'Hướng dẫn công việc', 'documents/135. PMW-06353-A HDCV Quy trình xử lý sau khi phát sinh ngoại quan.pdf', '2026-08-25 12:59:40', '2026-08-25 12:59:40'),
(137, 'PMW-0676-A', 'HDCV Đổi màu máy đùn nhựa', 'Hướng dẫn công việc', 'documents/137. PMW-0676-A HDCV Đổi màu máy đùn nhựa.pdf', '2026-08-25 12:59:40', '2026-08-25 12:59:40'),
(143, 'PMW-07339_A', 'MỘC CONTROL_HDCV Cấp liệu máy đùn', 'Hướng dẫn công việc', 'documents/143. PMW-07339_A MỘC CONTROL_HDCV Cấp liệu máy đùn.pdf', '2026-08-25 12:59:40', '2026-08-25 12:59:40');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `employees`
--

CREATE TABLE `employees` (
  `employee_code` varchar(8) NOT NULL,
  `full_name` varchar(50) NOT NULL,
  `gender` enum('Nam','Nữ') DEFAULT NULL,
  `job_level` varchar(50) DEFAULT NULL,
  `cost_center` varchar(50) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `resignation_date` date DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `employees`
--

INSERT INTO `employees` (`employee_code`, `full_name`, `gender`, `job_level`, `cost_center`, `hire_date`, `resignation_date`, `updated_at`) VALUES
('01510036', 'Nguyễn Thành Thân', 'Nam', 'M1', 'A00536', '2015-05-04', '0000-00-00', '2026-08-23 15:05:34'),
('01910698', 'Nguyễn Thị Hiền', 'Nữ', 'S3', 'A00536', '2019-07-01', '0000-00-00', '2026-08-23 15:05:35'),
('01911022', 'Kiều Minh Thiện', 'Nam', 'S4', 'A00791', '2019-08-01', '0000-00-00', '2026-08-23 15:05:35'),
('01920163', 'Lê Thị Phương Dung', 'Nữ', 'W1', 'A00430', '2019-03-04', '0000-00-00', '2026-08-23 15:05:37'),
('01920172', 'Võ Thị Mỹ', 'Nữ', 'W4', 'A00792', '2019-03-04', '0000-00-00', '2026-08-23 15:05:35'),
('01920260', 'Nguyễn Thị Lý', 'Nữ', 'W4', 'A00430', '2019-04-01', '0000-00-00', '2026-08-23 15:05:37'),
('01920507', 'Hồ Thị Mỹ', 'Nữ', 'W3', 'A00340', '2019-06-03', '0000-00-00', '2026-08-23 15:05:35'),
('01920525', 'Lương Thị Vân', 'Nữ', 'W2', 'A00792', '2019-06-03', '0000-00-00', '2026-08-23 15:05:35'),
('01920552', 'Lê Thị Phượng Hằng', 'Nữ', 'W2', 'A00430', '2019-06-03', '0000-00-00', '2026-08-23 15:05:37'),
('01920589', 'Nguyễn Ngọc Thiện', 'Nam', 'W4', 'A00330', '2019-06-03', '0000-00-00', '2026-08-23 15:05:36'),
('01920598', 'Trịnh Minh Thiện', 'Nam', 'W4', 'A00791', '2019-06-03', '0000-00-00', '2026-08-23 15:05:35'),
('01920668', 'Hà Viết Thế Thiện', 'Nam', 'W3', 'A00330', '2019-06-17', '0000-00-00', '2026-08-23 15:05:36'),
('01920862', 'Nguyễn Khắc Kiên', 'Nam', 'W2', 'A00330', '2019-07-15', '0000-00-00', '2026-08-23 15:05:36'),
('01920871', 'Xa Minh Hiếu', 'Nam', 'W3', 'A00791', '2019-07-15', '0000-00-00', '2026-08-23 15:05:35'),
('01921551', 'Trương Quang Nghĩa', 'Nam', 'W2', 'A00330', '2019-11-04', '0000-00-00', '2026-08-23 15:05:36'),
('01921560', 'Trần Quang Hiệu', 'Nam', 'W2', 'A00330', '2019-11-04', '0000-00-00', '2026-08-23 15:05:36'),
('01921603', 'Nguyễn Trần Anh Thư', 'Nữ', 'W3', 'A00430', '2019-11-04', '0000-00-00', '2026-08-23 15:05:38'),
('01921694', 'Tạ Tuyền Phong', 'Nam', 'W2', 'A00430', '2019-11-20', '0000-00-00', '2026-08-23 15:05:38'),
('01921700', 'Trần Thị Minh Phương', 'Nữ', 'W2', 'A00430', '2019-11-20', '0000-00-00', '2026-08-23 15:05:38'),
('01921764', 'Nguyễn Trung Nhân', 'Nam', 'W2', 'A00430', '2019-12-02', '0000-00-00', '2026-08-23 15:05:38'),
('02020390', 'Võ Thiên Tuế', 'Nam', 'W2', 'A00330', '2020-02-20', '0000-00-00', '2026-08-23 15:05:36'),
('02020488', 'Lê Thanh Trúc', 'Nam', 'W2', 'A00330', '2020-03-04', '0000-00-00', '2026-08-23 15:05:36'),
('02020497', 'Vũ Nhân Tài', 'Nam', 'W1', 'A00330', '2020-03-04', '0000-00-00', '2026-08-23 15:05:36'),
('02020752', 'Nguyễn Chí Cường', 'Nam', 'W3', 'A00442', '2020-03-23', '0000-00-00', '2026-08-23 15:05:37'),
('02020947', 'Nguyễn Hoàng Duy', 'Nam', 'W1', 'A00442', '2020-05-20', '0000-00-00', '2026-08-23 15:05:37'),
('02020965', 'Hà Xuân Tiến', 'Nam', 'W2', 'A00330', '2020-05-20', '0000-00-00', '2026-08-23 15:05:36'),
('02021131', 'Nguyễn Công Anh', 'Nam', 'W2', 'A00330', '2020-06-01', '0000-00-00', '2026-08-23 15:05:36'),
('02021159', 'Trần Vương', 'Nam', 'W2', 'A00330', '2020-06-01', '0000-00-00', '2026-08-23 15:05:36'),
('02021496', 'Nguyễn Việt Thắng', 'Nam', 'W1', 'A00430', '2020-07-01', '0000-00-00', '2026-08-23 15:05:38'),
('02021502', 'Phan Thị Huệ', 'Nữ', 'W2', 'A00430', '2020-07-01', '0000-00-00', '2026-08-23 15:05:38'),
('02021511', 'Võ Thị Vân Anh', 'Nữ', 'W2', 'A00430', '2020-07-01', '0000-00-00', '2026-08-23 15:05:38'),
('02021645', 'Trịnh Xuân Tỉnh', 'Nam', 'W2', 'A00330', '2020-08-03', '0000-00-00', '2026-08-23 15:05:36'),
('02021742', 'Nguyễn Ngọc Dũng', 'Nam', 'W2', 'A00330', '2020-08-20', '0000-00-00', '2026-08-23 15:05:36'),
('02021788', 'Nguyễn Thị Cẩm Tú', 'Nữ', 'W2', 'A00430', '2020-08-20', '0000-00-00', '2026-08-23 15:05:38'),
('02021797', 'Hoàng Thị  Yên', 'Nữ', 'W2', 'A00430', '2020-08-20', '0000-00-00', '2026-08-23 15:05:38'),
('02021803', 'Nguyễn Thị Yến Văn', 'Nữ', 'W2', 'A00854', '2020-08-20', '0000-00-00', '2026-08-23 15:05:38'),
('02022149', 'Nguyễn Xuân Mạnh', 'Nam', 'W2', 'A00330', '2020-10-05', '0000-00-00', '2026-08-23 15:05:36'),
('02022158', 'Bùi Lê Đức Minh', 'Nam', 'W2', 'A00330', '2020-10-05', '0000-00-00', '2026-08-23 15:05:36'),
('02114273', 'Thân Trọng Tuấn', 'Nam', 'S3', 'A00564', '2021-11-01', '0000-00-00', '2026-08-23 15:05:35'),
('02114275', 'Trọng Tuấn Thân', NULL, NULL, NULL, '2026-08-27', NULL, '2026-08-28 13:28:30'),
('02114276', 'Trọng Tuấn Thân', NULL, NULL, NULL, '2026-08-04', NULL, '2026-08-28 16:09:00'),
('02114277', 'Trọng Tuấn Thân', NULL, NULL, NULL, '2026-08-27', NULL, '2026-08-28 16:10:40'),
('02114279', 'Trọng Tuấn Thân', NULL, NULL, NULL, '2026-08-27', NULL, '2026-08-28 16:10:52'),
('02114280', 'Trọng Tuấn Thân', NULL, NULL, NULL, '2026-08-13', NULL, '2026-08-28 16:13:09'),
('02114281', 'Trọng Tuấn Thân', NULL, NULL, NULL, '2026-08-12', NULL, '2026-08-28 16:13:36'),
('02114282', 'Trọng Tuấn Thân', NULL, NULL, NULL, '2026-08-28', NULL, '2026-08-28 16:14:52'),
('02114283', 'Trọng Tuấn Thân', NULL, NULL, NULL, '2026-08-29', NULL, '2026-08-28 16:16:51'),
('02114285', 'Trọng Tuấn Thân', NULL, NULL, NULL, '2026-08-28', NULL, '2026-08-28 16:22:58'),
('02114288', 'Trọng Tuấn Thân', NULL, NULL, NULL, '2026-08-29', NULL, '2026-08-29 00:58:03'),
('02114290', 'Trọng Tuấn Thân', NULL, NULL, NULL, '2026-08-29', NULL, '2026-08-29 01:00:45'),
('02114291', 'Trọng Tuấn Thân', NULL, NULL, NULL, '2026-08-31', NULL, '2026-08-29 01:08:11'),
('02114292', 'Trọng Tuấn Thân', NULL, NULL, NULL, '2026-09-01', NULL, '2026-08-29 01:11:22'),
('02114811', 'Nguyễn Chấn Huy', 'Nam', 'S3', 'A00565', '2021-11-10', '0000-00-00', '2026-08-23 15:05:35'),
('02121398', 'Lê Đại Dương', 'Nam', 'W2', 'A00430', '2021-05-04', '0000-00-00', '2026-08-23 15:05:38'),
('02121404', 'Lê Hoàng Minh Tâm', 'Nam', 'W1', 'A00442', '2021-05-04', '0000-00-00', '2026-08-23 15:05:37'),
('02121714', 'Lê Hải', 'Nam', 'W2', 'A00430', '2021-05-10', '0000-00-00', '2026-08-23 15:05:38'),
('02125543', 'Đinh Thị Huyền Trang', 'Nữ', 'W2', 'A00792', '2021-11-15', '0000-00-00', '2026-08-23 15:05:36'),
('02125552', 'Trần Thị Thanh Kiều', 'Nữ', 'W1', 'A00430', '2021-11-15', '0000-00-00', '2026-08-23 15:05:38'),
('02125996', 'Lê Thị Thanh Vân', 'Nữ', 'W1', 'A00430', '2021-12-01', '0000-00-00', '2026-08-23 15:05:38'),
('02126667', 'Đào Hoàng Vũ', 'Nam', 'W2', 'A00430', '2021-12-13', '0000-00-00', '2026-08-23 15:05:38'),
('02216809', 'Nguyễn Bá Nhân Hậu', 'Nam', 'S1', 'A00855', '2022-07-01', '0000-00-00', '2026-08-23 15:05:35'),
('02220255', 'Nguyễn Đức Sơn', 'Nam', 'W1', 'A00330', '2022-02-09', '0000-00-00', '2026-08-23 15:05:36'),
('02220945', 'Vũ Thị Thu Hồng', 'Nữ', 'W1', 'A00430', '2022-02-17', '0000-00-00', '2026-08-23 15:05:38'),
('02221908', 'Nguyễn Minh Hoàng', 'Nam', 'W1', 'A00330', '2022-03-01', '0000-00-00', '2026-08-23 15:05:36'),
('02222350', 'Hoàng Văn Nguyên', 'Nam', 'W1', 'A00442', '2022-03-10', '0000-00-00', '2026-08-23 15:05:37'),
('02222785', 'Nguyễn Anh Duy', 'Nam', 'W1', 'A00330', '2022-04-01', '0000-00-00', '2026-08-23 15:05:36'),
('02223526', 'Nguyễn Thiên Phú', 'Nam', 'W1', 'A00442', '2022-04-12', '0000-00-00', '2026-08-23 15:05:37'),
('02223535', 'Nguyễn Quốc', 'Nam', 'W1', 'A00330', '2022-04-12', '0000-00-00', '2026-08-23 15:05:36'),
('02223580', 'Ngô Ánh Ngọc', 'Nữ', 'W1', 'A00430', '2022-04-12', '0000-00-00', '2026-08-23 15:05:38'),
('02223605', 'Võ Minh Bắc', 'Nam', 'W1', 'A00442', '2022-04-12', '0000-00-00', '2026-08-23 15:05:37'),
('02224288', 'Võ Văn Dương', 'Nam', 'W1', 'A00330', '2022-05-05', '0000-00-00', '2026-08-23 15:05:36'),
('02224358', 'Hoàng Trung Hiếu', 'Nam', 'W1', 'A00852', '2022-05-05', '0000-00-00', '2026-08-23 15:05:36'),
('02224367', 'Ngô Minh Hiếu', 'Nam', 'W1', 'A00330', '2022-05-05', '0000-00-00', '2026-08-23 15:05:36'),
('02224428', 'Lê Thị Lụa', 'Nữ', 'W1', 'A00430', '2022-05-05', '0000-00-00', '2026-08-23 15:05:38'),
('02224455', 'Ân Thành Trí', 'Nam', 'W1', 'A00430', '2022-05-05', '0000-00-00', '2026-08-23 15:05:38'),
('02225065', 'Trần Thị Thảo', 'Nữ', 'W1', 'A00430', '2022-05-16', '0000-00-00', '2026-08-23 15:05:38'),
('02225658', 'Nguyễn Nga Hoàng Dung', 'Nữ', 'W2', 'A00430', '2022-06-01', '0000-00-00', '2026-08-23 15:05:38'),
('02226596', 'Đoàn Văn Dương', 'Nam', 'W1', 'A00852', '2022-06-13', '0000-00-00', '2026-08-23 15:05:36'),
('02226602', 'Nguyễn Thị Tuyết', 'Nữ', 'W1', 'A00430', '2022-06-13', '0000-00-00', '2026-08-23 15:05:38'),
('02227708', 'Phạm Việt Khải', 'Nam', 'W1', 'A00330', '2022-07-01', '0000-00-00', '2026-08-23 15:05:36'),
('02228141', 'Nguyễn Thị Ngọc Quỳnh', 'Nữ', 'W1', 'A00430', '2022-07-11', '0000-00-00', '2026-08-23 15:05:38'),
('02228947', 'Phạm Lê Minh Tân', 'Nam', 'W1', 'A00852', '2022-08-01', '0000-00-00', '2026-08-23 15:05:36'),
('02229511', 'Phạm Thanh Lý', 'Nam', 'W1', 'A00430', '2022-08-15', '0000-00-00', '2026-08-23 15:05:38'),
('02229894', 'Nguyễn Hữu Tài', 'Nam', 'W1', 'A00330', '2022-09-06', '0000-00-00', '2026-08-23 15:05:36'),
('02241656', 'Lương Trọng Ân', 'Nam', 'W1', 'A00330', '2022-10-03', '0000-00-00', '2026-08-23 15:05:36'),
('02242761', 'Lê Đức Minh', 'Nam', 'W1', 'A00430', '2022-11-10', '0000-00-00', '2026-08-23 15:05:38'),
('02243654', 'Lưu Văn Giang', 'Nam', 'W1', 'A00442', '2022-12-01', '0000-00-00', '2026-08-23 15:05:37'),
('02243663', 'Vũ Văn Đông', 'Nam', 'W1', 'A00430', '2022-12-01', '0000-00-00', '2026-08-23 15:05:39'),
('02243991', 'Bùi Quang Chưởng', 'Nam', 'W1', 'A00330', '2022-12-12', '0000-00-00', '2026-08-23 15:05:36'),
('02317843', 'Đinh Hoàng Phúc', 'Nam', 'S2', 'A00853', '2023-08-01', '0000-00-00', '2026-08-23 15:05:35'),
('02324728', 'Bùi Thị Cẩm Nhi', 'Nữ', 'W1', 'A00430', '2023-02-01', '0000-00-00', '2026-08-23 15:05:38'),
('02324755', 'Lê Hải Vũ', 'Nam', 'W1', 'A00430', '2023-02-01', '0000-00-00', '2026-08-23 15:05:39'),
('02325277', 'Võ Hoàng Nhật Vi', 'Nữ', 'W1', 'A00430', '2023-02-13', '0000-00-00', '2026-08-23 15:05:39'),
('02325295', 'Lưu Văn Đủ', 'Nam', 'W1', 'A00330', '2023-02-13', '0000-00-00', '2026-08-23 15:05:36'),
('02325301', 'Cao Thế Mỹ', 'Nam', 'W1', 'A00330', '2023-02-13', '0000-00-00', '2026-08-23 15:05:36'),
('02325310', 'Nguyễn Công Hậu', 'Nam', 'W1', 'A00330', '2023-02-13', '0000-00-00', '2026-08-23 15:05:36'),
('02325365', 'Trần Minh Nhật', 'Nam', 'W1', 'A00330', '2023-02-13', '0000-00-00', '2026-08-23 15:05:36'),
('02325374', 'Trương Văn Tình', 'Nam', 'W1', 'A00330', '2023-02-13', '0000-00-00', '2026-08-23 15:05:36'),
('02411884', 'Phan Đình Lâm', 'Nam', 'S1', 'A00340', '2024-07-01', '0000-00-00', '2026-08-23 15:05:35'),
('02516134', 'Ngô Xuân Nghĩa', 'Nam', 'S1', 'A00340', '2025-07-10', '0000-00-00', '2026-08-23 15:05:35'),
('02519007', 'Ninh Quang Trường', 'Nam', 'S1', 'A00340', '2025-09-01', '0000-00-00', '2026-08-23 15:05:35'),
('02521747', 'Nguyễn Hoài Hậu', 'Nam', 'W1', 'A00430', '2025-04-01', '0000-00-00', '2026-08-23 15:05:39'),
('02521756', 'Lê Thị Kim Hồng', 'Nữ', 'W1', 'A00430', '2025-04-01', '0000-00-00', '2026-08-23 15:05:39'),
('02525044', 'Kim Thị Thương', 'Nữ', 'W1', 'A00430', '2025-06-02', '0000-00-00', '2026-08-23 15:05:39'),
('02525628', 'Lý Thị Thanh Hằng', 'Nữ', 'W1', 'A00430', '2025-07-01', '0000-00-00', '2026-08-23 15:05:39'),
('02525637', 'Nguyễn Thị Huyền Trang', 'Nữ', 'W1', 'A00430', '2025-07-01', '0000-00-00', '2026-08-23 15:05:39'),
('02525646', 'Vũ Thị Thủy', 'Nữ', 'W1', 'A00430', '2025-07-01', '0000-00-00', '2026-08-23 15:05:39'),
('02525655', 'Hà Thị Mỹ Hoa', 'Nữ', 'W1', 'A00430', '2025-07-01', '0000-00-00', '2026-08-23 15:05:39'),
('02542485', 'Trần Hoàng Nam', 'Nam', 'W1', 'A00430', '2025-11-10', '0000-00-00', '2026-08-23 15:05:39'),
('02542494', '\nVõ Phát Lợi', 'Nam', 'W1', 'A00430', '2025-11-10', '0000-00-00', '2026-08-23 15:05:39'),
('02619486', 'Nguyễn Đức Huy', 'Nam', 'S1', 'A00340', '2026-06-01', '0000-00-00', '2026-08-23 15:05:35'),
('02622116', 'Trịnh Nhựt Khánh', 'Nam', 'W1', 'A00442', '2026-02-03', '0000-00-00', '2026-08-23 15:05:37'),
('02644057', 'Phạm Văn Công', 'Nam', 'W1', 'A00442', '2026-07-10', '0000-00-00', '2026-08-23 15:05:37'),
('02644075', 'Huỳnh Đại Nhân', 'Nam', 'W1', 'A00442', '2026-07-10', '0000-00-00', '2026-08-23 15:05:37'),
('02644084', 'Trương Thế Đăng', 'Nam', 'W1', 'A00330', '2026-07-10', '0000-00-00', '2026-08-23 15:05:37'),
('02644109', 'Nguyễn Hữu Tình', 'Nam', 'W1', 'A00442', '2026-07-10', '0000-00-00', '2026-08-23 15:05:37'),
('02644118', 'Trần Khánh Linh', 'Nữ', 'W1', 'A00430', '2026-07-10', '0000-00-00', '2026-08-23 15:05:39'),
('02644127', 'Vũ Thị Dung', 'Nữ', 'W1', 'A00430', '2026-07-10', '0000-00-00', '2026-08-23 15:05:39'),
('02644136', 'Dương Thị Thanh Trúc', 'Nữ', 'W1', 'A00854', '2026-07-10', '0000-00-00', '2026-08-23 15:05:39'),
('02644552', 'Hàn Thị Trang', 'Nữ', 'W1', 'A00430', '2026-06-09', '0000-00-00', '2026-08-23 15:05:39'),
('02645241', 'Nguyễn Đình Hoàng', 'Nam', 'W1', 'A00330', '2026-08-03', '0000-00-00', '2026-08-23 15:05:37'),
('02645250', 'Trần Xuân Bắc', 'Nam', 'W1', 'A00330', '2026-08-03', '0000-00-00', '2026-08-23 15:05:37'),
('02645269', 'Lê Thanh Toàn', 'Nam', 'W1', 'A00330', '2026-08-03', '0000-00-00', '2026-08-23 15:05:37'),
('02645278', 'MOHA MAD NAZID', 'Nam', 'W1', 'A00330', '2026-08-03', '0000-00-00', '2026-08-23 15:05:37'),
('02646082', 'Phan Khánh Linh', 'Nam', 'W1', 'A00430', '2026-06-15', '0000-00-00', '2026-08-23 15:05:39'),
('02646365', 'Đinh Thị Đài Trang', 'Nữ', 'W1', 'A00430', '2026-08-10', '0000-00-00', '2026-08-23 15:05:39'),
('02646374', 'Lê Thị Mỹ Duyên', 'Nữ', 'W1', 'A00430', '2026-08-10', '0000-00-00', '2026-08-23 15:05:39'),
('02646383', 'Trần Thị Mỹ Duyên', 'Nữ', 'W1', 'A00430', '2026-08-10', '0000-00-00', '2026-08-23 15:05:39'),
('02646392', 'Trần Thị Tuyết', 'Nữ', 'W1', 'A00430', '2026-08-10', '0000-00-00', '2026-08-23 15:05:40'),
('02646408', 'Lưu Thị Phượng', 'Nữ', 'W1', 'A00430', '2026-08-10', '0000-00-00', '2026-08-23 15:05:40'),
('02646417', 'Đỗ Thị Yến Nhi', 'Nữ', 'W1', 'A00430', '2026-08-10', '0000-00-00', '2026-08-23 15:05:40'),
('1921108', 'Dương Thị Mỹ Hà', 'Nữ', 'W2', 'A00430', '0000-00-00', '0000-00-00', '2026-08-23 15:05:38'),
('I0000099', 'Lưu Quốc Bình', 'Nam', 'TTS', 'A00330', '2026-06-15', '0000-00-00', '2026-08-23 15:05:36'),
('I0000100', 'Lương Quốc Đại', 'Nam', 'TTS', 'A00330', '2026-06-15', '0000-00-00', '2026-08-23 15:05:36'),
('I0000101', 'Lê Trần Nhất Đáng', 'Nam', 'TTS', 'A00330', '2026-06-15', '0000-00-00', '2026-08-23 15:05:37'),
('I0000102', 'Nguyễn Minh Huy', 'Nữ', 'TTS', 'A00430', '2026-06-15', '0000-00-00', '2026-08-23 15:05:39'),
('I0000103', 'Thiều Hà Nam', 'Nữ', 'TTS', 'A00430', '2026-06-15', '0000-00-00', '2026-08-23 15:05:39'),
('I0000104', 'Nguyễn Minh Tú', 'Nữ', 'TTS', 'A00430', '2026-06-15', '0000-00-00', '2026-08-23 15:05:39'),
('TV004275', 'Nguyễn Quang Huy', 'Nam', 'TV', 'A00330', '2026-06-15', '0000-00-00', '2026-08-23 15:05:37'),
('TV004653', 'Tạ Văn Long', 'Nam', 'TV', 'A00330', '2026-08-06', '0000-00-00', '2026-08-23 15:05:37'),
('TV004654', 'Nguyễn Phan Cẩm Ly', 'Nữ', 'TV', 'A00430', '2026-08-06', '0000-00-00', '2026-08-23 15:05:40'),
('TV004730', 'Vũ Thị Kim Quyên', 'Nữ', 'TV', 'A00430', '2026-08-11', '0000-00-00', '2026-08-23 15:05:40'),
('TV004771', 'Trần Dương Tuấn Kiệt', 'Nam', 'TV', 'A00330', '2026-08-13', '0000-00-00', '2026-08-23 15:05:37'),
('TV004792', 'Đặng Lê Minh Tài', 'Nam', 'TV', 'A00330', '2026-08-18', '0000-00-00', '2026-08-23 15:05:37');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `logout_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Đang đổ dữ liệu cho bảng `login_logs`
--

INSERT INTO `login_logs` (`id`, `user_id`, `ip_address`, `user_agent`, `login_time`, `logout_time`) VALUES
(1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 14:32:21', '2026-08-18 21:32:38'),
(2, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 14:34:27', '2026-08-18 21:34:39'),
(3, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-18 14:39:34', '2026-08-18 21:39:59'),
(4, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-19 12:46:10', NULL),
(5, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-21 13:28:24', NULL),
(6, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-22 10:51:20', NULL),
(7, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 02:10:57', NULL),
(8, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 03:22:56', '2026-08-23 11:55:56'),
(9, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 04:56:18', NULL),
(10, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 04:57:54', '2026-08-23 11:58:07'),
(11, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 05:02:35', '2026-08-23 12:06:03'),
(12, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 05:06:51', NULL),
(13, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 05:38:23', '2026-08-23 16:28:43'),
(14, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 09:40:27', '2026-08-23 16:46:22'),
(15, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 09:46:26', '2026-08-23 16:49:52'),
(16, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 09:49:53', NULL),
(17, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 09:52:17', '2026-08-23 16:55:28'),
(18, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 09:55:29', '2026-08-23 17:05:13'),
(19, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 10:20:44', NULL),
(20, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 10:20:52', NULL),
(21, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 10:21:01', '2026-08-23 17:47:16'),
(22, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 10:47:17', '2026-08-23 17:48:22'),
(23, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 10:48:24', '2026-08-23 17:52:13'),
(24, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 10:52:15', '2026-08-23 17:53:57'),
(25, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 10:54:05', '2026-08-23 17:58:21'),
(26, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 10:58:22', '2026-08-23 18:05:40'),
(27, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 11:05:41', '2026-08-23 18:09:17'),
(28, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 11:09:19', '2026-08-23 18:18:42'),
(29, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 11:18:49', NULL),
(30, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 13:40:02', '2026-08-23 22:07:39'),
(31, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-23 15:07:41', '2026-08-23 22:07:46'),
(32, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-24 12:18:52', NULL),
(33, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 11:06:55', '2026-08-25 22:47:30'),
(34, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 15:47:39', '2026-08-25 23:44:56'),
(35, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 16:45:01', NULL),
(36, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 16:49:34', '2026-08-25 23:49:38'),
(37, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 16:49:39', '2026-08-25 23:50:08'),
(38, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 23:30:18', '2026-08-26 06:51:33'),
(39, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-25 23:51:35', NULL),
(40, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-26 11:54:55', NULL),
(41, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-26 14:36:27', NULL),
(42, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 13:34:09', NULL),
(43, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 13:52:47', '2026-08-27 20:52:51'),
(44, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-27 13:52:53', '2026-08-27 22:43:45'),
(45, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 13:12:44', NULL),
(46, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 00:57:38', NULL),
(47, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 01:00:07', NULL),
(48, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 05:43:10', NULL),
(49, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 05:43:55', NULL),
(50, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 05:44:26', NULL),
(51, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 05:44:57', NULL),
(52, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 06:24:30', NULL),
(53, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 06:26:20', NULL),
(54, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 06:29:19', NULL),
(55, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 06:46:43', NULL),
(56, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 06:49:15', NULL),
(57, 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 06:52:14', NULL),
(58, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 06:59:20', '2026-08-29 14:24:34'),
(59, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 07:32:43', '2026-08-29 14:32:48'),
(60, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 07:32:49', '2026-08-29 14:32:53'),
(61, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 07:32:56', '2026-08-29 14:33:04'),
(62, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-29 07:50:04', NULL),
(63, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 08:12:26', NULL),
(64, 1, '192.168.2.10', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/30.0 Chrome/143.0.0.0 Mobile Safari/537.36', '2026-08-31 08:29:31', NULL),
(65, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 10:51:12', NULL),
(66, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 13:03:56', NULL),
(67, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-09-01 01:55:25', NULL),
(68, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-02 23:44:42', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nhanvien`
--

CREATE TABLE `nhanvien` (
  `id` int(11) NOT NULL,
  `msnv` varchar(8) NOT NULL,
  `hoten` varchar(50) NOT NULL,
  `ngayvao` date DEFAULT NULL,
  `ngayra` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `nhanvien`
--

INSERT INTO `nhanvien` (`id`, `msnv`, `hoten`, `ngayvao`, `ngayra`, `created_at`) VALUES
(1, 'NV000001', 'An Van A', '2024-01-15', NULL, '2026-08-19 13:29:02'),
(2, 'NV000002', 'Binh Thi B', '2024-02-01', NULL, '2026-08-19 13:29:02'),
(3, 'NV000003', 'Cuong Le C', '2025-03-10', NULL, '2026-08-19 13:29:02'),
(5, '	NV00004', 'Tuan', '2026-08-17', NULL, '2026-08-19 14:33:15'),
(6, '02114273', 'Tuan', '2026-08-17', NULL, '2026-08-19 14:34:06'),
(7, '02114275', 'Tuana', '2026-08-19', NULL, '2026-08-19 14:36:40'),
(8, '02325365', 'Tuana', '2026-08-19', NULL, '2026-08-19 14:38:28'),
(10, '02114276', 'Tuan', '2026-08-21', NULL, '2026-08-21 13:29:59'),
(11, '', '', '0000-00-00', NULL, '2026-08-21 14:03:17'),
(16, '02114279', 'Tuan', '2026-08-24', NULL, '2026-08-21 14:05:52'),
(22, '02325369', 'Tuana', '2026-08-14', NULL, '2026-08-22 10:57:52'),
(24, '02325334', 'Tuana', '2026-08-14', NULL, '2026-08-22 10:58:13'),
(25, '02114277', 'Tuan', '2026-08-22', NULL, '2026-08-22 11:14:11'),
(28, '22', 'Tuan', '2026-08-22', NULL, '2026-08-22 11:15:40'),
(29, '25', 'Tuan', '2026-08-22', NULL, '2026-08-22 11:16:40');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive','banned') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `fullname`, `phone`, `status`, `created_at`, `updated_at`, `last_login`) VALUES
(1, 'admin', 'admin@myweb.com', 'admin', 'Admin User', '', 'active', '2026-08-18 14:32:03', '2026-09-02 23:44:42', '2026-09-03 06:44:42'),
(3, 'user', 'user@gmail.com', 'user', 'User', '', 'active', '2026-08-18 14:32:03', '2026-08-29 06:52:14', '2026-08-29 13:52:14');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `devices`
--
ALTER TABLE `devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `device_code` (`device_code`);

--
-- Chỉ mục cho bảng `device_history`
--
ALTER TABLE `device_history`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `device_historys`
--
ALTER TABLE `device_historys`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `device_status`
--
ALTER TABLE `device_status`
  ADD PRIMARY KEY (`device_id`);

--
-- Chỉ mục cho bảng `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_code`);

--
-- Chỉ mục cho bảng `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_login_time` (`login_time`);

--
-- Chỉ mục cho bảng `nhanvien`
--
ALTER TABLE `nhanvien`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `msnv` (`msnv`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `devices`
--
ALTER TABLE `devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `device_history`
--
ALTER TABLE `device_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT cho bảng `device_historys`
--
ALTER TABLE `device_historys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT cho bảng `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=144;

--
-- AUTO_INCREMENT cho bảng `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT cho bảng `nhanvien`
--
ALTER TABLE `nhanvien`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `login_logs`
--
ALTER TABLE `login_logs`
  ADD CONSTRAINT `login_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
