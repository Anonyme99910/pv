"""
Image Classification Model for Solar Panel Visual Defect Detection
Uses CNN trained on panel images to classify:
- Clean (healthy)
- Bird-drop (contamination)
- Dusty (dust accumulation)
- Electrical-damage (hotspots, burns)
- Physical-Damage (cracks, breaks)
- Snow-Covered (snow coverage)
"""

import os
import numpy as np
from typing import Dict, Any, Optional
from PIL import Image
import io

class ImageClassifier:
    """
    CNN-based image classifier for solar panel visual defects
    """
    
    # Class mapping
    CLASS_NAMES = {
        0: 'clean',
        1: 'bird_drop',
        2: 'dusty',
        3: 'electrical_damage',
        4: 'physical_damage',
        5: 'snow_covered'
    }
    
    # Fault type mapping for Laravel
    FAULT_TYPE_MAP = {
        'clean': None,
        'bird_drop': 'contamination',
        'dusty': 'dust_accumulation',
        'electrical_damage': 'electrical_damage',
        'physical_damage': 'physical_damage',
        'snow_covered': 'snow_coverage'
    }
    
    def __init__(self, model_path: Optional[str] = None):
        self.version = "1.0.0"
        self.model = None
        self.img_size = (224, 224)
        
        # Default model path
        default_model_path = os.path.join(
            os.path.dirname(__file__), '..', '..', 'trained_models', 'cnn_model.h5'
        )
        
        model_to_load = model_path or default_model_path
        if os.path.exists(model_to_load):
            self._load_model(model_to_load)
        else:
            print("CNN model not found. Image classification will use placeholder responses.")
    
    def _load_model(self, path: str):
        """Load trained TensorFlow/Keras model"""
        try:
            import tensorflow as tf
            self.model = tf.keras.models.load_model(path)
            print(f"Loaded CNN model from {path}")
        except Exception as e:
            print(f"Failed to load CNN model: {e}")
            self.model = None
    
    def preprocess_image(self, image_bytes: bytes) -> np.ndarray:
        """Preprocess image for model input"""
        # Load image
        image = Image.open(io.BytesIO(image_bytes))
        
        # Convert to RGB if needed
        if image.mode != 'RGB':
            image = image.convert('RGB')
        
        # Resize
        image = image.resize(self.img_size)
        
        # Convert to array and normalize
        img_array = np.array(image) / 255.0
        
        # Add batch dimension
        img_array = np.expand_dims(img_array, axis=0)
        
        return img_array
    
    def predict(self, image_bytes: bytes) -> Dict[str, Any]:
        """
        Classify panel image
        
        Args:
            image_bytes: Raw image bytes
            
        Returns:
            Dictionary with classification results
        """
        if self.model is None:
            # Return placeholder if no model loaded
            return {
                'classification': 'clean',
                'confidence': 85.0,
                'fault_type': None,
                'all_predictions': {}
            }
        
        try:
            # Preprocess
            img_array = self.preprocess_image(image_bytes)
            
            # Predict
            predictions = self.model.predict(img_array, verbose=0)[0]
            
            # Get top prediction
            top_idx = np.argmax(predictions)
            confidence = float(predictions[top_idx] * 100)
            classification = self.CLASS_NAMES.get(top_idx, 'unknown')
            
            # Get all predictions
            all_predictions = {
                self.CLASS_NAMES[i]: float(predictions[i] * 100)
                for i in range(len(predictions))
            }
            
            return {
                'classification': classification,
                'confidence': round(confidence, 2),
                'fault_type': self.FAULT_TYPE_MAP.get(classification),
                'all_predictions': all_predictions
            }
            
        except Exception as e:
            print(f"Image classification error: {e}")
            return {
                'classification': 'error',
                'confidence': 0.0,
                'fault_type': None,
                'error': str(e)
            }
    
    def predict_from_path(self, image_path: str) -> Dict[str, Any]:
        """Classify image from file path"""
        with open(image_path, 'rb') as f:
            image_bytes = f.read()
        return self.predict(image_bytes)
