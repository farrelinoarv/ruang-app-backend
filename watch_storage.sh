#!/bin/bash
# Watch changes in the storage folder and apply chown + chmod when new folders are created
while inotifywait -r -e create ~/projects/ruang-app-backend/storage/app/private/filament_exports; do
    sudo chown -R www-data:www-data ~/projects/ruang-app-backend/storage
    sudo chmod -R 775 ~/projects/ruang-app-backend/storage
done

