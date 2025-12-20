"""
Thermal Defect Detector using YOLOv9
Integrated from SolarPanelsDefectDetector project

Detects 8 types of thermal faults in solar panels:
1. Single Hotspot
2. Multiple Hotspots
3. Single Diode Fault
4. Multiple Diode Faults
5. Single Bypassed Substring
6. Multiple Bypassed Substrings
7. Open Circuit (String)
8. Reversed Polarity (String)
"""

import os
import numpy as np
from PIL import Image, ExifTags
import cv2
from io import BytesIO
from typing import Tuple, List, Dict, Optional
import logging

logger = logging.getLogger(__name__)

# Fault type mapping (class_id -> fault info)
THERMAL_FAULT_TYPES = {
    0: {"name": "single_hotspot", "severity": "medium", "description": "Localized overheating detected"},
    1: {"name": "multiple_hotspots", "severity": "high", "description": "Multiple heat points detected"},
    2: {"name": "single_diode_fault", "severity": "high", "description": "Bypass diode failure"},
    3: {"name": "multiple_diode_faults", "severity": "critical", "description": "Multiple diode failures"},
    4: {"name": "single_bypassed_substring", "severity": "medium", "description": "Partial module bypass"},
    5: {"name": "multiple_bypassed_substrings", "severity": "high", "description": "Multiple module bypasses"},
    6: {"name": "open_circuit_string", "severity": "critical", "description": "Broken string connection"},
    7: {"name": "reversed_polarity_string", "severity": "critical", "description": "Wiring polarity error"},
}


