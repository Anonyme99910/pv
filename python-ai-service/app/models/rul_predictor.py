"""
Remaining Useful Life (RUL) Predictor for PV Systems
Predicts how many days until a panel needs replacement
"""

import numpy as np
from typing import Dict, Any, Optional, List
from datetime import datetime
import os

class RULPredictor:
    """
    ML-based RUL predictor for solar panel systems
    
    Predicts remaining useful life based on:
    - Panel age
    - Current efficiency
    - Historical degradation rate
    - Environmental factors
    """
    
    def __init__(self, model_path: Optional[str] = None):
        self.version = "1.0.0"
        self.model = None
        
        # Average panel lifespan in days (25 years)
        self.base_lifespan = 25 * 365
        
        # Degradation rate per year (typical: 0.5-1%)
        self.typical_degradation_rate = 0.007  # 0.7% per year
        
        if model_path and os.path.exists(model_path):
            self._load_model(model_path)
        else:
            print("Using analytical RUL prediction (no trained model found)")
    
    def _load_model(self, path: str):
        """Load trained model"""
        try:
            import joblib
            self.model = joblib.load(path)
            print(f"Loaded RUL model from {path}")
        except Exception as e:
            print(f"Failed to load model: {e}")
            self.model = None
    
    def predict(self, data: Any) -> Dict[str, Any]:
        """
        Predict remaining useful life for a panel
        
        Args:
            data: RUL input data with panel info and readings
            
        Returns:
            Dictionary with RUL prediction
        """
        if self.model is not None:
            return self._model_predict(data)
        
        return self._analytical_predict(data)
    
    def _model_predict(self, data: Any) -> Dict[str, Any]:
        """Use trained ML model for RUL prediction"""
        try:
            # Extract features
            features = self._extract_features(data)
            
            # Predict
            rul_days = int(self.model.predict([features])[0])
            
            # Calculate confidence based on data quality
            confidence = self._calculate_confidence(data)
            
            # Calculate degradation rate
            degradation_rate = self._calculate_degradation_rate(data)
            
            return {
                'rul_days': max(0, rul_days),
                'confidence': round(confidence, 2),
                'degradation_rate': round(degradation_rate, 4)
            }
        except Exception as e:
            print(f"Model prediction error: {e}")
            return self._analytical_predict(data)
    
    def _analytical_predict(self, data: Any) -> Dict[str, Any]:
        """Analytical RUL prediction when no ML model is available"""
        
        # Get panel age in days
        installation_date = getattr(data, 'installation_date', None)
        if installation_date:
            try:
                install_date = datetime.strptime(installation_date, '%Y-%m-%d')
                age_days = (datetime.now() - install_date).days
            except:
                age_days = 365  # Default 1 year
        else:
            age_days = 365
        
        # Get current efficiency
        current_efficiency = getattr(data, 'current_efficiency', 90)
        if current_efficiency is None:
            current_efficiency = 90
        
        # Calculate degradation rate from readings if available
        readings = getattr(data, 'readings', None)
        if readings and len(readings) > 10:
            degradation_rate = self._calculate_degradation_from_readings(readings)
        else:
            degradation_rate = self.typical_degradation_rate
        
        # Calculate RUL
        # Assuming panel is replaced when efficiency drops below 80%
        min_efficiency = 80
        efficiency_remaining = current_efficiency - min_efficiency
        
        if efficiency_remaining <= 0:
            rul_days = 0
        else:
            # Days until efficiency drops to minimum
            daily_degradation = degradation_rate / 365
            if daily_degradation > 0:
                rul_days = int(efficiency_remaining / daily_degradation)
            else:
                rul_days = self.base_lifespan - age_days
        
        # Cap at reasonable values
        rul_days = max(0, min(rul_days, self.base_lifespan - age_days))
        
        # Calculate confidence
        confidence = self._calculate_confidence(data)
        
        return {
            'rul_days': rul_days,
            'confidence': round(confidence, 2),
            'degradation_rate': round(degradation_rate, 4)
        }
    
    def _extract_features(self, data: Any) -> List[float]:
        """Extract features for ML model"""
        installation_date = getattr(data, 'installation_date', None)
        if installation_date:
            try:
                install_date = datetime.strptime(installation_date, '%Y-%m-%d')
                age_days = (datetime.now() - install_date).days
            except:
                age_days = 365
        else:
            age_days = 365
        
        current_efficiency = getattr(data, 'current_efficiency', 90) or 90
        
        return [
            age_days,
            current_efficiency,
            self.typical_degradation_rate
        ]
    
    def _calculate_confidence(self, data: Any) -> float:
        """Calculate prediction confidence based on data quality"""
        confidence = 70.0  # Base confidence
        
        # Increase confidence if we have installation date
        if getattr(data, 'installation_date', None):
            confidence += 10
        
        # Increase confidence if we have efficiency data
        if getattr(data, 'current_efficiency', None):
            confidence += 10
        
        # Increase confidence if we have historical readings
        readings = getattr(data, 'readings', None)
        if readings:
            if len(readings) > 50:
                confidence += 10
            elif len(readings) > 20:
                confidence += 5
        
        return min(99, confidence)
    
    def _calculate_degradation_rate(self, data: Any) -> float:
        """Calculate annual degradation rate"""
        readings = getattr(data, 'readings', None)
        
        if readings and len(readings) > 10:
            return self._calculate_degradation_from_readings(readings)
        
        return self.typical_degradation_rate
    
    def _calculate_degradation_from_readings(self, readings: List[dict]) -> float:
        """Calculate degradation rate from historical readings"""
        try:
            # Extract power output values
            power_values = [r.get('power_output', 0) for r in readings if r.get('power_output')]
            
            if len(power_values) < 2:
                return self.typical_degradation_rate
            
            # Calculate trend
            initial_avg = np.mean(power_values[:len(power_values)//4])
            final_avg = np.mean(power_values[-len(power_values)//4:])
            
            if initial_avg > 0:
                degradation = (initial_avg - final_avg) / initial_avg
                # Annualize (assuming readings span about 1 month)
                annual_rate = degradation * 12
                return max(0.001, min(0.05, annual_rate))  # Cap between 0.1% and 5%
            
            return self.typical_degradation_rate
        except:
            return self.typical_degradation_rate
