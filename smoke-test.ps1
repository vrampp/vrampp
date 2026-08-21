$ErrorActionPreference = 'Stop'

$baseUrl = 'http://localhost:55080'
$page = Invoke-WebRequest "$baseUrl/" -UseBasicParsing
if ($page.StatusCode -ne 200 -or $page.Content -notmatch 'vrampp') {
    throw 'Dashboard HTTP check failed.'
}

$status = Invoke-RestMethod "$baseUrl/api/services.php"
if ($status.services.Count -lt 3) {
    throw 'Service status endpoint returned fewer than three services.'
}

Write-Output "Dashboard OK: HTTP $($page.StatusCode)"
Write-Output "Services reported: $($status.services.Count)"
Write-Output "phpMyAdmin: $baseUrl/phpmyadmin"
Write-Output 'MariaDB remains internal on guest port 3306 by default.'
