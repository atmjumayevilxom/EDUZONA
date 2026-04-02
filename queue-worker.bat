@echo off
title EDUZONE Queue Worker
color 0A
echo ==========================================
echo   EDUZONE - Queue Worker
echo   Bu oyna ochiq bo'lsin! Yopilsa o'yinlar
echo   yaratilmay qoladi.
echo ==========================================
echo.
cd /d "%~dp0"

:loop
echo [%time%] Queue worker ishlamoqda...
php artisan queue:work --sleep=1 --tries=3 --timeout=120
echo.
echo [%time%] Worker to'xtadi. 3 soniyadan keyin qayta ishga tushadi...
timeout /t 3 /nobreak >nul
echo.
goto loop
