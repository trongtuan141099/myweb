#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <ctype.h>
#include "freertos/FreeRTOS.h"
#include "freertos/task.h"
#include "freertos/event_groups.h"
#include "esp_system.h"
#include "esp_wifi.h"
#include "esp_event.h"
#include "esp_log.h"
#include "nvs_flash.h"
#include "nvs.h"
#include "driver/gpio.h"
#include "esp_http_server.h"
#include "esp_http_client.h"
#include "lwip/sockets.h"

#define INPUT_PIN          GPIO_NUM_4
#define RESET_BTN_PIN      GPIO_NUM_0  // Nút BOOT trên board ESP32

static const char *TAG = "ESP32_IOT";
static httpd_handle_t server = NULL;

static char device_id[32] = "PL01";
static char server_ip[64] = "192.168.2.16";
static char api_path[128] = "/myweb/api/iot_status.php";
static char my_ip[20]     = "Connecting...";
static int last_pin_state = -1;

// --- 1. HÀM GIẢI MÃ URL DECODE (CHUYỂN %2F THÀNH / TRÁNH LỖI PARSE URL) ---
static void url_decode(char *dst, const char *src) {
    char a, b;
    while (*src) {
        if ((*src == '%') && ((a = src[1]) && (b = src[2])) && (isxdigit((unsigned char)a) && isxdigit((unsigned char)b))) {
            if (a >= 'a' && a <= 'f') a -= 'a' - 'A';
            if (a >= 'A' && a <= 'F') a = a - 'A' + 10;
            else a -= '0';
            if (b >= 'a' && b <= 'f') b -= 'a' - 'A';
            if (b >= 'A' && b <= 'F') b = b - 'A' + 10;
            else b -= '0';
            *dst++ = 16 * a + b;
            src += 3;
        } else if (*src == '+') {
            *dst++ = ' ';
            src++;
        } else {
            *dst++ = *src++;
        }
    }
    *dst = '\0';
}

// --- 2. TASK THEO DÕI NÚT BOOT (GIỮ 5 GIÂY XÓA NVS VÀ REBOOT) ---
static void boot_button_monitor_task(void *pvParameters) {
    int hold_time_ms = 0;
    while (1) {
        if (gpio_get_level(RESET_BTN_PIN) == 0) { // Đang nhấn nút BOOT
            hold_time_ms += 200;
            if (hold_time_ms % 1000 == 0) {
                ESP_LOGW(TAG, "Giữ nút BOOT: %d/5 giây", hold_time_ms / 1000);
            }
            if (hold_time_ms >= 5000) {
                ESP_LOGE(TAG, "Đã giữ nút BOOT 5s! Xóa NVS & Reboot...");
                nvs_flash_erase();
                vTaskDelay(pdMS_TO_TICKS(500));
                esp_restart();
            }
        } else {
            hold_time_ms = 0;
        }
        vTaskDelay(pdMS_TO_TICKS(200));
    }
}

// --- 3. CAPTIVE PORTAL DNS TASK (AP MODE) ---
static void dns_server_task(void *pvParameters) {
    uint8_t rx_buffer[128];
    struct sockaddr_in server_addr, client_addr;
    socklen_t client_addr_len = sizeof(client_addr);

    int sock = socket(AF_INET, SOCK_DGRAM, IPPROTO_IP);
    if (sock < 0) { vTaskDelete(NULL); return; }

    memset(&server_addr, 0, sizeof(server_addr));
    server_addr.sin_family = AF_INET;
    server_addr.sin_port = htons(53);
    server_addr.sin_addr.s_addr = htonl(INADDR_ANY);

    if (bind(sock, (struct sockaddr *)&server_addr, sizeof(server_addr)) < 0) {
        close(sock); vTaskDelete(NULL); return;
    }

    while (1) {
        int len = recvfrom(sock, rx_buffer, sizeof(rx_buffer) - 1, 0, (struct sockaddr *)&client_addr, &client_addr_len);
        if (len > 12) {
            rx_buffer[2] |= 0x80; rx_buffer[3] |= 0x80; rx_buffer[7] = 1;
            uint8_t answer[] = { 0xc0, 0x0c, 0x00, 0x01, 0x00, 0x01, 0x00, 0x00, 0x00, 0x3c, 0x00, 0x04, 192, 168, 4, 1 };
            memcpy(rx_buffer + len, answer, sizeof(answer));
            sendto(sock, rx_buffer, len + sizeof(answer), 0, (struct sockaddr *)&client_addr, client_addr_len);
        }
    }
    close(sock);
    vTaskDelete(NULL);
}

