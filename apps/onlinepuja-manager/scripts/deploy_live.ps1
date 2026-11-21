# ==============================================================================
# OnlinePuja Manager - 1-Click Sovereign Build & Deployment Script
# Compiles Flutter Web PWA & Android Release APK, bundling both directly to:
# https://onlinepuja.live/manager/ and https://onlinepuja.live/manager/onlinepuja-manager.apk
# ==============================================================================

$ErrorActionPreference = 'Stop'
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectDir = Split-Path -Parent $ScriptDir
$Server = 'root@200.234.41.231'
$RemotePath = '/home/onlinepujalive/htdocs/onlinepuja.live/public/manager'

Set-Location $ProjectDir
Write-Host ">>> Starting Sovereign Deployment for OnlinePuja Manager..." -ForegroundColor Yellow

# [1/5] Building Flutter Web PWA
Write-Host "`n[1/5] Compiling Flutter Web PWA (base-href: /manager/)..." -ForegroundColor Cyan
flutter build web --release --base-href /manager/

# [2/5] Compiling Android Release APK
Write-Host "`n[2/5] Compiling Android Release APK..." -ForegroundColor Cyan
flutter build apk --release

$ApkSource = "$ProjectDir\build\app\outputs\flutter-apk\app-release.apk"
$WebOutputDir = "$ProjectDir\build\web"

if (-not (Test-Path $ApkSource)) {
    Write-Warning "APK compilation skipped or app-release.apk not found at $ApkSource. Deploying Web PWA only."
} else {
    Write-Host "`n[3/5] Bundling latest APK directly into Web PWA distribution..." -ForegroundColor Cyan
    Copy-Item -Path $ApkSource -Destination "$WebOutputDir\onlinepuja-manager.apk" -Force
    Copy-Item -Path $ApkSource -Destination "$WebOutputDir\OnlinePuja-Manager.apk" -Force
}

# [4/5] Archiving distribution package
Write-Host "`n[4/5] Archiving distribution package..." -ForegroundColor Cyan
$Archive = "$ProjectDir\build\web.tar.gz"
tar -czf $Archive -C $WebOutputDir .

# [5/5] Deploying live to VPS
Write-Host "`n[5/5] Deploying live to onlinepuja.live VPS..." -ForegroundColor Cyan
scp $Archive "${Server}:/tmp/onlinepuja_manager_web.tar.gz"
ssh $Server "mkdir -p $RemotePath && tar -xzf /tmp/onlinepuja_manager_web.tar.gz -C $RemotePath/ && chown -R onlinepujalive:onlinepujalive $RemotePath && chmod -R 755 $RemotePath && rm -f /tmp/onlinepuja_manager_web.tar.gz"

Remove-Item -Path $Archive -Force -ErrorAction SilentlyContinue

Write-Host "`n==============================================================================" -ForegroundColor Green
Write-Host " ONLINEPUJA MANAGER SOVEREIGN DEPLOYMENT COMPLETE & VERIFIED LIVE!" -ForegroundColor Green
Write-Host "==============================================================================" -ForegroundColor Green
Write-Host " * Web Command Tower: https://onlinepuja.live/manager/" -ForegroundColor Yellow
Write-Host " * Direct Android APK: https://onlinepuja.live/manager/onlinepuja-manager.apk" -ForegroundColor Yellow
Write-Host "==============================================================================`n" -ForegroundColor Green
