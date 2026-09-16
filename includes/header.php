
  <div class="main-wrapper">
      <header class="top-header">
        <button class="mobile-menu-toggle" type="button" aria-label="Mở menu điều hướng" aria-controls="sidebar" aria-expanded="false" onclick="toggleSidebar()">
          <span class="material-icons">menu</span>
        </button>
        <div class="header-actions">
          <div class="notification-menu">
            <button class="header-icon-button" type="button" id="notificationToggle" aria-label="Thông báo" aria-expanded="false">
              <span class="material-icons">notifications_none</span>
              <span class="notification-count d-none" id="notificationCount">0</span>
            </button>
            <div class="notification-dropdown" id="notificationDropdown" hidden>
              <div class="notification-header">
                <strong>Thông báo</strong>
                <span class="notification-date" id="notificationDate"></span>
              </div>
              <div class="notification-list" id="notificationList">
                <div class="notification-empty">Đang tải thông báo...</div>
              </div>
              <a class="notification-footer" href="index.php?mainpage=five_s&subpage=mobile_audit">Xem công việc 5S hôm nay</a>
            </div>
          </div>
          <div class="user-menu">
            <button class="user-menu-toggle" type="button" id="userMenuToggle" aria-expanded="false">
              <span class="header-user-avatar" id="headerUserAvatar">T</span>
              <span class="header-user-name" id="headerUserName">Đang tải...</span>
              <span class="material-icons user-menu-arrow">expand_more</span>
            </button>
            <div class="user-dropdown" id="userDropdown" hidden>
              <a href="pages/admin.php" class="user-dropdown-item">
                <span class="material-icons">person_outline</span>
                <span>Hồ sơ của tôi</span>
              </a>
              <button type="button" class="user-dropdown-item" onclick="logout()">
                <span class="material-icons">logout</span>
                <span>Đăng xuất</span>
              </button>
            </div>
          </div>
        </div>

      </header>

      <script>
        (function () {
          const notificationToggle = document.getElementById('notificationToggle');
          const notificationDropdown = document.getElementById('notificationDropdown');
          const userMenuToggle = document.getElementById('userMenuToggle');
          const userDropdown = document.getElementById('userDropdown');

          function closeHeaderMenus(except) {
            if (except !== notificationDropdown) {
              notificationDropdown.hidden = true;
              notificationToggle.setAttribute('aria-expanded', 'false');
            }
            if (except !== userDropdown) {
              userDropdown.hidden = true;
              userMenuToggle.setAttribute('aria-expanded', 'false');
            }
          }

          notificationToggle.addEventListener('click', function (event) {
            event.stopPropagation();
            const willOpen = notificationDropdown.hidden;
            closeHeaderMenus(willOpen ? notificationDropdown : null);
            notificationDropdown.hidden = !willOpen;
            notificationToggle.setAttribute('aria-expanded', String(willOpen));
          });

          userMenuToggle.addEventListener('click', function (event) {
            event.stopPropagation();
            const willOpen = userDropdown.hidden;
            closeHeaderMenus(willOpen ? userDropdown : null);
            userDropdown.hidden = !willOpen;
            userMenuToggle.setAttribute('aria-expanded', String(willOpen));
          });

          document.addEventListener('click', function () { closeHeaderMenus(null); });

          function renderFiveSNotifications(data) {
            const schedules = data.schedules || [];
            const notifications = data.notifications || [];
            const pendingSchedules = schedules.filter(item => Number(item.can_audit) === 1);
            const total = pendingSchedules.length + notifications.length;
            const count = document.getElementById('notificationCount');
            const list = document.getElementById('notificationList');
            count.textContent = total > 99 ? '99+' : String(total);
            count.classList.toggle('d-none', total === 0);
            document.getElementById('notificationDate').textContent = new Date().toLocaleDateString('vi-VN');
            list.innerHTML = '';

            if (!total) {
              list.innerHTML = '<div class="notification-empty"><span class="material-icons">done_all</span><span>Không có thông báo mới</span></div>';
              return;
            }

            notifications.forEach(item => {
              const row = document.createElement('a');
              row.className = 'notification-item';
              row.href = item.link || 'index.php?mainpage=five_s&subpage=mobile_audit';
              row.innerHTML = '<span class="notification-icon"><span class="material-icons">campaign</span></span><span><strong>' + escapeHeaderText(item.title) + '</strong><small>' + escapeHeaderText(item.message) + '</small></span>';
              list.appendChild(row);
            });

            pendingSchedules.forEach(item => {
              const row = document.createElement('a');
              row.className = 'notification-item';
              row.href = 'index.php?mainpage=five_s&subpage=mobile_audit';
              row.innerHTML = '<span class="notification-icon notification-icon-task"><span class="material-icons">assignment</span></span><span><strong>Việc 5S hôm nay</strong><small>' + escapeHeaderText(item.zone_name) + ' (' + escapeHeaderText(item.zone_code) + ')</small></span><span class="notification-status">Chưa làm</span>';
              list.appendChild(row);
            });
          }

          function escapeHeaderText(value) {
            const element = document.createElement('span');
            element.textContent = value || '';
            return element.innerHTML;
          }

          fetch('api/five_s_get_schedules.php', { credentials: 'same-origin' })
            .then(response => response.json())
            .then(renderFiveSNotifications)
            .catch(() => {
              document.getElementById('notificationList').innerHTML = '<div class="notification-empty">Không thể tải thông báo</div>';
            });
        }());
      </script>
