// SolarVision AI - Updated TSYP13 Prototype Code
// ESP32-CAM: Sensors + Camera + MQTT + HTTP to Laravel + Edge Impulse Defect Detection
// Laravel backend for email alerts via SMTP
// Note: Set simulateSensors = true to use CSV data instead of real sensors (for testing without hardware).

#include <WiFi.h>
#include <HTTPClient.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>
#include <Wire.h>
#include <Adafruit_INA219.h>
#include <BH1750.h>
#include <DHT.h>
#include "esp_camera.h"
#include <SD.h>
#include <SPI.h>

// Uncomment if using Edge Impulse model
// #include <Solar-Defect-Classification_inferencing.h>

// === WIFI CONFIG ===
const char* ssid = "YOUR_WIFI_SSID";
const char* password = "YOUR_WIFI_PASS";

// === LARAVEL BACKEND CONFIG ===
const char* LARAVEL_URL = "http://YOUR_SERVER_IP:8000/api";
const char* LARAVEL_SENSOR_ENDPOINT = "/sensors/data";
const char* LARAVEL_ALERT_ENDPOINT = "/webhooks/ai/fault-prediction";

// === MQTT CONFIG (HiveMQ) ===
const char* mqtt_server = "broker.hivemq.com";
const int mqtt_port = 1883;
const char* pub_topic = "solar/farm/p1";
const char* sub_topic = "solar/control/p1";

// === SENSOR CONFIG ===
#define DHTPIN 4
#define DHTTYPE DHT22
DHT dht(DHTPIN, DHTTYPE);
Adafruit_INA219 ina219(0x40);
BH1750 lightMeter;

// === SIMULATION CONFIG ===
bool simulateSensors = true;
String csvFile = "/sensor_data.csv";
int dataIndex = 0;
String csvData[100][4];

// === PANEL CONFIG ===
const char* PANEL_ID = "PV-001";
const char* PANEL_CODE = "PV-001";

// === CLIENTS ===
WiFiClient espClient;
PubSubClient mqttClient(espClient);
HTTPClient http;

// === CAMERA PINS (AI-Thinker ESP32-CAM) ===
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

// === GLOBAL VARIABLES ===
float imageConfidence = 0.0;
String imageDefect = "OK";
unsigned long lastDataSend = 0;
const unsigned long DATA_INTERVAL = 10000; // 10 seconds

// === DEFECT DETECTION ===
String getDefect(float power, float pred, String imgDefect, float imgConf) {
  bool sensorCritical = (power < pred * 0.7);
  bool sensorWarning = (power < pred * 0.9);
  bool imageCritical = (imgDefect == "DEFECT" && imgConf > 0.7);

  if (sensorCritical || imageCritical) return "CRITICAL";
  if (sensorWarning || (imgDefect == "DEFECT" && imgConf > 0.5)) return "WARNING";
  return "OK";
}

float predictPower(float irr, float temp) {
  return 0.005 * irr - 0.02 * temp;
}

