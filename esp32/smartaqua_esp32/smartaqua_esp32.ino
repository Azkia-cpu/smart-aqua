/*
 * ============================================================
 * SMART AQUACULTURE MONITORING AND AUTOMATIC WATER MANAGEMENT
 * ============================================================
 * 
 * File    : smartaqua_esp32.ino
 * Board   : ESP32 DevKit V1
 * Framework: Arduino
 * 
 * Deskripsi:
 *   Firmware ESP32 untuk sistem monitoring akuakultur cerdas.
 *   Membaca data sensor (level air, pH, debit air), mengirim
 *   ke REST API Laravel, menerima perintah pompa, dan
 *   mengontrol relay + LED indikator.
 * 
 * Library yang Dibutuhkan (install via Arduino Library Manager):
 *   1. ArduinoJson (by Benoit Blanchon) v7.x
 *   2. HTTPClient (built-in ESP32)
 *   3. WiFi (built-in ESP32)
 * 
 * Konfigurasi Board Arduino IDE:
 *   Board  : ESP32 Dev Module
 *   Upload : 115200
 *   Flash  : 4MB
 * ============================================================
 */

#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <Arduino.h>

// ============================================================
// KONFIGURASI WiFi
// ============================================================
const char* WIFI_SSID     = "TP-Link_9B8E";        // Ganti dengan SSID WiFi
const char* WIFI_PASSWORD = "58797044";     // Ganti dengan password WiFi

// ============================================================
// KONFIGURASI SERVER LARAVEL
// ============================================================
const char* SERVER_URL = "http://192.168.0.104/smartaqua/public";
const char* DEVICE_TOKEN   = "smartaqua_pond_a_token_2026"; // Token dari DeviceTokenSeeder
const char* POND_CODE      = "pond_a";                      // Kode kolam

// ============================================================
// KONFIGURASI PIN ESP32
// ============================================================

// --- Sensor Ultrasonik HC-SR04 ---
#define TRIG_PIN          32
#define ECHO_PIN          35

// --- Sensor pH (Analog) ---
#define PH_SENSOR_PIN     34

// --- Sensor Debit Air (Flow Sensor YF-S201) ---
#define FLOW_SENSOR_PIN   27

// --- Relay Pompa Air ---
#define RELAY_PUMP_PIN    33

// --- LED Indikator ---
#define LED_NORMAL        14    // Hijau  - kondisi normal
#define LED_WARNING       12    // Kuning - kondisi peringatan
#define LED_DANGER        13    // Merah  - kondisi kritis

// ============================================================
// KONFIGURASI SENSOR & THRESHOLD
// ============================================================

// HC-SR04: tinggi sensor dari dasar akuarium (cm)
// Jika sensor dipasang di atas akuarium setinggi 12 cm dari dasar,
// maka water_level = SENSOR_HEIGHT - jarak_terukur
#define SENSOR_HEIGHT_CM  11.5

// Batas level air (cm) - Nilai default yang dapat diperbarui secara dinamis oleh server
float MIN_WATER_LEVEL = 3.0;   // Pompa ON di bawah ini
float MAX_WATER_LEVEL = 9.0;  // Pompa OFF di atas ini

// Batas pH
#define MIN_PH            6.5
#define MAX_PH            8.5

// Kalibrasi pH Sensor (sesuaikan dengan kalibrasi Anda)
// Rumus: pH = PH_SLOPE * voltage + PH_OFFSET
// Default untuk modul pH-4502C:
//   pH 7.0 = ~2.5V (tengah)
//   pH 4.0 = ~3.04V
//   Slope ≈ -5.7
#define PH_OFFSET         21.34
#define PH_SLOPE          -5.70

// Kalibrasi Flow Sensor (YF-S201)
// Pulse per liter: 450 (dari datasheet)
// Factor: 7.5 (pulse per second = flow rate in L/min * 7.5)
#define FLOW_CALIBRATION  7.5

