@echo off
title VetTeleconsult - Environment Manager
:menu
cls
echo ========================================================
echo       VetTeleconsult - Environment Switcher
echo ========================================================
echo.
php artisan env:switch status
echo.
echo --------------------------------------------------------
echo   Choose an option:
echo --------------------------------------------------------
echo   [1] Switch to DEVELOPMENT (SQLite - Local Laptop)
echo   [2] Switch to PRODUCTION  (PostgreSQL - Render Cloud)
echo   [3] Refresh Environment Status
echo   [4] Run Database Migrations for Active Database
echo   [5] Start Local Development Services (start-dev.bat)
echo   [0] Exit
echo --------------------------------------------------------
set /p choice="Enter choice (0-5): "

if "%choice%"=="1" (
    echo.
    php artisan env:switch dev
    echo.
    pause
    goto menu
)
if "%choice%"=="2" (
    echo.
    php artisan env:switch prod
    echo.
    pause
    goto menu
)
if "%choice%"=="3" (
    goto menu
)
if "%choice%"=="4" (
    echo.
    echo Running database migrations...
    php artisan migrate
    echo.
    pause
    goto menu
)
if "%choice%"=="5" (
    call start-dev.bat
    goto menu
)
if "%choice%"=="0" (
    exit /b 0
)

echo Invalid choice.
pause
goto menu
