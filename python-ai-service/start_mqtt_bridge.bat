@echo off
echo ============================================
echo SOMA PV - MQTT to Laravel Bridge
echo ============================================
echo.
echo This bridges ESP32/Arduino sensor data to Laravel
echo.

REM Check if Python is installed
python --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Python is not installed or not in PATH
    pause
    exit /b 1
)

echo Installing dependencies...
pip install paho-mqtt requests -q

echo.
echo Starting MQTT Bridge...
echo.
echo MQTT Broker: broker.hivemq.com:1883
echo Laravel API: http://localhost/pv/pv-backend/public/api
echo.
echo Press Ctrl+C to stop
echo ============================================

cd esp32_integration
python mqtt_to_laravel.py
