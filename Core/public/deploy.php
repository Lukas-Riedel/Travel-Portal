<?php
    use Core\Event\Event;

    require_once(__DIR__ . "/../src/bootstrap.php");
    
    $onError = function($level, $message, $file, $line) {
        throw new RuntimeException($message);
    };

    $migrationScriptsBasePath = __DIR__ . "/db/";
    $migrationScriptFileNames = array_map(function($path) { 
        $tokens = explode("/", $path);
        return $tokens[count($tokens) - 1];
     }, array_filter((array) glob($migrationScriptsBasePath . "*")));
    asort($migrationScriptFileNames);

    $alreadyAppliedScripts = array();
    if ($databaseClient->isDatabaseInitialized()) {
        $alreadyAppliedScriptsRows = $databaseClient->query("SELECT * FROM migration_script");
        foreach ($alreadyAppliedScriptsRows as &$alreadyAppliedScriptsRow) {
            $alreadyAppliedScripts[$alreadyAppliedScriptsRow["name"]] = array(
                "hash" => $alreadyAppliedScriptsRow["hash"],
                "timestamp" => $alreadyAppliedScriptsRow["timestamp"]);
        }
    }

    foreach ($migrationScriptFileNames as &$migrationScriptFileName) {
        $path = $migrationScriptsBasePath . $migrationScriptFileName;
        $hash = hash_file("sha256", $path);

        if (!array_key_exists($migrationScriptFileName, $alreadyAppliedScripts)) {
            $handle = fopen($path, "r");
            $migrationScript = fread($handle, filesize($path));
            fclose($handle);

            $migrationScriptFileNameTokens = explode("-", $migrationScriptFileName);
            $delimiter = count($migrationScriptFileNameTokens) == 3 ? str_replace(".sql", "", $migrationScriptFileNameTokens[2]) : ";";

            try {
                set_error_handler($onError);
                $databaseClient->executeAtomically(function() use(&$migrationScript, &$hash, &$delimiter, &$databaseClient, &$migrationScriptFileName) {
                    foreach (explode($delimiter, $migrationScript) as &$migrationSubScript) {
                        if (trim($migrationSubScript) !== "") {             
                            $databaseClient->query($migrationSubScript);
                        }
                    }

                    $databaseClient->query("INSERT INTO migration_script (name, hash, timestamp) VALUES ('" . $migrationScriptFileName . "', '" . $hash . "', ROUND(EXTRACT(EPOCH FROM NOW())))");
                });
            }
            catch (Throwable $e) {
                http_response_code(500);
                die($e->getMessage());
            }
            finally {            
                restore_error_handler();
            }
        }
        else if ($hash != $alreadyAppliedScripts[$migrationScriptFileName]["hash"]) {
            http_response_code(500);
            die("Could not apply " . $migrationScriptFileName . " migration script. It was already applied at " . date('d.m.Y H:i:s', $alreadyAppliedScripts[$migrationScriptFileName]["timestamp"]) . ". Expected: " . $alreadyAppliedScripts[$migrationScriptFileName]["hash"] . " Actual: " . $hash);
        }
    }
    
    $tablesToBackupRows = $databaseClient
        ->query("SELECT (SELECT STRING_AGG(TABLE_NAME, ',') FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE <> 'VIEW' AND TABLE_SCHEMA = DATABASE() AND TABLE_NAME NOT IN (SELECT SUBSTRING(TABLE_NAME, 2) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'VIEW' AND TABLE_SCHEMA = DATABASE())) AS tables");
    $tablesToBackup = $tablesToBackupRows[0]["tables"];

    $eventPublisher->publish(Event::ApplicationStarted($tablesToBackup));
    
    http_response_code(200);
?>