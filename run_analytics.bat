@echo off

cd /d C:\laragon\www\egov-backend

php artisan dashboard:daily-analytics >> storage\logs\analytics_task.log 2>&1