// ============================================================
// INTERVAL WAKTU (millis)
// ============================================================
#define SEND_INTERVAL       2000    // Kirim data setiap 2 detik
#define PUMP_CHECK_INTERVAL 2000    // Cek status pompa setiap 2 detik
#define LED_UPDATE_INTERVAL 1000    // Update LED setiap 1 detik
#define WIFI_CHECK_INTERVAL 30000   // Cek WiFi setiap 30 detik
#define SENSOR_READ_INTERVAL 500    // Baca sensor setiap 500ms (0.5 detik)

// ============================================================
// VARIABEL GLOBAL
// ============================================================

// -- Nilai Sensor Terakhir --
float currentWaterLevel = 0.0;
float currentDistanceCm = 0.0;
float currentPhValue    = 7.0;
float currentFlowRate   = 0.0;

// -- Status Pompa --
volatile bool pumpIsOn           = false;
volatile bool isManualMode       = false;
volatile bool serverPumpState    = false; // Ditulis oleh Core 0 (HTTP), dibaca oleh Core 1 (Main)

// -- Flow Sensor (interrupt) --
volatile unsigned long pulseCount = 0;
unsigned long lastFlowCalcTime    = 0;

// -- Timer millis() --
unsigned long lastSendTime       = 0;
unsigned long lastPumpCheckTime  = 0;
unsigned long lastLedUpdateTime  = 0;
unsigned long lastWifiCheckTime  = 0;
unsigned long lastSensorReadTime = 0;

// -- Status koneksi --
bool serverConnected = false;
int  failedRequests  = 0;
#define MAX_FAILED_REQUESTS 10

// ============================================================
// INTERRUPT HANDLER - Flow Sensor
// ============================================================
void IRAM_ATTR flowPulseCounter() {
    pulseCount++;
}

// -- Task HTTP (Core 0) --
TaskHandle_t httpTaskHandle = NULL;

void httpTask(void *pvParameters) {
    Serial.println("[SYSTEM] HTTP Task dimulai pada Core 0");
    unsigned long lastWifiCheck = 0;
    
    for (;;) {
        unsigned long now = millis();
        
        // Cek WiFi setiap 10 detik di Core 0
        if (now - lastWifiCheck >= 10000) {
            lastWifiCheck = now;
            if (WiFi.status() != WL_CONNECTED) {
                Serial.println("[WiFi] Koneksi terputus. Rekoneksi di Core 0...");
                WiFi.disconnect();
                vTaskDelay(pdMS_TO_TICKS(100));
                WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
                
                int attempts = 0;
                while (WiFi.status() != WL_CONNECTED && attempts < 10) {
                    vTaskDelay(pdMS_TO_TICKS(500));
                    attempts++;
                }
                
                if (WiFi.status() == WL_CONNECTED) {
                    Serial.println("[WiFi] Reconnect berhasil pada Core 0!");
                }
            }
        }
        
        if (WiFi.status() == WL_CONNECTED) {
            kirimDataKeServer();
        }
        
        vTaskDelay(pdMS_TO_TICKS(SEND_INTERVAL));
    }
}

// ============================================================
// SETUP
// ============================================================
void setup() {
    Serial.begin(115200);
    delay(100);

    Serial.println();
    Serial.println("============================================");
    Serial.println("  SMART AQUACULTURE - ESP32 MONITORING");
    Serial.println("============================================");
    Serial.println();

    // --- Inisialisasi Pin ---
    setupPins();

    // --- Koneksi WiFi ---
    connectWiFi();

    // --- Tes koneksi awal ke server ---
    testServerConnection();

    // --- Indikator startup selesai ---
    startupBlink();

    // --- Buat Task HTTP di Core 0 ---
    xTaskCreatePinnedToCore(
        httpTask,           /* Fungsi task. */
        "HTTPTask",         /* Nama task. */
        10000,              /* Stack size */
        NULL,               /* Parameter task */
        1,                  /* Prioritas */
        &httpTaskHandle,    /* Handle task */
        0                   /* Core 0 */
    );

    Serial.println("[SETUP] Sistem siap beroperasi.");
    Serial.println();
}

