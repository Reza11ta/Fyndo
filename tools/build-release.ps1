#!/usr/bin/env pwsh
param(
    [string]$version = "1.0.0",
    [string]$output = "release"
)

$root = Split-Path -Parent $MyInvocation.MyCommand.Definition
Push-Location $root

$pluginFile = "my-ajax-search.php"
$content = Get-Content $pluginFile -Raw
$content = $content -replace "define\('FYND_VERSION', '.*'\);", "define('FYND_VERSION', '$version');"
Set-Content $pluginFile $content -Encoding UTF8

# update header Version: in plugin file
$contentHeader = Get-Content $pluginFile
$contentHeader = $contentHeader -replace "(\n\* Version:).*", "`$1     $version"
Set-Content $pluginFile $contentHeader -Encoding UTF8

if (!(Test-Path $output)) { New-Item -ItemType Directory -Path $output | Out-Null }
$zipName = "$output/fyndo-$version.zip"
if (Test-Path $zipName) { Remove-Item $zipName }

Compress-Archive -Path 'my-ajax-search\*' -DestinationPath $zipName -Force

Write-Host "Built release: $zipName"
Pop-Location 