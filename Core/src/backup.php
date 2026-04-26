<?php
    use Common\Client\Http\HttpMethod;

    require_once(__DIR__ . "/bootstrap.php");

    $backupFilePath = getenv("BACKUP_LOCATION");
    $backupLimit = intval(getenv("BACKUP_LIMIT"));
    
    $rootBackupFolderId = $googleClient->getOrCreateFolderId("Travel Portal Backups", null);  
    $backupFolderId = $googleClient->createFolder(date("Y-m-d H:i:s (#" . getenv("VERSION_TAG") . ")"), $rootBackupFolderId);

    $googleClient->createFileFromFilePath("db.sql.gz", $backupFolderId, "application/gzip", $backupFilePath);

    foreach ($configurationService->getConfigurationEntry("calendar") as $calendarName => $calendarUrl) {
        $googleClient->createFileFromString($calendarName . ".ics", $backupFolderId, "text/calendar", $httpClient->executeRequest(HttpMethod::GET, $calendarUrl));
    }

    // TODO: Handle deletion for cases when there are more than 100 backups (though very unlikely due to the current set-up).
    $existingFolders = $googleClient->getFolders(100, $rootBackupFolderId);
    $logger->info("Deleting " . max(0, count($existingFolders) - $backupLimit) . " backups...");
    
    for ($i = $backupLimit; $i < count($existingFolders); $i++) {
        $googleClient->deleteFile($existingFolders[$i]["id"]);
    }
?>