// --- 4. HÀM GỬI DỮ LIỆU TỚI XAMPP (ĐÃ CHỐNG LỖI PARSE URL & PANIC) ---
static void send_status_to_xampp(const char* status) {
    char post_data[256];
    snprintf(post_data, sizeof(post_data), "device_id=%s&status=%s&ip=%s", device_id, status, my_ip);

    char raw_url[256];
    char clean_url[256];
    
    snprintf(raw_url, sizeof(raw_url), "http://%s%s", server_ip, api_path);
    url_decode(clean_url, raw_url); // Giải mã chuỗi %2F -> /

    esp_http_client_config_t config = {
        .url = clean_url,
        .method = HTTP_METHOD_POST,
        .timeout_ms = 3000,
    };
    
    esp_http_client_handle_t client = esp_http_client_init(&config);
    if (client == NULL) {
        ESP_LOGE(TAG, "Tạo HTTP Client thất bại cho URL: %s", clean_url);
        return;
    }

    esp_http_client_set_post_field(client, post_data, strlen(post_data));
    esp_http_client_set_header(client, "Content-Type", "application/x-www-form-urlencoded");

    esp_err_t err = esp_http_client_perform(client);
    if (err == ESP_OK) {
        ESP_LOGI(TAG, "Gửi XAMPP (%s) thành công: %s", clean_url, status);
    } else {
        ESP_LOGE(TAG, "Gửi XAMPP (%s) thất bại: %s", clean_url, esp_err_to_name(err));
    }
    esp_http_client_cleanup(client);
}

// --- 5. GIAO DIỆN NỘI BỘ ESP32 (STATION MODE) ---
static esp_err_t status_get_handler(httpd_req_t *req) {
    int raw_pin = gpio_get_level(INPUT_PIN);
    bool is_on = (raw_pin == 1); 

    char *resp_html = (char *)malloc(4096);
    if (resp_html == NULL) {
        httpd_resp_send_500(req);
        return ESP_FAIL;
    }

    snprintf(resp_html, 4096,
        "<!DOCTYPE html><html lang='vi'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1'>"
        "<title>Bảng Giám Sát Thiết Bị</title><style>"
        "* { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }"
        "body { background: #f0f2f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }"
        ".card { background: #ffffff; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); width: 100%%; max-width: 420px; text-align: center; }"
        ".header-icon { font-size: 48px; margin-bottom: 10px; }"
        "h2 { color: #1a202c; font-size: 22px; margin-bottom: 20px; font-weight: 700; }"
        ".info-group { background: #f7fafc; padding: 15px; border-radius: 12px; margin-bottom: 20px; text-align: left; border: 1px solid #edf2f7; }"
        ".info-item { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; color: #4a5568; word-break: break-all; }"
        ".info-val { font-weight: 600; color: #2d3748; text-align: right; margin-left: 10px; }"
        ".status-box { padding: 16px; border-radius: 12px; font-weight: 700; font-size: 16px; margin-bottom: 25px; transition: 0.3s; }"
        ".ON { background: #e6fffa; color: #234e52; border: 1px solid #b2f5ea; }"
        ".OFF { background: #fff5f5; color: #742a2a; border: 1px solid #fed7d7; }"
        ".btn-reset { display: block; width: 100%%; background: #e53e3e; color: #fff; padding: 14px; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 15px; box-shadow: 0 4px 12px rgba(229,62,62,0.2); transition: 0.2s; }"
        "</style></head><body>"
        "<div class='card'>"
        "<div class='header-icon'>📡</div>"
        "<h2>Giám Sát Thiết Bị</h2>"
        "<div class='info-group'>"
        "<div class='info-item'><span>Mã thiết bị:</span><span class='info-val'>%s</span></div>"
        "<div class='info-item'><span>Địa chỉ IP ESP:</span><span class='info-val'>%s</span></div>"
        "<div class='info-item'><span>Server Target:</span><span class='info-val'>%s</span></div>"
        "<div class='info-item'><span>API Path:</span><span class='info-val'>%s</span></div>"
        "</div>"
        "<div class='status-box %s'>Trạng thái: %s</div>"
        "<a href='/reset_wifi' class='btn-reset' onclick='return confirm(\"Xác nhận Xóa Cấu Hình & Reset?\")'>Reset Cấu Hình Thiết Bị</a>"
        "</div></body></html>",
        device_id, my_ip, server_ip, api_path,
        is_on ? "ON" : "OFF",
        is_on ? "🟢 ĐANG BẬT (Thả nút)" : "🔴 ĐÃ TẮT (Nhấn nút)"
    );

    httpd_resp_send(req, resp_html, HTTPD_RESP_USE_STRLEN);
    free(resp_html);
    return ESP_OK;
}

