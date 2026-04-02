@echo off
title EDUZONE - Queue Service Setup
color 0B

echo ==========================================
echo   EDUZONE - Queue Worker Auto-Start Setup
echo ==========================================
echo.

set "PROJECT_DIR=%~dp0"
set "TASK_NAME=EDUZONE-QueueWorker"
set "WORKER_BAT=%PROJECT_DIR%queue-worker.bat"

REM Remove existing task silently
schtasks /delete /tn "%TASK_NAME%" /f >nul 2>&1

REM Register queue-worker.bat to run at every login (minimized)
schtasks /create /tn "%TASK_NAME%" /tr "\"%WORKER_BAT%\"" /sc onlogon /ru "%USERNAME%" /delay 0001:00 /f >nul 2>&1

if %errorlevel% == 0 (
    color 0A
    echo [OK] Muvaffaqiyatli! Task Scheduler ga qo'shildi.
    echo      Endi har Windows kirganingizda queue worker
    echo      avtomatik ravishda boshlanadi.
) else (
    color 0E
    echo [!!] Task Scheduler administrator huquqi talab qiladi.
    echo      Iltimos, bu faylni o'ng klik "Administrator sifatida ishga tushirish"
    echo      bilan qayta ishga tushiring.
    echo.
    pause
    exit /b 1
)

echo.
echo [*] Hozir ishga tushirmoqda...
start "EDUZONE Queue Worker" /min "%WORKER_BAT%"

echo.
echo [DONE] Queue worker fon rejimida ishlamoqda.
echo        Taskbar da "EDUZONE Queue Worker" ko'rinadi.
echo.
echo Tekshirish uchun: schtasks /query /tn "%TASK_NAME%"
echo.
pause
