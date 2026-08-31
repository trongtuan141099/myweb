#include <stdio.h>
#include <string.h>
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
#include "lwip/sockets.h"

#define LED_PIN GPIO_NUM_2
static const char *TAG = "SMART_CONFIG";

#define WIFI_CONNECTED_BIT BIT0
#define WIFI_FAIL_BIT      BIT1
static EventGroupHandle_t s_wifi_event_group;

static httpd_handle_t server = NULL;
static bool led_state = false;
static int s_retry_num = 0;

// --- 1. CAPTIVE PORTAL DNS TASK (REDIRECT TẤT CẢ DNS VỀ IP 192.168.4.1) ---
static void dns_server_task(void *pvParameters) {
    uint8_t rx_buffer[128];
    struct sockaddr_in server_addr, client_addr;
    socklen_t client_addr_len = sizeof(client_addr);

    int sock = socket(AF_INET, SOCK_DGRAM, IPPROTO_IP);
    if (sock < 0) {
        vTaskDelete(NULL);
        return;
    }

    memset(&server_addr, 0, sizeof(server_addr));
    server_addr.sin_family = AF_INET;
    server_addr.sin_port = htons(53);
    server_addr.sin_addr.s_addr = htonl(INADDR_ANY);

    if (bind(sock, (struct sockaddr *)&server_addr, sizeof(server_addr)) < 0) {
        close(sock);
        vTaskDelete(NULL);
        return;
    }

    while (1) {
        int len = recvfrom(sock, rx_buffer, sizeof(rx_buffer) - 1, 0, (struct sockaddr *)&client_addr, &client_addr_len);
        if (len > 12) {
            rx_buffer[2] |= 0x80; 
            rx_buffer[3] |= 0x80; 
            rx_buffer[7] = 1;    

            uint8_t answer[] = { 0xc0, 0x0c, 0x00, 0x01, 0x00, 0x01, 0x00, 0x00, 0x00, 0x3c, 0x00, 0x04, 192, 168, 4, 1 };
            memcpy(rx_buffer + len, answer, sizeof(answer));

            sendto(sock, rx_buffer, len + sizeof(answer), 0, (struct sockaddr *)&client_addr, client_addr_len);
        }
    }
    close(sock);
    vTaskDelete(NULL);
}

// --- 2. EVENT HANDLER ---
static void event_handler(void* arg, esp_event_base_t event_base, int32_t event_id, void* event_data) {
    if (event_base == WIFI_EVENT && event_id == WIFI_EVENT_STA_START) {
        esp_wifi_connect();
    } else if (event_base == WIFI_EVENT && event_id == WIFI_EVENT_STA_DISCONNECTED) {
        if (s_retry_num < 3) {
            esp_wifi_connect();
            s_retry_num++;
            ESP_LOGI(TAG, "Thu ket noi lai Wi-Fi (%d/3)...", s_retry_num);
        } else {
            xEventGroupSetBits(s_wifi_event_group, WIFI_FAIL_BIT);
        }
    } else if (event_base == IP_EVENT && event_id == IP_EVENT_STA_GOT_IP) {
        ip_event_got_ip_t* event = (ip_event_got_ip_t*) event_data;
        ESP_LOGI(TAG, "Ket noi thanh cong! IP: " IPSTR, IP2STR(&event->ip_info.ip));
        s_retry_num = 0;
        xEventGroupSetBits(s_wifi_event_group, WIFI_CONNECTED_BIT);
    }
}

// --- 3. ĐÁNH LỪA HỆ ĐIỀU HÀNH DI ĐỘNG (XỬ LÝ CHECK INTERNET CỦA IOS / ANDROID) ---
static esp_err_t captive_portal_handler(httpd_req_t *req) {
    httpd_resp_set_status(req, "302 Found");
    httpd_resp_set_hdr(req, "Location", "http://192.168.4.1/");
    httpd_resp_send(req, NULL, 0);
    return ESP_OK;
}

