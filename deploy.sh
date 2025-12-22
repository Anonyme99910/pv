#!/bin/bash

# SOMA PV - Complete Deployment Script
# Server: 45.93.139.14
# Database: soma / soma / Tabarka2016@@00

set -e

echo "=========================================="
echo "SOMA PV - Production Deployment"
echo "=========================================="

# Variables
DOMAIN="45.93.139.14"
DB_NAME="soma"
DB_USER="soma"
DB_PASS="Tabarka2016@@00"
APP_DIR="/var/www/soma-pv"
REPO_URL="https://github.com/Anonyme99910/pv.git"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# ==========================================
# STEP 1: System Update & Dependencies
# ==========================================
log_info "Step 1: Updating system and installing dependencies..."

apt update && apt upgrade -y

# Install essential packages
apt install -y \
    curl \
    wget \
    git \
    unzip \
    software-properties-common \
    apt-transport-https \
    ca-certificates \
    gnupg \
    lsb-release

# ==========================================
# STEP 2: Install PHP 8.2
# ==========================================
log_info "Step 2: Installing PHP 8.2..."

add-apt-repository ppa:ondrej/php -y
apt update

apt install -y \
    php8.2 \
    php8.2-fpm \
    php8.2-cli \
    php8.2-common \
    php8.2-mysql \
    php8.2-zip \
    php8.2-gd \
    php8.2-mbstring \
    php8.2-curl \
    php8.2-xml \
    php8.2-bcmath \
    php8.2-intl \
    php8.2-readline \
    php8.2-opcache

# ==========================================
# STEP 3: Install Composer
# ==========================================
log_info "Step 3: Installing Composer..."

curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# ==========================================
# STEP 4: Install Node.js 20
# ==========================================
log_info "Step 4: Installing Node.js 20..."

curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# ==========================================
# STEP 5: Install Python 3.11
# ==========================================
log_info "Step 5: Installing Python 3.11..."

add-apt-repository ppa:deadsnakes/ppa -y
apt update
apt install -y python3.11 python3.11-venv python3.11-dev python3-pip

# Set Python 3.11 as default
update-alternatives --install /usr/bin/python3 python3 /usr/bin/python3.11 1

# ==========================================
# STEP 6: Install Nginx
# ==========================================
log_info "Step 6: Installing Nginx..."

apt install -y nginx
systemctl enable nginx
systemctl start nginx

# ==========================================
# STEP 7: Install MySQL Client (if needed)
# ==========================================
log_info "Step 7: Installing MySQL client..."

apt install -y mysql-client

# ==========================================
# STEP 8: Clone Repository
# ==========================================
log_info "Step 8: Cloning repository..."

rm -rf $APP_DIR
mkdir -p $APP_DIR
git clone $REPO_URL $APP_DIR
cd $APP_DIR

# ==========================================
# STEP 9: Setup Laravel Backend
# ==========================================
log_info "Step 9: Setting up Laravel backend..."

cd $APP_DIR/pv-backend

# Install dependencies
composer install --no-dev --optimize-autoloader

# Create .env file
cat > .env << EOF
APP_NAME="SOMA PV"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://$DOMAIN

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=$DB_NAME
DB_USERNAME=$DB_USER
DB_PASSWORD=$DB_PASS

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

AI_SERVICE_URL=http://127.0.0.1:8001
AI_SERVICE_TIMEOUT=30

SANCTUM_STATEFUL_DOMAINS=$DOMAIN
SESSION_DOMAIN=$DOMAIN
EOF

# Generate application key
php artisan key:generate --force

# Run migrations and seed
php artisan migrate --force
php artisan db:seed --force

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
chown -R www-data:www-data $APP_DIR/pv-backend
chmod -R 755 $APP_DIR/pv-backend
chmod -R 775 $APP_DIR/pv-backend/storage
chmod -R 775 $APP_DIR/pv-backend/bootstrap/cache

# ==========================================
# STEP 10: Build Vue.js Frontend
# ==========================================
log_info "Step 10: Building Vue.js frontend..."

cd $APP_DIR/pv-main

# Create production .env
cat > .env << EOF
VITE_API_URL=http://$DOMAIN/api
VITE_APP_TITLE=SOMA PV Monitoring
EOF

# Install dependencies and build
npm ci
npm run build

# Set permissions
chown -R www-data:www-data $APP_DIR/pv-main/dist

# ==========================================
# STEP 11: Setup Python AI Service
# ==========================================
log_info "Step 11: Setting up Python AI service..."

cd $APP_DIR/python-ai-service

# Create virtual environment
python3 -m venv venv
source venv/bin/activate

