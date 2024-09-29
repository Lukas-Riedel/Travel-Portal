<?php
    require_once(dirname(__FILE__) . "/provider/DatabaseProvider.php");
    require_once(dirname(__FILE__) . "/provider/LoggingProvider.php");
    require_once(dirname(__FILE__) . "/provider/ConfigurationProvider.php");
    require_once(dirname(__FILE__) . "/provider/SchedulingProvider.php");
    require_once(dirname(__FILE__) . "/processor/Processor.php");

    $databaseProvider = new DatabaseProvider();
    $configurationProvider = new ConfigurationProvider($databaseProvider);
    $configuration = $configurationProvider->get(PUBLIC_CONFIGURATION, PRIVATE_CONFIGURATION);
    $schedulingProvider = new SchedulingProvider($databaseProvider, $configuration);

    $schedulers = $databaseProvider
        ->statementBuilder("SELECT * FROM scheduler")
        ->getResultSet();

    foreach ($schedulers as &$scheduler) {
        $interval = doubleval(array_values($databaseProvider
            ->statementBuilder(str_replace("{{name}}", $scheduler["name"], $scheduler["interval_query"]))
            ->getSingleRow())[0]);

        if ($scheduler["last_execution"] + $interval > time()) {
            continue;
        }

        if ($scheduler["args_query"] == NULL) {
            $schedulingProvider
                ->scheduleJobExecution($scheduler["processor"], NULL, NULL);
        }
        else {
            $argumentsList = $databaseProvider
                ->statementBuilder($scheduler["args_query"])
                ->getResultSet();

            foreach ($argumentsList as &$arguments) {
                $schedulingProvider
                    ->scheduleJobExecution($scheduler["processor"], $arguments, NULL);
            }
        }

        $databaseProvider
            ->statementBuilder("UPDATE scheduler SET last_execution = UNIX_TIMESTAMP() WHERE name = ?")
            ->withParameters($scheduler["name"])
            ->execute();
    }

    require_once(dirname(__FILE__) . "/runner.php");
?>