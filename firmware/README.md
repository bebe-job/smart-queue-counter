# NodeMCU ESP8266 hardware connection

Components: NodeMCU ESP8266, 12x12mm momentary push button with cap, 10k ohm
resistor, 5V active buzzer module, MB-102 breadboard, jumper wires, and Micro-USB
data cable. This firmware does not use an IR sensor.

Pressing the button requests one ticket from the PHP API. Ticket numbers appear
in Serial Monitor and the web waiting list. Optional buzzer feedback: one beep
for a confirmed ticket, two for paused, three for offline/unconfirmed requests.
The buzzer does not currently announce the admin's Call Next action.

## Button wiring

Disconnect USB power before wiring.

| Connection | Destination |
| --- | --- |
| NodeMCU 3V3 | Breadboard positive rail (3.3V) |
| NodeMCU GND | Breadboard ground rail |
| 10k resistor, first end | 3.3V rail |
| 10k resistor, second end | D1 (GPIO5) and one switched button terminal |
| Other switched button terminal | Ground rail |
| NodeMCU Micro-USB | Computer via data cable |

```text
3V3 ----[10k ohm]----+---- D1 (GPIO5)
                    |
                 [button]
                    |
GND ----------------+
```

The resistor pulls D1 HIGH when idle; pressing connects D1 to ground. The internal
pull-up is also enabled. Four-leg buttons have pairs of permanently connected
legs: choose terminals that connect only when pressed. Check continuity if unsure.
Check breadboard split rails and bridge only rails sharing the same voltage.

## Buzzer: identify the module first

The name '5V active buzzer module' does not identify its input circuit or trigger
polarity. Leave it disconnected and BUZZER_ENABLED=false until these are known.
The button works without the buzzer.

For a three-pin module explicitly rated for 3.3V control, whose signal input
never pulls the GPIO above 3.3V:

| Module pin | Connection |
| --- | --- |
| S / IN | D2 (GPIO4) |
| GND / - | Common ground with NodeMCU |
| VCC / + | Regulated 5V supply rated for the module's current |

Use a board USB 5V/VU pin only if the exact board documentation confirms it provides
USB 5V and sufficient current. Do not assume VIN supplies 5V output on every clone.
Keep the 5V rail separate from the 3.3V button rail. An MB-102 breadboard alone is
not a power supply. GPIO is the control signal, never the buzzer power supply.

After checking compatibility, set BUZZER_ENABLED=true and set BUZZER_ACTIVE_LOW
according to the module (false for HIGH-trigger, true for LOW-trigger).
A two-pin buzzer, a module requiring 5V logic, or an input pulled up to 5V needs an
appropriate transistor driver or level interface and possibly extra parts.
Never connect a 5V signal directly to D2. Boot-time sound depends on the module's
input bias while the NodeMCU pin is still floating.

## Configure and upload

1. Start Apache and MySQL in XAMPP. Serve this project from
   C:\xampp\htdocs\smart-queue-counter or configure Apache for this folder.
   Import database/schema.sql through phpMyAdmin. Check credentials in api/api.php.
2. Run ipconfig on the computer. From another device on the same LAN, open
   http://YOUR_PC_IP/smart-queue-counter/api/api.php?action=get_dashboard_data.
   It must return JSON. Include Apache's port if different from 80; allow Apache
   through Windows Firewall on the private network if needed.
3. In Arduino IDE preferences, add this Additional Boards Manager URL:
   https://arduino.esp8266.com/stable/package_esp8266com_index.json
   Install **esp8266 by ESP8266 Community** and select
   **NodeMCU 1.0 (ESP-12E Module)**. Install **ArduinoJson 7 by Benoit Blanchon**
   through Library Manager. Wi-Fi and HTTP libraries come with the board package.
4. Copy smart_queue/config.example.h to smart_queue/config.h. Enter your 2.4 GHz
   Wi-Fi name/password and complete API URL using the computer's LAN address,
   never localhost. If you copied the previous ESP32 template, replace its pin
   settings with this example. config.h is git-ignored.
5. Open smart_queue/smart_queue.ino, select the USB port, and upload.
   Open Serial Monitor at **115200 baud** and check for the connected message.
6. Open displays/public.php and displays/admin.php through Apache.

## Check operation and limits

- Press and release: one ticket is added. Holding does not repeat.
- Pause from admin: a press reports paused and adds no ticket. Resume to retry.
- Disconnect Wi-Fi: presses are discarded; the board attempts to reconnect.
- With a compatible buzzer enabled, verify the one/two/three-beep feedback above.
- No IR reports are sent. The existing web IR card is not a real measurement with
  this component set. The button WIRED badge is static, not a connectivity check.

Input is debounced for 50ms. HTTP requests and beep patterns are synchronous;
presses during either can be missed. Wait for feedback, then release and press
again. Ticket requests are never automatically retried: a response can be lost
after a ticket is saved, so check the waiting list before requesting another.
There is no printer or physical number display.

The existing unauthenticated HTTP API is intended for a trusted local network.

References: [ESP8266 setup](https://arduino-esp8266.readthedocs.io/en/latest/installing.html),
[HTTP API](https://github.com/esp8266/Arduino/blob/master/libraries/ESP8266HTTPClient/src/ESP8266HTTPClient.h),
[electrical specifications](https://documentation.espressif.com/0a-esp8266ex_datasheet_en.html).
