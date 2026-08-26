<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Bảng điều khiển</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      background: #f5f5f5;
      color: #333;
    }

    .navbar {
      background: linear-gradient(135deg, #0ea5e9, #0284c7);
      color: white;
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .navbar h1 {
      font-size: 1.5rem;
    }

    .user-info {
      display: flex;
      gap: 1rem;
      align-items: center;
    }

    .user-info span {
      font-size: 0.9rem;
    }

    .btn-logout {
      background: #ef4444;
      color: white;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      transition: background 0.3s ease;
    }

    .btn-logout:hover {
      background: #dc2626;
    }

    .container {
      max-width: 1200px;
      margin: 2rem auto;
      padding: 0 1rem;
    }

    .card {
      background: white;
      border-radius: 12px;
      padding: 2rem;
      margin-bottom: 2rem;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .card h2 {
      margin-bottom: 1rem;
      color: #0284c7;
      border-bottom: 2px solid #0ea5e9;
      padding-bottom: 0.5rem;
    }

    .user-details {
      background: #f0f9ff;
      padding: 1.5rem;
      border-left: 4px solid #0ea5e9;
      border-radius: 8px;
    }

    .user-details p {
      margin: 0.5rem 0;
      line-height: 1.6;
    }

    .user-details strong {
      color: #0284c7;
      min-width: 120px;
      display: inline-block;
    }

    .loading {
      text-align: center;
      padding: 2rem;
      color: #666;
    }

    .error {
      background: #fee2e2;
      color: #dc2626;
      padding: 1rem;
      border-radius: 8px;
      border-left: 4px solid #dc2626;
      margin-bottom: 1rem;
    }

    .success {
      background: #f0fdf4;
      color: #16a34a;
      padding: 1rem;
      border-radius: 8px;
      border-left: 4px solid #16a34a;
      margin-bottom: 1rem;
    }

    @media (max-width: 768px) {
      .navbar {
        flex-direction: column;
        gap: 1rem;
      }

      .user-info {
        width: 100%;
        justify-content: space-between;
      }
    }
  </style>
</head>
<body>
  <div class="navbar">
    <h1>🏠 Bảng Điều Khiển</h1>
    <div class="user-info">
      <span>Chào, <strong id="username">...</strong></span>
      <button class="btn-logout" onclick="logout()">Đăng xuất</button>
    </div>
  </div>

  <div class="container">
    <div id="loading" class="loading">
      <p>⏳ Đang tải dữ liệu...</p>
    </div>

    <div id="content" style="display: none;">
      <div id="message"></div>

      <div class="card">
        <h2>📋 Thông tin cá nhân</h2>
        <div class="user-details">
          <p><strong>ID:</strong> <span id="userId">-</span></p>
          <p><strong>Username:</strong> <span id="userUsername">-</span></p>
          <p><strong>Email:</strong> <span id="userEmail">-</span></p>
          <p><strong>Họ tên:</strong> <span id="userFullname">-</span></p>
          <p><strong>Trạng thái:</strong> <span id="userStatus">-</span></p>
        </div>
      </div>

      <div class="card">
        <h2>🔒 Bảo mật</h2>
        <p>Để đổi mật khẩu, vui lòng <a href="#change-password">nhấp vào đây</a>.</p>
      </div>
    </div>
  </div>

  <script src="../js/main.js"></script>
  <!-- <script>
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

    function displayUserInfo(user) {
      document.getElementById('loading').style.display = 'none';
      document.getElementById('content').style.display = 'block';

      document.getElementById('username').textContent = user.username;
      document.getElementById('userId').textContent = user.id;
      document.getElementById('userUsername').textContent = user.username;
      document.getElementById('userEmail').textContent = user.email;
      document.getElementById('userFullname').textContent = user.fullname || 'Chưa cập nhật';

      showMessage(`Chào mừng ${user.fullname || user.username}! 👋`, 'success');
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
  </script> -->
</body>
</html>
