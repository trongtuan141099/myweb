-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th9 22, 2026 lúc 11:54 AM
-- Phiên bản máy phục vụ: 10.4.25-MariaDB
-- Phiên bản PHP: 8.1.10

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
-- Cấu trúc bảng cho bảng `inventory_org_levels`
--

CREATE TABLE `inventory_org_levels` (
  `id` int(11) NOT NULL,
  `level_number` int(11) NOT NULL,
  `level_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `badge_color` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'primary',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `inventory_org_levels`
--

INSERT INTO `inventory_org_levels` (`id`, `level_number`, `level_name`, `badge_color`, `description`, `sort_order`, `created_at`) VALUES
(1, 1, 'Cấp 1 - Quản lý kiểm kê', 'primary', '', 1, '2026-09-22 02:46:36'),
(2, 2, 'Cấp 2 - Người đại diện chịu trách nhiệm KK', 'info', '', 2, '2026-09-22 02:46:36'),
(3, 3, 'Cấp 3 - Trưởng nhóm KK', 'warning', '', 3, '2026-09-22 02:46:36'),
(4, 4, 'Cấp 4 - Cặp Kiểm Kê', 'success', '', 4, '2026-09-22 02:46:36'),
(5, 5, 'Cấp 5 - Hỗ trợ LOG & hỗ trợ line', 'secondary', '', 5, '2026-09-22 02:46:36');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `inventory_org_levels`
--
ALTER TABLE `inventory_org_levels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `level_number` (`level_number`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `inventory_org_levels`
--
ALTER TABLE `inventory_org_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
