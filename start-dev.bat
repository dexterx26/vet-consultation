@echo off
title Laravel Teleconsultation Dev Services
echo ========================================================
echo   Veterinary Teleconsultation - Local Dev Services
echo ========================================================
echo.

:: Check if current environment is set to development
findstr /C:"DB_CONNECTION=sqlite" .env >nul 2>&1
if errorlevel 1 (
    echo [WARNING] Your active .env is currently configured for PRODUCTION (PostgreSQL)!
    echo Local development requires DEVELOPMENT mode with SQLite.
    echo.
    set /p auto_switch="Switch to DEVELOPMENT (SQLite) now? (Y/N, default=Y): "
    if /i not "%auto_switch%"=="n" (
        echo.
        php artisan env:switch dev
        echo.
    )
)

:: Ensure SQLite database exists
if not exist "database\database.sqlite" (
    echo Creating missing database\database.sqlite file...
    type nul > "database\database.sqlite"
    php artisan migrate --force
)

echo.
echo Starting development services in separate windows...
echo.

:: 1. Start Laravel Web Server
start "Laravel Web Server (Port 8000)" cmd /k "php artisan serve"

:: 2. Start Laravel Reverb WebSocket Server
start "Laravel Reverb WebSockets (Port 8001)" cmd /k "php artisan reverb:start --port=8001"

:: 3. Start Vite Frontend Bundler
start "Vite Dev Server" cmd /k "npm run dev"

echo ========================================================
echo   All development services have been launched!
echo ========================================================
echo - Web App:      http://localhost:8000
echo - Reverb WS:    ws://localhost:8001
echo - Vite HMR:     http://localhost:5173
echo ========================================================
echo.
echo Leave this window open, or close it whenever you want.
pause
