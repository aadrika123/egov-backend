@echo off
cd /d "c:\laragon\www\egov-backend"
php artisan schedule:run >> storage/logs/scheduler.log 2>&1