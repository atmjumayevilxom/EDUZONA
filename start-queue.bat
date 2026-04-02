@echo off
title EDUZONE - Queue Worker
color 0A
echo ============================================
echo   EDUZONE Queue Worker - Ishga tushdi
echo   Bu oynani YOPMANG!
echo ============================================
echo.
cd /d "c:\xampp\htdocs\games"
:loop
echo [%date% %time%] Queue worker ishlamoqda...
php artisan queue:work --timeout=120 --tries=3 --sleep=3
echo [%date% %time%] Worker to'xtadi. 5 soniyada qayta...
timeout /t 5 /nobreak >nul
goto loop
