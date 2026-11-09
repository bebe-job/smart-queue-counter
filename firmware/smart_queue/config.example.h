#pragma once

// Copy this file to config.h, then edit the values below.
constexpr char WIFI_SSID[] = "YOUR_WIFI_NAME";
constexpr char WIFI_PASSWORD[] = "YOUR_WIFI_PASSWORD";
// Use the web server computer's LAN IPv4 address, never localhost.
constexpr char API_URL[] = "http://192.168.1.100/smart-queue-counter/api/api.php";

// NodeMCU ESP8266 GPIO numbers (not the printed D-label numbers).
constexpr int BUTTON_PIN = 5; // D1: button to GND, 10k resistor to 3V3.
constexpr int BUZZER_PIN = 4; // D2: compatible module's signal input only.
// Enable only after confirming the module is safe for 3.3V GPIO control.
constexpr bool BUZZER_ENABLED = false;
constexpr bool BUZZER_ACTIVE_LOW = false; // true for a LOW-trigger module.
