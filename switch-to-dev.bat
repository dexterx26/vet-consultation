@echo off
title Switch Environment - Development (SQLite)
echo ========================================================
echo   Switching Application to DEVELOPMENT (SQLite)
echo ========================================================
echo.

php artisan env:switch dev

echo.
echo ========================================================
echo   Development mode is now active!
echo   Run "start-dev.bat" to start local services.
echo ========================================================
echo.
pause
