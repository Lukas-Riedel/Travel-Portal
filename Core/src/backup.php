<?php
    use Common\Client\Http\HttpMethod;

    require_once(__DIR__ . "/bootstrap.php");

    $backupFilePath = getenv("BACKUP_LOCATION");
    
    $rootBackupFolderId = $googleClient->getOrCreateFolderId("Travel Portal Backups", null);  
    $backupFolderId = $googleClient->createFolder(date("Y-m-d H:i:s (#" . getenv("VERSION_TAG") . ")"), $rootBackupFolderId);

    $googleClient->createFileFromFilePath("db.sql.gz", $backupFolderId, "application/gzip", $backupFilePath);

    foreach ($configurationService->getConfigurationEntry("calendar") as $calendarName => $calendarUrl) {
        $googleClient->createFileFromString($calendarName . ".ics", $backupFolderId, "text/calendar", $httpClient->executeRequest(HttpMethod::GET, $calendarUrl));
    }
?>