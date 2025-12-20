"""
Train CNN Image Classifier for Solar Panel Fault Detection
Uses the image dataset from pv-main/data/

Categories:
- Clean (healthy)
- Bird-drop (contamination)
- Dusty (dust accumulation)
- Electrical-damage (hotspots, burns)
- Physical-Damage (cracks, breaks)
- Snow-Covered (snow coverage)
"""

import os
import numpy as np
from pathlib import Path

# TensorFlow imports
import tensorflow as tf
from tensorflow.keras.preprocessing.image import ImageDataGenerator
from tensorflow.keras.models import Sequential
from tensorflow.keras.layers import Conv2D, MaxPooling2D, Flatten, Dense, Dropout, BatchNormalization
from tensorflow.keras.optimizers import Adam
from tensorflow.keras.callbacks import EarlyStopping, ModelCheckpoint, ReduceLROnPlateau

# Configuration
DATA_DIR = Path("../../pv-main/data")  # Relative to this script
MODEL_OUTPUT_DIR = Path("../trained_models")
IMG_SIZE = (224, 224)
BATCH_SIZE = 32
EPOCHS = 50

# Class mapping
CLASS_NAMES = ['Clean', 'Bird-drop', 'Dusty', 'Electrical-damage', 'Physical-Damage', 'Snow-Covered']

def prepare_data():
    """Prepare image data generators for training and validation"""
    
    # Data augmentation for training
    train_datagen = ImageDataGenerator(
        rescale=1./255,
        rotation_range=20,
        width_shift_range=0.2,
        height_shift_range=0.2,
        shear_range=0.2,
        zoom_range=0.2,
        horizontal_flip=True,
        vertical_flip=True,
        fill_mode='nearest',
        validation_split=0.2  # 20% for validation
    )
    
    # Training data
    train_generator = train_datagen.flow_from_directory(
        DATA_DIR,
        target_size=IMG_SIZE,
        batch_size=BATCH_SIZE,
        class_mode='categorical',
        subset='training',
        classes=CLASS_NAMES
    )
    
    # Validation data
    validation_generator = train_datagen.flow_from_directory(
        DATA_DIR,
        target_size=IMG_SIZE,
        batch_size=BATCH_SIZE,
        class_mode='categorical',
        subset='validation',
        classes=CLASS_NAMES
    )
    
    return train_generator, validation_generator

def build_model(num_classes):
    """Build CNN model for image classification"""
    
    model = Sequential([
        # First Conv Block
        Conv2D(32, (3, 3), activation='relu', input_shape=(IMG_SIZE[0], IMG_SIZE[1], 3)),
        BatchNormalization(),
        MaxPooling2D(pool_size=(2, 2)),
        
        # Second Conv Block
        Conv2D(64, (3, 3), activation='relu'),
        BatchNormalization(),
        MaxPooling2D(pool_size=(2, 2)),
        
        # Third Conv Block
        Conv2D(128, (3, 3), activation='relu'),
        BatchNormalization(),
        MaxPooling2D(pool_size=(2, 2)),
        
        # Fourth Conv Block
        Conv2D(256, (3, 3), activation='relu'),
        BatchNormalization(),
        MaxPooling2D(pool_size=(2, 2)),
        
        # Flatten and Dense layers
        Flatten(),
        Dense(512, activation='relu'),
        Dropout(0.5),
        Dense(256, activation='relu'),
        Dropout(0.3),
        Dense(num_classes, activation='softmax')
    ])
    
    model.compile(
        optimizer=Adam(learning_rate=0.001),
        loss='categorical_crossentropy',
        metrics=['accuracy']
    )
    
    return model

def train():
    """Main training function"""
    
    print("=" * 60)
    print("SOLAR PANEL IMAGE CLASSIFIER TRAINING")
    print("=" * 60)
    
    # Prepare data
    print("\n📁 Loading image data...")
    train_gen, val_gen = prepare_data()
    
    print(f"Training samples: {train_gen.samples}")
    print(f"Validation samples: {val_gen.samples}")
    print(f"Classes: {train_gen.class_indices}")
    
    # Build model
    print("\n🏗️ Building CNN model...")
    model = build_model(num_classes=len(CLASS_NAMES))
    model.summary()
    
    # Callbacks
    callbacks = [
        EarlyStopping(
            monitor='val_loss',
            patience=10,
            restore_best_weights=True
        ),
        ModelCheckpoint(
            MODEL_OUTPUT_DIR / 'cnn_model_best.h5',
            monitor='val_accuracy',
            save_best_only=True
        ),
        ReduceLROnPlateau(
            monitor='val_loss',
            factor=0.2,
            patience=5,
            min_lr=0.00001
        )
    ]
    
    # Create output directory
    MODEL_OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    
    # Train
    print("\n🚀 Starting training...")
    history = model.fit(
        train_gen,
        epochs=EPOCHS,
        validation_data=val_gen,
        callbacks=callbacks
    )
    
    # Save final model
    model.save(MODEL_OUTPUT_DIR / 'cnn_model.h5')
    print(f"\n✅ Model saved to {MODEL_OUTPUT_DIR / 'cnn_model.h5'}")
    
    # Save class names mapping
    import json
    with open(MODEL_OUTPUT_DIR / 'class_names.json', 'w') as f:
        json.dump(train_gen.class_indices, f)
    
    # Print final metrics
    print("\n📊 Training Results:")
    print(f"Final Training Accuracy: {history.history['accuracy'][-1]:.4f}")
    print(f"Final Validation Accuracy: {history.history['val_accuracy'][-1]:.4f}")
    
    return model, history

if __name__ == "__main__":
    train()
