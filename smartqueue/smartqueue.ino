#include <Arduino.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include "config.h"

constexpr unsigned long DEBOUNCE_MS = 50;
constexpr unsigned long RECONNECT_MS = 10000;
int lastReading = LOW;
unsigned long lastChangeTime = 0;
unsigned long lastReconnectTime = 0;
bool readyForPress = false;
bool wasConnected = false;

void beep(unsigned int count) {
  if (!BUZZER_ENABLED) return;
  const int on = BUZZER_ACTIVE_LOW ? LOW : HIGH;
  const int off = BUZZER_ACTIVE_LOW ? HIGH : LOW;
  for (unsigned int i = 0; i < count; ++i) {
    digitalWrite(BUZZER_PIN, on);
    delay(150);
    digitalWrite(BUZZER_PIN, off);
    if (i + 1 < count) delay(150);
  }
}

void requestTicket() {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("Offline: no ticket requested. Wait for Wi-Fi, then press again.");
    beep(3);
    return;
  }
  WiFiClient client;
  HTTPClient http;
  http.setConnectTimeout(3000);
  http.setTimeout(5000);
  if (!http.begin(client, API_URL)) {
    Serial.println("Cannot open API_URL. Check config.h.");
    beep(3);
    return;
  }
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  Serial.println("Requesting ticket...");
  const int code = http.POST("action=new_ticket");
  const String body = code > 0 ? http.getString() : String();
  http.end();

  JsonDocument response;
  const DeserializationError error = deserializeJson(response, body);
  if (code == 409 && !error && response["status"] == "paused") {
    Serial.println("Queue paused by admin. No ticket added.");
    beep(2);
    return;
  }
  if (code == 200 && !error && response["status"] == "success" &&
      response["queue_number"].is<unsigned long>() &&
      response["queue_number"].as<unsigned long>() > 0) {
    Serial.printf("Your queue number: %03lu\n", response["queue_number"].as<unsigned long>());
    beep(1);
    return;
  }
  Serial.printf("Ticket unconfirmed (HTTP %d). Check website before pressing again.\n", code);
  Serial.println("Check API_URL, Apache/MySQL, and LAN access. No automatic retry.");
  beep(3);
}

void setup() {
  Serial.begin(115200);
  // Button: 3.3V -> switch -> GPIO14; external 10k ohm GPIO14 -> GND.
  pinMode(BUTTON_PIN, INPUT);
  if (BUZZER_ENABLED) {
    digitalWrite(BUZZER_PIN, BUZZER_ACTIVE_LOW ? HIGH : LOW);
    pinMode(BUZZER_PIN, OUTPUT);
  }

  lastReading = digitalRead(BUTTON_PIN);
  lastChangeTime = millis();
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  lastReconnectTime = millis();
  Serial.println("Smart Queue ESP32: connecting to Wi-Fi...");
  Serial.println("Release, then press the button once connected.");
}

void loop() {
  const unsigned long now = millis();
  const bool connected = WiFi.status() == WL_CONNECTED;
  if (connected != wasConnected) {
    wasConnected = connected;
    if (connected) {
      Serial.print("Wi-Fi connected. ESP32 IP: ");
      Serial.println(WiFi.localIP());
    } else {
      Serial.println("Wi-Fi disconnected. Reconnecting; offline presses are discarded.");
    }
  }
  if (!connected && now - lastReconnectTime >= RECONNECT_MS) {
    lastReconnectTime = now;
    WiFi.reconnect();
  }
  const int reading = digitalRead(BUTTON_PIN);

  if (reading != lastReading) {
    lastReading = reading;
    lastChangeTime = now;
  }

  if (now - lastChangeTime >= DEBOUNCE_MS) {
    if (reading == LOW) {
      readyForPress = true;
    } else if (readyForPress) {
      readyForPress = false;
      requestTicket();
      // Require a fresh debounced release after HTTP/beep work finishes.
      lastReading = digitalRead(BUTTON_PIN);
      lastChangeTime = millis();
    }
  }

  delay(1);
}
