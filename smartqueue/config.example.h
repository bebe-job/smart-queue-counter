#pragma once

// Copy to config.h and enter your settings. Use 2.4 GHz Wi-Fi.
constexpr char WIFI_SSID[] = "YOUR_WIFI_NAME";
constexpr char WIFI_PASSWORD[] = "YOUR_WIFI_PASSWORD";
// The XAMPP computer's LAN IPv4 from ipconfig, never localhost.
// Include :PORT after the IP if Apache does not use port 80.
constexpr char API_URL[] = "http://192.168.1.100/smart-queue-counter/api/api.php";

constexpr int BUTTON_PIN = 14;
constexpr int BUZZER_PIN = 27;
// Enable only for a module rated for 3.3V supply and GPIO control.
constexpr bool BUZZER_ENABLED = false;
constexpr bool BUZZER_ACTIVE_LOW = true;