// === CSV DATA LOADING (SIMULATION) ===
void loadSensorData() {
  if (!SD.begin(5)) {
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
    if (line.length() > 0 && row > 0) {
      int col = 0;
      int start = 0;
      for (int i = 0; i <= line.length(); i++) {
        if (line.charAt(i) == ',' || i == line.length()) {
          String val = line.substring(start, i);
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
  Serial.println("SIMULATION: Loaded sensor data from CSV.");
}

void getSimulatedSensors(float &temp, float &current, float &irr) {
  if (dataIndex >= 100) dataIndex = 0;
  temp = csvData[dataIndex][1].toFloat();
  current = csvData[dataIndex][2].toFloat();
  irr = csvData[dataIndex][3].toFloat();
  dataIndex++;
  Serial.printf("SIMULATION: Row %d - Temp: %.1f°C, Curr: %.2fA, Irr: %.0flx\n", dataIndex, temp, current, irr);
}

// === HTTP: SEND DATA TO LARAVEL ===
bool sendToLaravel(String endpoint, String jsonPayload) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi not connected!");
    return false;
  }

  String url = String(LARAVEL_URL) + endpoint;
  http.begin(espClient, url);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Accept", "application/json");

  int httpCode = http.POST(jsonPayload);
  
  if (httpCode > 0) {
    String response = http.getString();
    Serial.printf("Laravel Response [%d]: %s\n", httpCode, response.c_str());
    http.end();
    return httpCode == 200 || httpCode == 201;
  } else {
    Serial.printf("Laravel Error: %s\n", http.errorToString(httpCode).c_str());
    http.end();
    return false;
  }
}

// === SEND SENSOR DATA TO LARAVEL ===
void sendSensorDataToLaravel(float voltage, float current, float power, float irr, float temp, float hum, float eff, String defect) {
  DynamicJsonDocument doc(1024);
  
  doc["panel_code"] = PANEL_CODE;
  doc["irradiance"] = irr / 100; // Convert lux to W/m² (approximate)
  doc["temperature"] = temp;
  doc["voltage"] = voltage * 20; // Scale to system voltage
  doc["current"] = current;
  doc["power_output"] = power / 1000; // Convert W to kW
  doc["humidity"] = hum;
  doc["dust_level"] = 0; // Add dust sensor if available
  
  String jsonString;
  serializeJson(doc, jsonString);
  
  if (sendToLaravel(LARAVEL_SENSOR_ENDPOINT, jsonString)) {
    Serial.println("Sensor data sent to Laravel successfully!");
  }
}

// === SEND ALERT TO LARAVEL (triggers email via SMTP) ===
void sendAlertToLaravel(String defect, float eff, float temp, String imgDefect, float imgConf) {
  DynamicJsonDocument doc(1024);
  
  doc["panel_code"] = PANEL_CODE;
  doc["fault_detected"] = true;
  doc["fault_type"] = defect == "CRITICAL" ? "critical_fault" : "performance_warning";
  doc["confidence"] = imgConf > 0 ? imgConf * 100 : 85.0;
  doc["severity"] = defect == "CRITICAL" ? "critical" : "high";
  
  JsonObject analysis = doc.createNestedObject("ai_analysis");
  analysis["root_cause"] = "ESP32 Edge AI detected: " + imgDefect;
  
  JsonArray factors = analysis.createNestedArray("contributing_factors");
  factors.add("Efficiency: " + String(eff, 1) + "%");
  factors.add("Temperature: " + String(temp, 1) + "°C");
  factors.add("Image confidence: " + String(imgConf * 100, 1) + "%");
  
  String jsonString;
  serializeJson(doc, jsonString);
  
  if (sendToLaravel(LARAVEL_ALERT_ENDPOINT, jsonString)) {
    Serial.println("Alert sent to Laravel - Email will be triggered!");
  }
}

// === MQTT CALLBACK ===
void mqttCallback(char* topic, byte* payload, unsigned int length) {
  String message;
  for (int i = 0; i < length; i++) {
    message += (char)payload[i];
  }
  Serial.println("MQTT Command: " + message);
  
  if (message == "reduce_output") {
    digitalWrite(2, LOW);
    mqttClient.publish(pub_topic, "{\"status\":\"reduced\"}");
  } else if (message == "status") {
    mqttClient.publish(pub_topic, "{\"status\":\"online\"}");
  }
}

// === CAMERA SETUP ===
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
  config.pixel_format = PIXFORMAT_RGB565;
  config.frame_size = FRAMESIZE_QVGA;
  config.jpeg_quality = 12;
  config.fb_count = 1;
  
  esp_err_t err = esp_camera_init(&config);
  if (err != ESP_OK) {
    Serial.printf("Camera init failed: 0x%x\n", err);
    return;
  }
  Serial.println("Camera ready!");
}

// === EDGE IMPULSE INFERENCE (if model available) ===
void runEdgeImpulse(camera_fb_t* fb) {
  // Placeholder for Edge Impulse inference
  // Uncomment and implement if using trained model
  /*
  ei_impulse_result_t result = {0};
  signal_t signal;
  
  // Convert frame buffer to signal
  // Run classifier
  // Parse results
  
  float max_conf = 0.0;
  imageDefect = "OK";
  for (size_t i = 0; i < EI_CLASSIFIER_LABEL_COUNT; i++) {
    if (result.classification[i].value > max_conf) {
      max_conf = result.classification[i].value;
      imageDefect = String(result.classification[i].label);
    }
  }
  imageConfidence = max_conf;
  */
  
  // Simulated inference for testing
  imageDefect = "OK";
  imageConfidence = 0.95;
}

// === SETUP ===
void setup() {
  Serial.begin(115200);
  pinMode(2, OUTPUT);
  
  // Connect to WiFi
  Serial.print("Connecting to WiFi");
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nWiFi connected!");
  Serial.print("IP: ");
  Serial.println(WiFi.localIP());
  
  // Setup MQTT
  mqttClient.setServer(mqtt_server, mqtt_port);
  mqttClient.setCallback(mqttCallback);
  
  // Connect to MQTT
  while (!mqttClient.connected()) {
    Serial.print("Connecting to MQTT...");
    if (mqttClient.connect("SolarVision_" + String(random(1000)))) {
      mqttClient.subscribe(sub_topic);
      Serial.println("connected!");
    } else {
      Serial.print("failed, rc=");
      Serial.println(mqttClient.state());
      delay(5000);
    }
  }
  
  // Setup sensors
  if (simulateSensors) {
    loadSensorData();
  } else {
    Wire.begin(21, 22);
    if (!ina219.begin()) Serial.println("INA219 init failed!");
    if (!lightMeter.begin()) Serial.println("BH1750 init failed!");
    dht.begin();
  }
  
  // Setup camera
  setupCamera();
  
  Serial.println("SolarVision AI Ready!");
  Serial.println("Backend: " + String(LARAVEL_URL));
}

// === MAIN LOOP ===
void loop() {
  // Maintain MQTT connection
  if (!mqttClient.connected()) {
    while (!mqttClient.connected()) {
      if (mqttClient.connect("SolarVision_" + String(random(1000)))) {
        mqttClient.subscribe(sub_topic);
      } else {
        delay(5000);
      }
    }
  }
  mqttClient.loop();
  
  // Collect and send data at interval
  if (millis() - lastDataSend >= DATA_INTERVAL) {
    lastDataSend = millis();
    collectAndProcessData();
  }
}

// === DATA COLLECTION AND PROCESSING ===
void collectAndProcessData() {
  float voltage, current, power, irr, temp, hum;
  float pred, eff;
  
  // Get sensor readings
  if (simulateSensors) {
    float sim_temp, sim_current, sim_irr;
    getSimulatedSensors(sim_temp, sim_current, sim_irr);
    temp = sim_temp;
    current = sim_current;
    irr = sim_irr;
    voltage = 12.0;
    power = voltage * current;
    hum = 50.0;
  } else {
    voltage = ina219.getBusVoltage_V();
    current = ina219.getCurrent_mA() / 1000.0;
    power = ina219.getPower_mW() / 1000.0;
    irr = lightMeter.readLightLevel();
    temp = dht.readTemperature();
    hum = dht.readHumidity();
  }
  
  // Calculate predictions
  pred = predictPower(irr, temp);
  eff = (irr > 50) ? (power / pred) * 100 : 0;
  
  // Camera + AI inference
  imageDefect = "OK";
  imageConfidence = 0.0;
  camera_fb_t* fb = esp_camera_fb_get();
  if (fb) {
    runEdgeImpulse(fb);
    esp_camera_fb_return(fb);
  }
  
  // Determine defect status
  String defect = getDefect(power, pred, imageDefect, imageConfidence);
  
  // Send to Laravel backend (triggers AI analysis + email alerts)
  sendSensorDataToLaravel(voltage, current, power, irr, temp, hum, eff, defect);
  
  // Send alert if critical (Laravel will send email via SMTP)
  if (defect == "CRITICAL" || eff < 80) {
    sendAlertToLaravel(defect, eff, temp, imageDefect, imageConfidence);
  }
  
  // Publish to MQTT for real-time dashboard
  DynamicJsonDocument doc(1024);
  doc["panel_id"] = PANEL_ID;
  doc["panel_code"] = PANEL_CODE;
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
    Serial.println("MQTT Published: " + jsonString);
  } else {
    Serial.println("MQTT Publish failed!");
  }
}
