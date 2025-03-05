#!/bin/bash

# M-Mart+ Backend Deployment Script
# This script automates the deployment of the backend to DigitalOcean

# Exit immediately if a command exits with a non-zero status
set -e

# Configuration
REMOTE_USER="root"
REMOTE_HOST="your_server_ip"
REMOTE_DIR="/var/www/mmartplus"
REPO_URL="https://github.com/yourusername/mmartplus-backend.git"

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}Starting deployment of M-Mart+ Backend...${NC}"

# Check if SSH connection works
echo -e "${GREEN}Checking SSH connection...${NC}"
ssh -T $REMOTE_USER@$REMOTE_HOST 'echo "SSH connection successful"' || { echo -e "${RED}SSH connection failed. Please check your SSH configuration.${NC}"; exit 1; }

# Create directory structure if it doesn't exist
echo -e "${GREEN}Creating directory structure...${NC}"
ssh $REMOTE_USER@$REMOTE_HOST "mkdir -p $REMOTE_DIR"

# Check if repository is already cloned
echo -e "${GREEN}Checking if repository is already cloned...${NC}"
ssh $REMOTE_USER@$REMOTE_HOST "if [ ! -d $REMOTE_DIR/.git ]; then git clone $REPO_URL $REMOTE_DIR; else echo 'Repository already cloned'; fi"

# Pull latest changes
echo -e "${GREEN}Pulling latest changes...${NC}"
ssh $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_DIR && git pull origin main"

# Install dependencies
echo -e "${GREEN}Installing dependencies...${NC}"
ssh $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_DIR && composer install --no-dev --optimize-autoloader"

# Set up environment file if it doesn't exist
echo -e "${GREEN}Setting up environment file...${NC}"
ssh $REMOTE_USER@$REMOTE_HOST "if [ ! -f $REMOTE_DIR/.env ]; then cp $REMOTE_DIR/.env.production.example $REMOTE_DIR/.env; echo 'Created .env file from .env.production.example'; fi"

# Generate application key if not already set
echo -e "${GREEN}Generating application key...${NC}"
ssh $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_DIR && php artisan key:generate --force"

# Run migrations
echo -e "${GREEN}Running migrations...${NC}"
ssh $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_DIR && php artisan migrate --force"

# Cache configuration
echo -e "${GREEN}Caching configuration...${NC}"
ssh $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_DIR && php artisan config:cache"
ssh $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_DIR && php artisan route:cache"
ssh $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_DIR && php artisan view:cache"

# Set proper permissions
echo -e "${GREEN}Setting proper permissions...${NC}"
ssh $REMOTE_USER@$REMOTE_HOST "chown -R www-data:www-data $REMOTE_DIR"
ssh $REMOTE_USER@$REMOTE_HOST "chmod -R 755 $REMOTE_DIR"
ssh $REMOTE_USER@$REMOTE_HOST "chmod -R 775 $REMOTE_DIR/storage $REMOTE_DIR/bootstrap/cache"

# Restart queue workers if using supervisor
echo -e "${GREEN}Restarting queue workers...${NC}"
ssh $REMOTE_USER@$REMOTE_HOST "if [ -f /etc/supervisor/conf.d/mmartplus-worker.conf ]; then sudo supervisorctl restart mmartplus-worker:*; fi"

# Restart Nginx
echo -e "${GREEN}Restarting Nginx...${NC}"
ssh $REMOTE_USER@$REMOTE_HOST "sudo service nginx restart"

echo -e "${GREEN}Deployment completed successfully!${NC}"
