import numpy as np
from PIL import Image, ImageEnhance, ImageFilter
import os
import random

# Modified Generate Synthetic Data with Variations
def generate_solar_panel_image(class_name, size=(224, 224), num_samples=50):
    images = []
    labels = []
    for i in range(num_samples):
        img = np.ones((size[0], size[1])) * 0.8  # Base grayish panel
        
        if class_name == 'OK':
            # Add grid lines for clean panel
            for row in range(0, size[0], 20):
                img[row:row+2, :] = 0.9
            for col in range(0, size[1], 20):
                img[:, col:col+2] = 0.9
        else:  # DEFECT
            # Add cracks (Bresenham lines) - vary starting points
            for _ in range(3):  # 3 cracks
                x1, y1 = np.random.randint(0, size[1]), np.random.randint(0, size[0])
                x2, y2 = np.random.randint(0, size[1]), np.random.randint(0, size[0])
                dx, dy = abs(x2 - x1), abs(y2 - y1)
                sx, sy = np.sign(x2 - x1), np.sign(y2 - y1)
                err = dx - dy
                x, y = x1, y1
                while True:
                    img[y, x] = 0.2  # Dark crack
                    if x == x2 and y == y2:
                        break
                    e2 = 2 * err
                    if e2 > -dy:
                        err -= dy
                        x += sx
                    if e2 < dx:
                        err += dx
                        y += sy
            # Add dirt spots - random sizes/positions
            for _ in range(random.randint(15, 25)):  # Vary number of spots
                cx, cy = np.random.randint(10, size[1]-10), np.random.randint(10, size[0]-10)
                radius = np.random.randint(2, 10)
                y_grid, x_grid = np.ogrid[:size[0], :size[1]]
                mask = (x_grid - cx)**2 + (y_grid - cy)**2 <= radius**2
                img[mask] = np.random.uniform(0.1, 0.4)  # Vary darkness
        
        # Convert to RGB
        img_rgb = np.stack([img] * 3, axis=-1).astype(np.float32)
        
        # Add variations for uniqueness: Gaussian noise, slight rotation, brightness tweak
        img_rgb += np.random.normal(0, 0.02, img_rgb.shape)  # Noise
        img_rgb = np.clip(img_rgb, 0, 1)
        img_pil = Image.fromarray((img_rgb * 255).astype(np.uint8))
        
        # Random rotation (small angle)
        angle = random.uniform(-5, 5)
        img_pil = img_pil.rotate(angle, expand=False)
        
        # Random brightness/contrast
        enhancer = ImageEnhance.Brightness(img_pil)
        img_pil = enhancer.enhance(random.uniform(0.9, 1.1))
        enhancer = ImageEnhance.Contrast(img_pil)
        img_pil = enhancer.enhance(random.uniform(0.95, 1.05))
        
        # Slight blur for realism
        img_pil = img_pil.filter(ImageFilter.GaussianBlur(radius=random.uniform(0, 0.5)))
        
        # Save to folder
        folder = f"{class_name.lower()}_varied/"
        os.makedirs(folder, exist_ok=True)
        filename = f"{folder}sample_{class_name.lower()}_{i:03d}.png"
        img_pil.save(filename)
        
        images.append(img_pil)
        labels.append(0 if class_name == 'OK' else 1)
        print(f"Generated varied: {filename}")
    
    return images, labels

# Generate varied datasets
print("Generating varied OK images...")
ok_images, ok_labels = generate_solar_panel_image('OK', num_samples=50)

print("\nGenerating varied DEFECT images...")
defect_images, defect_labels = generate_solar_panel_image('DEFECT', num_samples=50)

print(f"\nVaried dataset ready! Check 'ok_varied/' (50 images) and 'defect_varied/' (50 images) folders.")
