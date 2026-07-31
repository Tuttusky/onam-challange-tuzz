# Run Scenario 01 load test (creator flow) against local server.
# Usage: .\load-tests\run-scenario-01.ps1 [-Players 10000] [-Concurrency 30]

param(
    [int]$Players = 10000,
    [int]$Concurrency = 30,
    [int]$Port = 8002
)

$ErrorActionPreference = 'Stop'
$php = 'C:\Users\LUMIN ADS\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe'
$root = Split-Path -Parent $PSScriptRoot
$base = "http://127.0.0.1:$Port"

Set-Location $root
$env:LOAD_TEST_MODE = 'true'
$env:CACHE_STORE = 'array'
$env:SESSION_DRIVER = 'array'

Write-Host "Starting load-test server on port $Port (LOAD_TEST_MODE=true, CACHE_STORE=array)..."
$server = Start-Process -FilePath $php -ArgumentList @('artisan', 'serve', "--host=127.0.0.1", "--port=$Port") -PassThru -WindowStyle Hidden

Start-Sleep -Seconds 3

try {
    $health = Invoke-WebRequest -Uri "$base/up" -UseBasicParsing -TimeoutSec 10
    if ($health.StatusCode -ne 200) {
        throw "Health check failed with status $($health.StatusCode)"
    }

    Write-Host "Running Scenario 01: $Players players, concurrency $Concurrency"
    node "$PSScriptRoot\scenario-01-creator-flow.mjs" --players=$Players --concurrency=$Concurrency --base=$base
    exit $LASTEXITCODE
}
finally {
    if ($server -and -not $server.HasExited) {
        Stop-Process -Id $server.Id -Force -ErrorAction SilentlyContinue
    }
}
