"""
Export trained models from the AI models folder notebooks
Run this script to extract and save the trained models for use in the Python AI service

Usage:
    cd c:\xampp\htdocs\pv\python-ai-service
    python training/export_models_from_notebooks.py
"""

import os
import sys
import pickle
import pandas as pd
import numpy as np
from pathlib import Path

# Add parent directory to path
sys.path.insert(0, str(Path(__file__).parent.parent))

from sklearn.model_selection import train_test_split
from sklearn.preprocessing import StandardScaler, LabelEncoder
from sklearn.ensemble import RandomForestClassifier, RandomForestRegressor, IsolationForest
from sklearn.metrics import mean_absolute_error, classification_report

# Paths
AI_MODELS_DIR = Path(__file__).parent.parent.parent / "AI models"
OUTPUT_DIR = Path(__file__).parent.parent / "trained_models"

def train_xgboost_fault_predictor():
    """Train XGBoost fault predictor from Fault_Prediction.xlsx"""
    print("\n" + "="*60)
    print("TRAINING XGBOOST FAULT PREDICTOR")
    print("="*60)
    
    data_path = AI_MODELS_DIR / "Fault_Prediction.xlsx"
    
    if not data_path.exists():
        print(f"Data file not found: {data_path}")
        return None
    
    # Load data
    df = pd.read_excel(data_path)
    print(f"Loaded {len(df)} records from {data_path}")
    
    # Preprocess
    class_dict = {
        "Solar Panel": 0,
        "Charge Controller": 1,
        "Inverter": 2,
        "Battery": 3
    }
    
    df['DateTime'] = pd.to_datetime(df['DateTime'])
    df['component_type'] = df['component_type'].apply(lambda x: class_dict.get(x, 0))
    df = df.sort_values(by='DateTime')
    
    # Remove unnecessary columns
    if 'DateTime' in df.columns:
        del df['DateTime']
    if 'fault_status' in df.columns:
        del df['fault_status']
    
    df = df.dropna()
    
    X = df.drop(['predicted_time_to_fault (days)'], axis=1)
    Y = df['predicted_time_to_fault (days)']
    
    print(f"Features: {list(X.columns)}")
    print(f"Training samples: {len(X)}")
    
    # Train XGBoost
    try:
        from xgboost import XGBRegressor
        
        model = XGBRegressor(n_estimators=15, max_depth=10)
        model.fit(X, Y)
        
        # Evaluate
        X_train, X_test, y_train, y_test = train_test_split(X, Y, test_size=0.2, random_state=42)
        y_pred = model.predict(X_test)
        mae = mean_absolute_error(y_test, y_pred)
        
        print(f"MAE: {mae:.2f} days")
        
        # Save model
        output_path = OUTPUT_DIR / "fault_prediction.pickle"
        with open(output_path, 'wb') as f:
            pickle.dump(model, f)
        print(f"Saved to {output_path}")
        
        return model
        
    except ImportError:
        print("XGBoost not installed. Run: pip install xgboost")
        return None

