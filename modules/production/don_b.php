<?php
/**
 * Chuyển hướng kế thừa: Module Đơn B đã được chuyển sang phân hệ Quản Lý Đơn Hàng (Orders Management)
 */
if (!headers_sent()) {
    header("Location: index.php?mainpage=orders&subpage=don_b");
    exit;
} else {
    echo '<script>window.location.href="index.php?mainpage=orders&subpage=don_b";</script>';
    exit;
}
