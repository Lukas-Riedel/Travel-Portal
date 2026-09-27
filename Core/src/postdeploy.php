<?php
    require_once(__DIR__ . "/bootstrap.php");

    $logger->pushProcessor(function($record) use(&$loggingContext) {
        $record["context"]["transaction_id"] = $loggingContext->getTransactionId();
        $record["extra"]["transaction_id"] = $loggingContext->getTransactionId();
        return $record;
    });

    $migrationScriptsBasePath = __DIR__ . "/../postdeploy/";

    $sql = <<<'SQL'
        CREATE TABLE IF NOT EXISTS post_deploy_script (
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
        FROM post_deploy_script
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
    }, array_filter((array) glob($migrationScriptsBasePath . "*.php")));
    asort($migrationScriptFileNames);

    foreach ($migrationScriptFileNames as &$migrationScriptFileName) {
        $path = $migrationScriptsBasePath . $migrationScriptFileName;
        $hash = hash_file("sha256", $path);

        if (!array_key_exists($migrationScriptFileName, $alreadyAppliedScripts)) {
            try {
                require_once($path);

                $sql = <<<'SQL'
                    INSERT INTO post_deploy_script (
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
            }
            catch (\Throwable $e) {
                $logger->error("Could not apply " . $migrationScriptFileName . " post-deploy script. Reason: " . $e->getMessage());
                exit(1);
            }
        }
        else if ($hash != $alreadyAppliedScripts[$migrationScriptFileName]["hash"]) {
            $logger->error("Could not apply " . $migrationScriptFileName . " post-deploy script. It was already applied at " . $alreadyAppliedScripts[$migrationScriptFileName]["timestamp"] . ". Expected: " . $alreadyAppliedScripts[$migrationScriptFileName]["hash"] . " Actual: " . $hash);
            exit(1);
        }
    }
?>