// ============================================================
// LOOP UTAMA (Non-blocking dengan millis)
// ============================================================
void loop() {
    unsigned long now = millis();

    // 1. Baca semua sensor (setiap SENSOR_READ_INTERVAL)
    if (now - lastSensorReadTime >= SENSOR_READ_INTERVAL) {
        lastSensorReadTime = now;
        bacaSensorLevel();
        bacaSensorPH();
        bacaSensorDebit();
        updateLedIndikator();
    }

    // 2. Kontrol pompa berdasarkan mode
    kontrolPompa();

    // Delay kecil untuk mencegah watchdog timer reset
    delay(1);
}

// ============================================================
// INISIALISASI PIN
// ============================================================
void setupPins() {
    // Sensor Ultrasonik
    pinMode(TRIG_PIN, OUTPUT);
    pinMode(ECHO_PIN, INPUT);

    // pH Sensor (analog input, tidak perlu pinMode di ESP32)

    // Flow Sensor (interrupt)
    pinMode(FLOW_SENSOR_PIN, INPUT_PULLUP);
    attachInterrupt(digitalPinToInterrupt(FLOW_SENSOR_PIN), flowPulseCounter, RISING);

    // Relay Pompa (active LOW)
    pinMode(RELAY_PUMP_PIN, OUTPUT);
    digitalWrite(RELAY_PUMP_PIN, HIGH);  // OFF (active LOW)

    // LED Indikator
    pinMode(LED_NORMAL, OUTPUT);
    pinMode(LED_WARNING, OUTPUT);
    pinMode(LED_DANGER, OUTPUT);

    // Matikan semua LED
    digitalWrite(LED_NORMAL, LOW);
    digitalWrite(LED_WARNING, LOW);
    digitalWrite(LED_DANGER, LOW);

    Serial.println("[SETUP] Pin GPIO berhasil dikonfigurasi.");
}

// ============================================================
// KONEKSI WiFi
// ============================================================
void connectWiFi() {
    Serial.print("[WiFi] Menghubungkan ke: ");
    Serial.println(WIFI_SSID);

    WiFi.mode(WIFI_STA);
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

    int attempts = 0;
    while (WiFi.status() != WL_CONNECTED && attempts < 40) {
        delay(500);
        Serial.print(".");
        attempts++;

        // Blink LED warning selama connecting
        digitalWrite(LED_WARNING, !digitalRead(LED_WARNING));
    }

    digitalWrite(LED_WARNING, LOW);

    if (WiFi.status() == WL_CONNECTED) {
        Serial.println();
        Serial.print("[WiFi] Terhubung! IP: ");
        Serial.println(WiFi.localIP());
        Serial.print("[WiFi] RSSI: ");
        Serial.print(WiFi.RSSI());
        Serial.println(" dBm");
    } else {
        Serial.println();
        Serial.println("[WiFi] GAGAL terhubung! Sistem tetap berjalan offline.");
    }
}

void checkWiFiConnection() {
    if (WiFi.status() != WL_CONNECTED) {
        Serial.println("[WiFi] Koneksi terputus. Mencoba reconnect...");
        WiFi.disconnect();
        delay(100);
        WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

        int attempts = 0;
        while (WiFi.status() != WL_CONNECTED && attempts < 20) {
            delay(500);
            attempts++;
        }

        if (WiFi.status() == WL_CONNECTED) {
            Serial.println("[WiFi] Reconnect berhasil!");
            failedRequests = 0;
        } else {
            Serial.println("[WiFi] Reconnect gagal. Coba lagi nanti.");
        }
    }
}

// ============================================================
// TES KONEKSI SERVER
// ============================================================
void testServerConnection() {
    if (WiFi.status() != WL_CONNECTED) return;

    Serial.print("[SERVER] Menguji koneksi ke: ");
    Serial.println(SERVER_URL);

    HTTPClient http;
    String url = String(SERVER_URL) + "/api/pump-status/" + String(POND_CODE);
    http.begin(url);
    http.addHeader("X-Device-Token", DEVICE_TOKEN);
    http.addHeader("Accept", "application/json");

    int httpCode = http.GET();
    if (httpCode == 200) {
        Serial.println("[SERVER] Koneksi berhasil!");
        serverConnected = true;
    } else {
        Serial.print("[SERVER] Koneksi gagal. HTTP Code: ");
        Serial.println(httpCode);
        serverConnected = false;
    }
    http.end();
}

