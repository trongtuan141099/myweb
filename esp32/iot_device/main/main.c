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

#define RESET_BTN_PIN      GPIO_NUM_0  // Nút BOOT giữ 5s để xóa NVS

static const char *TAG = "ESP32_IOT";
static httpd_handle_t server = NULL;

// Các biến lưu trữ cấu hình trong NVS
static char device_id[32] = "PL01";
static char server_ip[64] = "192.168.2.16";
static char api_path[128] = "/myweb/api/iot_status.php";
static int gpio_status_pin = 4; // GPIO Chạy/Dừng
static int gpio_error_pin  = 5; // GPIO Lỗi/Sự cố

static char my_ip[20] = "Connecting...";
static int last_status_state = -1;
static int last_error_state  = -1;

// --- 1. HÀM DECODE URL (CHUYỂN %2F THÀNH / VÀ %20 THÀNH KHOẢNG TRẮNG) ---
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

// --- 2. TASK GIỮ NÚT BOOT 5 GIÂY ĐỂ RESET CẤU HÌNH ---
static void boot_button_monitor_task(void *pvParameters) {
    int hold_time_ms = 0;
    while (1) {
        if (gpio_get_level(RESET_BTN_PIN) == 0) {
            hold_time_ms += 200;
            if (hold_time_ms >= 5000) {
                ESP_LOGE(TAG, "Da giu nut BOOT 5s! Tien hanh xoa NVS & Reboot...");
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

// --- 3. HÀM GỬI HTTP REQUEST ---
static void send_http_request(const char* type, const char* status) {
    char post_data[256];
    snprintf(post_data, sizeof(post_data), "type=%s&device_id=%s&status=%s&ip=%s", type, device_id, status, my_ip);

    char clean_url[256];
    snprintf(clean_url, sizeof(clean_url), "http://%s%s", server_ip, api_path);

    esp_http_client_config_t config = {
        .url = clean_url,
        .method = HTTP_METHOD_POST,
        .timeout_ms = 2000,
    };
    
    esp_http_client_handle_t client = esp_http_client_init(&config);
    if (client == NULL) {
        ESP_LOGE(TAG, "Thiết lập HTTP Client thất bại cho URL: %s", clean_url);
        return;
    }

    esp_http_client_set_post_field(client, post_data, strlen(post_data));
    esp_http_client_set_header(client, "Content-Type", "application/x-www-form-urlencoded");

    esp_err_t err = esp_http_client_perform(client);
    if (err == ESP_OK && strcmp(type, "EVENT") == 0) {
        ESP_LOGI(TAG, "Gui EVENT thanh cong: %s", status);
    }
    esp_http_client_cleanup(client);
}

// --- 4. TASK GIÁM SÁT CHÂN GPIO ---
static void gpio_monitor_task(void *pvParameters) {
    while (1) {
        int err_level = gpio_get_level((gpio_num_t)gpio_error_pin);
        int status_level = gpio_get_level((gpio_num_t)gpio_status_pin);

        if (err_level != last_error_state || status_level != last_status_state) {
            last_error_state = err_level;
            last_status_state = status_level;

            const char* current_status = "OFF";
            if (err_level == 0) {
                current_status = "ERROR";
            } else if (status_level == 1) {
                current_status = "ON";
            } else {
                current_status = "OFF";
            }

            ESP_LOGI(TAG, "Cap nhat trang thai may -> %s", current_status);
            send_http_request("EVENT", current_status);
        }
        vTaskDelay(pdMS_TO_TICKS(200));
    }
}

// --- 5. TASK GỬI HEARTBEAT ---
static void heartbeat_task(void *pvParameters) {
    while (1) {
        if (strlen(my_ip) > 0 && strcmp(my_ip, "Connecting...") != 0) {
            send_http_request("HEARTBEAT", "");
        }
        vTaskDelay(pdMS_TO_TICKS(3000));
    }
}

// --- 6. API LẤY TRẠNG THÁI JSON CHO AJAX ---
static esp_err_t status_json_handler(httpd_req_t *req) {
    int err_level = gpio_get_level((gpio_num_t)gpio_error_pin);
    int status_level = gpio_get_level((gpio_num_t)gpio_status_pin);
    
    const char* current_status = "OFF";
    if (err_level == 0) current_status = "ERROR";
    else if (status_level == 1) current_status = "ON";

    char json_resp[512];
    snprintf(json_resp, sizeof(json_resp),
        "{\"device_id\":\"%s\",\"server_ip\":\"%s\",\"api_path\":\"%s\",\"my_ip\":\"%s\",\"gpio_status\":%d,\"gpio_error\":%d,\"status\":\"%s\"}",
        device_id, server_ip, api_path, my_ip, gpio_status_pin, gpio_error_pin, current_status);

    httpd_resp_set_type(req, "application/json");
    httpd_resp_send(req, json_resp, HTTPD_RESP_USE_STRLEN);
    return ESP_OK;
}

// --- 7. API LƯU CẤU HÌNH TRỰC TIẾP (ĐÃ GIẢI MÃ %2F TRƯỚC KHI LƯU) ---
static esp_err_t update_config_handler(httpd_req_t *req) {
    char buf[512];
    int ret = httpd_req_recv(req, buf, sizeof(buf) - 1);
    if (ret <= 0) return ESP_FAIL;
    buf[ret] = '\0';

    char raw_dev_id[32] = {0}, raw_srv_ip[64] = {0}, raw_path[128] = {0};
    int new_gpio_status = gpio_status_pin, new_gpio_err = gpio_error_pin;

    sscanf(buf, "dev_id=%31[^&]&srv_ip=%63[^&]&api_path=%127[^&]&gpio_status=%d&gpio_error=%d", 
           raw_dev_id, raw_srv_ip, raw_path, &new_gpio_status, &new_gpio_err);

    // Giải mã URLDecode cho tất cả các chuỗi để đổi %2F thành dấu / chuẩn
    char new_dev_id[32], new_srv_ip[64], new_path[128];
    url_decode(new_dev_id, raw_dev_id);
    url_decode(new_srv_ip, raw_srv_ip);
    url_decode(new_path, raw_path);

    // Lưu chuỗi đã làm sạch vào NVS
    nvs_handle_t my_handle;
    if (nvs_open("wifi_store", NVS_READWRITE, &my_handle) == ESP_OK) {
        nvs_set_str(my_handle, "dev_id", new_dev_id);
        nvs_set_str(my_handle, "srv_ip", new_srv_ip);
        nvs_set_str(my_handle, "api_path", new_path);
        nvs_set_i32(my_handle, "gpio_status", new_gpio_status);
        nvs_set_i32(my_handle, "gpio_error", new_gpio_err);
        nvs_commit(my_handle);
        nvs_close(my_handle);
    }

    // Cập nhật biến RAM
    strncpy(device_id, new_dev_id, sizeof(device_id));
    strncpy(server_ip, new_srv_ip, sizeof(server_ip));
    strncpy(api_path, new_path, sizeof(api_path));

    // Cấu hình lại chân GPIO mới nếu thay đổi
    if (new_gpio_status != gpio_status_pin || new_gpio_err != gpio_error_pin) {
        gpio_status_pin = new_gpio_status;
        gpio_error_pin = new_gpio_err;

        gpio_reset_pin((gpio_num_t)gpio_status_pin);
        gpio_set_direction((gpio_num_t)gpio_status_pin, GPIO_MODE_INPUT);
        gpio_set_pull_mode((gpio_num_t)gpio_status_pin, GPIO_PULLUP_ONLY);

        gpio_reset_pin((gpio_num_t)gpio_error_pin);
        gpio_set_direction((gpio_num_t)gpio_error_pin, GPIO_MODE_INPUT);
        gpio_set_pull_mode((gpio_num_t)gpio_error_pin, GPIO_PULLUP_ONLY);
        
        last_status_state = -1;
    }

    httpd_resp_send(req, "{\"status\":\"success\"}", HTTPD_RESP_USE_STRLEN);
    return ESP_OK;
}

// --- 8. GIAO DIỆN NỘI BỘ ESP32 (AJAX REAL-TIME) ---
static esp_err_t status_get_handler(httpd_req_t *req) {
    char *resp_html = (char *)malloc(8192);
    if (resp_html == NULL) {
        httpd_resp_send_500(req);
        return ESP_FAIL;
    }

    snprintf(resp_html, 8192,
        "<!DOCTYPE html><html lang='vi'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1'>"
        "<title>Bảng Điều Khiển ESP32 Nội Bộ</title><style>"
        "* { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }"
        "body { background: #0f172a; color: #f8fafc; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }"
        ".card { background: #1e293b; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); width: 100%%; max-width: 450px; text-align: center; border: 1px solid #334155; }"
        "h2 { color: #38bdf8; font-size: 22px; margin-bottom: 20px; font-weight: 700; }"
        ".info-group { background: #0f172a; padding: 15px; border-radius: 12px; margin-bottom: 20px; text-align: left; border: 1px solid #334155; }"
        ".info-item { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; color: #94a3b8; word-break: break-all; }"
        ".info-val { font-weight: 600; color: #f8fafc; text-align: right; margin-left: 10px; }"
        ".status-box { padding: 14px; border-radius: 12px; font-weight: 700; font-size: 16px; margin-bottom: 20px; text-align: center; }"
        ".ON { background: rgba(34, 197, 94, 0.2); color: #4ade80; border: 1px solid #22c55e; }"
        ".OFF { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid #ef4444; }"
        ".ERROR { background: rgba(234, 179, 8, 0.2); color: #facc15; border: 1px solid #eab308; }"
        "label { display: block; text-align: left; margin: 10px 0 4px; font-size: 13px; color: #94a3b8; font-weight: 600; }"
        "input, select { width: 100%%; padding: 10px; background: #0f172a; border: 1px solid #334155; color: #fff; border-radius: 8px; font-size: 14px; outline: none; margin-bottom: 10px; }"
        ".btn-update { width: 100%%; background: #3b82f6; color: #fff; border: none; padding: 12px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 15px; transition: 0.2s; margin-top: 10px; }"
        ".btn-update:hover { background: #2563eb; }"
        ".btn-reset { display: block; width: 100%%; background: #ef4444; color: #fff; padding: 10px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 13px; margin-top: 15px; text-align: center; }"
        "</style></head><body>"
        "<div class='card'>"
        "<h2>📡 Giám Sát Nội Bộ ESP32</h2>"
        "<div id='statusBox' class='status-box OFF'>Đang cập nhật...</div>"
        "<div class='info-group'>"
        "<div class='info-item'><span>IP ESP32:</span><span id='disp_ip' class='info-val'>-</span></div>"
        "</div>"
        "<form id='configForm' onsubmit='updateConfig(event)'>"
        "<label>Mã Thiết Bị IoT:</label><input type='text' id='dev_id' name='dev_id' required>"
        "<label>Server Target (IP):</label><input type='text' id='srv_ip' name='srv_ip' required>"
        "<label>API Path:</label><input type='text' id='api_path' name='api_path' required>"
        "<div style='display:flex; gap:10px;'>"
        "<div style='flex:1;'><label>GPIO Chạy/Dừng:</label><input type='number' id='gpio_status' name='gpio_status' min='0' max='39' required></div>"
        "<div style='flex:1;'><label>GPIO Báo Lỗi:</label><input type='number' id='gpio_error' name='gpio_error' min='0' max='39' required></div>"
        "</div>"
        "<button type='submit' class='btn-update'>💾 CẬP NHẬT CẤU HÌNH TRỰC TIẾP</button>"
        "</form>"
        "<a href='/reset_wifi' class='btn-reset' onclick='return confirm(\"Reset Wi-Fi thiết bị?\")'>Xóa Cấu Hình Wi-Fi</a>"
        "</div>"
        "<script>"
        "let isFirstLoad = true;"
        "function fetchStatus() {"
        "  fetch('/api/status').then(r=>r.json()).then(data=>{"
        "    document.getElementById('disp_ip').innerText = data.my_ip;"
        "    let sb = document.getElementById('statusBox');"
        "    if(data.status === 'ON') { sb.className='status-box ON'; sb.innerText = '🟢 ĐANG HOẠT ĐỘNG'; }"
        "    else if(data.status === 'ERROR') { sb.className='status-box ERROR'; sb.innerText = '🟡 CÓ LỖI SỰ CỐ'; }"
        "    else { sb.className='status-box OFF'; sb.innerText = '🔴 ĐANG DỪNG'; }"
        "    if(isFirstLoad) {"
        "      document.getElementById('dev_id').value = data.device_id;"
        "      document.getElementById('srv_ip').value = data.server_ip;"
        "      document.getElementById('api_path').value = data.api_path;"
        "      document.getElementById('gpio_status').value = data.gpio_status;"
        "      document.getElementById('gpio_error').value = data.gpio_error;"
        "      isFirstLoad = false;"
        "    }"
        "  });"
        "}"
        "setInterval(fetchStatus, 2000);"
        "fetchStatus();"
        "function updateConfig(e) {"
        "  e.preventDefault();"
        "  let fd = new FormData(document.getElementById('configForm'));"
        "  let params = new URLSearchParams(fd);"
        "  fetch('/api/update_config', {method:'POST', body: params})"
        "  .then(r=>r.json()).then(res=>{"
        "    if(res.status==='success') alert('Cập nhật cấu hình thành công!');"
        "    else alert('Lỗi cập nhật!');"
        "  });"
        "}"
        "</script></body></html>"
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

// --- 9. GIAO DIỆN SETUP WIFI (AP MODE) ---
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
        "input[type='submit'] { width: 100%; background: linear-gradient(to right, #667eea, #764ba2); color: #fff; border: none; padding: 13px; margin-top: 20px; border-radius: 8px; font-size: 15px; font-weight: 700; cursor: pointer; }"
        "</style></head><body>"
        "<div class='card'>"
        "<h2>⚡ Thiết Lập Wi-Fi Ban Đầu</h2>"
        "<form action='/save' method='post'>"
        "<label>Tên Wi-Fi (SSID):</label><input type='text' name='ssid' placeholder='Nhập tên Wi-Fi...' required>"
        "<label>Mật Khẩu Wi-Fi:</label><input type='password' name='pass' placeholder='Nhập mật khẩu...' required>"
        "<input type='submit' value='LƯU WI-FI & KẾT NỐI'>"
        "</form></div></body></html>";
    httpd_resp_send(req, resp_html, HTTPD_RESP_USE_STRLEN);
    return ESP_OK;
}

static esp_err_t save_post_handler(httpd_req_t *req) {
    char buf[256];
    int ret = httpd_req_recv(req, buf, sizeof(buf) - 1);
    if (ret <= 0) return ESP_FAIL;
    buf[ret] = '\0';

    char raw_ssid[32] = {0}, raw_pass[64] = {0};
    sscanf(buf, "ssid=%31[^&]&pass=%63s", raw_ssid, raw_pass);

    char ssid[32], pass[64];
    url_decode(ssid, raw_ssid);
    url_decode(pass, raw_pass);

    nvs_handle_t my_handle;
    if (nvs_open("wifi_store", NVS_READWRITE, &my_handle) == ESP_OK) {
        nvs_set_str(my_handle, "ssid", ssid);
        nvs_set_str(my_handle, "pass", pass);
        nvs_commit(my_handle);
        nvs_close(my_handle);
    }
    httpd_resp_send(req, "Da luu Wi-Fi! ESP32 dang reboot...", HTTPD_RESP_USE_STRLEN);
    vTaskDelay(pdMS_TO_TICKS(1500));
    esp_restart();
    return ESP_OK;
}

// --- 10. WEBSERVER ROUTING ---
static void start_webserver(bool is_ap) {
    if (server != NULL) return; 

    httpd_config_t config = HTTPD_DEFAULT_CONFIG();
    config.max_uri_handlers = 12;
    config.stack_size = 8192;
    config.lru_purge_enable = true;

    if (httpd_start(&server, &config) == ESP_OK) {
        if (is_ap) {
            httpd_uri_t c_uri = {.uri = "/", .method = HTTP_GET, .handler = config_get_handler};
            httpd_uri_t s_uri = {.uri = "/save", .method = HTTP_POST, .handler = save_post_handler};
            httpd_register_uri_handler(server, &c_uri);
            httpd_register_uri_handler(server, &s_uri);
        } else {
            httpd_uri_t st_uri   = {.uri = "/", .method = HTTP_GET, .handler = status_get_handler};
            httpd_uri_t json_uri = {.uri = "/api/status", .method = HTTP_GET, .handler = status_json_handler};
            httpd_uri_t upd_uri  = {.uri = "/api/update_config", .method = HTTP_POST, .handler = update_config_handler};
            httpd_uri_t r_uri    = {.uri = "/reset_wifi", .method = HTTP_GET, .handler = reset_wifi_handler};
            
            httpd_register_uri_handler(server, &st_uri);
            httpd_register_uri_handler(server, &json_uri);
            httpd_register_uri_handler(server, &upd_uri);
            httpd_register_uri_handler(server, &r_uri);
            
            ESP_LOGI(TAG, "WebServer Noi Bo Mo Tai IP: %s", my_ip);
        }
    }
}

// --- 11. EVENT HANDLER VÀ KHỞI TẠO MẠNG ---
static void event_handler(void* arg, esp_event_base_t event_base, int32_t event_id, void* event_data) {
    if (event_base == WIFI_EVENT && event_id == WIFI_EVENT_STA_START) {
        esp_wifi_connect();
    } else if (event_base == IP_EVENT && event_id == IP_EVENT_STA_GOT_IP) {
        ip_event_got_ip_t* event = (ip_event_got_ip_t*) event_data;
        snprintf(my_ip, sizeof(my_ip), IPSTR, IP2STR(&event->ip_info.ip));
        ESP_LOGI(TAG, "Da nhan IP: %s", my_ip);
        
        start_webserver(false);

        // Khôi phục trạng thái máy ban đầu ngay khi có Wi-Fi
        int err_level = gpio_get_level((gpio_num_t)gpio_error_pin);
        int status_level = gpio_get_level((gpio_num_t)gpio_status_pin);
        const char* current_status = "OFF";
        if (err_level == 0) current_status = "ERROR";
        else if (status_level == 1) current_status = "ON";
        
        send_http_request("EVENT", current_status);
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
    xTaskCreate(heartbeat_task, "heartbeat_task", 4096, NULL, 4, NULL);
}

// --- 12. MAIN FUNCTION ---
void app_main(void) {
    esp_err_t ret = nvs_flash_init();
    if (ret == ESP_ERR_NVS_NO_FREE_PAGES || ret == ESP_ERR_NVS_NEW_VERSION_FOUND) {
        ESP_ERROR_CHECK(nvs_flash_erase());
        ret = nvs_flash_init();
    }

    // Đọc cấu hình từ NVS
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
            
            int32_t val_stat = 4, val_err = 5;
            if (nvs_get_i32(my_handle, "gpio_status", &val_stat) == ESP_OK) gpio_status_pin = val_stat;
            if (nvs_get_i32(my_handle, "gpio_error", &val_err) == ESP_OK) gpio_error_pin = val_err;

            has_info = true;
        }
        nvs_close(my_handle);
    }

    // Cấu hình Nút BOOT xóa NVS (khi giữ 5s)
    gpio_reset_pin(RESET_BTN_PIN);
    gpio_set_direction(RESET_BTN_PIN, GPIO_MODE_INPUT);
    gpio_set_pull_mode(RESET_BTN_PIN, GPIO_PULLUP_ONLY);
    xTaskCreate(boot_button_monitor_task, "boot_btn_task", 2048, NULL, 5, NULL);

    // Cấu hình 2 chân GPIO đọc trạng thái
    gpio_reset_pin((gpio_num_t)gpio_status_pin);
    gpio_set_direction((gpio_num_t)gpio_status_pin, GPIO_MODE_INPUT);
    gpio_set_pull_mode((gpio_num_t)gpio_status_pin, GPIO_PULLUP_ONLY);

    gpio_reset_pin((gpio_num_t)gpio_error_pin);
    gpio_set_direction((gpio_num_t)gpio_error_pin, GPIO_MODE_INPUT);
    gpio_set_pull_mode((gpio_num_t)gpio_error_pin, GPIO_PULLUP_ONLY);

    ESP_ERROR_CHECK(esp_netif_init());
    ESP_ERROR_CHECK(esp_event_loop_create_default());

    wifi_init_config_t cfg = WIFI_INIT_CONFIG_DEFAULT();
    ESP_ERROR_CHECK(esp_wifi_init(&cfg));

    if (has_info) {
        init_wifi_sta(ssid, pass);
    } else {
        init_wifi_ap();
    }
}