static esp_err_t reset_wifi_handler(httpd_req_t *req) {
    nvs_handle_t my_handle;
    if (nvs_open("wifi_store", NVS_READWRITE, &my_handle) == ESP_OK) {
        nvs_erase_all(my_handle);
        nvs_commit(my_handle);
        nvs_close(my_handle);
    }
    httpd_resp_send(req, "Da xoa cau hinh! ESP32 dang reboot...", HTTPD_RESP_USE_STRLEN);
    vTaskDelay(pdMS_TO_TICKS(1500));
    esp_restart();
    return ESP_OK;
}

// --- 6. GIAO DIỆN SETUP CẤU HÌNH (AP MODE) ---
static esp_err_t config_get_handler(httpd_req_t *req) {
    const char *resp_html = 
        "<!DOCTYPE html><html lang='vi'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1'>"
        "<title>Thiết Lập ESP32</title><style>"
        "* { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }"
        "body { background: linear-gradient(135deg, #667eea, #764ba2); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }"
        ".card { background: #ffffff; padding: 30px 25px; border-radius: 20px; width: 100%; max-width: 400px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); }"
        "h2 { color: #2d3748; font-size: 22px; margin-bottom: 20px; text-align: center; font-weight: 700; }"
        "label { display: block; text-align: left; margin: 10px 0 4px; font-weight: 600; color: #4a5568; font-size: 13px; }"
        "input[type='text'], input[type='password'] { width: 100%; padding: 11px 13px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 14px; outline: none; }"
        ".pass-wrapper { position: relative; display: flex; align-items: center; }"
        ".toggle-btn { position: absolute; right: 10px; background: none; border: none; cursor: pointer; font-size: 16px; color: #718096; user-select: none; }"
        "input[type='submit'] { width: 100%; background: linear-gradient(to right, #667eea, #764ba2); color: #fff; border: none; padding: 13px; margin-top: 20px; border-radius: 8px; font-size: 15px; font-weight: 700; cursor: pointer; }"
        "</style></head><body>"
        "<div class='card'>"
        "<h2>⚡ Cấu Hình Thiết Bị & Wi-Fi</h2>"
        "<form action='/save' method='post'>"
        "<label>Mã Thiết Bị IoT:</label>"
        "<input type='text' name='dev_id' value='ESP32_DEV_01' required>"
        "<label>IP Server (XAMPP/PC):</label>"
        "<input type='text' name='srv_ip' value='192.168.2.100' placeholder='VD: 192.168.2.100' required>"
        "<label>Đường dẫn API (Path):</label>"
        "<input type='text' name='api_path' value='/iot_app/api.php' required>"
        "<label>Tên Wi-Fi (SSID):</label>"
        "<input type='text' name='ssid' placeholder='Nhập tên Wi-Fi...' required>"
        "<label>Mật Khẩu Wi-Fi:</label>"
        "<div class='pass-wrapper'>"
        "<input type='password' id='passInput' name='pass' placeholder='Nhập mật khẩu...' required>"
        "<button type='button' class='toggle-btn' onclick='togglePass()'>👁️</button>"
        "</div>"
        "<input type='submit' value='LƯU CẤU HÌNH & KẾT NỐI'>"
        "</form></div>"
        "<script>"
        "function togglePass() {"
        "  var p = document.getElementById('passInput');"
        "  p.type = (p.type === 'password') ? 'text' : 'password';"
        "}"
        "</script></body></html>";
    httpd_resp_send(req, resp_html, HTTPD_RESP_USE_STRLEN);
    return ESP_OK;
}

