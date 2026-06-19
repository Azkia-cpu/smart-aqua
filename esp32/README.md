# SmartAqua — Panduan ESP32

## Library yang Dibutuhkan

Install melalui **Arduino IDE > Library Manager**:

| Library | Versi | Fungsi |
|---------|-------|--------|
| ArduinoJson | v7.x | Parse JSON API response |
| WiFi | Built-in | Koneksi WiFi |
| HTTPClient | Built-in | HTTP request ke Laravel |

## Board Setup (Arduino IDE)

1. **File > Preferences** → Additional Board Manager URLs:
   ```
   https://raw.githubusercontent.com/espressif/arduino-esp32/gh-pages/package_esp32_index.json
   ```
2. **Tools > Board > Board Manager** → Install "ESP32 by Espressif Systems"
3. **Tools > Board** → Pilih "ESP32 Dev Module"
4. **Tools > Upload Speed** → 115200
5. **Tools > Port** → Pilih port COM yang sesuai

## Konfigurasi Sebelum Upload

Edit bagian atas file `smartaqua_esp32.ino`:

```cpp
// WiFi
const char* WIFI_SSID     = "NAMA_WIFI_ANDA";
const char* WIFI_PASSWORD = "PASSWORD_WIFI_ANDA";

// Server Laravel (ganti dengan IP komputer yang menjalankan Laravel)
const char* SERVER_URL     = "http://192.168.1.100:8000";

// Device Token (dari database, lihat tabel device_tokens)
const char* DEVICE_TOKEN   = "smartaqua_pond_a_token_2026";

// Kode Kolam
const char* POND_CODE      = "pond_a";
```

### Cara Mendapatkan IP Server

```bash
# Windows
ipconfig
# Cari IPv4 Address di adapter WiFi, contoh: 192.168.1.100

# Pastikan Laravel serve di semua interface:
php artisan serve --host=0.0.0.0 --port=8000
```

## Wiring Diagram

```
┌─────────────────────────────────────────────────┐
│                 ESP32 DevKit V1                  │
│                                                  │
│  GPIO 5  ──── TRIG (HC-SR04)                    │
│  GPIO 18 ──── ECHO (HC-SR04)                    │
│  GPIO 34 ──── OUT  (pH Sensor Module)            │
│  GPIO 27 ──── OUT  (Flow Sensor YF-S201)         │
│  GPIO 26 ──── IN1  (Relay 2CH)                   │
│  GPIO 14 ──── LED Hijau  (+ Resistor 220Ω)      │
│  GPIO 12 ──── LED Kuning (+ Resistor 220Ω)      │
│  GPIO 13 ──── LED Merah  (+ Resistor 220Ω)      │
│                                                  │
│  3.3V ─────── VCC (pH Sensor)                    │
│  5V   ─────── VCC (HC-SR04, Flow Sensor)         │
│  VIN  ─────── VCC (Relay Module)                 │
│  GND  ─────── GND (Semua komponen)               │
└─────────────────────────────────────────────────┘
```

## Pin Configuration

| Pin | Komponen | Fungsi |
|-----|----------|--------|
| GPIO 5 | HC-SR04 TRIG | Trigger ultrasonik |
| GPIO 18 | HC-SR04 ECHO | Echo ultrasonik |
| GPIO 34 | pH Sensor | Analog input pH |
| GPIO 27 | Flow Sensor | Pulse counter debit air |
| GPIO 26 | Relay | Kontrol pompa air |
| GPIO 14 | LED Hijau | Indikator NORMAL |
| GPIO 12 | LED Kuning | Indikator WARNING |
| GPIO 13 | LED Merah | Indikator DANGER |

## Kalibrasi Sensor pH

1. Siapkan larutan buffer **pH 7.0** dan **pH 4.0**
2. Celupkan sensor ke pH 7.0, catat voltage di Serial Monitor
3. Celupkan sensor ke pH 4.0, catat voltage di Serial Monitor
4. Hitung:
   ```
   PH_SLOPE  = (7.0 - 4.0) / (voltage_7 - voltage_4)
   PH_OFFSET = 7.0 - (PH_SLOPE * voltage_7)
   ```
5. Update nilai `PH_SLOPE` dan `PH_OFFSET` di kode

## Kalibrasi Sensor Level Air

Sesuaikan nilai `SENSOR_HEIGHT_CM` dengan jarak sensor HC-SR04 ke dasar akuarium:

```
SENSOR_HEIGHT_CM = 15.0  // cm (default)
```

```
   Sensor HC-SR04 ← dipasang di atas
       │
       │ ← distance_cm (jarak ke permukaan air)
       ▼
   ~~~~~~~~ ← permukaan air
   │      │
   │      │ ← water_level = SENSOR_HEIGHT_CM - distance_cm
   │      │
   └──────┘ ← dasar akuarium
```

## Token Perangkat (Default dari Seeder)

| Kolam | Code | Token |
|-------|------|-------|
| Kolam A | pond_a | `smartaqua_pond_a_token_2026` |
| Kolam B | pond_b | `smartaqua_pond_b_token_2026` |
| Kolam C | pond_c | `smartaqua_pond_c_token_2026` |
| Kolam D | pond_d | `smartaqua_pond_d_token_2026` |

## Serial Monitor Output

Buka Serial Monitor di **115200 baud** untuk melihat:

```
============================================
  SMART AQUACULTURE - ESP32 MONITORING
============================================

[SETUP] Pin GPIO berhasil dikonfigurasi.
[WiFi] Menghubungkan ke: SmartAqua_WiFi...
[WiFi] Terhubung! IP: 192.168.1.50
[SERVER] Koneksi berhasil!
[SETUP] Sistem siap beroperasi.

[SENSOR] Level Air: 8.5 cm (jarak: 6.5 cm)
[SENSOR] pH: 7.2 (ADC: 2048, V: 1.65V)
[SENSOR] Debit Air: 2.3 L/min (17 pulsa)
[API] Mengirim data... OK!
[POMPA] Mode: OTOMATIS
[POMPA] Status: OFF (MATI)
```
