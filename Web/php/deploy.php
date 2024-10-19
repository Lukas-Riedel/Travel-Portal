<?php
    require_once(dirname(__FILE__) . "/provider/DatabaseProvider.php");
    require_once(dirname(__FILE__) . "/processor/Processor.php");
    require_once(dirname(__FILE__) . "/processor/GetHttpResponseProcessor.php");

    $databaseProvider = new DatabaseProvider(FALSE);
    $hostName = $_SERVER["HTTP_HOST"];
    
    $onError = function($level, $message, $file, $line) {
        throw new RuntimeException($message);
    };

    $databaseProvider
        ->query("UPDATE configuration SET value = '" . $hostName . "' WHERE type = 'HOST_NAME'");

    $migrationScriptsBasePath = dirname(__FILE__) . "/../sql/";
    $migrationScriptFileNames = array_map(function ($path) { 
        $tokens = explode("/", $path);
        return $tokens[count($tokens) - 1];
     }, array_filter((array) glob($migrationScriptsBasePath . "*")));
    asort($migrationScriptFileNames);

    $alreadyAppliedScriptsRows = $databaseProvider
        ->query("SELECT * FROM migration_script");

    $alreadyAppliedScripts = array();
    while ($alreadyAppliedScriptsRow = $alreadyAppliedScriptsRows->fetch_assoc()) {
        $alreadyAppliedScripts[$alreadyAppliedScriptsRow["name"]] = array(
            "hash" => $alreadyAppliedScriptsRow["hash"],
            "timestamp" => $alreadyAppliedScriptsRow["timestamp"]);
    }

    foreach ($migrationScriptFileNames as &$migrationScriptFileName) {
        $path = $migrationScriptsBasePath . $migrationScriptFileName;
        $hash = hash_file("sha256", $path);

        if (!array_key_exists($migrationScriptFileName, $alreadyAppliedScripts)) {
            $handle = fopen($path, "r");
            $migrationScript = fread($handle, filesize($path));
            fclose($handle);

            $tablesToBackupRow = $databaseProvider
                ->query("SELECT (SELECT GROUP_CONCAT(TABLE_NAME SEPARATOR ',') FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE <> 'VIEW' AND TABLE_NAME NOT LIKE 'cache_%' AND TABLE_SCHEMA = DATABASE() AND TABLE_NAME NOT IN (SELECT SUBSTRING(TABLE_NAME, 2) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'VIEW' AND TABLE_SCHEMA = DATABASE())) AS tables");
            $tablesToBackup = $tablesToBackupRow->fetch_assoc()["tables"];

            $apiKeyRow = $databaseProvider
                ->query("SELECT api_key FROM users WHERE FIND_IN_SET('ADMIN', roles)");
            $apiKey = $apiKeyRow->fetch_assoc()["api_key"];

            $accessTokenResponse = (new GetHttpResponseProcessor())
                ->process(array(
                    "method" => "POST", 
                    "url" => "https://" . $hostName . "/iam",
                    "payload" => json_encode(array(
                        "apiKey" => $apiKey))));
        
            (new GetHttpResponseProcessor())
                ->process(array(
                    "method" => "POST", 
                    "url" => "https://" . $hostName . "/api/jobs/run",
                    "headers" => "Authorization: Bearer " . $accessTokenResponse["accessToken"],
                    "payload" => json_encode(array(
                        "action" => "BackupDatabase", 
                        "args" => array(
                            "tables" => $tablesToBackup)))));

            $migrationScriptFileNameTokens = explode("-", $migrationScriptFileName);
            $delimiter = count($migrationScriptFileNameTokens) == 3 ? str_replace(".sql", "", $migrationScriptFileNameTokens[2]) : ";";

            $databaseProvider->beginTransaction();
            $lastMigrationSubscript = "";
            try {
                set_error_handler($onError);

                foreach (explode($delimiter, $migrationScript) as &$migrationSubScript) {
                    if (trim($migrationSubScript) !== '') {                        
                        $lastMigrationSubscript = $migrationSubScript;
                        $databaseProvider->query($migrationSubScript);
                    }
                }

                $databaseProvider
                    ->statementBuilder("INSERT INTO migration_script (name, hash, timestamp) VALUES (?, ?, UNIX_TIMESTAMP())")
                    ->withParameters($migrationScriptFileName, $hash)
                    ->execute();
                
                $databaseProvider->commit();
            }
            catch (Throwable $e) {
                $databaseProvider->rollback();
                http_response_code(500);
                die($lastMigrationSubscript . " - " . $e->getMessage());
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
    
    http_response_code(200);
    echo "The database was successfully migrated.";
?>