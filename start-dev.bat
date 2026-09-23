@echo off
title Laravel Teleconsultation Dev Services
echo ========================================================
echo   Starting Laravel HTTP Server & Reverb WebSockets
echo ========================================================
echo.

:: Start Laravel Web Server
start "Laravel Web Server (Port 8000)" cmd /k "php artisan serve"

:: Start Laravel Reverb WebSocket Server
start "Laravel Reverb WebSockets (Port 8001)" cmd /k "php artisan reverb:start --port=8001"

:: Start Vite Frontend Bundler
start "Vite Dev Server" cmd /k "npm run dev"

echo.
echo All development services have been launched in separate windows!
echo - Laravel HTTP:   http://localhost:8000
echo - Reverb WS:      ws://localhost:8001
echo.
pause
