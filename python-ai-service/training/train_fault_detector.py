"""
Train Fault Detection Model using PV Inverter Control Dataset
Uses sensor data to detect anomalies and predict faults

Features used:
- Irradiance_Wm2
- Temperature_C
- Bus_Voltage_pu
- Load_Demand_MW
- Reactive_Power_Setting_MVAR
- Voltage_Setting_pu
- VOCR
- Power_Loss_kW

Target: Fault detection based on Power_Loss_kW threshold
"""

import pandas as pd
import numpy as np
from pathlib import Path
import joblib
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import StandardScaler, LabelEncoder
from sklearn.ensemble import RandomForestClassifier, IsolationForest
from sklearn.metrics import classification_report, confusion_matrix, accuracy_score
import warnings
warnings.filterwarnings('ignore')

# Configuration
DATA_PATH = Path("../../pv-main/data/PV_Inverter_Control_Dataset.csv")
MODEL_OUTPUT_DIR = Path("../trained_models")

# Thresholds for fault classification
POWER_LOSS_THRESHOLD = 2.5  # kW - above this is considered a fault
TEMPERATURE_THRESHOLD = 40  # °C - high temperature warning
VOLTAGE_THRESHOLD_LOW = 0.95  # pu - low voltage
VOLTAGE_THRESHOLD_HIGH = 1.05  # pu - high voltage

def load_and_prepare_data():
    """Load and prepare the dataset"""
    
    print("📁 Loading dataset...")
    df = pd.read_csv(DATA_PATH)
    
    print(f"Dataset shape: {df.shape}")
    print(f"Columns: {list(df.columns)}")
    print(f"\nFirst 5 rows:\n{df.head()}")
    
    return df

def create_fault_labels(df):
    """Create fault labels based on thresholds"""
    
    print("\n🏷️ Creating fault labels...")
    
    # Initialize fault column
    df['fault'] = 0
    df['fault_type'] = 'normal'
    
    # High power loss = efficiency degradation
    mask_power_loss = df['Power_Loss_kW'] > POWER_LOSS_THRESHOLD
    df.loc[mask_power_loss, 'fault'] = 1
    df.loc[mask_power_loss, 'fault_type'] = 'efficiency_degradation'
    
    # High temperature = temperature anomaly
    mask_temp = df['Temperature_C'] > TEMPERATURE_THRESHOLD
    df.loc[mask_temp, 'fault'] = 1
    df.loc[mask_temp & (df['fault_type'] == 'normal'), 'fault_type'] = 'temperature_anomaly'
    
    # Voltage anomaly
    mask_voltage = (df['Bus_Voltage_pu'] < VOLTAGE_THRESHOLD_LOW) | (df['Bus_Voltage_pu'] > VOLTAGE_THRESHOLD_HIGH)
    df.loc[mask_voltage, 'fault'] = 1
    df.loc[mask_voltage & (df['fault_type'] == 'normal'), 'fault_type'] = 'voltage_drop'
    
    # Print distribution
    print(f"\nFault distribution:")
    print(df['fault'].value_counts())
    print(f"\nFault types:")
    print(df['fault_type'].value_counts())
    
    return df

def prepare_features(df):
    """Prepare features for training"""
    
    print("\n🔧 Preparing features...")
    
    # Encode season
    le_season = LabelEncoder()
    df['Season_encoded'] = le_season.fit_transform(df['Season'])
    
    # Encode inverter ID
    le_inverter = LabelEncoder()
    df['Inverter_encoded'] = le_inverter.fit_transform(df['Inverter_ID'])
    
    # Select features
    feature_columns = [
        'Irradiance_Wm2',
        'Temperature_C',
        'Bus_Voltage_pu',
        'Load_Demand_MW',
        'Reactive_Power_Setting_MVAR',
        'Voltage_Setting_pu',
        'VOCR',
        'Power_Loss_kW',
        'Season_encoded',
        'Inverter_encoded'
    ]
    
    X = df[feature_columns]
    y = df['fault']
    
    print(f"Feature shape: {X.shape}")
    print(f"Features: {feature_columns}")
    
    return X, y, feature_columns, le_season, le_inverter

