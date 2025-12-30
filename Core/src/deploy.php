<?php
    use Common\Client\Http\HttpMethod;
    use Ramsey\Uuid\Uuid;

    require_once(__DIR__ . "/bootstrap.php");
    
    $transactionId = Uuid::uuid4()->toString();
    $logger->pushProcessor(function($record) use($transactionId) {
        $record["context"]["transaction_id"] = $transactionId;
        $record["extra"]["transaction_id"] = $transactionId;
        return $record;
    });

    $migrationScriptsBasePath = __DIR__ . "/../db/";

    $sql = <<<'SQL'
        CREATE TABLE IF NOT EXISTS migration_script (
            name text PRIMARY KEY,
            hash text NOT NULL,
            timestamp timestamptz NOT NULL
        )
    SQL;

    $databaseClient
        ->statementBuilder($sql)
        ->execute();

    $sql = <<<'SQL'
        SELECT *
        FROM migration_script
    SQL;

    $alreadyAppliedScriptRows = $databaseClient
        ->statementBuilder($sql)
        ->getResultSet();

    $alreadyAppliedScripts = array();
    foreach ($alreadyAppliedScriptRows as &$alreadyAppliedScriptsRow) {
        $alreadyAppliedScripts[$alreadyAppliedScriptsRow["name"]] = array(
            "hash" => $alreadyAppliedScriptsRow["hash"],
            "timestamp" => $alreadyAppliedScriptsRow["timestamp"]);
    }

    $migrationScriptFileNames = array_map(function($path) { 
        $tokens = explode("/", $path);
        return $tokens[count($tokens) - 1];
     }, array_filter((array) glob($migrationScriptsBasePath . "*")));
    asort($migrationScriptFileNames);

    foreach ($migrationScriptFileNames as &$migrationScriptFileName) {
        $path = $migrationScriptsBasePath . $migrationScriptFileName;
        $hash = hash_file("sha256", $path);

        if (!array_key_exists($migrationScriptFileName, $alreadyAppliedScripts)) {
            $migrationScript = file_get_contents($path);

            $migrationScriptFileNameTokens = explode("-", $migrationScriptFileName);
            $delimiter = count($migrationScriptFileNameTokens) == 3 ? str_replace(".sql", "", $migrationScriptFileNameTokens[2]) : ";";

            try {
                $databaseClient->executeAtomically(function() use(&$migrationScript, &$hash, &$delimiter, &$databaseClient, &$migrationScriptFileName) {
                    foreach (explode($delimiter, $migrationScript) as &$migrationSubScript) {
                        if (trim($migrationSubScript) !== "") {            
                            $databaseClient
                                ->statementBuilder($migrationSubScript)
                                ->execute();
                        }
                    }
                    
                    $sql = <<<'SQL'
                        INSERT INTO migration_script (
                            name,
                            hash,
                            timestamp
                        )
                        VALUES (
                            ?,
                            ?,
                            NOW()
                        )
                    SQL;

                    $databaseClient
                        ->statementBuilder($sql)
                        ->withParameters($migrationScriptFileName, $hash)
                        ->execute();
                });
            }
            catch (Throwable $e) {
                fwrite(STDERR, $e->getMessage() . PHP_EOL);
                exit(1);
            }
        }
        else if ($hash != $alreadyAppliedScripts[$migrationScriptFileName]["hash"]) {
            fwrite(STDERR, "Could not apply " . $migrationScriptFileName . " migration script. It was already applied at " . $alreadyAppliedScripts[$migrationScriptFileName]["timestamp"] . ". Expected: " . $alreadyAppliedScripts[$migrationScriptFileName]["hash"] . " Actual: " . $hash . PHP_EOL);
            exit(1);
        }
    }

    // If changing this, change also in docker-entrypoint.sh.
    $backupFilePath = "/var/tmp/backup.sql.gz";
    
    $rootBackupFolderId = $googleClient->getOrCreateFolderId("Travel Portal Backups", null);  
    $backupFolderId = $googleClient->createFolder(date("Y-m-d H:i:s"), $rootBackupFolderId);

    $googleClient->createFile("db.sql.gz", $backupFolderId, "application/gzip", file_get_contents($backupFilePath));

    foreach ($configurationService->getConfigurationEntry("calendars") as $calendarName => $calendarUrl) {
        $googleClient->createFile($calendarName . ".ics", $backupFolderId, "text/calendar", $httpClient->executeRequest(HttpMethod::GET, $calendarUrl));
    }
?>