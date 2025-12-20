# SOMA PV Monitoring System

AI-Powered Solar Panel Monitoring and Fault Detection Platform

---

## Table of Contents

1. [Overview](#overview)
2. [Features](#features)
3. [System Architecture](#system-architecture)
4. [Project Structure](#project-structure)
5. [Technology Stack](#technology-stack)
6. [Installation](#installation)
7. [Configuration](#configuration)
8. [API Reference](#api-reference)
9. [AI Models](#ai-models)
10. [Electronics Integration](#electronics-integration)
11. [Database Schema](#database-schema)
12. [Deployment](#deployment)
13. [Troubleshooting](#troubleshooting)

---

## Overview

SOMA PV is a comprehensive solar panel monitoring system that combines IoT sensors, machine learning, and real-time analytics to optimize solar farm operations and predict equipment failures before they occur.

The system consists of four main components:

- Laravel Backend API for data management and business logic
- Vue.js Frontend for user interface and dashboards
- Python AI Service for machine learning predictions
- ESP32 Hardware for sensor data collection

---

## Features

### Core Features

- Real-time monitoring of power output, efficiency, and environmental conditions
- AI-powered fault detection with predictive maintenance
- Automated email alerts for critical issues
- Maintenance task scheduling and tracking
- Historical data analysis and report export (PDF/Excel)
- Multi-language support (English and French)
- Dark and light theme support
- Role-based access control (Admin, Technician, Viewer)

### AI Capabilities

- Fault detection using RandomForest and rule-based systems
- Remaining Useful Life (RUL) prediction
- XGBoost multi-class fault classification
- CNN-based visual defect detection from thermal images
- AI chat assistant for system queries

### Integrations

- Weather API integration with solar efficiency forecasting
- SMTP email notifications
- MQTT communication with ESP32 sensors
- DeepSeek AI chat integration

---

## System Architecture

```
                    ESP32/Arduino Sensors
                (INA219, BH1750, DHT22, ESP32-CAM)
                            |
                          MQTT
                            |
                    MQTT Bridge (Python)
                            |
                          HTTP
                            |
                    Laravel Backend (PHP)
                    REST API + MySQL Database
                    /                   \
                   /                     \
        Vue.js Frontend           Python AI Service
        (Dashboard, Charts)       (Fault Detection, RUL, CNN)
```

### Data Flow

1. ESP32 sensors collect data every 10 seconds
2. Data is published to MQTT broker (HiveMQ)
3. MQTT bridge forwards data to Laravel API
4. Laravel stores data and triggers AI predictions
5. AI service analyzes data and sends results via webhook
6. Frontend displays real-time updates
7. Alerts are sent via email for critical issues

---

## Project Structure

```
pv/
|-- pv-backend/                 Laravel 11 REST API
|   |-- app/
|   |   |-- Http/Controllers/Api/   API controllers
|   |   |-- Models/                 Eloquent models
|   |   |-- Services/               Business logic services
|   |   |-- Events/                 Broadcast events
|   |-- database/migrations/        Database schema
|   |-- routes/api.php              API routes
|
|-- pv-main/                    Vue 3 Frontend
|   |-- src/
|   |   |-- views/                  Page components
|   |   |-- components/             Reusable components
|   |   |-- stores/                 Pinia state management
|   |   |-- services/api.js         API client
|   |   |-- i18n/                   Translations
|   |-- dist/                       Production build
|
|-- python-ai-service/          FastAPI AI Service
|   |-- app/
|   |   |-- models/                 ML model classes
|   |   |-- routes/                 API endpoints
|   |   |-- main.py                 FastAPI application
|   |-- esp32_integration/          MQTT bridge
|   |-- trained_models/             Exported model files
|   |-- training/                   Model training scripts
|
|-- AI models/                  Training Data and ESP32 Code
|   |-- code.ino                    ESP32 firmware
|   |-- *.ipynb                     Jupyter notebooks
|   |-- *.csv                       Training datasets
```

---

## Technology Stack

### Backend (Laravel 11)

| Component | Technology | Version |
|-----------|------------|---------|
| Framework | Laravel | 11.x |
| Language | PHP | 8.2+ |
| Database | MySQL | 8.0 |
| Cache | File/Redis | - |
| Auth | Laravel Sanctum | 4.x |
| HTTP Client | Guzzle | 7.x |

### Frontend (Vue 3)

| Component | Technology | Version |
|-----------|------------|---------|
| Framework | Vue.js | 3.4+ |
| Build Tool | Vite | 5.x |
| State | Pinia | 2.x |
| Router | Vue Router | 4.x |
| Styling | TailwindCSS | 3.x |
| Charts | Chart.js | 4.x |
| Icons | Lucide Vue | - |
| i18n | Vue I18n | 9.x |

### Python AI Service

| Component | Technology | Version |
|-----------|------------|---------|
| Framework | FastAPI | 0.109+ |
| Server | Uvicorn | 0.27+ |
| ML | scikit-learn, XGBoost | - |
| Deep Learning | TensorFlow | 2.x |
| HTTP | httpx | 0.26+ |

### Electronics (ESP32)

| Component | Model | Purpose |
|-----------|-------|---------|
| MCU | ESP32-CAM | Main controller with camera |
| Power Sensor | INA219 | Voltage, current, power |
| Light Sensor | BH1750 | Solar irradiance |
| Temp/Humidity | DHT22 | Environmental data |
| Communication | MQTT | HiveMQ broker |

---

## Installation

### Prerequisites

- XAMPP 8.2+ (Apache, MySQL, PHP)
- Node.js 18+
- Python 3.9+
- Composer

### Step 1: Backend Setup

```bash
cd pv-backend

# Install dependencies
composer install

# Copy environment file
copy .env.example .env

# Generate application key
php artisan key:generate

# Run migrations and seed database
php artisan migrate:fresh --seed

# Clear cache
php artisan cache:clear
php artisan config:clear
```

### Step 2: Frontend Setup

```bash
cd pv-main

# Install dependencies
npm install

# Build for production
npm run build

# Or run development server
npm run dev
```

### Step 3: Python AI Service Setup

```bash
cd python-ai-service

# Create virtual environment (optional)
python -m venv venv
venv\Scripts\activate  # Windows
source venv/bin/activate  # Linux/Mac

# Install dependencies
pip install -r requirements.txt

# Start service
python -m uvicorn app.main:app --port 8001
```

### Step 4: Access the Application

| Service | URL |
|---------|-----|
| Frontend | http://localhost/pv/pv-main/dist/en |
| Backend API | http://localhost/pv/pv-backend/public/api |
| API Health | http://localhost/pv/pv-backend/public/api/health |
| AI Service | http://localhost:8001 |
| AI Docs | http://localhost:8001/docs |

### Demo Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@soma.com | password |
| Technician | tech@soma.com | password |
| Viewer | viewer@soma.com | password |

---

## Configuration

### Backend Environment (pv-backend/.env)

```env
APP_NAME="SOMA PV"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/pv/pv-backend/public

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pv_monitoring
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

AI_SERVICE_URL=http://localhost:8001
AI_SERVICE_TIMEOUT=5
```

### Frontend Environment (pv-main/.env)

```env
VITE_API_URL=http://localhost/pv/pv-backend/public/api
VITE_APP_TITLE=SOMA PV Monitoring
```

### Python AI Service Environment (python-ai-service/.env)

```env
LARAVEL_WEBHOOK_URL=http://localhost/pv/pv-backend/public/api/webhooks/ai
LARAVEL_API_URL=http://localhost/pv/pv-backend/public/api
AI_SERVICE_PORT=8001
```

### Admin Settings (via UI)

Access Settings > Integrations to configure:

- SMTP: Host, port, username, password, from address
- AI Chat: DeepSeek API key, model selection
- Weather: WeatherAPI.com API key, default location

---

## API Reference

### Base URL

```
http://localhost/pv/pv-backend/public/api
```

### Authentication

All protected endpoints require Bearer token authentication:

```
Authorization: Bearer {token}
Content-Type: application/json
```

### Public Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /health | System health check |
| GET | /health/ping | Simple ping |
| POST | /login | User authentication |
| POST | /sensors/data | Receive sensor data from IoT |
| POST | /sensors/batch | Batch sensor data |

### Protected Endpoints

#### Dashboard

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /dashboard | Dashboard KPIs and charts |

#### Panels

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /panels | List all panels |
| GET | /panels/grid | Panel grid visualization |
| GET | /panels/{id} | Panel details |
| POST | /panels | Create panel |
| PUT | /panels/{id} | Update panel |
| DELETE | /panels/{id} | Delete panel |
| GET | /panels/{id}/readings | Sensor history |

#### Faults

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /faults | List faults with filters |
| GET | /faults/{id} | Fault details with AI analysis |
| POST | /faults | Create fault |
| POST | /faults/{id}/resolve | Resolve fault |
| PATCH | /faults/{id}/status | Update status |

#### Maintenance

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /maintenance | List tasks |
| GET | /maintenance/calendar | Calendar view |
| POST | /maintenance | Create task |
| PUT | /maintenance/{id} | Update task |
| POST | /maintenance/{id}/start | Start task |
| POST | /maintenance/{id}/complete | Complete task |
| POST | /maintenance/{id}/cancel | Cancel task |

#### Analytics

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /analytics | Analytics data |
| GET | /analytics/panels | Panel analysis |

#### Alerts

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /alerts | List alerts |
| GET | /alerts/unread-count | Unread count |
| POST | /alerts/{id}/read | Mark as read |
| POST | /alerts/read-all | Mark all read |

#### AI Chat

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /chat/message | Send message to AI |
| GET | /chat/history | Chat history |
| DELETE | /chat/history | Clear history |
| GET | /chat/suggestions | Get suggestions |

#### Weather

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /weather/current | Current weather |
| GET | /weather/forecast | Weather forecast |
| GET | /weather/solar-forecast | Solar forecast |

#### Settings (Admin only)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /settings | All settings |
| GET/PUT | /settings/smtp | SMTP configuration |
| GET/PUT | /settings/ai | AI configuration |
| GET/PUT | /settings/weather | Weather configuration |

#### Users (Admin only)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /users | List users |
| GET | /users/technicians | List technicians |
| POST | /users | Create user |
| PUT | /users/{id} | Update user |
| DELETE | /users/{id} | Delete user |

### AI Webhooks (Python to Laravel)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /webhooks/ai/fault-prediction | Receive fault prediction |
| POST | /webhooks/ai/rul-prediction | Receive RUL prediction |
| POST | /webhooks/ai/image-classification | Receive image result |
| POST | /webhooks/ai/batch-prediction | Batch predictions |

### Error Responses

| Code | Description |
|------|-------------|
| 400 | Bad Request - Validation failed |
| 401 | Unauthorized - Invalid or missing token |
| 403 | Forbidden - Insufficient permissions |
| 404 | Not Found - Resource does not exist |
| 422 | Unprocessable Entity - Invalid data |
| 500 | Internal Server Error |

---

## AI Models

### Available Models

| Model | Class | Algorithm | Purpose |
|-------|-------|-----------|---------|
| Fault Detector | FaultDetector | RandomForest + Rules | Real-time fault detection |
| RUL Predictor | RULPredictor | Regression | Remaining useful life |
| XGBoost Predictor | XGBoostFaultPredictor | XGBoost | Days to fault prediction |
| Image Classifier | ImageClassifier | CNN (TensorFlow) | Visual defect detection |
| PV Monitor | PVMonitoringSystem | RandomForest | Power loss prediction |

### AI Service Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /health | Service health check |
| GET | /status | Detailed status with model info |
| POST | /predict/fault | Fault prediction from sensor data |
| POST | /predict/rul | Remaining useful life prediction |
| POST | /predict/image | Image-based defect classification |
| POST | /predict/batch | Batch predictions |

### Fault Prediction Request

```json
POST /predict/fault
{
  "panel_code": "PV-001",
  "irradiance": 850.5,
  "temperature": 45.2,
  "voltage": 235.5,
  "current": 10.2,
  "power_output": 2.4,
  "dust_level": 15.0,
  "humidity": 65.0
}
```

### Fault Prediction Response

```json
{
  "panel_code": "PV-001",
  "fault_detected": true,
  "fault_type": "overheating",
  "confidence": 0.87,
  "severity": "high",
  "ai_analysis": {
    "root_cause": "Excessive temperature detected",
    "contributing_factors": ["High ambient temperature", "Dust accumulation"]
  },
  "suggested_actions": [
    {"action": "Clean panel surface", "priority": "high"},
    {"action": "Check cooling system", "priority": "medium"}
  ]
}
```

### Model Performance

| Model | Metric | Value |
|-------|--------|-------|
| XGBoost Fault Predictor | R2 Score | 0.90 |
| XGBoost Fault Predictor | MAE | 1.0 days |
| Power Loss Predictor | MAE | 0.14 kW |
| Edge Impulse CNN | Accuracy | 85% |

---

## Electronics Integration

### Hardware Components

| Component | Model | Purpose | Interface |
|-----------|-------|---------|-----------|
| MCU | ESP32-CAM | Main controller + Camera | WiFi, I2C |
| Power Sensor | INA219 | Voltage, Current, Power | I2C (0x40) |
| Light Sensor | BH1750 | Solar irradiance | I2C |
| Temp/Humidity | DHT22 | Environmental data | GPIO 4 |
| SD Card | - | Data logging | SPI |

### Wiring

```
ESP32-CAM Pinout:
- GPIO 21 (SDA) -> I2C Bus
- GPIO 22 (SCL) -> I2C Bus
- GPIO 4 -> DHT22 Data
- GPIO 5 -> SD Card CS
- GPIO 2 -> Relay/LED
- 3.3V -> Sensors VCC
- GND -> Sensors GND

I2C Bus:
- INA219 (0x40) - Power Monitor
- BH1750 (0x23) - Light Sensor
```

### ESP32 Configuration (code.ino)

```cpp
const char* ssid = "YOUR_WIFI_SSID";
const char* password = "YOUR_WIFI_PASS";
const char* mqtt_server = "broker.hivemq.com";
const int mqtt_port = 1883;
const char* pub_topic = "solar/farm/p1";
const char* sub_topic = "solar/control/p1";
bool simulateSensors = true;  // Set false for real hardware
```

### MQTT Data Format

Published to solar/farm/{panel_id}:

```json
{
  "panel_id": "p1",
  "timestamp": 1703001234,
  "voltage_v": 12.5,
  "current_a": 2.3,
  "power_w": 28.75,
  "irradiance_lux": 45000,
  "temp_c": 35.2,
  "humidity_pct": 55,
  "efficiency_pct": 92.5,
  "defect": "OK",
  "predicted_loss_pct": -2.5,
  "image_defect": "OK",
  "image_confidence_pct": 95.5
}
```

### Starting MQTT Bridge

```bash
cd python-ai-service
python esp32_integration/mqtt_to_laravel.py
```

---

## Database Schema

### Main Tables

#### users

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | varchar | User name |
| email | varchar | Email (unique) |
| password | varchar | Hashed password |
| role | enum | admin, technician, viewer |
| avatar | varchar | Profile image |
| phone | varchar | Phone number |

#### panels

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| panel_code | varchar | Unique code (PV-001) |
| location | varchar | Physical location |
| zone | varchar | Zone identifier |
| row | int | Grid row |
| column | int | Grid column |
| status | enum | active, warning, critical, offline |
| temperature | decimal | Current temp (C) |
| voltage | decimal | Current voltage (V) |
| current | decimal | Current amperage (A) |
| power_output | decimal | Power output (kW) |
| efficiency | decimal | Efficiency (%) |

#### sensor_readings

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| panel_id | bigint | Foreign key |
| irradiance | decimal | W/m2 |
| temperature | decimal | C |
| voltage | decimal | V |
| current | decimal | A |
| power_output | decimal | kW |
| dust_level | decimal | % |
| humidity | decimal | % |
| recorded_at | timestamp | Reading time |

#### faults

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| panel_id | bigint | Foreign key |
| fault_type | varchar | Type of fault |
| severity | enum | low, medium, high, critical |
| status | enum | detected, investigating, resolved |
| confidence | decimal | AI confidence (%) |
| ai_analysis | json | AI analysis data |
| suggested_actions | json | Recommended actions |
| detected_at | timestamp | Detection time |
| resolved_at | timestamp | Resolution time |

#### maintenance_tasks

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| panel_id | bigint | Foreign key (nullable) |
| title | varchar | Task title |
| description | text | Details |
| type | enum | preventive, corrective, inspection |
| priority | enum | low, medium, high, urgent |
| status | enum | scheduled, in_progress, completed, cancelled |
| assigned_to | bigint | User FK |
| scheduled_date | date | Scheduled date |
| completed_at | timestamp | Completion time |

---

## Deployment

### Production Build

#### Frontend

```bash
cd pv-main
npm run build
# Output in dist/ folder
```

#### Backend

```bash
cd pv-backend
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Apache Configuration

Add to .htaccess in pv-main/dist/:

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /pv/pv-main/dist/
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /pv/pv-main/dist/index.html [L]
</IfModule>
```

### Running Services

| Service | Command | Port |
|---------|---------|------|
| Apache (XAMPP) | Start from XAMPP Control Panel | 80 |
| MySQL | Start from XAMPP Control Panel | 3306 |
| AI Service | start_ai_service.bat | 8001 |
| MQTT Bridge | start_mqtt_bridge.bat | - |

### Health Check

```bash
cd python-ai-service
python test_connection.py
```

Expected output:

```
Laravel API: healthy
AI Service: healthy
Sensor Endpoint: working
AI Prediction: working
All systems operational
```

---

## Troubleshooting

### Common Issues

#### CORS Errors

Check Laravel CORS configuration in config/cors.php. Ensure the frontend URL is allowed.

#### 401 Unauthorized

Token expired. Re-login to get a new token.

#### AI Service Timeout

Increase timeout in app/Services/AIService.php or check if the Python service is running.

#### Database Connection

Verify MySQL is running and credentials in .env are correct.

#### Frontend Not Loading

Ensure npm run build was executed and dist folder exists.

### Log Locations

| Service | Log Path |
|---------|----------|
| Laravel | pv-backend/storage/logs/laravel.log |
| Apache | xampp/apache/logs/error.log |
| AI Service | Console output |

### Quick Commands

```bash
# Clear Laravel cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Rebuild frontend
npm run build

# Restart AI service
python -m uvicorn app.main:app --port 8001 --reload

# Test API health
curl http://localhost/pv/pv-backend/public/api/health
```

---

## License

This project is licensed under the MIT License.

---

## Version

SOMA PV Monitoring System v1.0.0

Last updated: December 2025
