"""
PV Inverter Monitoring System - Integrated from AI models/PV_INVERTER_MONITORING_SYSTEM (2).ipynb

Three models in one:
1. Power Loss Predictor (RandomForest) - MAE: 0.14 kW
2. Functionality Classifier (RandomForest) - 71% accuracy  
3. Anomaly Detector (IsolationForest) - 10% anomaly detection rate

Original Model Metrics:
- Power Loss MAE: 0.14 kW
- Classification Accuracy: 71%
"""

import os
import pickle
import numpy as np
import pandas as pd
from typing import Dict, Any, Optional, List
from sklearn.ensemble import RandomForestClassifier, RandomForestRegressor, IsolationForest
from sklearn.preprocessing import StandardScaler, LabelEncoder

class PVMonitoringSystem:
    """
    Complete PV Inverter Monitoring System with:
    - Anomaly Detection
    - Power Loss Prediction
    - Functionality Classification
    """
    
    FEATURES = [
        'Irradiance_Wm2', 'Temperature_C', 'Bus_Voltage_pu',
        'Load_Demand_MW', 'Reactive_Power_Setting_MVAR',
        'Voltage_Setting_pu', 'VOCR', 'Season_encoded',
        'Control_Scheme_encoded'
    ]
    
    SEASON_MAP = {'Summer': 0, 'Monsoon': 1, 'Winter': 2}
    CONTROL_MAP = {'Centralized': 0, 'Decentralized': 1, 'Distributed': 2}
    
    def __init__(self, models_dir: Optional[str] = None):
        self.version = "1.0.0"
        
        # Initialize models
        self.power_loss_predictor = None
        self.functionality_classifier = None
        self.anomaly_detector = None
        self.scaler = StandardScaler()
        self.label_encoder = LabelEncoder()
        self.is_fitted = False
        
        # Try to load pre-trained models
        default_dir = models_dir or os.path.join(
            os.path.dirname(__file__), '..', '..', 'trained_models'
        )
        
        self._load_models(default_dir)
    
    def _load_models(self, models_dir: str):
        """Load pre-trained models if available"""
        try:
            # Check for saved models
            power_loss_path = os.path.join(models_dir, 'power_loss_predictor.pickle')
            classifier_path = os.path.join(models_dir, 'functionality_classifier.pickle')
            anomaly_path = os.path.join(models_dir, 'anomaly_detector.pickle')
            
            if os.path.exists(power_loss_path):
                with open(power_loss_path, 'rb') as f:
                    self.power_loss_predictor = pickle.load(f)
                print(f"Loaded power loss predictor from {power_loss_path}")
            
            if os.path.exists(classifier_path):
                with open(classifier_path, 'rb') as f:
                    self.functionality_classifier = pickle.load(f)
                print(f"Loaded functionality classifier from {classifier_path}")
            
            if os.path.exists(anomaly_path):
                with open(anomaly_path, 'rb') as f:
                    self.anomaly_detector = pickle.load(f)
                print(f"Loaded anomaly detector from {anomaly_path}")
            
            if self.power_loss_predictor and self.functionality_classifier and self.anomaly_detector:
                self.is_fitted = True
            else:
                print("Some models not found. Will use default models.")
                self._initialize_default_models()
                
        except Exception as e:
            print(f"Error loading models: {e}")
            self._initialize_default_models()
    
    def _initialize_default_models(self):
        """Initialize default untrained models"""
        self.power_loss_predictor = RandomForestRegressor(n_estimators=100, random_state=42)
        self.functionality_classifier = RandomForestClassifier(n_estimators=100, random_state=42)
        self.anomaly_detector = IsolationForest(contamination=0.1, random_state=42)
        self.is_fitted = False
    
    def train(self, df: pd.DataFrame):
        """Train all models on provided data"""
        print("Training PV Monitoring System...")
        
        # Encode categorical variables
        df = df.copy()
        df['Season_encoded'] = df['Season'].map(self.SEASON_MAP).fillna(0)
        df['Control_Scheme_encoded'] = df['Control_Scheme'].map(self.CONTROL_MAP).fillna(0)
        
        X = df[self.FEATURES]
        y_power_loss = df['Power_Loss_kW']
        
        # Train power loss predictor
        X_scaled = self.scaler.fit_transform(X)
        self.power_loss_predictor.fit(X_scaled, y_power_loss)
        print("Power loss predictor trained.")
        
        # Create functionality labels
        y_labels = self._create_functionality_labels(y_power_loss)
        y_encoded = self.label_encoder.fit_transform(y_labels)
        
        # Train functionality classifier
        self.functionality_classifier.fit(X_scaled, y_encoded)
        print("Functionality classifier trained.")
        
        # Train anomaly detector
        self.anomaly_detector.fit(X)
        print("Anomaly detector trained.")
        
        self.is_fitted = True
        return self
    
    def _create_functionality_labels(self, power_losses: pd.Series) -> List[str]:
        """Convert power losses to functionality labels"""
        labels = []
        for loss in power_losses:
            if loss <= 2.0:
                labels.append('Optimal')
            elif loss <= 2.5:
                labels.append('Normal')
            elif loss <= 2.8:
                labels.append('Degraded')
            else:
                labels.append('Critical')
        return labels
    
    def prepare_features(self, data: Dict[str, Any]) -> pd.DataFrame:
        """Prepare feature dataframe from input data"""
        # Map input fields to expected features
        feature_mapping = {
            'irradiance': 'Irradiance_Wm2',
            'temperature': 'Temperature_C',
            'bus_voltage_pu': 'Bus_Voltage_pu',
            'load_demand_mw': 'Load_Demand_MW',
            'reactive_power': 'Reactive_Power_Setting_MVAR',
            'voltage_setting': 'Voltage_Setting_pu',
            'vocr': 'VOCR',
        }
        
        # Create dataframe with defaults
        defaults = {
            'Irradiance_Wm2': 600.0,
            'Temperature_C': 35.0,
            'Bus_Voltage_pu': 1.0,
            'Load_Demand_MW': 0.5,
            'Reactive_Power_Setting_MVAR': 0.15,
            'Voltage_Setting_pu': 1.0,
            'VOCR': 0.03,
            'Season_encoded': 0,
            'Control_Scheme_encoded': 0
        }
        
        df = pd.DataFrame([defaults])
        
        # Map input data to features
        for input_key, feature_key in feature_mapping.items():
            if input_key in data and data[input_key] is not None:
                df[feature_key] = data[input_key]
        
        # Handle season
        if 'season' in data:
            df['Season_encoded'] = self.SEASON_MAP.get(data['season'], 0)
        
        # Handle control scheme
        if 'control_scheme' in data:
            df['Control_Scheme_encoded'] = self.CONTROL_MAP.get(data['control_scheme'], 0)
        
        return df[self.FEATURES]
    
    def analyze(self, data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Analyze inverter data and return comprehensive results
        
        Args:
            data: Dictionary with sensor readings
            
        Returns:
            Dictionary with analysis results
        """
        # Prepare features
        X = self.prepare_features(data)
        
        # Default results if not fitted
        if not self.is_fitted:
            return self._analytical_analysis(data)
        
        try:
            # Scale features for prediction
            X_scaled = self.scaler.transform(X)
            
            # Predict power loss
            power_loss = float(self.power_loss_predictor.predict(X_scaled)[0])
            
            # Classify functionality
            functionality_encoded = self.functionality_classifier.predict(X_scaled)[0]
            functionality = self.label_encoder.inverse_transform([functionality_encoded])[0]
            
            # Detect anomaly
            anomaly_pred = self.anomaly_detector.predict(X)
            is_anomaly = bool(anomaly_pred[0] == -1)
            
            # Determine fault status
            fault_detected = is_anomaly or functionality in ['Degraded', 'Critical']
            
            # Determine fault type
            if fault_detected:
                if power_loss > 2.8:
                    fault_type = 'efficiency_degradation'
                elif is_anomaly:
                    fault_type = 'anomaly_detected'
                else:
                    fault_type = 'performance_degradation'
            else:
                fault_type = None
            
            # Calculate confidence
            if hasattr(self.functionality_classifier, 'predict_proba'):
                proba = self.functionality_classifier.predict_proba(X_scaled)[0]
                confidence = float(max(proba) * 100)
            else:
                confidence = 85.0
            
            return {
                'fault_detected': fault_detected,
                'fault_type': fault_type,
                'predicted_power_loss': round(power_loss, 2),
                'functionality_status': functionality,
                'is_anomaly': is_anomaly,
                'confidence': round(confidence, 2),
                'severity': self._get_severity(functionality, is_anomaly),
                'ai_analysis': {
                    'root_cause': self._get_root_cause(functionality, power_loss, is_anomaly),
                    'contributing_factors': self._get_contributing_factors(data, functionality)
                },
                'suggested_actions': self._get_suggested_actions(functionality, is_anomaly)
            }
            
        except Exception as e:
            print(f"Analysis error: {e}")
            return self._analytical_analysis(data)
    
    def _analytical_analysis(self, data: Dict[str, Any]) -> Dict[str, Any]:
        """Fallback analytical analysis when models not available"""
        temp = data.get('temperature', 35)
        irradiance = data.get('irradiance', 600)
        
        # Simple rule-based analysis
        is_anomaly = temp > 55 or irradiance < 100
        
        if temp > 55:
            functionality = 'Critical'
            fault_type = 'temperature_anomaly'
        elif temp > 45:
            functionality = 'Degraded'
            fault_type = 'temperature_warning'
        else:
            functionality = 'Normal'
            fault_type = None
        
        return {
            'fault_detected': functionality in ['Degraded', 'Critical'],
            'fault_type': fault_type,
            'predicted_power_loss': 2.2,  # Average from training data
            'functionality_status': functionality,
            'is_anomaly': is_anomaly,
            'confidence': 70.0,
            'severity': self._get_severity(functionality, is_anomaly),
            'ai_analysis': {
                'root_cause': 'Analysis based on rule-based system (ML models not loaded)',
                'contributing_factors': []
            },
            'suggested_actions': self._get_suggested_actions(functionality, is_anomaly)
        }
    
    def _get_severity(self, functionality: str, is_anomaly: bool) -> str:
        """Determine severity level"""
        if functionality == 'Critical' or is_anomaly:
            return 'critical'
        elif functionality == 'Degraded':
            return 'high'
        elif functionality == 'Normal':
            return 'medium'
        return 'low'
    
    def _get_root_cause(self, functionality: str, power_loss: float, is_anomaly: bool) -> str:
        """Generate root cause description"""
        if functionality == 'Critical':
            return f"Critical performance degradation detected. Power loss of {power_loss:.2f} kW exceeds safe thresholds."
        elif functionality == 'Degraded':
            return f"Performance degradation detected. Power loss of {power_loss:.2f} kW indicates efficiency issues."
        elif is_anomaly:
            return "Anomalous operating conditions detected. Sensor readings deviate from normal patterns."
        return "System operating within normal parameters."
    
    def _get_contributing_factors(self, data: Dict[str, Any], functionality: str) -> List[str]:
        """Identify contributing factors"""
        factors = []
        
        temp = data.get('temperature', 35)
        irradiance = data.get('irradiance', 600)
        
        if temp > 45:
            factors.append(f"Elevated temperature ({temp}°C)")
        if irradiance < 400:
            factors.append(f"Low irradiance ({irradiance} W/m²)")
        if irradiance > 900:
            factors.append(f"High irradiance stress ({irradiance} W/m²)")
        
        if functionality in ['Degraded', 'Critical']:
            factors.append("Accumulated wear on components")
        
        return factors
    
    def _get_suggested_actions(self, functionality: str, is_anomaly: bool) -> List[Dict[str, str]]:
        """Generate suggested maintenance actions"""
        actions = []
        
        if functionality == 'Critical':
            actions.append({
                'priority': 'high',
                'action': 'Immediate inspection required',
                'estimatedTime': '60 minutes'
            })
            actions.append({
                'priority': 'high',
                'action': 'Check all electrical connections',
                'estimatedTime': '30 minutes'
            })
        elif functionality == 'Degraded':
            actions.append({
                'priority': 'medium',
                'action': 'Schedule maintenance within 48 hours',
                'estimatedTime': '45 minutes'
            })
            actions.append({
                'priority': 'medium',
                'action': 'Review performance logs',
                'estimatedTime': '15 minutes'
            })
        
        if is_anomaly:
            actions.append({
                'priority': 'high',
                'action': 'Investigate anomalous readings',
                'estimatedTime': '30 minutes'
            })
        
        if not actions:
            actions.append({
                'priority': 'low',
                'action': 'Continue routine monitoring',
                'estimatedTime': '5 minutes'
            })
        
        return actions
    
    def save_models(self, output_dir: str):
        """Save trained models to disk"""
        os.makedirs(output_dir, exist_ok=True)
        
        with open(os.path.join(output_dir, 'power_loss_predictor.pickle'), 'wb') as f:
            pickle.dump(self.power_loss_predictor, f)
        
        with open(os.path.join(output_dir, 'functionality_classifier.pickle'), 'wb') as f:
            pickle.dump(self.functionality_classifier, f)
        
        with open(os.path.join(output_dir, 'anomaly_detector.pickle'), 'wb') as f:
            pickle.dump(self.anomaly_detector, f)
        
        with open(os.path.join(output_dir, 'scaler.pickle'), 'wb') as f:
            pickle.dump(self.scaler, f)
        
        with open(os.path.join(output_dir, 'label_encoder.pickle'), 'wb') as f:
            pickle.dump(self.label_encoder, f)
        
        print(f"Models saved to {output_dir}")
