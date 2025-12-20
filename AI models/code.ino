// SolarVision AI - Complete TSYP13 Prototype Code with Trained Edge Impulse Model + Sensor Simulation
// ESP32-CAM: Sensors + Camera + MQTT + Twilio Alerts + Edge Impulse Defect Detection + Synthetic Data Sim
// Note: Set simulateSensors = true to use CSV data instead of real sensors (for testing without hardware).
//       CSV: sensor_data.csv on SD card (100 rows: timestamp, temperature_c, current_a, irradiance_lux).
//       Install lib via Arduino IDE: Sketch > Include Library > Add .ZIP Library for EI.

#include <WiFi.h>
#include <PubSubClient.h> // MQTT (HiveMQ)
#include <ArduinoJson.h> // JSON
#include <Wire.h>
#include <Adafruit_INA219.h> // Power
#include <BH1750.h> // Irradiance
#include <DHT.h> // Temp/Hum
#include "esp_camera.h" // Camera
#include <base64.h> // Twilio auth
#include <Solar-Defect-Classification_inferencing.h> // Edge Impulse: Your trained model header
#include <SD.h> // SIMULATION: For CSV reading from SD card
#include <SPI.h> // SIMULATION: SD dependency

// === CONFIG ===
const char* ssid = "YOUR_WIFI_SSID"; // Replace
const char* password = "YOUR_WIFI_PASS";
const char* mqtt_server = "broker.hivemq.com"; // HiveMQ broker
const int mqtt_port = 1883;
const char* pub_topic = "solar/farm/p1"; // Publish data
const char* sub_topic = "solar/control/p1"; // Subscribe grid cmds
#define DHTPIN 4
#define DHTTYPE DHT22
DHT dht(DHTPIN, DHTTYPE);
Adafruit_INA219 ina219(0x40); // I2C addr
BH1750 lightMeter;

// SIMULATION: Toggle for synthetic data (true = CSV, false = real sensors)
bool simulateSensors = true; // Set to false when hardware connected
String csvFile = "/sensor_data.csv"; // Path on SD card
int dataIndex = 0; // CSV row counter
String csvData[100][4]; // Buffer for 100 rows (timestamp, temp, current, irr)

// Twilio Config (Replace with trial creds)
const char* TWILIO_SID = "ACxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
const char* TWILIO_TOKEN = "your_xxxxxxxxxxxxxxxxxxxxxxxx";
const char* TWILIO_FROM = "+12345678901"; // Trial number
const char* TWILIO_TO = "+216xxxxxxxxx"; // Your phone
// MQTT Client
WiFiClient espClient;
PubSubClient mqttClient(espClient);
// Camera Pins (AI-Thinker ESP32-CAM)
#define PWDN_GPIO_NUM     32
#define RESET_GPIO_NUM    -1
#define XCLK_GPIO_NUM      0
#define SIOD_GPIO_NUM     26
#define SIOC_GPIO_NUM     27
#define Y9_GPIO_NUM       35
#define Y8_GPIO_NUM       34
#define Y7_GPIO_NUM       39
#define Y6_GPIO_NUM       36
#define Y5_GPIO_NUM       21
#define Y4_GPIO_NUM       19
#define Y3_GPIO_NUM       18
#define Y2_GPIO_NUM        5
#define VSYNC_GPIO_NUM    25
#define HREF_GPIO_NUM     23
#define PCLK_GPIO_NUM     22

// Edge Impulse Variables
EI_IMPULSE_ERROR ei_err; // For error handling

// Combined Defect Logic: Sensor + Image
String getDefect(float power, float pred, String imageDefect, float imageConf) {
  bool sensorCritical = (power < pred * 0.7);
  bool sensorWarning = (power < pred * 0.9);
  bool imageCritical = (imageDefect == "DEFECT" && imageConf > 0.7);

  if (sensorCritical || imageCritical) return "CRITICAL";
  if (sensorWarning || (imageDefect == "DEFECT" && imageConf > 0.5)) return "WARNING";
  return "OK";
}

float predictPower(float irr, float temp) {
  return 0.005 * irr - 0.02 * temp; // Simulated ±8% error
}

// Globals for image result
float imageConfidence = 0.0;
String imageDefect = "OK";

// SIMULATION: Load CSV data from SD on boot
void loadSensorData() {
  if (!SD.begin(5)) { // SD CS pin = 5 for ESP32-CAM
    Serial.println("SIMULATION: SD Card init failed!");
    return;
  }
  File file = SD.open(csvFile);
  if (!file) {
    Serial.println("SIMULATION: CSV file not found!");
    return;
  }
  dataIndex = 0;
  int row = 0;
  while (file.available() && row < 100) {
    String line = file.readStringUntil('\n');
    line.trim();
    if (line.length() > 0 && row > 0) { // Skip header
      int col = 0;
      int start = 0;
      for (int i = 0; i < line.length(); i++) {
        if (line.charAt(i) == ',' || i == line.length() - 1) {
          String val = line.substring(start, i + 1);
          val.trim();
          if (col < 4) csvData[row - 1][col] = val;
          col++;
          start = i + 1;
        }
      }
    }
    row++;
  }
  file.close();
  Serial.println("SIMULATION: Loaded 100 sensor rows from CSV.");
}