def train_pv_monitoring_system():
    """Train PV Inverter Monitoring System from PV_Inverter_Control_Dataset.csv"""
    print("\n" + "="*60)
    print("TRAINING PV INVERTER MONITORING SYSTEM")
    print("="*60)
    
    data_path = AI_MODELS_DIR / "PV_Inverter_Control_Dataset.csv"
    
    if not data_path.exists():
        print(f"Data file not found: {data_path}")
        return None
    
    # Load data
    df = pd.read_csv(data_path)
    print(f"Loaded {len(df)} records from {data_path}")
    
    # Encode categorical variables
    le_season = LabelEncoder()
    le_control = LabelEncoder()
    df['Season_encoded'] = le_season.fit_transform(df['Season'])
    df['Control_Scheme_encoded'] = le_control.fit_transform(df['Control_Scheme'])
    
    # Define features
    features = [
        'Irradiance_Wm2', 'Temperature_C', 'Bus_Voltage_pu',
        'Load_Demand_MW', 'Reactive_Power_Setting_MVAR',
        'Voltage_Setting_pu', 'VOCR', 'Season_encoded',
        'Control_Scheme_encoded'
    ]
    
    X = df[features]
    y_power_loss = df['Power_Loss_kW']
    
    # Scale features
    scaler = StandardScaler()
    X_scaled = scaler.fit_transform(X)
    
    # 1. Train Power Loss Predictor
    print("\n1. Training Power Loss Predictor...")
    power_loss_model = RandomForestRegressor(n_estimators=100, random_state=42, n_jobs=-1)
    
    X_train, X_test, y_train, y_test = train_test_split(X_scaled, y_power_loss, test_size=0.2, random_state=42)
    power_loss_model.fit(X_train, y_train)
    
    y_pred = power_loss_model.predict(X_test)
    mae = mean_absolute_error(y_test, y_pred)
    print(f"Power Loss Prediction MAE: {mae:.2f} kW")
    
    # 2. Train Functionality Classifier
    print("\n2. Training Functionality Classifier...")
    
    def create_labels(power_losses):
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
    
    y_labels = create_labels(y_power_loss)
    label_encoder = LabelEncoder()
    y_encoded = label_encoder.fit_transform(y_labels)
    
    X_train, X_test, y_train, y_test = train_test_split(X_scaled, y_encoded, test_size=0.2, random_state=42)
    
    classifier = RandomForestClassifier(n_estimators=100, random_state=42, n_jobs=-1)
    classifier.fit(X_train, y_train)
    
    y_pred = classifier.predict(X_test)
    print(f"Classification Report:\n{classification_report(y_test, y_pred, target_names=label_encoder.classes_)}")
    
    # 3. Train Anomaly Detector
    print("\n3. Training Anomaly Detector...")
    anomaly_detector = IsolationForest(contamination=0.1, random_state=42)
    anomaly_detector.fit(X)
    
    anomalies = (anomaly_detector.predict(X) == -1).sum()
    print(f"Detected {anomalies} anomalies ({anomalies/len(X)*100:.1f}%)")
    
    # Save all models
    print("\n4. Saving models...")
    
    with open(OUTPUT_DIR / "power_loss_predictor.pickle", 'wb') as f:
        pickle.dump(power_loss_model, f)
    print(f"Saved power_loss_predictor.pickle")
    
    with open(OUTPUT_DIR / "functionality_classifier.pickle", 'wb') as f:
        pickle.dump(classifier, f)
    print(f"Saved functionality_classifier.pickle")
    
    with open(OUTPUT_DIR / "anomaly_detector.pickle", 'wb') as f:
        pickle.dump(anomaly_detector, f)
    print(f"Saved anomaly_detector.pickle")
    
    with open(OUTPUT_DIR / "scaler.pickle", 'wb') as f:
        pickle.dump(scaler, f)
    print(f"Saved scaler.pickle")
    
    with open(OUTPUT_DIR / "label_encoder.pickle", 'wb') as f:
        pickle.dump(label_encoder, f)
    print(f"Saved label_encoder.pickle")
    
    # Save feature importance
    importance = pd.DataFrame({
        'feature': features,
        'importance': power_loss_model.feature_importances_
    }).sort_values('importance', ascending=False)
    
    print("\nTop 5 Factors Affecting Power Loss:")
    for i, row in importance.head().iterrows():
        print(f"  {row['feature']}: {row['importance']:.3f}")
    
    return {
        'power_loss_predictor': power_loss_model,
        'functionality_classifier': classifier,
        'anomaly_detector': anomaly_detector,
        'scaler': scaler,
        'label_encoder': label_encoder
    }

def main():
    """Main function to export all models"""
    print("="*60)
    print("EXPORTING TRAINED MODELS FROM AI MODELS FOLDER")
    print("="*60)
    
    # Create output directory
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    print(f"\nOutput directory: {OUTPUT_DIR}")
    print(f"AI Models directory: {AI_MODELS_DIR}")
    
    # Check if AI models folder exists
    if not AI_MODELS_DIR.exists():
        print(f"\nERROR: AI models folder not found at {AI_MODELS_DIR}")
        return
    
    # List available files
    print(f"\nAvailable files in AI models folder:")
    for f in AI_MODELS_DIR.iterdir():
        print(f"  - {f.name}")
    
    # Train and export models
    train_xgboost_fault_predictor()
    train_pv_monitoring_system()
    
    print("\n" + "="*60)
    print("EXPORT COMPLETE!")
    print("="*60)
    print(f"\nModels saved to: {OUTPUT_DIR}")
    print("\nTo use these models, start the Python AI service:")
    print("  uvicorn app.main:app --reload --host 0.0.0.0 --port 8001")

if __name__ == "__main__":
    main()
