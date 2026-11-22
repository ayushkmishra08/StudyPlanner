@echo off
title StudyPlanner Launcher
echo ===================================================
echo             Starting StudyPlanner
echo ===================================================
echo.

REM 1. Check and start MariaDB if not running
netstat -ano | findstr 3306 >nul
if %errorlevel% neq 0 (
    echo [1/3] Starting MariaDB Database...
    start "MariaDB Server" /B "C:\Program Files\MariaDB 13.0\bin\mysqld.exe" --console
    timeout /t 3 >nul
) else (
    echo [1/3] MariaDB Database is already running on port 3306.
)

REM 2. Verify Database Connection and Tables
echo [2/3] Verifying database setup and tables...
php setup.php

REM 3. Start PHP Web Server and open browser
echo [3/3] Starting PHP Web Server on http://127.0.0.1:8000 ...
start http://127.0.0.1:8000/

php -S 127.0.0.1:8000 index.php
pause
