function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  
  // Kiểm tra nếu là màn hình Mobile/Tablet
  if (window.innerWidth <= 768) {
    sidebar.classList.toggle('open');
  } else {
    // Màn hình Desktop: Thu gọn hoặc Mở rộng Sidebar
    sidebar.classList.toggle('collapsed');
  }
}

document.addEventListener("DOMContentLoaded", function () {
  // Đóng/Mở Submenu
  const parents = document.querySelectorAll(".menu-parent");

  parents.forEach((parent) => {
    const arrow = parent.querySelector(".arrow-icon");

    if (arrow) {
      arrow.addEventListener("click", function (e) {
        e.preventDefault(); // Ngăn chuyển trang nếu bấm vào mũi tên
        e.stopPropagation();

        const submenuContainer = parent.closest(".has-submenu");
        submenuContainer.classList.toggle("open");
      });
    }
  });
});