// ============================================================
// BACA SENSOR LEVEL AIR (HC-SR04)
// ============================================================
void bacaSensorLevel() {
    // Kirim pulse trigger 10µs
    digitalWrite(TRIG_PIN, LOW);
    delayMicroseconds(2);
    digitalWrite(TRIG_PIN, HIGH);
    delayMicroseconds(10);
    digitalWrite(TRIG_PIN, LOW);

    // Baca durasi echo (timeout 30ms = ~500cm max)
    long duration = pulseIn(ECHO_PIN, HIGH, 30000);

    if (duration == 0) {
        // Timeout - sensor error
        Serial.println("[SENSOR] HC-SR04 timeout! Menggunakan nilai terakhir.");
        return;
    }

    // Hitung jarak (cm)
    float distance = (duration * 0.0343) / 2.0;

    // Validasi range (1-400 cm valid untuk HC-SR04)
    if (distance < 1.0 || distance > 400.0) {
        Serial.print("[SENSOR] Jarak tidak valid: ");
        Serial.println(distance);
        return;
    }

    currentDistanceCm = distance;

    // Hitung level air: tinggi sensor - jarak ke permukaan air
    currentWaterLevel = SENSOR_HEIGHT_CM - distance;

    // Clamp ke range valid (0 - max)
    if (currentWaterLevel < 0) currentWaterLevel = 0;
    if (currentWaterLevel > SENSOR_HEIGHT_CM) currentWaterLevel = SENSOR_HEIGHT_CM;

    // Bulatkan ke 1 desimal
    currentWaterLevel = round(currentWaterLevel * 10.0) / 10.0;

    Serial.print("[SENSOR] Level Air: ");
    Serial.print(currentWaterLevel);
    Serial.print(" cm (jarak: ");
    Serial.print(currentDistanceCm);
    Serial.println(" cm)");
}

// ============================================================
// BACA SENSOR pH
// ============================================================
void bacaSensorPH() {
    // Ambil rata-rata dari 10 pembacaan untuk stabilitas
    long totalAdc = 0;
    int validReadings = 0;

    for (int i = 0; i < 10; i++) {
        int adcValue = analogRead(PH_SENSOR_PIN);
        if (adcValue > 0 && adcValue < 4095) {
            totalAdc += adcValue;
            validReadings++;
        }
        delayMicroseconds(100);
    }

    if (validReadings == 0) {
        Serial.println("[SENSOR] pH sensor error! Tidak ada pembacaan valid.");
        return;
    }

    float avgAdc = (float)totalAdc / validReadings;

    // Konversi ADC ke tegangan (ESP32: 12-bit ADC, 0-3.3V)
    float voltage = avgAdc * (3.3 / 4095.0);

    // Konversi tegangan ke nilai pH menggunakan kalibrasi
    float phValue = PH_OFFSET + (PH_SLOPE * voltage);

    // Clamp ke range pH valid (0-14)
    if (phValue < 0) phValue = 0;
    if (phValue > 14) phValue = 14;

    // Bulatkan ke 1 desimal
    currentPhValue = round(phValue * 10.0) / 10.0;

    Serial.print("[SENSOR] pH: ");
    Serial.print(currentPhValue);
    Serial.print(" (ADC: ");
    Serial.print(avgAdc, 0);
    Serial.print(", V: ");
    Serial.print(voltage, 2);
    Serial.println("V)");
}

// ============================================================
// BACA SENSOR DEBIT AIR (Flow Sensor)
// ============================================================
void bacaSensorDebit() {
    unsigned long now = millis();
    unsigned long elapsed = now - lastFlowCalcTime;

    if (elapsed < 1000) return;  // Minimum 1 detik

    // Disable interrupt sementara untuk baca pulseCount
    noInterrupts();
    unsigned long pulses = pulseCount;
    pulseCount = 0;
    interrupts();

    // Hitung flow rate (L/min)
    // Rumus: flowRate = (pulses / calibrationFactor) * (60000 / elapsed)
    currentFlowRate = (float)pulses / FLOW_CALIBRATION;

    // Bulatkan ke 1 desimal
    currentFlowRate = round(currentFlowRate * 10.0) / 10.0;

    lastFlowCalcTime = now;

    if (currentFlowRate > 0) {
        Serial.print("[SENSOR] Debit Air: ");
        Serial.print(currentFlowRate);
        Serial.print(" L/min (");
        Serial.print(pulses);
        Serial.println(" pulsa)");
    }
}

