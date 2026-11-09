# ESP32 + website using Arduino IDE

Open `smartqueue/smartqueue.ino` for the Wi-Fi-connected ESP32 firmware.
The older `firmware/` sketches are not used by this guide.

## 1. Start the website

1. Copy the project to `C:\xampp\htdocs\smart-queue-counter`, or configure
   Apache to serve the current project folder. Keep the served copy updated.
2. Start Apache and MySQL in XAMPP.
3. Open `http://localhost/phpmyadmin` and import `database/schema.sql`.
4. Check database credentials in `api/api.php` (defaults: `root`, blank password,
   database `smart_queue_db`).
5. Open these pages:
   - `http://localhost/smart-queue-counter/displays/admin.php`
   - `http://localhost/smart-queue-counter/displays/public.php`
6. Run `ipconfig` and find the active Wi-Fi/Ethernet adapter's IPv4.
   From another device on the same LAN, open
   `http://YOUR_PC_IP/smart-queue-counter/api/api.php?action=get_dashboard_data`.
   It must return JSON. Include Apache's port if not 80. If unreachable,
   check Apache's private-network firewall access and router client isolation.

## 2. Arduino IDE setup

1. In File > Preferences > Additional Boards Manager URLs, add:
   `https://espressif.github.io/arduino-esp32/package_esp32_index.json`
2. In Boards Manager, install **esp32 by Espressif Systems**.
3. For the ESP32-WROOM-32 board used here, select **ESP32 Dev Module**.
4. In Library Manager, install **ArduinoJson 7 by Benoit Blanchon**.
   WiFi and HTTPClient are included with the ESP32 board package.
5. Open `smartqueue.ino`. Edit the `config.h` tab. If missing, copy
   `config.example.h` to `config.h` first:
   - `WIFI_SSID`: your 2.4 GHz network name.
   - `WIFI_PASSWORD`: its password.
   - `API_URL`: full URL using the XAMPP computer's LAN IPv4, never `localhost`.
6. Connect the ESP32 using a USB data cable, select its port, then Verify / Upload.
   If upload remains at Connecting, hold BOOT until writing begins, then release.
7. Open Serial Monitor at **115200 baud**. Press EN/reset if needed to see startup.
   Wait for `Wi-Fi connected` before pressing the button.

## 3. Wiring (disconnect USB first)

| Connection | Destination |
| --- | --- |
| Button switched terminal | ESP32 3.3V |
| Other switched terminal | GPIO14 |
| External 10k ohm resistor | GPIO14 to GND |
| Compatible 3.3V active buzzer signal | GPIO27 |
| Buzzer VCC / GND | 3.3V / common GND |

Use board GPIO labels, not physical connector positions. Four-leg buttons have
permanently connected pairs: use terminals that connect only when pressed.
The button reads LOW when released, HIGH when pressed. Holding does not repeat.

The button works with `BUZZER_ENABLED=false`. Enable only after checking module
compatibility; set `BUZZER_ACTIVE_LOW` for its trigger polarity. A module requiring
5V logic or a two-pin buzzer needs an appropriate driver circuit.

## 4. Check the full flow

- Press and release: Serial Monitor prints the server's saved number; the public
  waiting list updates within about two seconds. One beep if enabled.
- Call Next Customer in admin: the website serves the next waiting ticket.
  The ESP32 buzzer acknowledges button requests only, not Call Next.
- Pause tickets in admin, then press: no ticket added; two beeps if enabled.
- Disconnect Wi-Fi, then press: no request queued; three beeps if enabled.
  The ESP32 attempts to reconnect every ten seconds.
- For an unconfirmed request, check the waiting list before pressing again: a
  ticket may have been saved even if its response was lost. Requests are never
  automatically retried. HTTP and beeps are synchronous; presses during them can
  be missed. Wait for feedback, then release for 50ms before pressing again.

Queue numbers come from MySQL and survive ESP32 restarts. No IR sensor data is
sent. The web button WIRED badge is static, not proof of connectivity.
The existing HTTP API is intended for a trusted local network.

References: [Espressif Arduino IDE setup](https://docs.espressif.com/projects/arduino-esp32/en/latest/installing.html),
[ArduinoJson deserialization](https://arduinojson.org/v7/api/json/deserializejson/).
