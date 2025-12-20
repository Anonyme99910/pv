"""
Health check endpoints for the AI service
"""

from fastapi import APIRouter, Request
from datetime import datetime

router = APIRouter()

@router.get("/health")
async def health_check():
    """Basic health check endpoint"""
    return {
        "status": "healthy",
        "timestamp": datetime.utcnow().isoformat()
    }

@router.get("/status")
async def status(request: Request):
    """Detailed status including model information"""
    fault_detector = getattr(request.app.state, 'fault_detector', None)
    rul_predictor = getattr(request.app.state, 'rul_predictor', None)
    
    return {
        "status": "running",
        "timestamp": datetime.utcnow().isoformat(),
        "models": {
            "fault_detector": {
                "loaded": fault_detector is not None,
                "version": getattr(fault_detector, 'version', '1.0.0') if fault_detector else None
            },
            "rul_predictor": {
                "loaded": rul_predictor is not None,
                "version": getattr(rul_predictor, 'version', '1.0.0') if rul_predictor else None
            }
        }
    }