// ============================================================
// KONTROL POMPA
// ============================================================
void kontrolPompa() {
    // SAFETY OVERRIDE: Jika level air mencapai atau melebihi batas maksimum,
    // pompa HARUS mati untuk mencegah banjir, baik dalam mode manual maupun otomatis.
    if (currentWaterLevel >= MAX_WATER_LEVEL && pumpIsOn) {
        setPompa(false);
        isManualMode = false; // Kembalikan ke otomatis demi keselamatan
        serverPumpState = false; // Override status pompa dari server
        Serial.println("[POMPA] SAFETY OFF - Level air mencapai/melebihi batas maksimum!");
    }

    if (isManualMode) {
        // MODE MANUAL: Ikuti perintah dari server
        if (serverPumpState != pumpIsOn) {
            // Pengaman tambahan: jangan nyalakan jika sudah mencapai batas maks
            if (serverPumpState && currentWaterLevel >= MAX_WATER_LEVEL) {
                Serial.println("[POMPA] Mengabaikan perintah ON dari server karena level air sudah maksimum!");
                isManualMode = false;
                serverPumpState = false;
                setPompa(false);
            } else {
                setPompa(serverPumpState);
                Serial.print("[POMPA] Perintah manual diterapkan: ");
                Serial.println(serverPumpState ? "ON" : "OFF");
            }
        }
    } else {
        // MODE OTOMATIS
        if (currentWaterLevel < MIN_WATER_LEVEL && !pumpIsOn) {
            setPompa(true);
            Serial.println("[POMPA] AUTO ON - Level air di bawah batas minimum!");
        }
    }
}

void setPompa(bool on) {
    pumpIsOn = on;
    // Relay active LOW: LOW = ON, HIGH = OFF
    digitalWrite(RELAY_PUMP_PIN, on ? LOW : HIGH);

    Serial.print("[POMPA] Status: ");
    Serial.println(on ? "ON (HIDUP)" : "OFF (MATI)");
}

// ============================================================
// UPDATE LED INDIKATOR
// ============================================================
void updateLedIndikator() {
    // Matikan semua LED dulu
    digitalWrite(LED_NORMAL, LOW);
    digitalWrite(LED_WARNING, LOW);
    digitalWrite(LED_DANGER, LOW);

    // Cek kondisi DANGER (kritis)
    bool isDanger = false;
    bool isWarning = false;

    // Level air kritis
    if (currentWaterLevel < MIN_WATER_LEVEL || currentWaterLevel > MAX_WATER_LEVEL) {
        isDanger = true;
    }

    // pH kritis
    if (currentPhValue < MIN_PH || currentPhValue > MAX_PH) {
        isDanger = true;
    }

    // pH mendekati batas (warning zone)
    if ((currentPhValue >= MIN_PH && currentPhValue < 7.0) ||
        (currentPhValue > 8.0 && currentPhValue <= MAX_PH)) {
        isWarning = true;
    }

    // Level air mendekati batas (warning zone: 1cm dari threshold)
    if ((currentWaterLevel >= MIN_WATER_LEVEL && currentWaterLevel < (MIN_WATER_LEVEL + 1.0)) ||
        (currentWaterLevel > (MAX_WATER_LEVEL - 1.0) && currentWaterLevel <= MAX_WATER_LEVEL)) {
        isWarning = true;
    }

    // Nyalakan LED sesuai kondisi (prioritas: danger > warning > normal)
    if (isDanger) {
        digitalWrite(LED_DANGER, HIGH);
    } else if (isWarning) {
        digitalWrite(LED_WARNING, HIGH);
    } else {
        digitalWrite(LED_NORMAL, HIGH);
    }
}

