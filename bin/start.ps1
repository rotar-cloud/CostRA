$ErrorActionPreference = 'Stop'
$php = 'C:\PHP\php.exe'
$credential = Get-Credential -UserName 'postgres' -Message 'PostgreSQL / awd_li'
$demo = Read-Host 'Demo password (at least 8 characters, at most 72 UTF-8 bytes)' -AsSecureString
Push-Location (Split-Path $PSScriptRoot)
try {
    $env:DB_HOST = '127.0.0.1'
    $env:DB_PORT = '5432'
    $env:DB_NAME = 'awd_li'
    $env:DB_USER = $credential.UserName
    $env:DB_PASSWORD = $credential.GetNetworkCredential().Password
    & $php bin/setup.php
    if ($LASTEXITCODE -ne 0) { throw 'Database setup failed.' }
    $env:COSTRA_DEMO_PASSWORD = [Net.NetworkCredential]::new('', $demo).Password
    & $php bin/seed.php
    if ($LASTEXITCODE -ne 0) { throw 'Seed failed.' }
    Remove-Item Env:COSTRA_DEMO_PASSWORD
    & $php -S 127.0.0.1:8000 -t public
} finally {
    Remove-Item Env:DB_PASSWORD,Env:COSTRA_DEMO_PASSWORD -ErrorAction SilentlyContinue
    $demo.Dispose()
    Pop-Location
}