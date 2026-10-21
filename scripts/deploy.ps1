<#
    Deploys Gluta RepubliQ Aesthetics to the live server.

    Run it from the project root:

        pwsh -File scripts/deploy.ps1
#>
[CmdletBinding()]
param(
    [string]$Host_ = 'htrjymuo@ck.deskpulse.click',
    [int]$Port = 9022,
    [string]$Remote = '~/public_html/glutarepubliq.recursive-frog.com',
    [string]$AppUrl = 'https://glutarepubliq.recursive-frog.com',
    [string]$ExpectedDatabase = 'htrjymuo_glutarepubliq',
    [switch]$AllowDatabaseMismatch,
    [switch]$SkipTests,
    [string]$IdentityFile = ''
)

$ErrorActionPreference = 'Stop'
Set-Location (Join-Path $PSScriptRoot '..')

# Which site is this? Read it from config/clinic.php
$slug = (Select-String -Path config\clinic.php `
    -Pattern "'organization' => env\('CLINIC_ORGANIZATION', '([^']+)'\)"
).Matches[0].Groups[1].Value

if (-not $slug) { throw 'Could not read the organization slug from config/clinic.php.' }

$expected = "https://glutarepubliq.recursive-frog.com"

Write-Host "This repository is the '$slug' site; target URL: $AppUrl"

if ($Remote.TrimEnd('/') -notlike "*/public_html/glutarepubliq.recursive-frog.com") {
    throw @"
Refusing to deploy. Target remote folder must be:
    ~/public_html/glutarepubliq.recursive-frog.com
but is:
    $Remote
"@
}

$Ssh = @('-F', 'none', '-p', $Port, '-o', 'BatchMode=yes', '-o', 'ConnectTimeout=20', '-o', 'StrictHostKeyChecking=accept-new')
if ($IdentityFile) { $Ssh += @('-i', $IdentityFile) }

function Invoke-Remote([string]$Command) {
    & ssh @Ssh $Host_ $Command
    if ($LASTEXITCODE -ne 0) { throw "remote command failed: $Command" }
}

Write-Host "Confirming $Remote is configured against database '$ExpectedDatabase'..."
$remoteDbRaw = & ssh @Ssh $Host_ "sed -n 's/^DB_DATABASE=//p' $Remote/.env | head -1"
$remoteDb = ([string]($remoteDbRaw | Out-String)).Trim().Trim('"').Trim("'")

if ($LASTEXITCODE -ne 0 -or -not $remoteDb) {
    throw "Could not read DB_DATABASE from $Remote/.env. Refusing to deploy blind."
}

Write-Host "  remote DB_DATABASE = $remoteDb"

if (-not $AllowDatabaseMismatch) {
    if ($remoteDb -ne $ExpectedDatabase) {
        throw "Refusing to deploy. Remote .env database is $remoteDb but expected $ExpectedDatabase."
    }
}

if (-not $SkipTests) {
    Write-Host 'Checking test suite...'
    php artisan test --filter=ExampleTest
}

Write-Host "Building frontend assets for $AppUrl..."
$previous = $env:APP_URL
$env:APP_URL = $AppUrl
try {
    npm run build
} finally {
    $env:APP_URL = $previous
}

$cut = $Remote.LastIndexOf('/')
$remoteParent = $Remote.Substring(0, $cut)
$remoteLeaf = $Remote.Substring($cut + 1)

$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
Write-Host 'Backing up remote state if present...'
Invoke-Remote "mkdir -p ~/backups"
Invoke-Remote "cd $remoteParent && tar czf ~/backups/site-$slug-$stamp.tar.gz $remoteLeaf 2>/dev/null || true"
Invoke-Remote "cd $Remote && DB=`$(grep -E '^DB_' .env | sed 's/^export //') && eval `"`$DB`" && mysqldump --single-transaction --quick -h `"`${DB_HOST:-localhost}`" -u `"`$DB_USERNAME`" -p`"`$DB_PASSWORD`" `"`$DB_DATABASE`" > ~/backups/db-$slug-$stamp.sql 2>/dev/null || true"

$dumpSize = (& ssh @Ssh $Host_ "wc -c < ~/backups/db-$slug-$stamp.sql 2>/dev/null || echo 0").Trim()
Write-Host "  Database dump size: $dumpSize bytes"

Write-Host 'Uploading application source...'
git archive --format=tar HEAD | & ssh @Ssh $Host_ "cd $Remote && tar xf -"

Write-Host 'Uploading built frontend assets & public media...'
tar cf - -C public build media | & ssh @Ssh $Host_ "cd $Remote/public && rm -rf build && tar xf -"

Write-Host 'Migrating, seeding and optimizing live server caches...'
Invoke-Remote "cd $Remote && php artisan migrate --force"
Invoke-Remote "cd $Remote && php artisan db:seed --force"

Invoke-Remote "cd $Remote && php artisan optimize:clear"
Invoke-Remote "cd $Remote && php artisan config:cache"
& ssh @Ssh $Host_ "cd $Remote && php artisan route:cache"

Invoke-Remote "cd $Remote && mkdir -p public/media/photos/staff public/media/photos/products storage/framework/cache storage/framework/sessions storage/framework/views && chmod -R 775 storage bootstrap/cache"

$cached = & ssh @Ssh $Host_ "cd $Remote && ls bootstrap/cache/config.php 2>/dev/null | wc -l"
if ($cached.Trim() -ne '1') {
    throw 'config:cache did not produce bootstrap/cache/config.php'
}
Write-Host '  Caches cleared and rebuilt successfully.'

Write-Host 'Verifying public endpoints...'
foreach ($path in @('/', '/about', '/treatments', '/contact')) {
    $code = & ssh @Ssh $Host_ "curl -k -s -o /dev/null -w '%{http_code}' $AppUrl$path"
    Write-Host "  $path -> $code"
}

# Restore local wayfinder
php artisan wayfinder:generate --with-form | Out-Null
Write-Host "Deployed successfully to $AppUrl"