// ============================================================
// KIRIM DATA KE SERVER LARAVEL
// ============================================================
void kirimDataKeServer() {
    if (WiFi.status() != WL_CONNECTED) {
        Serial.println("[API] WiFi tidak terhubung. Data tidak dikirim.");
        return;
    }

    // Siapkan JSON payload
    JsonDocument doc;
    doc["water_level"] = currentWaterLevel;
    doc["ph_value"]    = currentPhValue;
    doc["flow_rate"]   = currentFlowRate;
    doc["distance_cm"] = currentDistanceCm;

    String jsonPayload;
    serializeJson(doc, jsonPayload);

    // Kirim HTTP POST
    HTTPClient http;
    String url = String(SERVER_URL) + "/api/sensor-data";
    http.begin(url);
    http.addHeader("Content-Type", "application/json");
    http.addHeader("Accept", "application/json");
    http.addHeader("X-Device-Token", DEVICE_TOKEN);
    http.setTimeout(1500);  // Timeout 1.5 detik

    Serial.print("[API] Mengirim data... ");

    int httpCode = http.POST(jsonPayload);

    if (httpCode == 200 || httpCode == 201) {
        String response = http.getString();
        Serial.println("OK!");
        failedRequests = 0;

        // Parse response untuk mendapat status pompa terbaru
        parseServerResponse(response);

    } else if (httpCode < 0) {
        Serial.print("ERROR: ");
        Serial.println(http.errorToString(httpCode));
        failedRequests++;
    } else {
        Serial.print("HTTP ");
        Serial.print(httpCode);
        Serial.print(": ");
        Serial.println(http.getString());
        failedRequests++;
    }

    http.end();

    // Jika terlalu banyak gagal, coba reconnect WiFi
    if (failedRequests >= MAX_FAILED_REQUESTS) {
        Serial.println("[API] Terlalu banyak request gagal. Reconnect WiFi...");
        checkWiFiConnection();
        failedRequests = 0;
    }
}

// ============================================================
// PARSE RESPONSE SERVER (dari POST /api/sensor-data)
// ============================================================
void parseServerResponse(String response) {
    JsonDocument doc;
    DeserializationError error = deserializeJson(doc, response);

    if (error) {
        Serial.print("[API] Parse error: ");
        Serial.println(error.c_str());
        return;
    }

    bool success = doc["success"] | false;
    if (!success) return;

    // Baca status pompa dari response
    JsonObject pump = doc["data"]["pump"];
    if (!pump.isNull()) {
        bool serverPumpOn = pump["is_on"] | false;
        bool serverManualMode = pump["is_manual_mode"] | false;

        // Update thresholds dynamically from server
        if (pump.containsKey("min_water_level")) {
            MIN_WATER_LEVEL = pump["min_water_level"] | MIN_WATER_LEVEL;
        }
        if (pump.containsKey("max_water_level")) {
            MAX_WATER_LEVEL = pump["max_water_level"] | MAX_WATER_LEVEL;
        }

        // Update mode manual dari server
        if (serverManualMode != isManualMode) {
            isManualMode = serverManualMode;
            Serial.print("[POMPA] Mode berubah ke: ");
            Serial.println(isManualMode ? "MANUAL" : "OTOMATIS");
        }

        // Update status pompa dari server
        serverPumpState = serverPumpOn;
    }
}

// cekStatusPompa telah dihapus karena fungsinya digantikan secara penuh oleh respons dari POST sensor data yang jauh lebih efisien.

// ============================================================
// STARTUP BLINK (indikator sistem siap)
// ============================================================
void startupBlink() {
    for (int i = 0; i < 3; i++) {
        digitalWrite(LED_NORMAL, HIGH);
        digitalWrite(LED_WARNING, HIGH);
        digitalWrite(LED_DANGER, HIGH);
        delay(150);
        digitalWrite(LED_NORMAL, LOW);
        digitalWrite(LED_WARNING, LOW);
        digitalWrite(LED_DANGER, LOW);
        delay(150);
    }
    // Nyalakan LED normal sebagai default awal
    digitalWrite(LED_NORMAL, HIGH);
}

