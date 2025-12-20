import numpy as np
import pandas as pd
from datetime import datetime, timedelta
import random

# Generate Synthetic Sensor Data for SolarVision
# Sensors: Temperature (°C from DHT22), Current (A from INA219), Irradiance (lux from BH1750)
# 100 samples, 10s intervals, starting Nov 25, 2025 10:00
# Realistic ranges: Temp 20-40°C, Current 0-5A (scales with light), Irradiance 0-100k lux

def generate_sensor_data(num_samples=100, start_time="2025-11-25 10:00:00"):
    # Base time
    base_time = datetime.strptime(start_time, "%Y-%m-%d %H:%M:%S")
    
    # Generate timestamps
    timestamps = [base_time + timedelta(seconds=10 * i) for i in range(num_samples)]
    timestamps_str = [ts.strftime("%Y-%m-%d %H:%M:%S") for ts in timestamps]
    
    # Simulate diurnal cycle: higher light/current midday, temp follows light + lag
    time_of_day = np.array([t.hour + t.minute/60 for t in timestamps])
    irradiance_base = 50000 * np.sin(2 * np.pi * (time_of_day - 6) / 24)  # Peak at noon
    irradiance = np.maximum(0, irradiance_base + np.random.normal(0, 20000, num_samples))
    irradiance = np.clip(irradiance, 0, 100000)  # Full sun ~100k lux
    
    # Current scales with irradiance (efficiency ~0.15-0.2 A per 1000 lux, noise)
    current = 0.00015 * irradiance + np.random.normal(0, 0.5, num_samples)
    current = np.clip(current, 0, 5.0)
    
    # Temp: Base 25°C + 0.0001 * irradiance + diurnal/heat lag
    temp = 25 + 0.0001 * irradiance + 5 * np.sin(2 * np.pi * (time_of_day - 6) / 24) + np.random.normal(0, 2, num_samples)
    temp = np.clip(temp, 20, 40)
    
    # Create DataFrame
    df = pd.DataFrame({
        'timestamp': timestamps_str,
        'temperature_c': np.round(temp, 1),
        'current_a': np.round(current, 2),
        'irradiance_lux': np.round(irradiance, 0)
    })
    
    # Save to CSV
    filename = 'sensor_data.csv'
    df.to_csv(filename, index=False)
    print(f"Generated {num_samples} samples in {filename}")
    print(df.head(10))  # Preview first 10 rows
    return df

# Run generation
if __name__ == "__main__":
    generate_sensor_data(100)  # Generate 100 samples
