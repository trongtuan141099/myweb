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

  // Kiểm tra xem người dùng đã đăng nhập hay chưa
    async function checkAuthentication() {
      try {
        const response = await fetch('../php/check_auth.php');
        const data = await response.json();

        if (!data.authenticated) {
          // Chưa đăng nhập, chuyển hướng về login
          window.location.href = '../login.html';
          return;
        }

        // Hiển thị thông tin người dùng
        displayUserInfo(data.user);
      } catch (error) {
        console.error('Lỗi:', error);
        showMessage('Lỗi khi kiểm tra xác thực', 'error');
      }
    }

    // function displayUserInfo(user) {
    //   document.getElementById('loading').style.display = 'none';
    //   document.getElementById('content').style.display = 'block';

    //   document.getElementById('username').textContent = user.username;
    //   document.getElementById('userId').textContent = user.id;
    //   document.getElementById('userUsername').textContent = user.username;
    //   document.getElementById('userEmail').textContent = user.email;
    //   document.getElementById('userFullname').textContent = user.fullname || 'Chưa cập nhật';

    // //   showMessage(`Chào mừng ${user.fullname || user.username}! 👋`, 'success');
    // }

    function displayUserInfo(user) {
        // 1. Kiểm tra an toàn cho loading / content (nếu có)
        const loadingEl = document.getElementById('loading');
        if (loadingEl) loadingEl.style.display = 'none';

        const contentEl = document.getElementById('content');
        if (contentEl) contentEl.style.display = 'block';

        // 2. Gán dữ liệu vào các thẻ (Chỉ gán nếu tìm thấy ID trên giao diện)
        const elements = {
            'username': user.username,
            'userId': user.id,
            'userUsername': user.username,
            'userEmail': user.email,
            'userFullname': user.fullname || 'Chưa cập nhật'
        };

        // Vòng lặp gán dữ liệu an toàn không lo sập code
        Object.keys(elements).forEach(id => {
            const el = document.getElementById(id);
            if (el) {
            el.textContent = elements[id];
            }
        });

        // 3. Cập nhật chữ cái Avatar trên Sidebar (Tùy chọn)
        const avatarEl = document.querySelector('.user-avatar');
        if (avatarEl) {
            const displayName = user.fullname || user.username || 'T';
            avatarEl.textContent = displayName.charAt(0).toUpperCase();
        }
        }

    function showMessage(message, type) {
      const messageEl = document.getElementById('message');
      messageEl.innerHTML = `<div class="${type}">${message}</div>`;
    }

    async function logout() {
      if (confirm('Bạn có chắc chắn muốn đăng xuất?')) {
        try {
          const response = await fetch('../php/logout.php');
          const data = await response.json();

          if (data.success) {
            localStorage.removeItem('loggedInUser');
            window.location.href = '../login.html';
          }
        } catch (error) {
          console.error('Lỗi đăng xuất:', error);
          alert('Đã xảy ra lỗi khi đăng xuất');
        }
      }
    }

    // Kiểm tra khi trang tải
    document.addEventListener('DOMContentLoaded', checkAuthentication);
