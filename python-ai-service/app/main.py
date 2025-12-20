"""
SOMA PV System - AI Fault Detection Service
FastAPI application for ML-based fault detection in solar panels
"""

from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from contextlib import asynccontextmanager
import uvicorn

from app.routes import predict, health, thermal
from app.models.fault_detector import FaultDetector
from app.models.rul_predictor import RULPredictor
from app.models.xgboost_fault_predictor import XGBoostFaultPredictor
from app.models.pv_monitoring_system import PVMonitoringSystem
from app.models.image_classifier import ImageClassifier
from app.models.thermal_detector import get_thermal_detector

# Global model instances
fault_detector = None
rul_predictor = None
xgboost_predictor = None
pv_monitoring = None
image_classifier = None
thermal_detector = None

@asynccontextmanager
async def lifespan(app: FastAPI):
    """Load ML models on startup"""
    global fault_detector, rul_predictor, xgboost_predictor, pv_monitoring, image_classifier, thermal_detector
    
    print("Loading AI models...")
    
    # Load all models
    fault_detector = FaultDetector()
    rul_predictor = RULPredictor()
    xgboost_predictor = XGBoostFaultPredictor()
    pv_monitoring = PVMonitoringSystem()
    image_classifier = ImageClassifier()
    
    # Load thermal detector (YOLOv9)
    print("Loading thermal detector (YOLOv9)...")
    thermal_detector = get_thermal_detector()
    if thermal_detector.is_available():
        print("Thermal detector loaded successfully!")
    else:
        print("Warning: Thermal detector model not found. Thermal detection will be unavailable.")
    
    # Store in app state for access in routes
    app.state.fault_detector = fault_detector
    app.state.rul_predictor = rul_predictor
    app.state.xgboost_predictor = xgboost_predictor
    app.state.pv_monitoring = pv_monitoring
    app.state.image_classifier = image_classifier
    app.state.thermal_detector = thermal_detector
    
    print("AI models loaded successfully!")
    yield
    
    print("Shutting down AI service...")

app = FastAPI(
    title="SOMA PV AI Service",
    description="AI-powered fault detection and predictive maintenance for solar panel systems",
    version="1.0.0",
    lifespan=lifespan
)

# CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Include routers
app.include_router(health.router, tags=["Health"])
app.include_router(predict.router, prefix="/predict", tags=["Predictions"])
app.include_router(thermal.router, tags=["Thermal Detection"])

@app.get("/")
async def root():
    return {
        "service": "SOMA PV AI Service",
        "version": "1.0.0",
        "status": "running",
        "endpoints": {
            "health": "/health",
            "status": "/status",
            "fault_prediction": "/predict/fault",
            "rul_prediction": "/predict/rul",
            "image_classification": "/predict/image",
            "thermal_detection": "/thermal/detect",
            "thermal_annotated": "/thermal/detect/annotated",
            "thermal_batch": "/thermal/detect/batch",
            "thermal_status": "/thermal/status"
        }
    }

if __name__ == "__main__":
    uvicorn.run("app.main:app", host="0.0.0.0", port=8000, reload=True)
