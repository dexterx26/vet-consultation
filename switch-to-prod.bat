@echo off
title Switch Environment - Production (PostgreSQL)
echo ========================================================
echo   Switching Application to PRODUCTION (PostgreSQL)
echo ========================================================
echo.

php artisan env:switch prod

echo.
echo ========================================================
echo   Production mode is now active!
echo   Target: PostgreSQL Database (Render Cloud)
echo ========================================================
echo.
pause
