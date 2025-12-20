"""
Prediction endpoints for fault detection, RUL prediction, and image classification
"""

from fastapi import APIRouter, HTTPException, Request, UploadFile, File, Form
from pydantic import BaseModel, Field
from typing import Optional, List
import numpy as np
import httpx
import os

router = APIRouter()

# Laravel webhook URL - Updated for localhost/pv setup
LARAVEL_WEBHOOK_URL = os.getenv("LARAVEL_WEBHOOK_URL", "http://localhost/pv/pv-backend/public/api/webhooks/ai")

class SensorData(BaseModel):
    """Input schema for sensor data"""
    panel_code: str = Field(..., description="Panel identifier (e.g., PV-001)")
    irradiance: Optional[float] = Field(None, description="Solar irradiance (W/m²)")
    temperature: Optional[float] = Field(None, description="Panel temperature (°C)")
    voltage: Optional[float] = Field(None, description="Voltage (V)")
    current: Optional[float] = Field(None, description="Current (A)")
    power_output: Optional[float] = Field(None, description="Power output (kW)")
    dust_level: Optional[float] = Field(None, description="Dust accumulation level (%)")
    humidity: Optional[float] = Field(None, description="Humidity (%)")
    bus_voltage_pu: Optional[float] = None
    load_demand_mw: Optional[float] = None
    vocr: Optional[float] = None
    power_loss_kw: Optional[float] = None

class FaultPredictionResponse(BaseModel):
    """Response schema for fault prediction"""
    panel_code: str
    fault_detected: bool
    fault_type: Optional[str] = None
    confidence: float
    severity: Optional[str] = None
    ai_analysis: Optional[dict] = None
    suggested_actions: Optional[List[dict]] = None

class RULInput(BaseModel):
    """Input schema for RUL prediction"""
    panel_code: str
    installation_date: Optional[str] = None
    current_efficiency: Optional[float] = None
    readings: Optional[List[dict]] = None

class RULPredictionResponse(BaseModel):
    """Response schema for RUL prediction"""
    panel_code: str
    rul_days: int
    confidence: float
    degradation_rate: Optional[float] = None

@router.post("/fault", response_model=FaultPredictionResponse)
async def predict_fault(data: SensorData, request: Request):
    """
    Predict faults based on sensor data
    
    This endpoint receives sensor readings and returns fault predictions
    using multiple trained ML models:
    - FaultDetector (rule-based + sklearn)
    - PVMonitoringSystem (RandomForest ensemble)
    """
    fault_detector = request.app.state.fault_detector
    pv_monitoring = getattr(request.app.state, 'pv_monitoring', None)
    
    if not fault_detector:
        raise HTTPException(status_code=503, detail="Fault detection model not loaded")
    
    try:
        # Prepare features for prediction
        features = np.array([[
            data.irradiance or 0,
            data.temperature or 0,
            data.voltage or 0,
            data.current or 0,
            data.power_output or 0,
            data.dust_level or 0,
            data.humidity or 0,
            data.bus_voltage_pu or 0,
            data.load_demand_mw or 0,
            data.vocr or 0,
            data.power_loss_kw or 0
        ]])
        
        # Get prediction from fault detector
        result = fault_detector.predict(features, data)
        
        # Also get prediction from PV monitoring system if available
        if pv_monitoring:
            pv_result = pv_monitoring.analyze({
                'irradiance': data.irradiance,
                'temperature': data.temperature,
                'bus_voltage_pu': data.bus_voltage_pu,
                'vocr': data.vocr,
            })
            # Combine results - use higher confidence prediction
            if pv_result.get('confidence', 0) > result.get('confidence', 0):
                result = pv_result
        
        response = FaultPredictionResponse(
            panel_code=data.panel_code,
            fault_detected=result['fault_detected'],
            fault_type=result.get('fault_type'),
            confidence=result['confidence'],
            severity=result.get('severity'),
            ai_analysis=result.get('ai_analysis'),
            suggested_actions=result.get('suggested_actions')
        )
        
        # Send to Laravel webhook if fault detected
        if result['fault_detected']:
            await send_to_laravel_webhook("/fault-prediction", response.model_dump())
        
        return response
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Prediction failed: {str(e)}")

@router.post("/rul", response_model=RULPredictionResponse)
async def predict_rul(data: RULInput, request: Request):
    """
    Predict Remaining Useful Life (RUL) for a panel
    """
    rul_predictor = request.app.state.rul_predictor
    
    if not rul_predictor:
        raise HTTPException(status_code=503, detail="RUL prediction model not loaded")
    
    try:
        result = rul_predictor.predict(data)
        
        response = RULPredictionResponse(
            panel_code=data.panel_code,
            rul_days=result['rul_days'],
            confidence=result['confidence'],
            degradation_rate=result.get('degradation_rate')
        )
        
        # Send to Laravel webhook
        await send_to_laravel_webhook("/rul-prediction", response.model_dump())
        
        return response
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"RUL prediction failed: {str(e)}")

@router.post("/image")
async def classify_image(
    panel_code: str = Form(...),
    image: UploadFile = File(...)
):
    """
    Classify panel image for visual defects (dust, damage, etc.)
    """
    try:
        # Read image
        contents = await image.read()
        
        # TODO: Load and use CNN model for classification
        # For now, return a placeholder response
        classification = "clean"
        confidence = 95.0
        
        response = {
            "panel_code": panel_code,
            "classification": classification,
            "confidence": confidence,
            "image_filename": image.filename
        }
        
        # Send to Laravel webhook
        await send_to_laravel_webhook("/image-classification", response)
        
        return response
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Image classification failed: {str(e)}")

@router.post("/batch")
async def batch_predict(readings: List[SensorData], request: Request):
    """
    Batch prediction for multiple panels
    """
    fault_detector = request.app.state.fault_detector
    
    if not fault_detector:
        raise HTTPException(status_code=503, detail="Fault detection model not loaded")
    
    results = []
    for data in readings:
        try:
            features = np.array([[
                data.irradiance or 0,
                data.temperature or 0,
                data.voltage or 0,
                data.current or 0,
                data.power_output or 0,
                data.dust_level or 0,
                data.humidity or 0,
                data.bus_voltage_pu or 0,
                data.load_demand_mw or 0,
                data.vocr or 0,
                data.power_loss_kw or 0
            ]])
            
            result = fault_detector.predict(features, data)
            results.append({
                "panel_code": data.panel_code,
                "fault_detected": result['fault_detected'],
                "fault_type": result.get('fault_type'),
                "confidence": result['confidence'],
                "severity": result.get('severity')
            })
        except Exception as e:
            results.append({
                "panel_code": data.panel_code,
                "error": str(e)
            })
    
    # Send batch results to Laravel
    await send_to_laravel_webhook("/batch-prediction", {"predictions": results})
    
    return {"predictions": results}

async def send_to_laravel_webhook(endpoint: str, data: dict):
    """Send prediction results to Laravel webhook"""
    try:
        async with httpx.AsyncClient() as client:
            response = await client.post(
                f"{LARAVEL_WEBHOOK_URL}{endpoint}",
                json=data,
                timeout=10.0
            )
            return response.json()
    except Exception as e:
        print(f"Failed to send to Laravel webhook: {e}")
        return None
