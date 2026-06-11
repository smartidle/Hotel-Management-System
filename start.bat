@echo off
title Hotel Management System Demo
echo ============================================
echo   Hotel Management System Demo
echo ============================================
echo.

REM Set project directory
set "PROJECT_DIR=%~dp0"
set "PHP_EXE=C:\php\php.exe"

REM Remove trailing backslash from PROJECT_DIR
if "%PROJECT_DIR:~-1%"=="\" set "PROJECT_DIR=%PROJECT_DIR:~0,-1%"

echo [INFO] Project: %PROJECT_DIR%
echo [INFO] PHP: %PHP_EXE%
echo.

REM Check PHP
"%PHP_EXE%" -v >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Cannot run PHP at: %PHP_EXE%
    echo.
    echo Possible fixes:
    echo   1. Install PHP 8.2+ to C:\php\
    echo   2. Or edit this file and set PHP_EXE to your PHP path
    echo.
    pause
    exit /b 1
)
echo [OK] PHP found.

REM Check if database exists
if not exist "%USERPROFILE%\hotel_demo_data\hotel_management.sqlite" (
    echo.
    echo [1/2] Initializing database...
    "%PHP_EXE%" -c "%PROJECT_DIR%\php.ini" "%PROJECT_DIR%\setup.php"
    if errorlevel 1 (
        echo [ERROR] Database initialization failed!
        pause
        exit /b 1
    )
    echo       Database created!
) else (
    echo [1/2] Database ready.
)

echo.
echo [2/2] Starting server...
echo.
echo   URL:  http://localhost:8000
echo   User: admin / admin123
echo.
echo   Press Ctrl+C to stop.
echo ============================================
echo.

start http://localhost:8000

"%PHP_EXE%" -c "%PROJECT_DIR%\php.ini" -S localhost:8000 -t "%PROJECT_DIR%"

echo.
echo [Server stopped]
pause
