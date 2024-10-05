<?php
    require_once(dirname(__FILE__) . "/provider/DatabaseProvider.php");

    $databaseProvider = new DatabaseProvider(FALSE);
    
    $onError = function($level, $message, $file, $line) {
        throw new RuntimeException($message);
    };

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

            $databaseProvider->beginTransaction();
            $lastMigrationSubscript = "";
            try {
                set_error_handler($onError);

                foreach (explode(";", $migrationScript) as &$migrationSubScript) {
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