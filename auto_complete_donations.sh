#!/bin/bash

# Auto-complete donations background worker
# Runs every 60 seconds in an infinite loop
# Usage: nohup ./auto_complete_donations.sh > auto_complete_donations.log 2>&1 &

# Change to Laravel directory (adjust path if needed)
cd /var/www || cd "$(dirname "$0")" || exit

echo "========================================="
echo "Auto-Complete Donations Worker Started"
echo "Started at: $(date '+%Y-%m-%d %H:%M:%S')"
echo "========================================="

while true; do
    echo ""
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Running donations:auto-complete..."
    php artisan donations:auto-complete
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Completed. Next run in 60 seconds..."
    sleep 60
done