// SIMULATION: Get next sensor values from CSV
void getSimulatedSensors(float &temp, float &current, float &irr) {
  if (dataIndex >= 100) dataIndex = 0; // Loop data
  temp = csvData[dataIndex][1].toFloat(); // temperature_c
  current = csvData[dataIndex][2].toFloat(); // current_a
  irr = csvData[dataIndex][3].toFloat(); // irradiance_lux
  dataIndex++;
  Serial.printf("SIMULATION: Row %d - Temp: %.1f°C, Curr: %.2fA, Irr: %.0flx\n", dataIndex, temp, current, irr);
}

// Twilio SMS Function
void sendTwilioSMS(String message) {
  if (WiFi.status() == WL_CONNECTED) {
    WiFiClient client;
    client.setInsecure(); // Test mode (add CA cert for prod)
    HTTPClient http;
    String url = "https://api.twilio.com/2010-04-01/Accounts/" + String(TWILIO_SID) + "/Messages.json";
    http.begin(client, url);
    http.addHeader("Authorization", "Basic " + base64::encode(TWILIO_SID + ":" + TWILIO_TOKEN));
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    String payload = "To=" + String(TWILIO_TO) + "&From=" + String(TWILIO_FROM) + "&Body=" + message;
    int httpCode = http.POST(payload);
    if (httpCode == 201) {
      Serial.println("SMS sent: " + message);
    } else {
      Serial.println("SMS fail: " + String(httpCode));
    }
    http.end();
  }
}

// MQTT Callback for Grid Commands
void mqttCallback(char* topic, byte* payload, unsigned int length) {
  String message;
  for (int i = 0; i < length; i++) {
    message += (char)payload[i];
  }
  Serial.println("Grid cmd: " + message);
  if (message == "reduce_output") {
    digitalWrite(2, LOW); // Sim relay off
    mqttClient.publish(pub_topic, "{\"status\":\"reduced\"}");
  }
}

// Setup Camera (QVGA for EI inference speed)
void setupCamera() {
  camera_config_t config;
  config.ledc_channel = LEDC_CHANNEL_0;
  config.ledc_timer = LEDC_TIMER_0;
  config.pin_d0 = Y2_GPIO_NUM;
  config.pin_d1 = Y3_GPIO_NUM;
  config.pin_d2 = Y4_GPIO_NUM;
  config.pin_d3 = Y5_GPIO_NUM;
  config.pin_d4 = Y6_GPIO_NUM;
  config.pin_d5 = Y7_GPIO_NUM;
  config.pin_d6 = Y8_GPIO_NUM;
  config.pin_d7 = Y9_GPIO_NUM;
  config.pin_xclk = XCLK_GPIO_NUM;
  config.pin_pclk = PCLK_GPIO_NUM;
  config.pin_vsync = VSYNC_GPIO_NUM;
  config.pin_href = HREF_GPIO_NUM;
  config.pin_sscb_sda = SIOD_GPIO_NUM;
  config.pin_sscb_scl = SIOC_GPIO_NUM;
  config.pin_pwdn = PWDN_GPIO_NUM;
  config.pin_reset = RESET_GPIO_NUM;
  config.xclk_freq_hz = 20000000;
  config.pixel_format = PIXFORMAT_RGB565; // EI prefers RGB565
  config.frame_size = FRAMESIZE_QVGA; // 320x240, fast for inference
  config.jpeg_quality = 12;
  config.fb_count = 1;
  esp_err_t err = esp_camera_init(&config);
  if (err != ESP_OK) {
    Serial.printf("Camera init failed with error 0x%x", err);
    return;
  }
  Serial.println("Camera ready for EI!");
}

// Edge Impulse Run (Adapted from EI template)
void runEdgeImpulse(camera_fb_t * fb) {
  ei_impulse_result_t result = {0};
  signal_t signal;
  bool success = false;

  // Convert fb to EI signal (RGB565 -> features)
  ei_err = signal_from_camera(fb, &signal, &success, EI_CAMERA_RAW);
  if (!success) {
    Serial.println("ERR: Failed to locate image");
    return;
  }
  if (ei_err != EI_IMPULSE_OK) {
    Serial.printf("ERR: signal_from_camera failed (%d)\n", ei_err);
    return;
  }

  // Run classifier
  ei_err = run_classifier(&signal, &result, false);
  if (ei_err != EI_IMPULSE_OK) {
    Serial.printf("ERR: run_classifier failed (%d)\n", ei_err);
    return;
  }

  // Parse: Find max confidence label ("OK" or "DEFECT")
  float max_conf = 0.0;
  imageDefect = "UNKNOWN";
  for (size_t i = 0; i < EI_CLASSIFIER_LABEL_COUNT; i++) {
    ei_printf("Pred %s: %.5f\n", result.classification[i].label, result.classification[i].value);
    if (result.classification[i].value > max_conf) {
      max_conf = result.classification[i].value;
      imageDefect = String(result.classification[i].label);
    }
  }
  imageConfidence = max_conf;
  Serial.printf("EI Result: %s (%.1f%%)\n", imageDefect.c_str(), max_conf * 100);
  ei_free_signal(&signal);
}

