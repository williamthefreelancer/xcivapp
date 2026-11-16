# Start local WordPress on http://localhost:8080
$ErrorActionPreference = "Stop"
Set-Location (Split-Path $PSScriptRoot -Parent)

$svc = Get-Service MySQL84 -ErrorAction SilentlyContinue
if (-not $svc -or $svc.Status -ne "Running") {
  Write-Host "Starting MySQL84..."
  Start-Process powershell -Verb RunAs -ArgumentList '-NoProfile -Command "Start-Service MySQL84"' -Wait
}

# Free port 8080 if an old PHP server is still bound
$listeners = Get-NetTCPConnection -LocalPort 8080 -State Listen -ErrorAction SilentlyContinue
foreach ($l in $listeners) {
  Stop-Process -Id $l.OwningProcess -Force -ErrorAction SilentlyContinue
}

Write-Host "WordPress running at http://localhost:8080"
Write-Host "Press Ctrl+C to stop."
php -S localhost:8080 router.php