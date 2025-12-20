"""
MQTT to Laravel Bridge
Receives sensor data from ESP32 devices via MQTT and forwards to Laravel backend

This script bridges the ESP32 IoT devices (from code.ino) with the Laravel backend.
It subscribes to MQTT topics and forwards data to Laravel API endpoints.

Usage:
    python mqtt_to_laravel.py
"""

import json
import time
import requests
import paho.mqtt.client as mqtt
from datetime import datetime

# Configuration
MQTT_BROKER = "broker.hivemq.com"  # Same as in code.ino
MQTT_PORT = 1883
MQTT_TOPIC = "solar/farm/#"  # Subscribe to all solar farm topics

LARAVEL_URL = "http://localhost/pv/pv-backend/public/api"
AI_SERVICE_URL = "http://localhost:8001"

# Panel code mapping (ESP32 panel_id to Laravel panel_code)
PANEL_MAP = {
    "p1": "PV-001",
    "p2": "PV-002",
    "p3": "PV-003",
    # Add more mappings as needed
}

def on_connect(client, userdata, flags, rc):
    """Callback when connected to MQTT broker"""
    if rc == 0:
        print(f"✅ Connected to MQTT broker: {MQTT_BROKER}")
        client.subscribe(MQTT_TOPIC)
        print(f"📡 Subscribed to: {MQTT_TOPIC}")
    else:
        print(f"❌ Connection failed with code: {rc}")

def on_message(client, userdata, msg):
    """Callback when message received from MQTT"""
    try:
        topic = msg.topic
        payload = json.loads(msg.payload.decode())
        
        print(f"\n📨 Received from {topic}:")
        print(json.dumps(payload, indent=2))
        
        # Process sensor data
        if "solar/farm" in topic:
            process_sensor_data(payload)
        
        # Process control commands
        if "solar/control" in topic:
            process_control_command(payload)
            
    except json.JSONDecodeError as e:
        print(f"❌ JSON decode error: {e}")
    except Exception as e:
        print(f"❌ Error processing message: {e}")

def process_sensor_data(data):
    """Process sensor data from ESP32 and forward to Laravel
    
    ESP32 Data Format (from code.ino):
    {
        "panel_id": "p1",
        "timestamp": 1234567890,
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
    
    Laravel API Format (SensorDataController expects):
    {
        "panel_code": "PV-001",
        "irradiance": 450.0,      # W/m²
        "temperature": 35.2,       # °C
        "voltage": 240.0,          # V (system voltage)
        "current": 2.3,            # A
        "power_output": 0.028,     # kW
        "humidity": 55.0,          # %
        "dust_level": 0.0,         # % (optional)
        "recorded_at": "2025-12-20T12:00:00"
    }
    """
    
    # Map ESP32 data format to Laravel API format
    panel_id = data.get("panel_id", "p1")
    panel_code = PANEL_MAP.get(panel_id, f"PV-{panel_id.upper()}")
    
    # Prepare data for Laravel sensor endpoint
    sensor_data = {
        "panel_code": panel_code,
        "irradiance": data.get("irradiance_lux", 0) / 100,  # Convert lux to W/m² (approximate)
        "temperature": data.get("temp_c", 0),
        "voltage": data.get("voltage_v", 0) * 20,  # Scale 12V to ~240V system
        "current": data.get("current_a", 0),
        "power_output": data.get("power_w", 0) / 1000,  # Convert W to kW
        "humidity": data.get("humidity_pct", 0),
        "dust_level": 0,  # ESP32 doesn't have dust sensor by default
        "recorded_at": datetime.now().isoformat()
    }
    
    # Send to Laravel
    try:
        response = requests.post(
            f"{LARAVEL_URL}/sensors/data",
            json=sensor_data,
            timeout=10
        )
        
        if response.status_code == 200:
            print(f"✅ Sent to Laravel: {panel_code}")
            result = response.json()
            
            # Check if AI prediction was triggered
            if result.get("ai_prediction_queued"):
                print(f"🤖 AI prediction queued for {panel_code}")
        else:
            print(f"⚠️ Laravel response: {response.status_code}")
            
    except requests.exceptions.RequestException as e:
        print(f"❌ Failed to send to Laravel: {e}")
    
    # Check for defects from ESP32 Edge Impulse
    defect = data.get("defect", "OK")
    image_defect = data.get("image_defect", "OK")
    
    if defect != "OK" or image_defect != "OK":
        # Create fault in Laravel
        create_fault(panel_code, data)