// --- 4. GIAO DIỆN CẤU HÌNH WI-FI (KHÔNG DÙNG SCAN AP) ---
static esp_err_t config_get_handler(httpd_req_t *req) {
    const char *resp_html = 
        "<!DOCTYPE html><html lang='vi'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1'>"
        "<title>Cấu hình Wi-Fi</title><style>"
        "* { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }"
        "body { background: linear-gradient(135deg, #667eea, #764ba2); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }"
        ".card { background: #fff; padding: 30px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); width: 100%; max-width: 400px; text-align: center; }"
        "h2 { color: #333; margin-bottom: 20px; font-size: 24px; }"
        "label { display: block; text-align: left; margin: 10px 0 5px; font-weight: 600; color: #555; }"
        "input[type='text'], input[type='password'] { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 15px; outline: none; margin-bottom: 10px; }"
        "input[type='submit'] { width: 100%; background: linear-gradient(to right, #667eea, #764ba2); color: #fff; border: none; padding: 14px; margin-top: 10px; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; }"
        "</style></head><body>"
        "<div class='card'>"
        "<h2>⚡ Thiết Lập Wi-Fi</h2>"
        "<form action='/save' method='post'>"
        "<label>Tên Wi-Fi (SSID):</label>"
        "<input type='text' name='ssid' placeholder='Nhập tên Wi-Fi nhà bạn...' required>"
        "<label>Mật khẩu Wi-Fi:</label>"
        "<input type='password' name='pass' placeholder='Nhập mật khẩu...' required>"
        "<input type='submit' value='LƯU & KẾT NỐI'>"
        "</form></div></body></html>";

    httpd_resp_send(req, resp_html, HTTPD_RESP_USE_STRLEN);
    return ESP_OK;
}

static esp_err_t save_post_handler(httpd_req_t *req) {
    char buf[256];
    int ret = httpd_req_recv(req, buf, sizeof(buf) - 1);
    if (ret <= 0) return ESP_FAIL;
    buf[ret] = '\0';

    char ssid[32] = {0}, pass[64] = {0};
    sscanf(buf, "ssid=%31[^&]&pass=%63s", ssid, pass);

    nvs_handle_t my_handle;
    if (nvs_open("wifi_store", NVS_READWRITE, &my_handle) == ESP_OK) {
        nvs_set_str(my_handle, "ssid", ssid);
        nvs_set_str(my_handle, "pass", pass);
        nvs_commit(my_handle);
        nvs_close(my_handle);
    }

    const char *resp = "<!DOCTYPE html><html><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1'>"
                       "<style>body{font-family:sans-serif;text-align:center;padding-top:50px;background:#f4f4f9;}</style></head><body>"
                       "<h2>⏳ Đang kết nối...</h2><p>ESP32 đang lưu Wi-Fi và reboot lại.</p></body></html>";
    httpd_resp_send(req, resp, HTTPD_RESP_USE_STRLEN);

    vTaskDelay(pdMS_TO_TICKS(1500));
    esp_restart();
    return ESP_OK;
}

// --- 5. GIAO DIỆN ĐIỀU KHIỂN ĐÈN ---
static esp_err_t ctrl_get_handler(httpd_req_t *req) {
    char resp_html[2048];
    snprintf(resp_html, sizeof(resp_html),
        "<!DOCTYPE html><html lang='vi'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1'>"
        "<title>Bảng Điều Khiển</title><style>"
        "* { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }"
        "body { background: #eef2f3; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }"
        ".card { background: #fff; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); width: 100%%; max-width: 380px; text-align: center; }"
        "h1 { font-size: 22px; color: #2c3e50; margin-bottom: 25px; }"
        ".status-box { padding: 15px; border-radius: 12px; margin-bottom: 25px; font-weight: 600; font-size: 16px; background: %s; color: %s; }"
        ".btn { display: block; width: 100%%; padding: 15px; border-radius: 12px; font-size: 18px; font-weight: bold; color: #fff; text-decoration: none; margin-bottom: 15px; transition: 0.2s; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }"
        ".btn-toggle { background: %s; }"
        ".btn-reset { background: #e74c3c; font-size: 14px; padding: 10px; opacity: 0.8; }"
        "</style></head><body>"
        "<div class='card'>"
        "<h1>💡 HỆ THỐNG ĐIỀU KHIỂN</h1>"
        "<div class='status-box'>Trạng thái: %s</div>"
        "<a href='/toggle' class='btn btn-toggle'>%s ĐÈN</a>"
        "<a href='/reset' class='btn btn-reset' onclick='return confirm(\"Xác nhận xóa cấu hình Wi-Fi?\")'>Reset Wi-Fi</a>"
        "</div></body></html>",
        led_state ? "#e8f8f5" : "#fdf2e9",
        led_state ? "#27ae60" : "#e67e22",
        led_state ? "#27ae60" : "#2980b9",
        led_state ? "ĐANG BẬT" : "ĐANG TẮT",
        led_state ? "TẮT" : "BẬT"
    );
    httpd_resp_send(req, resp_html, HTTPD_RESP_USE_STRLEN);
    return ESP_OK;
}