static esp_err_t save_post_handler(httpd_req_t *req) {
    char buf[512];
    int ret = httpd_req_recv(req, buf, sizeof(buf) - 1);
    if (ret <= 0) return ESP_FAIL;
    buf[ret] = '\0';

    char dev_id[32] = {0}, srv_ip[64] = {0}, path[128] = {0}, ssid[32] = {0}, pass[64] = {0};
    sscanf(buf, "dev_id=%31[^&]&srv_ip=%63[^&]&api_path=%127[^&]&ssid=%31[^&]&pass=%63s", dev_id, srv_ip, path, ssid, pass);

    nvs_handle_t my_handle;
    if (nvs_open("wifi_store", NVS_READWRITE, &my_handle) == ESP_OK) {
        nvs_set_str(my_handle, "dev_id", dev_id);
        nvs_set_str(my_handle, "srv_ip", srv_ip);
        nvs_set_str(my_handle, "api_path", path);
        nvs_set_str(my_handle, "ssid", ssid);
        nvs_set_str(my_handle, "pass", pass);
        nvs_commit(my_handle);
        nvs_close(my_handle);
    }
    httpd_resp_send(req, "Da luu thong tin! ESP32 dang reboot...", HTTPD_RESP_USE_STRLEN);
    vTaskDelay(pdMS_TO_TICKS(1500));
    esp_restart();
    return ESP_OK;
}

static void start_webserver(bool is_ap) {
    if (server != NULL) return; 

    httpd_config_t config = HTTPD_DEFAULT_CONFIG();
    config.max_uri_handlers = 8;
    config.stack_size = 8192;
    config.lru_purge_enable = true;

    if (httpd_start(&server, &config) == ESP_OK) {
        if (is_ap) {
            httpd_uri_t c_uri = {.uri = "/", .method = HTTP_GET, .handler = config_get_handler};
            httpd_uri_t s_uri = {.uri = "/save", .method = HTTP_POST, .handler = save_post_handler};
            httpd_register_uri_handler(server, &c_uri);
            httpd_register_uri_handler(server, &s_uri);
        } else {
            httpd_uri_t st_uri = {.uri = "/", .method = HTTP_GET, .handler = status_get_handler};
            httpd_uri_t r_uri  = {.uri = "/reset_wifi", .method = HTTP_GET, .handler = reset_wifi_handler};
            httpd_register_uri_handler(server, &st_uri);
            httpd_register_uri_handler(server, &r_uri);
            ESP_LOGI(TAG, "WebServer Nội Bộ Đã Mở Tại IP: %s", my_ip);
        }
    }
}

// --- 7. TASK GIÁM SÁT CHÂN GPIO4 ---
static void gpio_monitor_task(void *pvParameters) {
    while (1) {
        int raw_pin = gpio_get_level(INPUT_PIN);
        if (raw_pin != last_pin_state) {
            last_pin_state = raw_pin;
            const char* status_str = (raw_pin == 1) ? "ON" : "OFF";
            ESP_LOGI(TAG, "Trạng thái thay đổi: %s (Raw: %d)", status_str, raw_pin);
            send_status_to_xampp(status_str);
        }
        vTaskDelay(pdMS_TO_TICKS(300));
    }
}

// --- 8. EVENT HANDLER MẠNG ---
static void event_handler(void* arg, esp_event_base_t event_base, int32_t event_id, void* event_data) {
    if (event_base == WIFI_EVENT && event_id == WIFI_EVENT_STA_START) {
        esp_wifi_connect();
    } else if (event_base == IP_EVENT && event_id == IP_EVENT_STA_GOT_IP) {
        ip_event_got_ip_t* event = (ip_event_got_ip_t*) event_data;
        snprintf(my_ip, sizeof(my_ip), IPSTR, IP2STR(&event->ip_info.ip));
        ESP_LOGI(TAG, "Đã nhận IP: %s", my_ip);
        start_webserver(false);
    }
}

