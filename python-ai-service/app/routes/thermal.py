"""
Thermal Detection API Routes
Integrates YOLOv9-based thermal defect detection for solar panels.
"""

import os
import base64
from io import BytesIO
from typing import Optional
from fastapi import APIRouter, UploadFile, File, Form, HTTPException
from fastapi.responses import JSONResponse, StreamingResponse
from pydantic import BaseModel
import httpx
import logging

from ..models.thermal_detector import get_thermal_detector, THERMAL_FAULT_TYPES

logger = logging.getLogger(__name__)

router = APIRouter(prefix="/thermal", tags=["Thermal Detection"])

# Laravel webhook URL
LARAVEL_WEBHOOK_URL = os.getenv(
    "LARAVEL_WEBHOOK_URL", 
    "http://localhost/pv/pv-backend/public/api/webhooks/ai"
)


class ThermalDetectionResponse(BaseModel):
    """Response model for thermal detection."""
    success: bool
    panel_code: Optional[str] = None
    fault_detected: bool
    anomaly_count: int = 0
    overall_severity: Optional[str] = None
    detections: list = []
    suggested_actions: list = []
    ai_analysis: dict = {}
    message: Optional[str] = None
    error: Optional[str] = None


@router.get("/status")
async def thermal_status():
    """Check thermal detector status."""
    detector = get_thermal_detector()
    return {
        "available": detector.is_available(),
        "model_path": detector.model_path,
        "fault_types": list(THERMAL_FAULT_TYPES.values()),
        "confidence_threshold": detector.confidence_threshold
    }


@router.post("/detect", response_model=ThermalDetectionResponse)
async def detect_thermal_defects(
    file: UploadFile = File(...),
    panel_code: Optional[str] = Form(None),
    send_webhook: bool = Form(True)
):
    """
    Detect thermal defects in a solar panel image.
    
    - **file**: Thermal image file (grayscale recommended, 640x640 optimal)
    - **panel_code**: Optional panel identifier for tracking
    - **send_webhook**: Whether to send results to Laravel webhook
    
    Returns detection results with fault types, severities, and suggested actions.
    """
    detector = get_thermal_detector()
    
    if not detector.is_available():
        raise HTTPException(
            status_code=503,
            detail="Thermal detector model not available. Please ensure Th_G_v9.pt is in trained_models/"
        )
    
    try:
        # Read image bytes
        image_bytes = await file.read()
        
        # Run detection
        result = detector.detect_from_bytes(image_bytes, panel_code)
        
        if not result["success"]:
            raise HTTPException(status_code=500, detail=result.get("error", "Detection failed"))
        
        # Send webhook to Laravel if faults detected
        if send_webhook and result["fault_detected"]:
            await send_to_laravel(result, image_bytes)
        
        # Remove PIL image from response (not JSON serializable)
        result.pop("annotated_image", None)
        
        return ThermalDetectionResponse(**result)
        
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Thermal detection error: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/detect/annotated")
async def detect_with_annotated_image(
    file: UploadFile = File(...),
    panel_code: Optional[str] = Form(None)
):
    """
    Detect thermal defects and return annotated image with bounding boxes.
    
    Returns JPEG image with detection boxes drawn.
    Headers include X-Anomaly-Count and X-Detection-Result.
    """
    detector = get_thermal_detector()
    
    if not detector.is_available():
        raise HTTPException(
            status_code=503,
            detail="Thermal detector model not available"
        )
    
    try:
        image_bytes = await file.read()
        result = detector.detect_from_bytes(image_bytes, panel_code)
        
        if not result["success"]:
            return JSONResponse(
                content={"error": result.get("error", "Detection failed")},
                status_code=500
            )
        
        annotated_image = result.get("annotated_image")
        
        if annotated_image is None:
            # No detections, return original image info
            return JSONResponse(
                content={
                    "status": "No anomalies detected",
                    "detections": []
                },
                status_code=200,
                headers={
                    "X-Anomaly-Count": "0",
                    "X-Detection-Result": "No anomalies"
                }
            )
        
        # Convert PIL image to bytes
        img_io = BytesIO()
        annotated_image.save(img_io, format='JPEG', quality=90)
        img_io.seek(0)
        
        return StreamingResponse(
            img_io,
            media_type="image/jpeg",
            headers={
                "X-Anomaly-Count": str(result["anomaly_count"]),
                "X-Detection-Result": result["ai_analysis"].get("summary", "Detection complete"),
                "X-Overall-Severity": result.get("overall_severity", "none"),
                "X-Panel-Code": panel_code or "unknown"
            }
        )
        
    except Exception as e:
        logger.error(f"Annotated detection error: {e}")
        raise HTTPException(status_code=500, detail=str(e))


@router.post("/detect/batch")
async def detect_batch(
    files: list[UploadFile] = File(...),
    panel_codes: Optional[str] = Form(None)
):
    """
    Batch thermal detection for multiple images.
    
    - **files**: List of thermal image files
    - **panel_codes**: Comma-separated panel codes (optional)
    
    Returns list of detection results.
    """
    detector = get_thermal_detector()
    
    if not detector.is_available():
        raise HTTPException(
            status_code=503,
            detail="Thermal detector model not available"
        )
    
    # Parse panel codes
    codes = panel_codes.split(",") if panel_codes else []
    
    results = []
    for i, file in enumerate(files):
        panel_code = codes[i] if i < len(codes) else None
        
        try:
            image_bytes = await file.read()
            result = detector.detect_from_bytes(image_bytes, panel_code)
            result.pop("annotated_image", None)  # Remove non-serializable
            results.append(result)
        except Exception as e:
            results.append({
                "success": False,
                "error": str(e),
                "panel_code": panel_code,
                "filename": file.filename
            })
    
    # Summary
    total_faults = sum(1 for r in results if r.get("fault_detected", False))
    
    return {
        "total_images": len(files),
        "images_with_faults": total_faults,
        "results": results
    }


async def send_to_laravel(result: dict, image_bytes: bytes = None):
    """Send detection results to Laravel webhook."""
    try:
        # Prepare payload
        payload = {
            "detection_type": "thermal",
            "panel_code": result.get("panel_code"),
            "fault_detected": result.get("fault_detected", False),
            "anomaly_count": result.get("anomaly_count", 0),
            "overall_severity": result.get("overall_severity"),
            "detections": result.get("detections", []),
            "suggested_actions": result.get("suggested_actions", []),
            "ai_analysis": result.get("ai_analysis", {})
        }
        
        # Include base64 image if available
        if image_bytes:
            payload["image_base64"] = base64.b64encode(image_bytes).decode('utf-8')
        
        # Send to Laravel
        async with httpx.AsyncClient(timeout=10.0) as client:
            response = await client.post(
                f"{LARAVEL_WEBHOOK_URL}/thermal-detection",
                json=payload
            )
            
            if response.status_code not in [200, 201]:
                logger.warning(f"Laravel webhook returned {response.status_code}")
                
    except Exception as e:
        logger.error(f"Failed to send webhook to Laravel: {e}")


@router.get("/fault-types")
async def get_fault_types():
    """Get list of detectable thermal fault types."""
    return {
        "fault_types": [
            {
                "class_id": class_id,
                **info
            }
            for class_id, info in THERMAL_FAULT_TYPES.items()
        ]
    }