def train_fault_classifier(X, y):
    """Train Random Forest classifier for fault detection"""
    
    print("\n🚀 Training Fault Detection Model...")
    
    # Split data
    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.2, random_state=42, stratify=y
    )
    
    print(f"Training samples: {len(X_train)}")
    print(f"Test samples: {len(X_test)}")
    
    # Scale features
    scaler = StandardScaler()
    X_train_scaled = scaler.fit_transform(X_train)
    X_test_scaled = scaler.transform(X_test)
    
    # Train Random Forest
    model = RandomForestClassifier(
        n_estimators=100,
        max_depth=10,
        min_samples_split=5,
        min_samples_leaf=2,
        random_state=42,
        n_jobs=-1
    )
    
    model.fit(X_train_scaled, y_train)
    
    # Evaluate
    y_pred = model.predict(X_test_scaled)
    
    print("\n📊 Model Evaluation:")
    print(f"Accuracy: {accuracy_score(y_test, y_pred):.4f}")
    print(f"\nClassification Report:\n{classification_report(y_test, y_pred)}")
    print(f"\nConfusion Matrix:\n{confusion_matrix(y_test, y_pred)}")
    
    # Feature importance
    print("\n🔍 Feature Importance:")
    importance = pd.DataFrame({
        'feature': X.columns,
        'importance': model.feature_importances_
    }).sort_values('importance', ascending=False)
    print(importance)
    
    return model, scaler

def train_anomaly_detector(X):
    """Train Isolation Forest for anomaly detection"""
    
    print("\n🚀 Training Anomaly Detection Model (Isolation Forest)...")
    
    # Scale features
    scaler = StandardScaler()
    X_scaled = scaler.fit_transform(X)
    
    # Train Isolation Forest
    model = IsolationForest(
        n_estimators=100,
        contamination=0.1,  # Expected proportion of anomalies
        random_state=42,
        n_jobs=-1
    )
    
    model.fit(X_scaled)
    
    # Predict anomalies
    predictions = model.predict(X_scaled)
    anomalies = (predictions == -1).sum()
    
    print(f"Detected anomalies: {anomalies} ({anomalies/len(X)*100:.2f}%)")
    
    return model, scaler

def save_models(fault_model, fault_scaler, anomaly_model, anomaly_scaler, feature_columns):
    """Save trained models"""
    
    MODEL_OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    
    # Save fault detection model
    joblib.dump(fault_model, MODEL_OUTPUT_DIR / 'fault_model.pkl')
    joblib.dump(fault_scaler, MODEL_OUTPUT_DIR / 'fault_scaler.pkl')
    
    # Save anomaly detection model
    joblib.dump(anomaly_model, MODEL_OUTPUT_DIR / 'anomaly_model.pkl')
    joblib.dump(anomaly_scaler, MODEL_OUTPUT_DIR / 'anomaly_scaler.pkl')
    
    # Save feature columns
    import json
    with open(MODEL_OUTPUT_DIR / 'feature_columns.json', 'w') as f:
        json.dump(feature_columns, f)
    
    print(f"\n✅ Models saved to {MODEL_OUTPUT_DIR}")

def main():
    """Main training pipeline"""
    
    print("=" * 60)
    print("FAULT DETECTION MODEL TRAINING")
    print("=" * 60)
    
    # Load data
    df = load_and_prepare_data()
    
    # Create fault labels
    df = create_fault_labels(df)
    
    # Prepare features
    X, y, feature_columns, le_season, le_inverter = prepare_features(df)
    
    # Train fault classifier
    fault_model, fault_scaler = train_fault_classifier(X, y)
    
    # Train anomaly detector
    anomaly_model, anomaly_scaler = train_anomaly_detector(X)
    
    # Save models
    save_models(fault_model, fault_scaler, anomaly_model, anomaly_scaler, feature_columns)
    
    print("\n" + "=" * 60)
    print("TRAINING COMPLETE!")
    print("=" * 60)

if __name__ == "__main__":
    main()