static void init_wifi_ap(void) {
    esp_netif_create_default_wifi_ap();

    wifi_config_t wifi_config = {
        .ap = { .ssid = "ESP32-Config-AP", .ssid_len = strlen("ESP32-Config-AP"), .channel = 1, .max_connection = 4, .authmode = WIFI_AUTH_OPEN },
    };
    ESP_ERROR_CHECK(esp_wifi_set_mode(WIFI_MODE_AP));
    ESP_ERROR_CHECK(esp_wifi_set_config(WIFI_IF_AP, &wifi_config));
    ESP_ERROR_CHECK(esp_wifi_start());

    start_webserver(true);
    xTaskCreate(dns_server_task, "dns_task", 2048, NULL, 5, NULL);
}

static void init_wifi_sta(const char* ssid, const char* pass) {
    esp_netif_create_default_wifi_sta();

    ESP_ERROR_CHECK(esp_event_handler_instance_register(WIFI_EVENT, ESP_EVENT_ANY_ID, &event_handler, NULL, NULL));
    ESP_ERROR_CHECK(esp_event_handler_instance_register(IP_EVENT, IP_EVENT_STA_GOT_IP, &event_handler, NULL, NULL));

    wifi_config_t wifi_config = {0};
    strncpy((char *)wifi_config.sta.ssid, ssid, sizeof(wifi_config.sta.ssid));
    strncpy((char *)wifi_config.sta.password, pass, sizeof(wifi_config.sta.password));

    ESP_ERROR_CHECK(esp_wifi_set_mode(WIFI_MODE_STA));
    ESP_ERROR_CHECK(esp_wifi_set_config(WIFI_IF_STA, &wifi_config));
    ESP_ERROR_CHECK(esp_wifi_start());

    xTaskCreate(gpio_monitor_task, "gpio_task", 4096, NULL, 5, NULL);
}

// --- 9. MAIN FUNCTION ---
void app_main(void) {
    esp_err_t ret = nvs_flash_init();
    if (ret == ESP_ERR_NVS_NO_FREE_PAGES || ret == ESP_ERR_NVS_NEW_VERSION_FOUND) {
        ESP_ERROR_CHECK(nvs_flash_erase());
        ret = nvs_flash_init();
    }

    gpio_reset_pin(RESET_BTN_PIN);
    gpio_set_direction(RESET_BTN_PIN, GPIO_MODE_INPUT);
    gpio_set_pull_mode(RESET_BTN_PIN, GPIO_PULLUP_ONLY);

    gpio_reset_pin(INPUT_PIN);
    gpio_set_direction(INPUT_PIN, GPIO_MODE_INPUT);
    gpio_set_pull_mode(INPUT_PIN, GPIO_PULLUP_ONLY);

    xTaskCreate(boot_button_monitor_task, "boot_btn_task", 2048, NULL, 5, NULL);

    ESP_ERROR_CHECK(esp_netif_init());
    ESP_ERROR_CHECK(esp_event_loop_create_default());

    wifi_init_config_t cfg = WIFI_INIT_CONFIG_DEFAULT();
    ESP_ERROR_CHECK(esp_wifi_init(&cfg));

    nvs_handle_t my_handle;
    char ssid[32] = {0}, pass[64] = {0};
    size_t s_len = sizeof(ssid), p_len = sizeof(pass), d_len = sizeof(device_id);
    size_t srv_len = sizeof(server_ip), path_len = sizeof(api_path);
    bool has_info = false;

    if (nvs_open("wifi_store", NVS_READWRITE, &my_handle) == ESP_OK) {
        if (nvs_get_str(my_handle, "ssid", ssid, &s_len) == ESP_OK &&
            nvs_get_str(my_handle, "pass", pass, &p_len) == ESP_OK) {
            
            nvs_get_str(my_handle, "dev_id", device_id, &d_len);
            nvs_get_str(my_handle, "srv_ip", server_ip, &srv_len);
            nvs_get_str(my_handle, "api_path", api_path, &path_len);
            has_info = true;
        }
        nvs_close(my_handle);
    }

    if (has_info) {
        init_wifi_sta(ssid, pass);
    } else {
        init_wifi_ap();
    }
}