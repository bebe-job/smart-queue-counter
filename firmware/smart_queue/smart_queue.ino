const int BUTTON_PIN = 14;
const int BUZZER_PIN = 27;

// Kung baliktad ang beep, ilisi ang HIGH og LOW.
const int BUZZER_ON = HIGH;
const int BUZZER_OFF = (BUZZER_ON == HIGH) ? LOW : HIGH;

unsigned long queueNumber = 0;

int lastReading = LOW;
int stableState = LOW;
unsigned long lastChangeTime = 0;
const unsigned long DEBOUNCE_MS = 50;

bool buzzerPlaying = false;
unsigned long beepStartTime = 0;
const unsigned long BEEP_MS = 150;

void setup() {
  Serial.begin(115200);

  // External 10k ohm pull-down resistor ang gamit.
  pinMode(BUTTON_PIN, INPUT);

  digitalWrite(BUZZER_PIN, BUZZER_OFF);
  pinMode(BUZZER_PIN, OUTPUT);

  // Dili mag-generate og ticket kung held ang button sa startup.
  lastReading = digitalRead(BUTTON_PIN);
  stableState = lastReading;
  lastChangeTime = millis();

  Serial.println();
  Serial.println("Smart Queue Counter ready!");
  Serial.println("Press the button to get a number.");
}

void loop() {
  unsigned long now = millis();
  int reading = digitalRead(BUTTON_PIN);

  if (reading != lastReading) {
    lastChangeTime = now;
    lastReading = reading;
  }

  // Hulaton nga stable ang button aron malikayan ang duplicate.
  if (now - lastChangeTime >= DEBOUNCE_MS &&
      reading != stableState) {

    stableState = reading;

    // Usa ra ka ticket kada press; release una para sa sunod.
    if (stableState == HIGH) {
      queueNumber++;

      Serial.print("Your queue number: ");
      if (queueNumber < 100) Serial.print("0");
      if (queueNumber < 10) Serial.print("0");
      Serial.println(queueNumber);

      digitalWrite(BUZZER_PIN, BUZZER_ON);
      buzzerPlaying = true;
      beepStartTime = now;
    }
  }

  if (buzzerPlaying && now - beepStartTime >= BEEP_MS) {
    digitalWrite(BUZZER_PIN, BUZZER_OFF);
    buzzerPlaying = false;
  }
}