"""
Fault Detection Model for PV Systems
Uses anomaly detection and classification to identify faults
"""

import numpy as np
from typing import Dict, Any, Optional
import os

class FaultDetector:
    """
    ML-based fault detector for solar panel systems
    
    Fault Types:
    - voltage_drop: Abnormal voltage readings
    - temperature_anomaly: Overheating issues
    - dust_accumulation: Dust blocking panels
    - current_fluctuation: Irregular current patterns
    - efficiency_degradation: Performance decline
    - connection_issue: Electrical connection problems
    """
    
    def __init__(self, model_path: Optional[str] = None):
        self.version = "1.0.0"
        self.model = None
        self.scaler = None
        self.thresholds = {
            'voltage_min': 215,
            'voltage_max': 245,
            'temperature_max': 55,
            'dust_max': 20,
            'efficiency_min': 80
        }
        
        # Default model path
        default_model_path = os.path.join(os.path.dirname(__file__), '..', '..', 'trained_models', 'fault_model.pkl')
        default_scaler_path = os.path.join(os.path.dirname(__file__), '..', '..', 'trained_models', 'fault_scaler.pkl')
        
        # Load trained model if exists
        model_to_load = model_path or default_model_path
        if os.path.exists(model_to_load):
            self._load_model(model_to_load)
            if os.path.exists(default_scaler_path):
                self._load_scaler(default_scaler_path)
        else:
            print("Using rule-based fault detection (no trained model found)")
    
    def _load_model(self, path: str):
        """Load trained sklearn model"""
        try:
            import joblib
            self.model = joblib.load(path)
            print(f"Loaded fault detection model from {path}")
        except Exception as e:
            print(f"Failed to load model: {e}")
            self.model = None
    
    def _load_scaler(self, path: str):
        """Load trained scaler"""
        try:
            import joblib
            self.scaler = joblib.load(path)
            print(f"Loaded scaler from {path}")
        except Exception as e:
            print(f"Failed to load scaler: {e}")
            self.scaler = None
    
    def predict(self, features: np.ndarray, sensor_data: Any) -> Dict[str, Any]:
        """
        Predict faults based on sensor readings
        
        Args:
            features: numpy array of sensor features
            sensor_data: original sensor data object
            
        Returns:
            Dictionary with prediction results
        """
        # If we have a trained model, use it
        if self.model is not None:
            return self._model_predict(features, sensor_data)
        
        # Otherwise, use rule-based detection
        return self._rule_based_predict(sensor_data)
    
    def _model_predict(self, features: np.ndarray, sensor_data: Any) -> Dict[str, Any]:
        """Use trained ML model for prediction"""
        try:
            prediction = self.model.predict(features)[0]
            probabilities = self.model.predict_proba(features)[0]
            confidence = float(max(probabilities) * 100)
            
            fault_detected = bool(prediction == 1)
            
            if fault_detected:
                fault_type = self._determine_fault_type(sensor_data)
                severity = self._calculate_severity(confidence, sensor_data)
                analysis = self._generate_analysis(fault_type, sensor_data)
                actions = self._suggest_actions(fault_type, severity)
            else:
                fault_type = None
                severity = None
                analysis = None
                actions = None
            
            return {
                'fault_detected': fault_detected,
                'fault_type': fault_type,
                'confidence': round(confidence, 2),
                'severity': severity,
                'ai_analysis': analysis,
                'suggested_actions': actions
            }
        except Exception as e:
            print(f"Model prediction error: {e}")
            return self._rule_based_predict(sensor_data)
    
    def _rule_based_predict(self, sensor_data: Any) -> Dict[str, Any]:
        """Rule-based fault detection when no ML model is available"""
        faults = []
        confidence_scores = []
        
        # Check voltage
        voltage = getattr(sensor_data, 'voltage', None)
        if voltage is not None:
            if voltage < self.thresholds['voltage_min']:
                faults.append('voltage_drop')
                confidence_scores.append(90 + (self.thresholds['voltage_min'] - voltage) * 2)
            elif voltage > self.thresholds['voltage_max']:
                faults.append('voltage_drop')
                confidence_scores.append(90 + (voltage - self.thresholds['voltage_max']) * 2)
        
        # Check temperature
        temperature = getattr(sensor_data, 'temperature', None)
        if temperature is not None and temperature > self.thresholds['temperature_max']:
            faults.append('temperature_anomaly')
            confidence_scores.append(85 + (temperature - self.thresholds['temperature_max']) * 3)
        
        # Check dust level
        dust_level = getattr(sensor_data, 'dust_level', None)
        if dust_level is not None and dust_level > self.thresholds['dust_max']:
            faults.append('dust_accumulation')
            confidence_scores.append(80 + (dust_level - self.thresholds['dust_max']) * 2)
        
        # Check power loss
        power_loss = getattr(sensor_data, 'power_loss_kw', None)
        if power_loss is not None and power_loss > 3.0:
            faults.append('efficiency_degradation')
            confidence_scores.append(75 + power_loss * 5)
        
        if faults:
            fault_type = faults[0]  # Primary fault
            confidence = min(99, max(confidence_scores))
            severity = self._calculate_severity(confidence, sensor_data)
            
            return {
                'fault_detected': True,
                'fault_type': fault_type,
                'confidence': round(confidence, 2),
                'severity': severity,
                'ai_analysis': self._generate_analysis(fault_type, sensor_data),
                'suggested_actions': self._suggest_actions(fault_type, severity)
            }
        
        return {
            'fault_detected': False,
            'fault_type': None,
            'confidence': 95.0,
            'severity': None,
            'ai_analysis': None,
            'suggested_actions': None
        }
    
    def _determine_fault_type(self, sensor_data: Any) -> str:
        """Determine the type of fault based on sensor readings"""
        voltage = getattr(sensor_data, 'voltage', 230)
        temperature = getattr(sensor_data, 'temperature', 35)
        dust_level = getattr(sensor_data, 'dust_level', 10)
        
        if voltage < 215 or voltage > 245:
            return 'voltage_drop'
        elif temperature > 55:
            return 'temperature_anomaly'
        elif dust_level > 20:
            return 'dust_accumulation'
        else:
            return 'efficiency_degradation'
    
    def _calculate_severity(self, confidence: float, sensor_data: Any) -> str:
        """Calculate fault severity"""
        temperature = getattr(sensor_data, 'temperature', 35)
        
        if confidence > 95 or temperature > 70:
            return 'critical'
        elif confidence > 85 or temperature > 60:
            return 'high'
        elif confidence > 75:
            return 'medium'
        else:
            return 'low'
    
    def _generate_analysis(self, fault_type: str, sensor_data: Any) -> Dict[str, Any]:
        """Generate AI analysis for the fault"""
        analyses = {
            'voltage_drop': {
                'root_cause': 'Abnormal voltage detected. This may indicate connection issues, inverter problems, or grid instability.',
                'contributing_factors': [
                    'Loose electrical connections',
                    'Inverter malfunction',
                    'Grid voltage fluctuations',
                    'Cable degradation'
                ]
            },
            'temperature_anomaly': {
                'root_cause': 'Elevated temperature detected. This could be due to dust accumulation, poor ventilation, or component failure.',
                'contributing_factors': [
                    'Dust accumulation blocking heat dissipation',
                    'Ambient temperature spike',
                    'Reduced airflow around panel',
                    'Internal component degradation'
                ]
            },
            'dust_accumulation': {
                'root_cause': 'High dust accumulation detected on panel surface, reducing efficiency.',
                'contributing_factors': [
                    'Extended period without cleaning',
                    'High dust environment',
                    'Recent construction nearby',
                    'Seasonal pollen accumulation'
                ]
            },
            'efficiency_degradation': {
                'root_cause': 'Panel efficiency has degraded below acceptable threshold.',
                'contributing_factors': [
                    'Cell degradation over time',
                    'Micro-cracks in solar cells',
                    'Hotspot formation',
                    'PID (Potential Induced Degradation)'
                ]
            },
            'current_fluctuation': {
                'root_cause': 'Irregular current patterns detected indicating potential issues.',
                'contributing_factors': [
                    'Partial shading',
                    'Bypass diode failure',
                    'String mismatch',
                    'Inverter MPPT issues'
                ]
            },
            'connection_issue': {
                'root_cause': 'Electrical connection problems detected.',
                'contributing_factors': [
                    'Corroded connectors',
                    'Loose junction box connections',
                    'Cable damage',
                    'Water ingress'
                ]
            }
        }
        
        return analyses.get(fault_type, {
            'root_cause': 'Unknown fault detected. Manual inspection recommended.',
            'contributing_factors': []
        })
    
    def _suggest_actions(self, fault_type: str, severity: str) -> list:
        """Generate suggested actions based on fault type and severity"""
        actions = {
            'voltage_drop': [
                {'priority': 'high', 'action': 'Check all electrical connections', 'estimatedTime': '30 minutes'},
                {'priority': 'medium', 'action': 'Inspect inverter status and logs', 'estimatedTime': '20 minutes'},
                {'priority': 'low', 'action': 'Verify grid voltage stability', 'estimatedTime': '10 minutes'}
            ],
            'temperature_anomaly': [
                {'priority': 'high', 'action': 'Schedule immediate cleaning of panel surface', 'estimatedTime': '30 minutes'},
                {'priority': 'medium', 'action': 'Inspect mounting structure for airflow obstructions', 'estimatedTime': '15 minutes'},
                {'priority': 'low', 'action': 'Review cleaning schedule frequency for this zone', 'estimatedTime': '5 minutes'}
            ],
            'dust_accumulation': [
                {'priority': 'high', 'action': 'Clean panel surface immediately', 'estimatedTime': '45 minutes'},
                {'priority': 'medium', 'action': 'Inspect neighboring panels', 'estimatedTime': '20 minutes'},
                {'priority': 'low', 'action': 'Adjust cleaning schedule', 'estimatedTime': '5 minutes'}
            ],
            'efficiency_degradation': [
                {'priority': 'high', 'action': 'Perform detailed panel inspection', 'estimatedTime': '60 minutes'},
                {'priority': 'medium', 'action': 'Check for hotspots with thermal camera', 'estimatedTime': '30 minutes'},
                {'priority': 'low', 'action': 'Review historical performance data', 'estimatedTime': '15 minutes'}
            ]
        }
        
        return actions.get(fault_type, [
            {'priority': 'high', 'action': 'Schedule manual inspection', 'estimatedTime': '60 minutes'}
        ])
