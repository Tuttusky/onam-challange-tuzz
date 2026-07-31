@echo off
set PHP83=%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe
if not exist "%PHP83%" (
    echo PHP 8.3 not found at %PHP83%
    echo Install with: winget install PHP.PHP.8.3
    exit /b 1
)
"%PHP83%" artisan %*