class ThermalDefectDetector:
    """
    YOLOv9-based thermal defect detector for solar panels.
    Uses grayscale thermal images to detect various fault types.
    """
    
    def __init__(self, model_path: str = None):
        """
        Initialize the thermal detector.
        
        Args:
            model_path: Path to YOLOv9 model file (.pt)
        """
        self.model = None
        self.model_path = model_path or self._find_model_path()
        self.confidence_threshold = 0.5
        self.input_size = (640, 640)
        self._load_model()
    
    def _find_model_path(self) -> str:
        """Find the YOLOv9 model file."""
        possible_paths = [
            # Relative to python-ai-service
            "trained_models/Th_G_v9.pt",
            "../SolarPanelsDefectDetector-master/ThermalDetector/Th_G_v9.pt",
            # Absolute paths
            "c:/xampp/htdocs/pv/SolarPanelsDefectDetector-master/ThermalDetector/Th_G_v9.pt",
            "c:/xampp/htdocs/pv/python-ai-service/trained_models/Th_G_v9.pt",
        ]
        
        for path in possible_paths:
            if os.path.exists(path):
                return path
        
        return possible_paths[0]  # Default path
    
    def _load_model(self):
        """Load the YOLOv9 model."""
        try:
            from ultralytics import YOLO
            
            if os.path.exists(self.model_path):
                self.model = YOLO(self.model_path)
                logger.info(f"Thermal detector model loaded from {self.model_path}")
            else:
                logger.warning(f"Thermal model not found at {self.model_path}")
                self.model = None
        except ImportError:
            logger.warning("ultralytics not installed. Thermal detection unavailable.")
            self.model = None
        except Exception as e:
            logger.error(f"Failed to load thermal model: {e}")
            self.model = None
    
    def is_available(self) -> bool:
        """Check if the model is loaded and available."""
        return self.model is not None
    
    def preprocess_image(self, img: Image.Image) -> np.ndarray:
        """
        Preprocess image for YOLOv9 inference.
        
        Args:
            img: PIL Image (can be RGB or grayscale)
            
        Returns:
            Preprocessed numpy array
        """
        # Handle EXIF orientation
        try:
            for orientation in ExifTags.TAGS.keys():
                if ExifTags.TAGS[orientation] == 'Orientation':
                    break
            exif = img._getexif()
            if exif is not None:
                orientation_val = exif.get(orientation)
                if orientation_val == 3:
                    img = img.rotate(180, expand=True)
                elif orientation_val == 6:
                    img = img.rotate(270, expand=True)
                elif orientation_val == 8:
                    img = img.rotate(90, expand=True)
        except:
            pass
        
        # Convert to numpy array
        img_array = np.array(img)
        
        # Convert to BGR if RGB
        if len(img_array.shape) == 3:
            img_array = cv2.cvtColor(img_array, cv2.COLOR_RGB2BGR)
        
        # Resize to model input size
        img_array = cv2.resize(img_array, self.input_size)
        
        # Convert to grayscale (thermal images are grayscale)
        if len(img_array.shape) == 3:
            img_array = cv2.cvtColor(img_array, cv2.COLOR_BGR2GRAY)
        
        # Normalize
        img_array = img_array / 255.0
        
        return img_array.astype(np.float32)
    
    def detect(self, image: Image.Image, panel_code: str = None) -> Dict:
        """
        Run thermal defect detection on an image.
        
        Args:
            image: PIL Image (thermal image)
            panel_code: Optional panel identifier
            
        Returns:
            Detection results dictionary
        """
        if not self.is_available():
            return {
                "success": False,
                "error": "Thermal detector model not available",
                "panel_code": panel_code,
                "detections": [],
                "fault_detected": False
            }
        
        try:
            # Preprocess
            pre_img = self.preprocess_image(image)
            uint_img = (pre_img * 255).astype(np.uint8)
            pil_img = Image.fromarray(uint_img)
            
            # Run inference
            results = self.model(pil_img)
            
            if not results or len(results) == 0:
                return {
                    "success": True,
                    "panel_code": panel_code,
                    "fault_detected": False,
                    "message": "No thermal anomalies detected",
                    "detections": [],
                    "annotated_image": None
                }
            
            # Parse detections
            detections = []
            for r in results:
                if r.boxes is None:
                    continue
                    
                for idx in range(len(r.boxes.xyxy)):
                    conf = float(r.boxes.conf[idx])
                    if conf > self.confidence_threshold:
                        class_id = int(r.boxes.cls[idx])
                        fault_info = THERMAL_FAULT_TYPES.get(class_id, {
                            "name": f"unknown_fault_{class_id}",
                            "severity": "medium",
                            "description": "Unknown fault type"
                        })
                        
                        detections.append({
                            "class_id": class_id,
                            "fault_type": fault_info["name"],
                            "severity": fault_info["severity"],
                            "description": fault_info["description"],
                            "confidence": round(conf, 3),
                            "bbox_normalized": r.boxes.xyxyn[idx].cpu().numpy().tolist(),
                            "bbox_pixels": r.boxes.xyxy[idx].cpu().numpy().tolist()
                        })
            
            # Generate annotated image
            annotated_image = None
            if len(detections) > 0:
                output_image_np = results[0].plot()
                output_image_rgb = cv2.cvtColor(output_image_np, cv2.COLOR_BGR2RGB)
                annotated_image = Image.fromarray(output_image_rgb)
            
            # Determine overall severity
            severities = [d["severity"] for d in detections]
            if "critical" in severities:
                overall_severity = "critical"
            elif "high" in severities:
                overall_severity = "high"
            elif "medium" in severities:
                overall_severity = "medium"
            else:
                overall_severity = "low"
            
            # Build response
            return {
                "success": True,
                "panel_code": panel_code,
                "fault_detected": len(detections) > 0,
                "anomaly_count": len(detections),
                "overall_severity": overall_severity if detections else None,
                "detections": detections,
                "annotated_image": annotated_image,
                "suggested_actions": self._get_suggested_actions(detections),
                "ai_analysis": self._generate_analysis(detections)
            }
            
        except Exception as e:
            logger.error(f"Thermal detection error: {e}")
            return {
                "success": False,
                "error": str(e),
                "panel_code": panel_code,
                "detections": [],
                "fault_detected": False
            }
    
    def _get_suggested_actions(self, detections: List[Dict]) -> List[Dict]:
        """Generate suggested actions based on detected faults."""
        actions = []
        fault_types = set(d["fault_type"] for d in detections)
        
        action_map = {
            "single_hotspot": {
                "action": "Inspect panel for debris or cell damage",
                "priority": "medium",
                "estimated_time": "30 minutes"
            },
            "multiple_hotspots": {
                "action": "Schedule immediate panel inspection and cleaning",
                "priority": "high",
                "estimated_time": "1 hour"
            },
            "single_diode_fault": {
                "action": "Test and replace bypass diode",
                "priority": "high",
                "estimated_time": "2 hours"
            },
            "multiple_diode_faults": {
                "action": "Replace junction box or entire panel",
                "priority": "urgent",
                "estimated_time": "4 hours"
            },
            "single_bypassed_substring": {
                "action": "Check cell connections and bypass diode",
                "priority": "medium",
                "estimated_time": "1 hour"
            },
            "multiple_bypassed_substrings": {
                "action": "Full electrical inspection required",
                "priority": "high",
                "estimated_time": "3 hours"
            },
            "open_circuit_string": {
                "action": "Emergency: Locate and repair broken connection",
                "priority": "urgent",
                "estimated_time": "2-4 hours"
            },
            "reversed_polarity_string": {
                "action": "Emergency: Correct wiring polarity immediately",
                "priority": "urgent",
                "estimated_time": "1-2 hours"
            }
        }
        
        for fault_type in fault_types:
            if fault_type in action_map:
                actions.append(action_map[fault_type])
        
        # Sort by priority
        priority_order = {"urgent": 0, "high": 1, "medium": 2, "low": 3}
        actions.sort(key=lambda x: priority_order.get(x["priority"], 4))
        
        return actions
    
    def _generate_analysis(self, detections: List[Dict]) -> Dict:
        """Generate AI analysis summary."""
        if not detections:
            return {
                "summary": "No thermal anomalies detected",
                "risk_level": "low",
                "recommendations": ["Continue regular monitoring"]
            }
        
        fault_counts = {}
        for d in detections:
            ft = d["fault_type"]
            fault_counts[ft] = fault_counts.get(ft, 0) + 1
        
        # Determine risk level
        severities = [d["severity"] for d in detections]
        if "critical" in severities or len(detections) > 3:
            risk_level = "critical"
        elif "high" in severities or len(detections) > 1:
            risk_level = "high"
        else:
            risk_level = "medium"
        
        # Build summary
        fault_summary = ", ".join([f"{count}x {ft.replace('_', ' ')}" 
                                   for ft, count in fault_counts.items()])
        
        return {
            "summary": f"Detected {len(detections)} thermal anomalies: {fault_summary}",
            "risk_level": risk_level,
            "fault_breakdown": fault_counts,
            "recommendations": [
                "Schedule maintenance within 24 hours" if risk_level == "critical" else
                "Schedule maintenance within 1 week" if risk_level == "high" else
                "Monitor and schedule routine inspection",
                "Document findings with photos",
                "Check adjacent panels for similar issues"
            ]
        }
    
    def detect_from_bytes(self, image_bytes: bytes, panel_code: str = None) -> Dict:
        """
        Run detection from image bytes.
        
        Args:
            image_bytes: Image file bytes
            panel_code: Optional panel identifier
            
        Returns:
            Detection results
        """
        try:
            image = Image.open(BytesIO(image_bytes))
            return self.detect(image, panel_code)
        except Exception as e:
            return {
                "success": False,
                "error": f"Failed to load image: {e}",
                "panel_code": panel_code,
                "detections": [],
                "fault_detected": False
            }


# Singleton instance
_thermal_detector = None

def get_thermal_detector() -> ThermalDefectDetector:
    """Get or create the thermal detector singleton."""
    global _thermal_detector
    if _thermal_detector is None:
        _thermal_detector = ThermalDefectDetector()
    return _thermal_detector
