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
-- Cấu trúc bảng cho bảng `inventory_campaigns`
--

CREATE TABLE `inventory_campaigns` (
  `id` int(11) NOT NULL,
  `campaign_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `audit_date` date DEFAULT NULL,
  `status` enum('planning','active','completed') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `inventory_campaigns`
--

INSERT INTO `inventory_campaigns` (`id`, `campaign_name`, `audit_date`, `status`, `notes`, `created_at`) VALUES
(1, 'Kiểm Kê Định Kỳ Nhà Máy - Quý 3/2026', '2026-09-30', 'active', 'Đợt kiểm kê toàn bộ nguyên vật liệu, bán thành phẩm, thành phẩm và công cụ dụng cụ', '2026-09-22 02:46:36'),
(2, 'Kiểm kê 9.2026', '2026-09-30', 'planning', 'Kiểm kê nhóm Đùn - Nghiền nhựa', '2026-09-22 03:07:12');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `inventory_campaigns`
--
ALTER TABLE `inventory_campaigns`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `inventory_campaigns`
--
ALTER TABLE `inventory_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
