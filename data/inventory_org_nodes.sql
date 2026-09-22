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
-- Cấu trúc bảng cho bảng `inventory_org_nodes`
--

CREATE TABLE `inventory_org_nodes` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) DEFAULT 1,
  `parent_id` int(11) DEFAULT NULL,
  `level_number` int(11) NOT NULL DEFAULT 1,
  `position_title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `job_level` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `area_assigned` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duties` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `inventory_org_nodes`
--

INSERT INTO `inventory_org_nodes` (`id`, `campaign_id`, `parent_id`, `level_number`, `position_title`, `employee_code`, `full_name`, `department`, `job_level`, `area_assigned`, `duties`, `phone`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 1, 'Trưởng Ban Kiểm Kê', '01510036', 'Nguyễn Thành Thân', 'A00536', 'M1', 'Toàn Nhà Máy', 'Chỉ đạo toàn diện công tác kiểm kê, chốt thời điểm khóa sổ, phê duyệt kết quả xử lý chênh lệch tồn kho.', '0901 234 567', 1, 'active', '2026-09-22 02:46:36', '2026-09-22 02:46:36'),
(2, 1, 1, 2, 'Phó Ban / Thư Ký Tổng Hợp', '01910698', 'Nguyễn Thị Hiền', 'A00536', 'S3', 'Văn Phòng Kiểm Kê & Giám Sát', 'Phát thẻ kiểm kê, giám sát tiến độ các tổ, thu nhận biên bản, đối soát thẻ kho trên hệ thống.', '0902 345 678', 1, 'active', '2026-09-22 02:46:36', '2026-09-22 02:46:36'),
(3, 1, 2, 3, 'Tổ Trưởng Kho Nguyên Vật Liệu', '01911022', 'Kiều Minh Thiện', 'A00791', 'S4', 'Kho Hạt Nhựa & Phụ Gia', 'Phụ trách phân công đếm thực tế các lô hạt nhựa nguyên sinh, tái sinh, masterbatch; niêm phong sau kiểm kê.', '0903 456 789', 1, 'active', '2026-09-22 02:46:36', '2026-09-22 02:46:36'),
(4, 1, 2, 3, 'Tổ Trưởng Kho Thành Phẩm', '01920163', 'Lê Thị Phương Dung', 'A00430', 'W1', 'Kho Thành Phẩm B1 & B2', 'Tổ chức kiểm đếm kiện hàng thành phẩm theo quy cách, pallet, lô sản xuất; ký xác nhận thẻ kho.', '0904 567 890', 2, 'active', '2026-09-22 02:46:36', '2026-09-22 02:46:36'),
(5, 1, 3, 4, 'Cặp Kiểm Kê 1 - Hạt Nhựa', '01920172', 'Võ Thị Mỹ', 'A00792', 'W4', 'Dãy Kệ A1 - A5 Kho NVL', 'Kiểm đếm bao bì, cân trọng lượng mẫu, dán thẻ kiểm kê màu xanh đã kiểm kê.', '0905 678 901', 1, 'active', '2026-09-22 02:46:36', '2026-09-22 02:46:36'),
(6, 1, 3, 4, 'Cặp Kiểm Kê 2 - Phụ Gia Màu', '01920260', 'Nguyễn Thị Lý', 'A00430', 'W4', 'Dãy Kệ B1 - B3 Kho Phụ Gia', 'Đếm thùng, can phụ gia màu nước và hạt màu, ghi chép số lượng thực tế vào phiếu.', '0906 789 012', 2, 'active', '2026-09-22 02:46:36', '2026-09-22 02:46:36'),
(7, 1, 4, 4, 'Cặp Kiểm Kê 3 - Ống Nhựa HDPE', '01920507', 'Hồ Thị Mỹ', 'A00340', 'W3', 'Bãi Thành Phẩm Ngoài Trời', 'Kiểm đếm cuộn ống HDPE và cây ống quy cách, đo chiều dài hoặc đếm số lượng.', '0907 890 123', 1, 'active', '2026-09-22 02:46:36', '2026-09-22 02:46:36'),
(8, 1, 3, 4, 'Cặp Kiểm Kê Dự Phòng - Test', '01911022', 'Kiều Minh Thiện', 'A00791', 'S4', 'Kho NVL Kệ C', 'Kiểm đếm bổ sung và đối soát', '999', 5, 'active', '2026-09-22 02:51:43', '2026-09-22 02:51:43'),
(9, 1, 3, 4, 'Cặp Kiểm Kê Dự Phòng - Test', '01911022', 'Kiều Minh Thiện', 'A00791', 'S4', 'Kho NVL Kệ C', 'Kiểm đếm bổ sung và đối soát', '999', 5, 'active', '2026-09-22 02:52:10', '2026-09-22 02:52:10'),
(10, 1, 3, 4, 'Cặp Kiểm Kê Đã Sửa Title', '01911022', 'Kiều Minh Thiện', 'A00791', 'S4', 'Kho NVL Kệ C (Đã sửa)', 'Nhiệm vụ mới cập nhật', '999', 10, 'active', '2026-09-22 02:52:24', '2026-09-22 02:52:24'),
(11, 1, 3, 4, 'Cặp Kiểm Kê Đã Sửa Title', '01911022', 'Kiều Minh Thiện', 'A00791', 'S4', 'Kho NVL Kệ C (Đã sửa)', 'Nhiệm vụ mới cập nhật', '999', NULL, 'active', '2026-09-22 02:52:50', '2026-09-22 02:52:50'),
(13, 2, NULL, 1, 'Trưởng Ban Kiểm Kê', '01510036', 'Nguyễn Thành Thân', 'A00536', 'M1', 'Bộ phận Plastic', '', '0', 1, 'active', '2026-09-22 03:50:55', '2026-09-22 03:50:55'),
(14, 2, 13, 2, 'Đại diện chịu trách nhiệm', '01911022', 'Kiều Minh Thiện', 'A00791', 'S4', 'Bộ phận Plastic', '', '0', 1, 'active', '2026-09-22 03:51:35', '2026-09-22 03:51:35'),
(15, 2, 14, 3, 'Trưởng nhóm KK', '02114273', 'Thân Trọng Tuấn', 'A00564', 'S3', 'A00330 - Nhóm Đùn nhựa', '', '0', 1, 'active', '2026-09-22 03:52:41', '2026-09-22 03:52:41'),
(16, 2, 14, 3, 'Trưởng nhóm KK', '02020752', 'Nguyễn Chí Cường', 'A00442', 'W3', 'A00442 - Nhóm Nghiền nhựa', '', '0', 1, 'active', '2026-09-22 03:55:00', '2026-09-22 03:55:00');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `inventory_org_nodes`
--
ALTER TABLE `inventory_org_nodes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaign_id` (`campaign_id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `employee_code` (`employee_code`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `inventory_org_nodes`
--
ALTER TABLE `inventory_org_nodes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
