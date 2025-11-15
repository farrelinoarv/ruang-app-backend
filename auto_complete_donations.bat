@echo off
echo =========================================
echo Auto-Complete Donations Worker Started
echo Started at: %date% %time%
echo =========================================

:loop
echo.
echo [%date% %time%] Running donations:auto-complete...
php artisan donations:auto-complete
echo [%date% %time%] Completed. Next run in 60 seconds...
timeout /t 60 /nobreak
goto loop