// ============================================================
// CATATAN KALIBRASI SENSOR pH
// ============================================================
/*
 * PROSEDUR KALIBRASI pH SENSOR:
 * 
 * 1. Siapkan larutan buffer pH 4.0 dan pH 7.0
 * 
 * 2. Celupkan sensor ke larutan pH 7.0
 *    - Baca nilai ADC yang muncul di Serial Monitor
 *    - Catat: adc_7 = ???, voltage_7 = ???
 * 
 * 3. Celupkan sensor ke larutan pH 4.0
 *    - Baca nilai ADC yang muncul di Serial Monitor
 *    - Catat: adc_4 = ???, voltage_4 = ???
 * 
 * 4. Hitung slope dan offset:
 *    PH_SLOPE  = (7.0 - 4.0) / (voltage_7 - voltage_4)
 *    PH_OFFSET = 7.0 - (PH_SLOPE * voltage_7)
 * 
 * 5. Update nilai PH_SLOPE dan PH_OFFSET di bagian konfigurasi
 * 
 * Contoh:
 *   voltage_7 = 2.52V, voltage_4 = 3.04V
 *   PH_SLOPE  = (7.0 - 4.0) / (2.52 - 3.04) = -5.77
 *   PH_OFFSET = 7.0 - (-5.77 * 2.52) = 21.54
 */

// ============================================================
// CATATAN PEMASANGAN HC-SR04
// ============================================================
/*
 * PEMASANGAN SENSOR ULTRASONIK:
 * 
 * Sensor dipasang di ATAS akuarium/kolam, menghadap ke bawah
 * ke permukaan air.
 * 
 *   ┌──────────────┐ ← Sensor HC-SR04
 *   │  TRIG  ECHO  │
 *   └──────┬───────┘
 *          │ ← Jarak terukur (distance_cm)
 *          ▼
 *   ~~~~~~~~ ← Permukaan air
 *   │      │
 *   │      │ ← Level air (water_level)
 *   │      │
 *   └──────┘ ← Dasar akuarium
 * 
 * Rumus:
 *   water_level = SENSOR_HEIGHT_CM - distance_cm
 * 
 * SENSOR_HEIGHT_CM = jarak dari sensor ke dasar akuarium
 *   → Untuk akuarium 12cm + jarak sensor ~3cm = 15cm
 *   → Sesuaikan dengan kondisi pemasangan Anda
 */

// ============================================================
// WIRING DIAGRAM
// ============================================================
/*
 * ESP32 DevKit V1 Wiring:
 * 
 * ┌─────────────────────────────────────────────────┐
 * │                 ESP32 DevKit V1                  │
 * │                                                  │
 * │  GPIO 5  ──── TRIG (HC-SR04)                    │
 * │  GPIO 18 ──── ECHO (HC-SR04)                    │
 * │  GPIO 34 ──── OUT  (pH Sensor Module)            │
 * │  GPIO 27 ──── OUT  (Flow Sensor YF-S201)         │
 * │  GPIO 26 ──── IN1  (Relay 2CH)                   │
 * │  GPIO 14 ──── LED Hijau  (+ Resistor 220Ω)      │
 * │  GPIO 12 ──── LED Kuning (+ Resistor 220Ω)      │
 * │  GPIO 13 ──── LED Merah  (+ Resistor 220Ω)      │
 * │                                                  │
 * │  3.3V ─────── VCC (pH Sensor)                    │
 * │  5V   ─────── VCC (HC-SR04, Flow Sensor)         │
 * │  VIN  ─────── VCC (Relay Module)                 │
 * │  GND  ─────── GND (Semua komponen)               │
 * └─────────────────────────────────────────────────┘
 * 
 * Relay 2CH:
 *   IN1  ← GPIO 26
 *   VCC  ← VIN (5V dari USB)
 *   GND  ← GND
 *   COM  ← (+) Adaptor 5V Pompa
 *   NO   ← (+) Pompa DC
 *   (-)  Pompa DC ← (-) Adaptor 5V
 * 
 * CATATAN:
 *   - HC-SR04 membutuhkan 5V (gunakan pin 5V ESP32)
 *   - pH Sensor Module membutuhkan 3.3V (bukan 5V!)
 *   - Flow Sensor membutuhkan 5V
 *   - Relay active LOW (LOW = ON, HIGH = OFF)
 *   - Semua LED menggunakan resistor 220Ω ke GND
 */
