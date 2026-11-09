# ESP32-WROOM-32 wiring and checks (38-pin board)

Open `smartqueue/smartqueue.ino` in Arduino IDE for the ESP32 firmware that
connects to the website. Follow `smartqueue/README.md` for Wi-Fi, XAMPP, and
Arduino IDE setup. The older sketches in this folder are preserved separately.

Disconnect power before wiring. Use the board's GPIO labels, not connector
position numbers.

| Connection | Destination |
| --- | --- |
| Button switched terminal | ESP32 3.3V |
| Other button switched terminal | GPIO14 |
| External 10k ohm resistor | GPIO14 to GND |
| Active buzzer signal / IN | GPIO27 |
| Active buzzer VCC | ESP32 3.3V |
| Active buzzer GND | ESP32 GND |

Use an active buzzer module rated to operate at 3.3V. The released button reads
LOW; a pressed button reads HIGH. In `smartqueue/config.h`, enable
`BUZZER_ENABLED` after checking compatibility, and set `BUZZER_ACTIVE_LOW`
to match the module's trigger polarity.

Select ESP32 Dev Module and the correct serial port in Arduino IDE, then
Verify / Upload. Serial Monitor uses 115200 baud.

After Wi-Fi connects, release the button for at least 50ms before pressing it.
Each press requests one ticket from the website and prints its saved number.
Holding the button produces no additional tickets, including when held at startup.
Wait for feedback, then release for at least 50ms before the next press.
Numbers are stored in MySQL and survive board restarts. Paused or offline
requests do not generate local numbers. See `smartqueue/README.md` for beep
patterns and handling unconfirmed requests.
