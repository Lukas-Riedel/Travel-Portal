<?php
    require_once(__DIR__ . "/bootstrap.php");
    
    $logger->pushProcessor(function($record) use(&$loggingContext) {
        $record["context"]["transaction_id"] = $loggingContext->getTransactionId();
        $record["extra"]["transaction_id"] = $loggingContext->getTransactionId();
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
            catch (\Throwable $e) {
                fwrite(STDERR, $e->getMessage() . PHP_EOL);
                exit(1);
            }
        }
        else if ($hash != $alreadyAppliedScripts[$migrationScriptFileName]["hash"]) {
            fwrite(STDERR, "Could not apply " . $migrationScriptFileName . " migration script. It was already applied at " . $alreadyAppliedScripts[$migrationScriptFileName]["timestamp"] . ". Expected: " . $alreadyAppliedScripts[$migrationScriptFileName]["hash"] . " Actual: " . $hash . PHP_EOL);
            exit(1);
        }
    }
?>