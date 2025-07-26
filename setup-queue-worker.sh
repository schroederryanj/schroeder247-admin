#!/bin/bash

# Cloudways Queue Worker Setup Script
# Run this on your Cloudways server as the master user

echo "=== Setting up Queue Worker for Schroeder247 Admin ==="

# Variables
APP_PATH="/home/master/applications/zgrjnvnece/public_html"
SUPERVISOR_CONF="/etc/supervisor/conf.d/schroeder247-queue.conf"

# Check if running as correct user
if [ "$USER" != "master" ]; then
    echo "Please run this script as the 'master' user"
    exit 1
fi

# Create supervisor config
echo "Creating supervisor configuration..."
sudo tee $SUPERVISOR_CONF > /dev/null <<EOF
[program:schroeder247-queue]
process_name=%(program_name)s_%(process_num)02d
command=php $APP_PATH/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=master
numprocs=2
redirect_stderr=true
stdout_logfile=$APP_PATH/storage/logs/queue.log
stopwaitsecs=3600
EOF

# Create log file if it doesn't exist
touch $APP_PATH/storage/logs/queue.log
chmod 775 $APP_PATH/storage/logs/queue.log

# Update supervisor
echo "Updating supervisor..."
sudo supervisorctl reread
sudo supervisorctl update

# Start the queue workers
echo "Starting queue workers..."
sudo supervisorctl start schroeder247-queue:*

# Show status
echo -e "\n=== Queue Worker Status ==="
sudo supervisorctl status | grep schroeder247

echo -e "\n=== Testing Queue ==="
cd $APP_PATH
php artisan queue:work --once

echo -e "\n✅ Queue worker setup complete!"
echo "Your monitors will now be checked automatically."