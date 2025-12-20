@echo off
echo ============================================
echo SOMA PV - AI Service Startup
echo ============================================
echo.

REM Check if Python is installed
python --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Python is not installed or not in PATH
    pause
    exit /b 1
)

REM Install dependencies if needed
echo Installing dependencies...
pip install -r requirements.txt -q

echo.
echo Starting AI Service on port 8001...
echo.
echo Laravel API: http://localhost/pv/pv-backend/public/api
echo AI Service:  http://localhost:8001
echo.
echo Press Ctrl+C to stop the service
echo ============================================

python -m uvicorn app.main:app --host 0.0.0.0 --port 8001 --reload