static esp_err_t toggle_handler(httpd_req_t *req) {
    led_state = !led_state;
    gpio_set_level(LED_PIN, led_state);
    httpd_resp_set_status(req, "302 Found");
    httpd_resp_set_hdr(req, "Location", "/");
    httpd_resp_send(req, NULL, 0);
    return ESP_OK;
}

static esp_err_t reset_handler(httpd_req_t *req) {
    nvs_handle_t my_handle;
    if (nvs_open("wifi_store", NVS_READWRITE, &my_handle) == ESP_OK) {
        nvs_erase_all(my_handle);
        nvs_commit(my_handle);
        nvs_close(my_handle);
    }
    httpd_resp_send(req, "Da xoa bo nho! Dang reboot...", HTTPD_RESP_USE_STRLEN);
    vTaskDelay(pdMS_TO_TICKS(1500));
    esp_restart();
    return ESP_OK;
}

// --- 6. WEBSERVER ROUTING ---
static void start_webserver(bool is_ap) {
    httpd_config_t config = HTTPD_DEFAULT_CONFIG();
    config.max_uri_handlers = 12;

    if (httpd_start(&server, &config) == ESP_OK) {
        if (is_ap) {
            httpd_uri_t config_uri = {.uri = "/", .method = HTTP_GET, .handler = config_get_handler};
            httpd_uri_t save_uri = {.uri = "/save", .method = HTTP_POST, .handler = save_post_handler};
            httpd_register_uri_handler(server, &config_uri);
            httpd_register_uri_handler(server, &save_uri);

            // Bắt các request check internet của Android/iOS để ép bật Pop-up
            httpd_uri_t generate_204 = {.uri = "/generate_204", .method = HTTP_GET, .handler = captive_portal_handler};
            httpd_uri_t redirect_uri = {.uri = "/redirect", .method = HTTP_GET, .handler = captive_portal_handler};
            httpd_register_uri_handler(server, &generate_204);
            httpd_register_uri_handler(server, &redirect_uri);
        } else {
            httpd_uri_t ctrl_uri = {.uri = "/", .method = HTTP_GET, .handler = ctrl_get_handler};
            httpd_uri_t toggle_uri = {.uri = "/toggle", .method = HTTP_GET, .handler = toggle_handler};
            httpd_uri_t reset_uri = {.uri = "/reset", .method = HTTP_GET, .handler = reset_handler};
            httpd_register_uri_handler(server, &ctrl_uri);
            httpd_register_uri_handler(server, &toggle_uri);
            httpd_register_uri_handler(server, &reset_uri);
        }
    }
}

// --- 7. KHỞI TẠO WIFI SOFTAP ---
static void init_wifi_ap(void) {
    esp_netif_t *ap_netif = esp_netif_create_default_wifi_ap();
    
    esp_netif_ip_info_t ip_info;
    IP4_ADDR(&ip_info.ip, 192, 168, 4, 1);
    IP4_ADDR(&ip_info.gw, 192, 168, 4, 1);
    IP4_ADDR(&ip_info.netmask, 255, 255, 255, 0);
    esp_netif_dhcps_stop(ap_netif);
    esp_netif_set_ip_info(ap_netif, &ip_info);
    esp_netif_dhcps_start(ap_netif);

    wifi_init_config_t cfg = WIFI_INIT_CONFIG_DEFAULT();
    ESP_ERROR_CHECK(esp_wifi_init(&cfg));

    wifi_config_t wifi_config = {
        .ap = {
            .ssid = "ESP32-Smart-Setup",
            .ssid_len = strlen("ESP32-Smart-Setup"),
            .channel = 1,
            .max_connection = 4,
            .authmode = WIFI_AUTH_OPEN,
        },
    };
    
    ESP_ERROR_CHECK(esp_wifi_set_mode(WIFI_MODE_AP));
    ESP_ERROR_CHECK(esp_wifi_set_config(WIFI_IF_AP, &wifi_config));
    ESP_ERROR_CHECK(esp_wifi_start());

    ESP_LOGI(TAG, "SoftAP Ready: ESP32-Smart-Setup (IP: 192.168.4.1)");
    
    start_webserver(true);
    xTaskCreate(dns_server_task, "dns_server", 2048, NULL, 5, NULL);
}