def create_fault(panel_code, data):
    """Create fault record in Laravel based on ESP32 detection"""
    
    defect = data.get("defect", "OK")
    image_defect = data.get("image_defect", "OK")
    image_confidence = data.get("image_confidence_pct", 0)
    efficiency = data.get("efficiency_pct", 100)
    
    # Determine fault type
    if defect == "CRITICAL":
        fault_type = "critical_fault"
        severity = "critical"
    elif image_defect == "DEFECT":
        fault_type = "visual_defect"
        severity = "high" if image_confidence > 70 else "medium"
    else:
        fault_type = "performance_warning"
        severity = "medium"
    
    fault_data = {
        "panel_code": panel_code,
        "fault_type": fault_type,
        "confidence": image_confidence if image_defect == "DEFECT" else 85.0,
        "severity": severity,
        "ai_analysis": {
            "root_cause": f"ESP32 Edge Impulse detected: {image_defect}",
            "contributing_factors": [
                f"Efficiency: {efficiency}%",
                f"Temperature: {data.get('temp_c', 0)}°C",
                f"Image confidence: {image_confidence}%"
            ]
        },
        "sensor_data_snapshot": data
    }
    
    try:
        # Send to AI service for additional analysis
        ai_response = requests.post(
            f"{AI_SERVICE_URL}/predict/fault",
            json={
                "panel_code": panel_code,
                "irradiance": data.get("irradiance_lux", 0) / 100,
                "temperature": data.get("temp_c", 0),
                "voltage": data.get("voltage_v", 0) * 20,
                "current": data.get("current_a", 0),
                "power_output": data.get("power_w", 0) / 1000,
            },
            timeout=30
        )
        
        if ai_response.status_code == 200:
            ai_result = ai_response.json()
            print(f"🤖 AI Analysis: {ai_result}")
            
            # Merge AI analysis
            if ai_result.get("ai_analysis"):
                fault_data["ai_analysis"] = ai_result["ai_analysis"]
            if ai_result.get("suggested_actions"):
                fault_data["suggested_actions"] = ai_result["suggested_actions"]
                
    except Exception as e:
        print(f"⚠️ AI service unavailable: {e}")
    
    # Create fault in Laravel
    try:
        response = requests.post(
            f"{LARAVEL_URL}/faults",
            json=fault_data,
            headers={"Authorization": "Bearer YOUR_API_TOKEN"},  # Add auth if needed
            timeout=10
        )
        
        if response.status_code in [200, 201]:
            print(f"🚨 Fault created in Laravel: {fault_type} for {panel_code}")
        else:
            print(f"⚠️ Failed to create fault: {response.status_code}")
            
    except requests.exceptions.RequestException as e:
        print(f"❌ Failed to create fault: {e}")

def process_control_command(data):
    """Process control commands (from grid or manual)"""
    print(f"🎮 Control command received: {data}")
    # Add control logic here if needed

def main():
    """Main function to run MQTT bridge"""
    print("="*60)
    print("MQTT TO LARAVEL BRIDGE")
    print("="*60)
    print(f"MQTT Broker: {MQTT_BROKER}:{MQTT_PORT}")
    print(f"Laravel API: {LARAVEL_URL}")
    print(f"AI Service: {AI_SERVICE_URL}")
    print("="*60)
    
    # Create MQTT client
    client = mqtt.Client()
    client.on_connect = on_connect
    client.on_message = on_message
    
    # Connect to broker
    try:
        client.connect(MQTT_BROKER, MQTT_PORT, 60)
        print("🔄 Connecting to MQTT broker...")
        
        # Start loop
        client.loop_forever()
        
    except KeyboardInterrupt:
        print("\n👋 Shutting down...")
        client.disconnect()
    except Exception as e:
        print(f"❌ Error: {e}")

if __name__ == "__main__":
    main()
