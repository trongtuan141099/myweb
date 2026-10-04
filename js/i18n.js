/**
 * DX Plastic Group - Client-side Internationalization (i18n) Engine
 * Hỗ trợ chuyển đổi ngôn ngữ mượt mà không cần tải lại trang (Việt - Anh - Nhật)
 */

(function (window, document) {
  'use strict';

  // Khởi tạo trạng thái ngôn ngữ
  let currentLang = window.__APP_LANG || localStorage.getItem('dx-lang') || 'vi';
  if (!['vi', 'en', 'ja'].includes(currentLang)) {
    currentLang = 'vi';
  }

  const allDicts = window.__I18N_ALL_DICTS || {
    vi: window.__I18N_DICT || {},
    en: {},
    ja: {}
  };

  const supportedLangs = window.__APP_LANGS || {
    vi: { code: 'vi', name: 'Tiếng Việt', flag: '🇻🇳', short: 'VI', locale: 'vi-VN' },
    en: { code: 'en', name: 'English', flag: '🇬🇧', short: 'EN', locale: 'en-US' },
    ja: { code: 'ja', name: '日本語', flag: '🇯🇵', short: 'JA', locale: 'ja-JP' }
  };

  const FLAG_SVGS = {
    vi: '<svg class="lang-flag-svg" viewBox="0 0 30 20" width="18" height="12" aria-hidden="true" style="border-radius:2px;vertical-align:middle;box-shadow:0 0 1px rgba(0,0,0,0.25);flex-shrink:0;"><rect width="30" height="20" rx="2" fill="#da251d"/><polygon points="15,4 16.5,8.8 21.5,8.8 17.5,11.8 19,16.5 15,13.5 11,16.5 12.5,11.8 8.5,8.8 13.5,8.8" fill="#ff0"/></svg>',
    en: '<svg class="lang-flag-svg" viewBox="0 0 60 40" width="18" height="12" aria-hidden="true" style="border-radius:2px;vertical-align:middle;box-shadow:0 0 1px rgba(0,0,0,0.3);flex-shrink:0;"><clipPath id="dxgb_js_trig"><rect width="60" height="40" rx="3"/></clipPath><g clip-path="url(#dxgb_js_trig)"><rect width="60" height="40" fill="#012169"/><path d="M0,0 L60,40 M60,0 L0,40" stroke="#fff" stroke-width="8"/><path d="M0,0 L60,40 M60,0 L0,40" stroke="#c8102e" stroke-width="4"/><path d="M30,0 v40 M0,20 h60" stroke="#fff" stroke-width="12"/><path d="M30,0 v40 M0,20 h60" stroke="#c8102e" stroke-width="7"/></g></svg>',
    ja: '<svg class="lang-flag-svg" viewBox="0 0 30 20" width="18" height="12" aria-hidden="true" style="border-radius:2px;vertical-align:middle;box-shadow:0 0 1px rgba(0,0,0,0.25);flex-shrink:0;"><rect width="30" height="20" rx="2" fill="#ffffff" stroke="#cbd5e1" stroke-width="0.8"/><circle cx="15" cy="10" r="6" fill="#bc002d"/></svg>'
  };

  // Tạo bản đồ tra cứu ngược các cụm từ (Reverse Phrase Map) để tự động dịch các text UI phổ biến
  let reversePhraseMap = null;

  function buildReversePhraseMap() {
    if (reversePhraseMap) return reversePhraseMap;
    reversePhraseMap = new Map();

    const keys = new Set();
    ['vi', 'en', 'ja'].forEach(lang => {
      const dict = allDicts[lang] || {};
      Object.keys(dict).forEach(k => keys.add(k));
    });

    keys.forEach(k => {
      const viVal = (allDicts.vi && allDicts.vi[k]) ? String(allDicts.vi[k]).trim().toLowerCase() : null;
      const enVal = (allDicts.en && allDicts.en[k]) ? String(allDicts.en[k]).trim().toLowerCase() : null;
      const jaVal = (allDicts.ja && allDicts.ja[k]) ? String(allDicts.ja[k]).trim().toLowerCase() : null;

      if (viVal && viVal.length > 1) reversePhraseMap.set(viVal, k);
      if (enVal && enVal.length > 1) reversePhraseMap.set(enVal, k);
      if (jaVal && jaVal.length > 1) reversePhraseMap.set(jaVal, k);
    });

    return reversePhraseMap;
  }

  /**
   * Hàm dịch khóa sang ngôn ngữ hiện tại
   * @param {string} key - Khóa dịch (VD: 'common.btn_save')
   * @param {string} [fallback] - Giá trị dự phòng nếu không tìm thấy khóa
   * @param {Object} [params] - Tham số thay thế
   * @returns {string}
   */
  function t(key, fallback, params) {
    if (!key) return '';

    const langDict = allDicts[currentLang] || {};
    let text = langDict[key];

    // Fallback sang tiếng Việt nếu ngôn ngữ hiện tại chưa có key
    if (text === undefined || text === null) {
      const viDict = allDicts.vi || {};
      text = viDict[key];
    }

    if (text === undefined || text === null) {
      text = (fallback !== undefined) ? fallback : key;
    }

    // Thay thế tham số :param hoặc {param}
    if (params && typeof params === 'object') {
      Object.keys(params).forEach(pKey => {
        const val = params[pKey];
        const cleanKey = pKey.replace(/^[:{]/, '').replace(/[}]$/, '');
        const regex = new RegExp('([:\{]' + cleanKey + '[\}]?)', 'g');
        text = text.replace(regex, val);
      });
    }

    return text;
  }

  /**
   * Cập nhật ngôn ngữ và chuyển đổi giao diện mượt mà lập tức
   * @param {string} lang - 'vi' | 'en' | 'ja'
   * @param {boolean} [syncServer=true] - Gửi API cập nhật phiên làm việc lên server
   */
  function setAppLanguage(lang, syncServer = true) {
    if (!['vi', 'en', 'ja'].includes(lang)) {
      lang = 'vi';
    }

    currentLang = lang;
    window.__APP_LANG = lang;
    try {
      localStorage.setItem('dx-lang', lang);
    } catch (e) {}

    // Cập nhật thuộc tính trên thẻ <html>
    document.documentElement.setAttribute('lang', lang);

    // Đồng bộ giao diện nút chuyển đổi trên Head bar
    updateHeadbarLanguageUI(lang);

    // Dịch toàn bộ cây DOM
    translateDocument(lang);

    // Gửi thông báo đến server lưu phiên & cookie không cần reload
    if (syncServer) {
      fetch('api/set_language.php?lang=' + encodeURIComponent(lang), {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      }).catch(err => {
        console.warn('Lỗi đồng bộ ngôn ngữ lên server:', err);
      });
    }

    // Phát sự kiện toàn cục để các bảng dữ liệu, biểu đồ hay module cập nhật
    window.dispatchEvent(new CustomEvent('dxLanguageChanged', {
      detail: {
        lang: lang,
        t: t,
        dict: allDicts[lang] || {}
      }
    }));
  }

  /**
   * Cập nhật trạng thái hiển thị của component Ngôn ngữ trên Head bar
   */
  function updateHeadbarLanguageUI(lang) {
    const info = supportedLangs[lang] || supportedLangs.vi;

    const flagEl = document.getElementById('currentLangFlag');
    if (flagEl) {
      flagEl.innerHTML = FLAG_SVGS[lang] || info.flag || '';
    }

    const codeEl = document.getElementById('currentLangCode');
    if (codeEl) codeEl.textContent = info.short;

    // Đánh dấu active trong dropdown
    document.querySelectorAll('.lang-item[data-lang]').forEach(item => {
      const itemLang = item.getAttribute('data-lang');
      const isActive = (itemLang === lang);
      item.classList.toggle('active', isActive);
      const checkIcon = item.querySelector('.lang-check');
      if (checkIcon) {
        checkIcon.style.display = isActive ? 'inline-flex' : 'none';
      }
    });

    // Tự động đóng dropdown sau khi chọn
    const dropdown = document.getElementById('langDropdown');
    const toggle = document.getElementById('langMenuToggle');
    if (dropdown) dropdown.hidden = true;
    if (toggle) toggle.setAttribute('aria-expanded', 'false');
  }

  /**
   * Quét và dịch toàn bộ nội dung hiển thị trên trang hiện tại
   */
  function translateDocument(lang) {
    // 1. Dịch các phần tử có thuộc tính [data-i18n]
    document.querySelectorAll('[data-i18n]').forEach(el => {
      const key = el.getAttribute('data-i18n');
      const translated = t(key);
      if (!translated) return;

      // Bảo toàn các thẻ icon Material Icons nếu có bên trong phần tử
      const icon = el.querySelector('.material-icons');
      const label = el.querySelector('.label');

      if (label) {
        label.textContent = translated;
      } else if (icon) {
        // Nếu có icon và text node liền kề
        let foundTextNode = false;
        el.childNodes.forEach(node => {
          if (node.nodeType === Node.TEXT_NODE && node.nodeValue.trim().length > 0) {
            node.nodeValue = ' ' + translated;
            foundTextNode = true;
          }
        });
        if (!foundTextNode) {
          // Thêm text node mới sau icon
          const textNode = document.createTextNode(' ' + translated);
          el.appendChild(textNode);
        }
      } else {
        el.textContent = translated;
      }
    });

    // 2. Dịch placeholder [data-i18n-placeholder]
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
      const key = el.getAttribute('data-i18n-placeholder');
      el.placeholder = t(key);
    });

    // 3. Dịch title tooltip [data-i18n-title]
    document.querySelectorAll('[data-i18n-title]').forEach(el => {
      const key = el.getAttribute('data-i18n-title');
      el.title = t(key);
    });

    // 4. Dịch aria-label [data-i18n-aria]
    document.querySelectorAll('[data-i18n-aria]').forEach(el => {
      const key = el.getAttribute('data-i18n-aria');
      el.setAttribute('aria-label', t(key));
    });

    // 5. Quét thông minh (Smart Phrase Matcher) cho các thành phần giao diện phổ biến chưa gắn data-i18n
    smartTranslateVisiblePhrases();
  }

  /**
   * Bộ dịch cụm từ thông minh dựa trên bản đồ tra cứu ngược
   */
  function smartTranslateVisiblePhrases() {
    const revMap = buildReversePhraseMap();
    if (!revMap) return;

    // Bộ chọn các thành phần UI cần dịch tự động
    const selectors = [
      '.sidebar-menu .label',
      '.app-page-title',
      '.app-page-subtitle',
      '.app-btn:not([data-i18n])',
      'table th:not([data-i18n])',
      '.app-pagination-info',
      '.app-pagination-size span',
      '.notification-header span:first-child',
      '.notification-footer',
      '.theme-switch-info span#themeSwitchLabel',
      '.user-dropdown-item span:not(.material-icons)'
    ];

    selectors.forEach(sel => {
      document.querySelectorAll(sel).forEach(el => {
        // Bỏ qua nếu đã gắn data-i18n
        if (el.hasAttribute('data-i18n') || el.closest('[data-i18n]')) return;

        // Chỉ xử lý các phần tử không có con phức tạp
        let directText = '';
        el.childNodes.forEach(child => {
          if (child.nodeType === Node.TEXT_NODE) {
            directText += child.nodeValue;
          }
        });
        directText = directText.trim().toLowerCase();

        if (directText && revMap.has(directText)) {
          const matchedKey = revMap.get(directText);
          const translated = t(matchedKey);
          if (translated) {
            el.childNodes.forEach(child => {
              if (child.nodeType === Node.TEXT_NODE && child.nodeValue.trim().length > 0) {
                child.nodeValue = child.nodeValue.startsWith(' ') ? (' ' + translated) : translated;
              }
            });
            // Tự động gắn data-i18n để các lần chuyển sau không cần tra cứu ngược nữa
            el.setAttribute('data-i18n', matchedKey);
          }
        }
      });
    });
  }

  /**
   * Gắn sự kiện điều khiển Dropdown chọn ngôn ngữ
   */
  function initLangDropdownUI() {
    const toggleBtn = document.getElementById('langMenuToggle');
    const dropdown = document.getElementById('langDropdown');

    if (!toggleBtn || !dropdown) return;
    if (toggleBtn._langDropdownBound) return;
    toggleBtn._langDropdownBound = true;

    toggleBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      const willOpen = dropdown.hidden;

      // Đóng các dropdown khác nếu đang mở
      const notifDd = document.getElementById('notificationDropdown');
      const userDd = document.getElementById('userDropdown');
      if (notifDd) notifDd.hidden = true;
      if (userDd) userDd.hidden = true;

      dropdown.hidden = !willOpen;
      toggleBtn.setAttribute('aria-expanded', String(willOpen));
    });

    // Đóng khi click ngoài
    document.addEventListener('click', function (e) {
      if (!dropdown.hidden && !dropdown.contains(e.target) && !toggleBtn.contains(e.target)) {
        dropdown.hidden = true;
        toggleBtn.setAttribute('aria-expanded', 'false');
      }
    });

    // Đóng khi nhấn Escape
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !dropdown.hidden) {
        dropdown.hidden = true;
        toggleBtn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // Khởi động khi tải xong DOM
  document.addEventListener('DOMContentLoaded', function () {
    initLangDropdownUI();
    updateHeadbarLanguageUI(currentLang);

    // Dịch các thành phần ban đầu
    translateDocument(currentLang);
  });

  // Xuất các phương thức ra window toàn cục
  window.t = t;
  window.__t = t;
  window.__ = t;
  window.setAppLanguage = setAppLanguage;
  window.translateDocument = translateDocument;
  window.getCurrentLanguage = function () { return currentLang; };
  window.getSupportedLanguages = function () { return supportedLangs; };
  window.getDictionary = function (lang) { return allDicts[lang || currentLang] || {}; };

  // Định dạng số & ngày theo ngôn ngữ
  window.formatNumber = function (num, options) {
    const locale = (supportedLangs[currentLang] || {}).locale || 'vi-VN';
    return new Intl.NumberFormat(locale, options).format(num);
  };

  window.formatDate = function (date, options) {
    const locale = (supportedLangs[currentLang] || {}).locale || 'vi-VN';
    const d = (date instanceof Date) ? date : new Date(date);
    return new Intl.DateTimeFormat(locale, options).format(d);
  };

}(window, document));
