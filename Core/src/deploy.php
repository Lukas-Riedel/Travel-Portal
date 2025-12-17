<?php
    require_once(__DIR__ . "/bootstrap.php");
    
    $lockKeyFormat = "Worker:Lock:%s";
    for ($i = 0; $i < getenv("MAX_WORKERS_COUNT"); ++$i) {
        $cacheClient->delete(sprintf($lockKeyFormat, $i));
    }

    // If changing this, change also in docker-entrypoint.sh.
    $backupFilePath = "/var/tmp/backup.sql.gz";
    
    $backupFolderId = $googleClient->getOrCreateFolderId("Travel Portal Backups", null);    
    $googleClient->createFile(date("Y-m-d H:i:s") . ".sql.gz", $backupFolderId, "application/gzip", file_get_contents($backupFilePath));
?>