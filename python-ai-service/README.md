# SOMA PV AI Service

AI-powered fault detection and predictive maintenance service for solar panel systems.

## Quick Start (localhost/pv setup)

### 1. Start AI Service
```bash
cd c:\xampp\htdocs\pv\python-ai-service
start_ai_service.bat
```
Or manually:
```bash
pip install -r requirements.txt
python -m uvicorn app.main:app --port 8001
```

### 2. Start MQTT Bridge (for Arduino/ESP32)
```bash
start_mqtt_bridge.bat
```

### 3. Test Connection
```bash
python test_connection.py
```

## URLs
- **Laravel API**: http://localhost/pv/pv-backend/public/api
- **AI Service**: http://localhost:8001
- **Health Check**: http://localhost:8001/health

---

## Features

- **Fault Detection**: ML-based anomaly detection for identifying panel faults
- **RUL Prediction**: Remaining Useful Life prediction for panels
- **Image Classification**: CNN-based visual defect detection (dust, damage, etc.)
- **Webhook Integration**: Automatic notification to Laravel backend

## Installation

```bash
# Create virtual environment
python -m venv venv

# Activate virtual environment
# Windows:
venv\Scripts\activate
# Linux/Mac:
source venv/bin/activate

# Install dependencies
pip install -r requirements.txt
```

## Running the Service

```bash
# Development mode
uvicorn app.main:app --reload --host 0.0.0.0 --port 8001

# Production mode
uvicorn app.main:app --host 0.0.0.0 --port 8001 --workers 4
```

## API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/health` | GET | Health check |
| `/status` | GET | Detailed service status |
| `/predict/fault` | POST | Fault prediction from sensor data |
| `/predict/rul` | POST | RUL prediction for a panel |
| `/predict/image` | POST | Image classification for visual defects |
| `/predict/batch` | POST | Batch prediction for multiple panels |

## Sensor Data Input Format

```json
{
  "panel_code": "PV-001",
  "irradiance": 850.5,
  "temperature": 45.2,
  "voltage": 235.5,
  "current": 10.2,
  "power_output": 125.5,
  "dust_level": 15.0,
  "humidity": 65.0
}
```

## Training Custom Models

Place trained models in the `trained_models/` directory:
- `fault_model.pkl` - Fault detection model (scikit-learn)
- `rul_model.pkl` - RUL prediction model
- `cnn_model.h5` - Image classification model (TensorFlow)

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `LARAVEL_WEBHOOK_URL` | `http://localhost:8000/api/webhooks/ai` | Laravel webhook endpoint |

## Integration with Laravel

The AI service automatically sends predictions to the Laravel backend via webhooks:

1. **Fault Detection**: POST to `/api/webhooks/ai/fault-prediction`
2. **RUL Prediction**: POST to `/api/webhooks/ai/rul-prediction`
3. **Image Classification**: POST to `/api/webhooks/ai/image-classification`

## Electronics/Sensor Integration

To send sensor data from IoT devices:

```python
import requests

sensor_data = {
    "panel_code": "PV-001",
    "irradiance": 850.5,
    "temperature": 45.2,
    "voltage": 235.5,
    "current": 10.2,
    "power_output": 125.5
}

# Send to Laravel (stores in database)
requests.post("http://localhost:8000/api/sensors/data", json=sensor_data)

# Or send directly to AI service for prediction
response = requests.post("http://localhost:8001/predict/fault", json=sensor_data)
print(response.json())
```
