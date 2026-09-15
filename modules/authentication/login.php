<!-- <!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Đăng nhập</title>
  <style>
    :root {
      --bg1: #f8fafc;
      --bg2: #f1f5f9;
      --panel: rgba(255, 255, 255, 0.95);
      --panel-border: rgba(226, 232, 240, 0.6);
      --primary: #0ea5e9;
      --primary-strong: #0284c7;
      --success: #22c55e;
      --warning: #f59e0b;
      --danger: #ef4444;
      --text: #1e293b;
      --muted: #64748b;
      --input: rgba(255, 255, 255, 0.8);
      --shadow: 0 25px 50px rgba(0, 0, 0, 0.08);
    }

    * { box-sizing: border-box; }

    html, body {
      margin: 0;
      min-height: 100%;
      width: 100%;
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, var(--bg1), var(--bg2));
      color: var(--text);
    }

    body {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      min-height: 100dvh;
      padding: max(16px, env(safe-area-inset-top)) max(16px, env(safe-area-inset-right)) max(16px, env(safe-area-inset-bottom)) max(16px, env(safe-area-inset-left));
      overflow-x: hidden;
    }

    .login-shell {
      width: min(100%, 440px);
      max-height: calc(100dvh - 32px);
      overflow-y: auto;
      background: var(--panel);
      border: 1px solid var(--panel-border);
      border-radius: 22px;
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      box-shadow: var(--shadow);
      overflow-x: hidden;
      overflow-y: auto;
    }

    .login-header {
      padding: clamp(22px, 6vw, 28px) clamp(20px, 6vw, 28px) 18px;
      text-align: center;
      background: linear-gradient(180deg, rgba(96,165,250,0.08), rgba(255,255,255,0));
    }

    .brand {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 62px;
      height: 62px;
      border-radius: 18px;
      background: linear-gradient(135deg, var(--primary), var(--primary-strong));
      color: white;
      font-size: 30px;
      margin-bottom: 14px;
      box-shadow: 0 10px 24px rgba(37, 99, 235, 0.45);
    }

    h1 {
      margin: 0;
      font-size: clamp(1.65rem, 7vw, 2rem);
      line-height: 1.15;
      letter-spacing: -0.04em;
    }

    .subtitle {
      margin-top: 8px;
      color: var(--muted);
      font-size: 0.97rem;
    }

    .login-body {
      padding: 10px clamp(20px, 6vw, 28px) clamp(22px, 6vw, 28px);
    }

    .form-group {
      position: relative;
      margin-bottom: 18px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      color: var(--muted);
      font-size: 0.88rem;
      font-weight: 600;
    }

    .input-wrap {
      position: relative;
    }

    .input-wrap i {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--muted);
      font-size: 0.95rem;
      pointer-events: none;
    }

    .input-wrap input {
      width: 100%;
      min-width: 0;
      height: 52px;
      border: 1px solid rgba(148, 163, 184, 0.2);
      border-radius: 12px;
      background: var(--input);
      color: var(--text);
      padding: 0 16px 0 42px;
      font-size: 1rem;
      outline: none;
      transition: .2s ease;
    }

    .input-wrap input:focus {
      border-color: rgba(96, 165, 250, 0.85);
      box-shadow: 0 0 0 4px rgba(96,165,250,0.12);
    }

    .password-toggle {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      border: 0;
      background: transparent;
      color: var(--muted);
      cursor: pointer;
      font-size: 0.9rem;
    }

    .meta-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 18px;
      gap: 12px;
      color: var(--muted);
      font-size: 0.9rem;
    }

    .remember {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
    }

    .remember input {
      accent-color: var(--primary);
      width: 15px;
      height: 15px;
    }

    .link {
      color: var(--primary);
      text-decoration: none;
      transition: opacity .2s ease;
    }

    .link:hover { opacity: .8; }

    .btn-login {
      width: 100%;
      border: none;
      border-radius: 12px;
      height: 52px;
      color: white;
      font-size: 1rem;
      font-weight: 700;
      background: linear-gradient(135deg, var(--primary), var(--primary-strong));
      cursor: pointer;
      transition: transform .2s ease, box-shadow .2s ease;
      box-shadow: 0 12px 25px rgba(37, 99, 235, 0.35);
    }

    .btn-login:hover {
      transform: translateY(-1px);
    }

    .status {
      min-height: 22px;
      margin-top: 16px;
      font-size: 0.92rem;
      font-weight: 600;
      text-align: center;
      display: none;
    }

    .status.success {
      display: block;
      color: #a7f3d0;
    }

    .status.error {
      display: block;
      color: #fecaca;
    }

    .status.success { color: #15803d; }
    .status.error { color: #b91c1c; }

    .register-note {
      margin-top: 22px;
      text-align: center;
      color: var(--muted);
      font-size: 0.95rem;
    }

    @media (max-width: 480px) {
      body { align-items: stretch; padding: 0; }
      .login-shell { width: 100%; min-height: 100dvh; max-height: none; border-radius: 0; border: 0; }
      .login-header { padding-top: max(28px, env(safe-area-inset-top)); }
      .brand { width: 54px; height: 54px; margin-bottom: 10px; font-size: 26px; border-radius: 15px; }
      .subtitle { font-size: .9rem; }
      .form-group { margin-bottom: 15px; }
      .input-wrap input, .btn-login { height: 50px; }
      .meta-row { align-items: flex-start; font-size: .84rem; }
      .register-note { margin-top: 18px; font-size: .88rem; }
    }

    @media (max-height: 640px) and (min-width: 481px) {
      body { align-items: flex-start; padding-top: 12px; padding-bottom: 12px; }
      .login-shell { max-height: calc(100dvh - 24px); }
      .login-header { padding-top: 18px; padding-bottom: 10px; }
      .brand { width: 50px; height: 50px; margin-bottom: 8px; }
      .login-body { padding-top: 6px; padding-bottom: 18px; }
    }
  </style>
</head>
<body> -->
  <style>
    :root {
      --bg1: #f8fafc;
      --bg2: #f1f5f9;
      --panel: rgba(255, 255, 255, 0.98);
      --panel-border: rgba(226, 232, 240, 0.8);
      --primary: #0ea5e9;
      --primary-strong: #0284c7;
      --success: #22c55e;
      --warning: #f59e0b;
      --danger: #ef4444;
      --text: #1e293b;
      --muted: #64748b;
      --input: #ffffff;
      --shadow: 0 25px 50px rgba(0, 0, 0, 0.08);
    }

    * { box-sizing: border-box; }

    /* Căn giữa trang ở Desktop và Full màn hình ở Mobile */
    .login-wrapper-container {
      width: 100%;
      min-height: 100vh;
      min-height: 100dvh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, var(--bg1), var(--bg2));
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      padding: 16px;
    }

    .login-shell {
      width: 100%;
      max-width: 440px;
      background: var(--panel);
      border: 1px solid var(--panel-border);
      border-radius: 22px;
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      box-shadow: var(--shadow);
      overflow: hidden;
    }

    .login-header {
      padding: clamp(28px, 6vw, 36px) clamp(20px, 6vw, 28px) 18px;
      text-align: center;
      background: linear-gradient(180deg, rgba(14, 165, 233, 0.06), rgba(255,255,255,0));
    }

    .brand {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 62px;
      height: 62px;
      border-radius: 18px;
      background: linear-gradient(135deg, var(--primary), var(--primary-strong));
      color: white;
      font-size: 30px;
      margin-bottom: 14px;
      box-shadow: 0 10px 24px rgba(14, 165, 233, 0.35);
    }

    h1 {
      margin: 0;
      font-size: clamp(1.65rem, 6vw, 1.85rem);
      line-height: 1.15;
      letter-spacing: -0.03em;
      color: var(--text);
    }

    .subtitle {
      margin-top: 8px;
      color: var(--muted);
      font-size: 0.95rem;
    }

    .login-body {
      padding: 10px clamp(20px, 6vw, 28px) clamp(24px, 6vw, 32px);
    }

    .form-group {
      position: relative;
      margin-bottom: 18px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      color: var(--muted);
      font-size: 0.88rem;
      font-weight: 600;
    }

    .input-wrap {
      position: relative;
    }

    .input-wrap i {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--muted);
      font-size: 1rem;
      pointer-events: none;
    }

    .input-wrap input {
      width: 100%;
      min-width: 0;
      height: 52px;
      border: 1px solid rgba(148, 163, 184, 0.3);
      border-radius: 12px;
      background: var(--input);
      color: var(--text);
      padding: 0 45px 0 42px;
      font-size: 1rem;
      outline: none;
      transition: .2s ease;
    }

    .input-wrap input:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.15);
    }

    .password-toggle {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      border: 0;
      background: transparent;
      color: var(--muted);
      cursor: pointer;
      font-size: 0.88rem;
      font-weight: 600;
      padding: 6px;
    }

    .meta-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      gap: 12px;
      color: var(--muted);
      font-size: 0.9rem;
    }

    .remember {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
    }

    .remember input {
      accent-color: var(--primary);
      width: 16px;
      height: 16px;
    }

    .link {
      color: var(--primary-strong);
      text-decoration: none;
      font-weight: 600;
      transition: opacity .2s ease;
    }

    .link:hover { opacity: .8; }

    .btn-login {
      width: 100%;
      border: none;
      border-radius: 12px;
      height: 52px;
      color: white;
      font-size: 1rem;
      font-weight: 700;
      background: linear-gradient(135deg, var(--primary), var(--primary-strong));
      cursor: pointer;
      transition: transform .2s ease, box-shadow .2s ease;
      box-shadow: 0 10px 20px rgba(14, 165, 233, 0.3);
    }

    .btn-login:active {
      transform: scale(0.98);
    }

    .status {
      min-height: 22px;
      margin-top: 16px;
      font-size: 0.92rem;
      font-weight: 600;
      text-align: center;
      display: none;
    }

    .status.success {
      display: block;
      color: #15803d;
    }

    .status.error {
      display: block;
      color: #b91c1c;
    }

    .register-note {
      margin-top: 22px;
      text-align: center;
      color: var(--muted);
      font-size: 0.95rem;
    }

    /* ĐIỀU CHỈNH RESPONSIVE CHUẨN ĐIỆN THOẠI (MOBILE FULL SCREEN) */
    @media (max-width: 576px) {
      .login-wrapper-container {
        padding: 0;
        align-items: stretch;
        background: #ffffff;
      }

      .login-shell {
        max-width: 100%;
        min-height: 100dvh;
        border-radius: 0;
        border: none;
        box-shadow: none;
        display: flex;
        flex-direction: column;
        justify-content: center;
      }

      .login-header {
        padding-top: max(40px, env(safe-area-inset-top));
      }

      .input-wrap input, .btn-login {
        height: 52px; /* Tối ưu chiều cao nút bấm cảm ứng */
      }
    }
  </style>

  <div class="login-wrapper-container">
    <div class="login-shell">
      <div class="login-header">
        <div class="brand">◌</div>
        <h1>Đăng nhập</h1>
        <div class="subtitle">Chào mừng bạn quay trở lại</div>
      </div>

      <div class="login-body">
        <form id="loginForm" novalidate>
          <div class="form-group">
            <label for="username">Tên đăng nhập hoặc Email</label>
            <div class="input-wrap">
              <i>👤</i>
              <input id="username" name="username" type="text" placeholder="Nhập tên đăng nhập hoặc email" required />
            </div>
          </div>

          <div class="form-group">
            <label for="password">Mật khẩu</label>
            <div class="input-wrap">
              <i>🔒</i>
              <input id="password" name="password" type="password" placeholder="Nhập mật khẩu" required />
              <button type="button" class="password-toggle" id="togglePassword" aria-label="Hiển thị mật khẩu">Hiện</button>
            </div>
          </div>

          <div class="meta-row">
            <label class="remember">
              <input type="checkbox" name="remember" />
              <span>Ghi nhớ đăng nhập</span>
            </label>
            <a class="link" href="#">Quên mật khẩu?</a>
          </div>

          <button class="btn-login" type="submit">Đăng nhập</button>
          <div id="loginStatus" class="status" aria-live="polite"></div>
        </form>

        <div class="register-note">
          Chưa có tài khoản? <a class="link" href="#">Đăng ký ngay</a>
        </div>
      </div>
    </div>
  </div>

  <script>
    const form = document.getElementById('loginForm');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const statusEl = document.getElementById('loginStatus');
    const togglePassword = document.getElementById('togglePassword');
    const loginButton = document.querySelector('.btn-login');

    function showStatus(message, type) {
      statusEl.textContent = message;
      statusEl.className = 'status ' + type;
    }

    togglePassword.addEventListener('click', function () {
      const isPassword = passwordInput.type === 'password';
      passwordInput.type = isPassword ? 'text' : 'password';
      this.textContent = isPassword ? 'Ẩn' : 'Hiện';
    });

    form.addEventListener('submit', async function (event) {
      event.preventDefault();

      const username = usernameInput.value.trim();
      const password = passwordInput.value.trim();

      if (!username || !password) {
        showStatus('Vui lòng nhập đầy đủ tên đăng nhập/email và mật khẩu.', 'error');
        return;
      }

      // Vô hiệu hóa nút đăng nhập khi đang xử lý
      loginButton.disabled = true;
      loginButton.textContent = 'Đang xử lý...';

      try {
        // Gửi request đến backend PHP ở thư mục gốc của ứng dụng
        const response = await fetch('/myweb/api/login_process.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            username: username,
            password: password
          })
        });

        const data = await response.json();

        if (data.success) {
          // Lưu thông tin user vào localStorage
          localStorage.setItem('loggedInUser', JSON.stringify(data.user));
          
          showStatus('✓ Đăng nhập thành công! Chào mừng ' + data.user.fullname, 'success');
          
          // Chuyển hướng sau 1 giây
          setTimeout(() => {
            window.location.href = '/myweb/index.php?mainpage=dashboard&subpage=overview';
          }, 500);
        } else {
          showStatus('✗ ' + data.message, 'error');
        }
      } catch (error) {
        showStatus('✗ Lỗi kết nối: ' + error.message, 'error');
      } finally {
        // Bật lại nút đăng nhập
        loginButton.disabled = false;
        loginButton.textContent = 'Đăng nhập';
      }
    });
  </script>
<!-- </body>
</html> -->