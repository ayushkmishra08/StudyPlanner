# StudyPlanner PowerShell Launcher
Write-Host "===================================================" -ForegroundColor Cyan
Write-Host "             Starting StudyPlanner                 " -ForegroundColor Cyan
Write-Host "===================================================" -ForegroundColor Cyan

# 1. Check and start MariaDB if not running
$tcp = Get-NetTCPConnection -LocalPort 3306 -ErrorAction SilentlyContinue
if (-not $tcp) {
    Write-Host "[1/3] Starting MariaDB Database..." -ForegroundColor Yellow
    $mariaPath = "C:\Program Files\MariaDB 13.0\bin\mysqld.exe"
    if (Test-Path $mariaPath) {
        Start-Process -FilePath $mariaPath -ArgumentList "--console" -WindowStyle Hidden
        Start-Sleep -Seconds 3
    } else {
        Write-Host "Warning: MariaDB not found at default location. Please ensure MySQL/MariaDB is running." -ForegroundColor Red
    }
} else {
    Write-Host "[1/3] MariaDB Database is already running on port 3306." -ForegroundColor Green
}

# 2. Verify database and schema
Write-Host "[2/3] Verifying database tables and migrations..." -ForegroundColor Yellow
php setup.php

# 3. Start server and launch browser
Write-Host "[3/3] Starting PHP Web Server on http://127.0.0.1:8000 ..." -ForegroundColor Green
Start-Process "http://127.0.0.1:8000/"
php -S 127.0.0.1:8000 index.php
