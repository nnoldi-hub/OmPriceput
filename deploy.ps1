#
# Deploy OmPriceput pe un server Windows (PowerShell).
#
# Usage:
#   .\deploy.ps1             # deploy the master branch
#   .\deploy.ps1 -Branch develop
#
param(
    [string]$Branch = "master"
)

$ErrorActionPreference = "Stop"
Set-Location -Path $PSScriptRoot

if (-not (Test-Path artisan)) {
    throw "Eroare: artisan nu a fost gasit in $PSScriptRoot. Ruleaza scriptul din radacina proiectului."
}

function Invoke-Step {
    param([string]$Name, [scriptblock]$Action)
    Write-Host "==> $Name"
    & $Action
    if ($LASTEXITCODE -ne 0) {
        throw "Pasul '$Name' a esuat (cod $LASTEXITCODE)."
    }
}

Write-Host "==> Deploy OmPriceput (branch: $Branch)"

Invoke-Step "Git pull" { git pull origin $Branch }
Invoke-Step "Composer install" { composer install --no-dev --optimize-autoloader --no-interaction }
Invoke-Step "Migrari" { php artisan migrate --force }
Invoke-Step "Curatare cache" { php artisan optimize:clear }
Invoke-Step "Cache config" { php artisan config:cache }
Invoke-Step "Cache rute" { php artisan route:cache }
Invoke-Step "Cache views" { php artisan view:cache }
php artisan queue:restart 2>$null | Out-Null

Write-Host "==> Deploy finalizat"
