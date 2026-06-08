# Builds a WordPress-installable plugin ZIP for release.
# Usage: .\bin\package.ps1

$ErrorActionPreference = 'Stop'

$root     = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$dist     = Join-Path $root 'dist'
$slug     = 'nap-prilozhenie-38'
$mainFile = Join-Path $root "$slug.php"

if (-not (Test-Path $mainFile)) {
    throw "Main plugin file not found: $mainFile"
}

$version = '1.0.0'
if (Test-Path $mainFile) {
    $content = Get-Content $mainFile -Raw
    if ($content -match '\* Version:\s+([0-9.]+)') {
        $version = $Matches[1]
    }
}

$include = @(
    "$slug.php",
    'readme.txt',
    'uninstall.php',
    'includes'
)

$staging = Join-Path $env:TEMP "nap38-package-$([guid]::NewGuid().ToString())"
$target  = Join-Path $staging $slug

New-Item -ItemType Directory -Path $target -Force | Out-Null

foreach ($item in $include) {
    $source = Join-Path $root $item
    if (-not (Test-Path $source)) {
        throw "Missing required path: $source"
    }
    Copy-Item -Path $source -Destination $target -Recurse -Force
}

New-Item -ItemType Directory -Path $dist -Force | Out-Null
$zipName = "$slug-$version.zip"
$zipPath = Join-Path $dist $zipName

if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
}

Compress-Archive -Path $target -DestinationPath $zipPath -Force
Remove-Item $staging -Recurse -Force

Write-Host "Created: $zipPath"