static bool init_wifi_sta(const char *ssid, const char *pass) {
    s_wifi_event_group = xEventGroupCreate();

    esp_netif_create_default_wifi_sta();
    wifi_init_config_t cfg = WIFI_INIT_CONFIG_DEFAULT();
    ESP_ERROR_CHECK(esp_wifi_init(&cfg));

    ESP_ERROR_CHECK(esp_event_handler_instance_register(WIFI_EVENT, ESP_EVENT_ANY_ID, &event_handler, NULL, NULL));
    ESP_ERROR_CHECK(esp_event_handler_instance_register(IP_EVENT, IP_EVENT_STA_GOT_IP, &event_handler, NULL, NULL));

    wifi_config_t wifi_config = {0};
    strncpy((char *)wifi_config.sta.ssid, ssid, sizeof(wifi_config.sta.ssid));
    strncpy((char *)wifi_config.sta.password, pass, sizeof(wifi_config.sta.password));

    ESP_ERROR_CHECK(esp_wifi_set_mode(WIFI_MODE_STA));
    ESP_ERROR_CHECK(esp_wifi_set_config(WIFI_IF_STA, &wifi_config));
    ESP_ERROR_CHECK(esp_wifi_start());

    EventBits_t bits = xEventGroupWaitBits(s_wifi_event_group,
            WIFI_CONNECTED_BIT | WIFI_FAIL_BIT,
            pdFALSE, pdFALSE, portMAX_DELAY);

    if (bits & WIFI_CONNECTED_BIT) {
        ESP_LOGI(TAG, "Ket noi thanh cong SSID:%s", ssid);
        start_webserver(false);
        return true;
    } else if (bits & WIFI_FAIL_BIT) {
        ESP_LOGE(TAG, "Ket noi THAT BAI SSID:%s", ssid);
        return false;
    }
    return false;
}

// --- 8. MAIN ---
void app_main(void) {
    esp_err_t ret = nvs_flash_init();
    if (ret == ESP_ERR_NVS_NO_FREE_PAGES || ret == ESP_ERR_NVS_NEW_VERSION_FOUND) {
        ESP_ERROR_CHECK(nvs_flash_erase());
        ret = nvs_flash_init();
    }
    ESP_ERROR_CHECK(ret);

    gpio_reset_pin(LED_PIN);
    gpio_set_direction(LED_PIN, GPIO_MODE_OUTPUT);
    gpio_set_level(LED_PIN, led_state);

    ESP_ERROR_CHECK(esp_netif_init());
    ESP_ERROR_CHECK(esp_event_loop_create_default());

    nvs_handle_t my_handle;
    char ssid[32] = {0}, pass[64] = {0};
    size_t ssid_len = sizeof(ssid), pass_len = sizeof(pass);
    bool has_wifi_info = false;

    if (nvs_open("wifi_store", NVS_READWRITE, &my_handle) == ESP_OK) {
        if (nvs_get_str(my_handle, "ssid", ssid, &ssid_len) == ESP_OK &&
            nvs_get_str(my_handle, "pass", pass, &pass_len) == ESP_OK) {
            has_wifi_info = true;
        }
        nvs_close(my_handle);
    }

    if (has_wifi_info) {
        bool connected = init_wifi_sta(ssid, pass);
        if (!connected) {
            ESP_LOGW(TAG, "Sai mat khau! Dang xoa NVS va reboot...");
            if (nvs_open("wifi_store", NVS_READWRITE, &my_handle) == ESP_OK) {
                nvs_erase_all(my_handle);
                nvs_commit(my_handle);
                nvs_close(my_handle);
            }
            vTaskDelay(pdMS_TO_TICKS(2000));
            esp_restart();
        }
    } else {
        init_wifi_ap();
    }
}