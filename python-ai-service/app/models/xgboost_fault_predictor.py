"""
XGBoost Fault Predictor - Integrated from AI models/fault_prediction_(1).ipynb
Predicts time to fault (days) for solar system components

Original Model Metrics:
- R² Score: 0.9037 (90% accuracy)
- MAE: 1.0 days

Components: Solar Panel, Charge Controller, Inverter, Battery
"""

import os
import pickle
import numpy as np
import pandas as pd
from typing import Dict, Any, Optional
from datetime import datetime, timedelta

class XGBoostFaultPredictor:
    """
    XGBoost-based fault prediction model
    Predicts days until component failure with context-aware weighting
    """
    
    CLASS_DICT = {
        "Solar Panel": 0,
        "Charge Controller": 1,
        "Inverter": 2,
        "Battery": 3
    }
    
    REVERSE_CLASS_DICT = {v: k for k, v in CLASS_DICT.items()}
    
    def __init__(self, model_path: Optional[str] = None):
        self.version = "1.0.0"
        self.model = None
        
        # Default model path - look in AI models folder
        default_paths = [
            os.path.join(os.path.dirname(__file__), '..', '..', 'trained_models', 'fault_prediction.pickle'),
            os.path.join(os.path.dirname(__file__), '..', '..', '..', 'AI models', 'fault_prediction.pickle'),
        ]
        
        model_to_load = model_path
        if not model_to_load:
            for path in default_paths:
                if os.path.exists(path):
                    model_to_load = path
                    break
        
        if model_to_load and os.path.exists(model_to_load):
            self._load_model(model_to_load)
        else:
            print("XGBoost fault prediction model not found. Using analytical fallback.")
    
    def _load_model(self, path: str):
        """Load trained XGBoost model"""
        try:
            with open(path, 'rb') as f:
                self.model = pickle.load(f)
            print(f"Loaded XGBoost fault predictor from {path}")
        except Exception as e:
            print(f"Failed to load XGBoost model: {e}")
            self.model = None
    
    def calculate_context_weights(self, df: pd.DataFrame) -> float:
        """Calculate context-aware weights based on feature values"""
        weight_multiplier = 1.0
        
        # Context multipliers
        context_multipliers = {
            'temperature_stress': 1.5,
            'electrical_stress': 1.8,
            'environmental_stress': 1.3
        }
        
        row = df.iloc[0]
        
        # Temperature stress
        temp_dev = abs(row.get('device_temperature (°C)', 40) - 42.5)  # midpoint of 25-60
        if temp_dev > 15:
            weight_multiplier *= context_multipliers['temperature_stress']
        
        # Electrical stress
        current = row.get('output_current (A)', 15)
        voltage = row.get('output_voltage (V)', 24)
        current_stress = (current - 5) / 25
        voltage_stress = (voltage - 12) / 36
        if current_stress > 0.8 or voltage_stress > 0.8:
            weight_multiplier *= context_multipliers['electrical_stress']
        
        # Environmental stress
        humidity = row.get('humidity (%)', 65)
        irradiance = row.get('irradiance (W/m²)', 600)
        if humidity < 60 or humidity > 80 or irradiance > 800:
            weight_multiplier *= context_multipliers['environmental_stress']
        
        # Component-specific weights
        component_type = row.get('component_type', 0)
        if isinstance(component_type, str):
            component_type = self.CLASS_DICT.get(component_type, 0)
        component_weights = {0: 1.2, 1: 1.1, 2: 1.3, 3: 1.4}
        weight_multiplier *= component_weights.get(component_type, 1.0)
        
        return weight_multiplier
    
    def get_dominant_context(self, df: pd.DataFrame, weight: float) -> str:
        """Determine the dominant stress context"""
        row = df.iloc[0]
        
        contexts = []
        
        # Temperature stress check
        temp_dev = abs(row.get('device_temperature (°C)', 40) - 42.5)
        if temp_dev > 15:
            contexts.append(('Temperature Stress', 1.5))
        
        # Electrical stress check
        current = row.get('output_current (A)', 15)
        voltage = row.get('output_voltage (V)', 24)
        if (current - 5) / 25 > 0.8 or (voltage - 12) / 36 > 0.8:
            contexts.append(('Electrical Stress', 1.8))
        
        # Environmental stress check
        humidity = row.get('humidity (%)', 65)
        irradiance = row.get('irradiance (W/m²)', 600)
        if humidity < 60 or humidity > 80 or irradiance > 800:
            contexts.append(('Environmental Stress', 1.3))
        
        if not contexts:
            return "Normal operating conditions"
        
        return max(contexts, key=lambda x: x[1])[0]
    
    def predict(self, data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Predict time to fault for a component
        
        Args:
            data: Dictionary with sensor readings and component_type
            
        Returns:
            Dictionary with prediction results
        """
        # Prepare dataframe
        df = pd.DataFrame([data])
        
        # Encode component type if string
        if 'component_type' in df.columns:
            if isinstance(df['component_type'].iloc[0], str):
                component_name = df['component_type'].iloc[0]
                df['component_type'] = df['component_type'].apply(
                    lambda x: self.CLASS_DICT.get(x, 0)
                )
            else:
                component_name = self.REVERSE_CLASS_DICT.get(df['component_type'].iloc[0], 'Solar Panel')
        else:
            component_name = 'Solar Panel'
            df['component_type'] = 0
        
        # Calculate context weight
        weight = self.calculate_context_weights(df)
        
        # Predict using model or fallback
        if self.model is not None:
            try:
                # Ensure correct column order for model
                feature_cols = [
                    'ambient_temperature (°C)', 'irradiance (W/m²)', 'humidity (%)',
                    'device_temperature (°C)', 'output_current (A)', 'output_voltage (V)',
                    'component_type'
                ]
                
                # Fill missing columns with defaults
                defaults = {
                    'ambient_temperature (°C)': 28.0,
                    'irradiance (W/m²)': 600.0,
                    'humidity (%)': 65.0,
                    'device_temperature (°C)': 45.0,
                    'output_current (A)': 15.0,
                    'output_voltage (V)': 24.0,
                    'component_type': 0
                }
                
                for col in feature_cols:
                    if col not in df.columns:
                        df[col] = defaults[col]
                
                X = df[feature_cols]
                time_to_fault = self.model.predict(X)[0]
            except Exception as e:
                print(f"XGBoost prediction error: {e}")
                time_to_fault = self._analytical_predict(df)
        else:
            time_to_fault = self._analytical_predict(df)
        
        # Apply context weight adjustment
        adjusted_time = time_to_fault * (1 / weight)
        adjusted_time = int(adjusted_time + 0.5) if adjusted_time - int(adjusted_time) > 0.5 else int(adjusted_time)
        adjusted_time = max(1, adjusted_time)  # Minimum 1 day
        
        # Calculate fault date
        current_time = datetime.now()
        fault_time = current_time + timedelta(days=adjusted_time)
        fault_date = fault_time.strftime("%Y-%m-%d")
        
        # Get dominant context
        dominant_context = self.get_dominant_context(df, weight)
        
        # Determine severity
        if adjusted_time <= 2:
            severity = 'critical'
        elif adjusted_time <= 7:
            severity = 'high'
        elif adjusted_time <= 14:
            severity = 'medium'
        else:
            severity = 'low'
        
        return {
            'component': component_name,
            'days_to_fault': adjusted_time,
            'fault_date': fault_date,
            'context': dominant_context,
            'weight': round(weight, 2),
            'severity': severity,
            'confidence': 90.0 if self.model else 70.0
        }
    
    def _analytical_predict(self, df: pd.DataFrame) -> float:
        """Fallback analytical prediction when no model available"""
        row = df.iloc[0]
        
        # Base days (average from training data)
        base_days = 7.0
        
        # Adjust based on temperature
        temp = row.get('device_temperature (°C)', 45)
        if temp > 55:
            base_days *= 0.5
        elif temp > 45:
            base_days *= 0.8
        
        # Adjust based on current
        current = row.get('output_current (A)', 15)
        if current > 25:
            base_days *= 0.6
        elif current > 20:
            base_days *= 0.8
        
        return base_days
    
    def predict_batch(self, data_list: list) -> list:
        """Predict for multiple components"""
        return [self.predict(data) for data in data_list]