# Install dependencies
pip install --upgrade pip
pip install -r requirements.txt

# Create .env
cat > .env << EOF
LARAVEL_WEBHOOK_URL=http://127.0.0.1:8000/api/webhooks/ai
LARAVEL_API_URL=http://127.0.0.1:8000/api
AI_SERVICE_PORT=8001
EOF

deactivate

# Create systemd service for AI
cat > /etc/systemd/system/soma-ai.service << EOF
[Unit]
Description=SOMA PV AI Service
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=$APP_DIR/python-ai-service
Environment="PATH=$APP_DIR/python-ai-service/venv/bin"
ExecStart=$APP_DIR/python-ai-service/venv/bin/uvicorn app.main:app --host 127.0.0.1 --port 8001
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable soma-ai
systemctl start soma-ai

# ==========================================
# STEP 12: Configure Nginx
# ==========================================
log_info "Step 12: Configuring Nginx..."

cat > /etc/nginx/sites-available/soma-pv << 'EOF'
server {
    listen 80;
    server_name 45.93.139.14;
    
    root /var/www/soma-pv/pv-main/dist;
    index index.html;

    # Frontend - Vue.js SPA
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Backend API - Laravel
    location /api {
        alias /var/www/soma-pv/pv-backend/public;
        try_files $uri $uri/ @laravel;

        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
            fastcgi_param SCRIPT_FILENAME $request_filename;
            include fastcgi_params;
        }
    }

    location @laravel {
        rewrite /api/(.*)$ /api/index.php?/$1 last;
    }

    # AI Service Proxy
    location /ai/ {
        proxy_pass http://127.0.0.1:8001/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
        proxy_read_timeout 300;
    }

    # Static files caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml application/javascript application/json;

    # Logs
    access_log /var/log/nginx/soma-pv-access.log;
    error_log /var/log/nginx/soma-pv-error.log;
}
EOF

# Enable site
ln -sf /etc/nginx/sites-available/soma-pv /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test and reload Nginx
nginx -t
systemctl reload nginx

# ==========================================
# STEP 13: Configure PHP-FPM
# ==========================================
log_info "Step 13: Configuring PHP-FPM..."

# Increase PHP limits
sed -i 's/upload_max_filesize = .*/upload_max_filesize = 64M/' /etc/php/8.2/fpm/php.ini
sed -i 's/post_max_size = .*/post_max_size = 64M/' /etc/php/8.2/fpm/php.ini
sed -i 's/memory_limit = .*/memory_limit = 256M/' /etc/php/8.2/fpm/php.ini
sed -i 's/max_execution_time = .*/max_execution_time = 300/' /etc/php/8.2/fpm/php.ini

systemctl restart php8.2-fpm

# ==========================================
# STEP 14: Setup Firewall
# ==========================================
log_info "Step 14: Configuring firewall..."

ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 1883/tcp  # MQTT
ufw --force enable

# ==========================================
# STEP 15: Create MQTT Bridge Service (Optional)
# ==========================================
log_info "Step 15: Creating MQTT bridge service..."

cat > /etc/systemd/system/soma-mqtt.service << EOF
[Unit]
Description=SOMA PV MQTT Bridge
After=network.target soma-ai.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=$APP_DIR/python-ai-service
Environment="PATH=$APP_DIR/python-ai-service/venv/bin"
ExecStart=$APP_DIR/python-ai-service/venv/bin/python esp32_integration/mqtt_to_laravel.py
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable soma-mqtt
# Don't start yet - needs configuration
# systemctl start soma-mqtt

# ==========================================
# STEP 16: Final Checks
# ==========================================
log_info "Step 16: Running final checks..."

echo ""
echo "=========================================="
echo "Deployment Complete!"
echo "=========================================="
echo ""
echo "Services Status:"
systemctl status nginx --no-pager -l | head -5
systemctl status php8.2-fpm --no-pager -l | head -5
systemctl status soma-ai --no-pager -l | head -5
echo ""
echo "=========================================="
echo "Access URLs:"
echo "=========================================="
echo "Frontend:  http://$DOMAIN"
echo "API:       http://$DOMAIN/api"
echo "AI Health: http://$DOMAIN/ai/health"
echo ""
echo "Demo Accounts:"
echo "  Admin:      admin@soma.com / password"
echo "  Technician: tech@soma.com / password"
echo ""
echo "=========================================="
echo "ESP32 Configuration:"
echo "=========================================="
echo "Update your ESP32 code with:"
echo "  MQTT Server: broker.hivemq.com"
echo "  API URL: http://$DOMAIN/api/sensors/data"
echo ""
echo "=========================================="
log_info "Deployment finished successfully!"