void setup() {
  Serial.begin(115200);
  pinMode(2, OUTPUT); // Relay for grid sim
  // WiFi
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("WiFi connected!");
  // MQTT
  mqttClient.setServer(mqtt_server, mqtt_port);
  mqttClient.setCallback(mqttCallback);
  while (!mqttClient.connected()) {
    if (mqttClient.connect("SolarVisionP1")) {
      mqttClient.subscribe(sub_topic);
      Serial.println("MQTT connected!");
    } else {
      delay(5000);
    }
  }
  // Sensors (real or sim)
  if (simulateSensors) {
    loadSensorData(); // SIMULATION: Load CSV
  } else {
    Wire.begin(21, 22); // SDA=21, SCL=22
    if (!ina219.begin()) Serial.println("INA219 fail!");
    if (!lightMeter.begin()) Serial.println("BH1750 fail!");
    dht.begin();
  }
  setupCamera();
  // EI Init
  if (ei_impulse_init() != EI_IMPULSE_OK) {
    Serial.println("ERR: EI init failed!");
  }
  Serial.println("SolarVision AI Ready with Trained EI Model!");
}

void loop() {
  if (!mqttClient.connected()) {
    while (!mqttClient.connected()) {
      if (mqttClient.connect("SolarVisionP1")) {
        mqttClient.subscribe(sub_topic);
      } else {
        delay(5000);
      }
    }
  }
  mqttClient.loop();
  static unsigned long lastRun = 0;
  if (millis() - lastRun > 10000) { // 10s cycle
    lastRun = millis();
    collectAndProcessData();
  }
}

void collectAndProcessData() {
  float voltage, current, power, irr, temp, hum;
  float pred, eff;

  if (simulateSensors) {
    // SIMULATION: Use CSV data
    float sim_temp, sim_current, sim_irr;
    getSimulatedSensors(sim_temp, sim_current, sim_irr);
    temp = sim_temp;
    current = sim_current;
    irr = sim_irr;
    voltage = 12.0; // Fixed sim voltage
    power = voltage * current;
    hum = 50.0; // Fixed sim humidity
  } else {
    // Real sensors
    voltage = ina219.getBusVoltage_V();
    current = ina219.getCurrent_mA() / 1000.0;
    power = ina219.getPower_mW() / 1000.0; // W
    irr = lightMeter.readLightLevel(); // lux
    temp = dht.readTemperature(); // °C
    hum = dht.readHumidity(); // %
  }

  pred = predictPower(irr, temp);
  eff = (irr > 50) ? (power / pred) * 100 : 0; // %

  // Camera + EI Inference
  imageDefect = "OK";
  imageConfidence = 0.0;
  camera_fb_t * fb = esp_camera_fb_get();
  if (fb) {
    runEdgeImpulse(fb);  // Runs trained model
    esp_camera_fb_return(fb);
  } else {
    Serial.println("Camera capture failed");
  }

  // Combined Analysis
  String defect = getDefect(power, pred, imageDefect, imageConfidence);

  // Alert if Critical
  if (defect == "CRITICAL" || eff < 80) {
    String alert = "SolarVision ALERT: P1 - " + defect + " (Eff: " + String(eff, 1) + "%, Temp: " + String(temp, 1) + "°C, Image: " + imageDefect + " (" + String(imageConfidence * 100, 1) + "%))";
    sendTwilioSMS(alert);
  }

  // MQTT Publish JSON
  DynamicJsonDocument doc(1024);
  doc["panel_id"] = "p1";
  doc["timestamp"] = millis() / 1000;
  doc["voltage_v"] = voltage;
  doc["current_a"] = current;
  doc["power_w"] = power;
  doc["irradiance_lux"] = irr;
  doc["temp_c"] = temp;
  doc["humidity_pct"] = hum;
  doc["efficiency_pct"] = eff;
  doc["defect"] = defect;
  doc["predicted_loss_pct"] = ((power - pred) / pred) * 100;
  doc["image_defect"] = imageDefect;
  doc["image_confidence_pct"] = imageConfidence * 100;
  String jsonString;
  serializeJson(doc, jsonString);
  if (mqttClient.publish(pub_topic, jsonString.c_str())) {
    Serial.println("Data published: " + jsonString);
  } else {
    Serial.println("Publish failed!");
  }
}