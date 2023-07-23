<?php
    session_start();

    require_once(dirname(__FILE__) . "/provider/DatabaseProvider.php");
    require_once(dirname(__FILE__) . "/provider/LoggingProvider.php");
    require_once(dirname(__FILE__) . "/provider/ConfigurationProvider.php");
    require_once(dirname(__FILE__) . "/provider/SchedulingProvider.php");
    require_once(dirname(__FILE__) . "/provider/ProcessorProvider.php");
    require_once(dirname(__FILE__) . "/processor/Processor.php");

    $databaseProvider = new DatabaseProvider();
    $configurationProvider = new ConfigurationProvider($databaseProvider);
    $configuration = $configurationProvider->get(PUBLIC_CONFIGURATION, PRIVATE_CONFIGURATION);
    $loggingProvider = new LoggingProvider($databaseProvider);
    $schedulingProvider = new SchedulingProvider($databaseProvider, $configuration);
    $processorProvider = new ProcessorProvider($databaseProvider, $schedulingProvider, $loggingProvider, TRUE, FALSE, TRUE);

    $key = ftok(__FILE__, 1);
    $semaphore = sem_get($key);

    if (sem_acquire($semaphore, true)) {
        while (($jobExecution = getNextScheduledJobExecution()) != NULL) {
            $result = $processorProvider->run($jobExecution["processor"], $jobExecution["args"]);
            terminateJobExecution($jobExecution["id"]);
            echo json_encode($result, JSON_HEX_QUOT | JSON_HEX_TAG);
        }

        sem_release($semaphore);
    }

    function getNextScheduledJobExecution() {
        global $databaseProvider;

        $nextJob = $databaseProvider
            ->statementBuilder("SELECT * FROM queue_job ORDER BY priority ASC LIMIT 1")
            ->getSingleRow();

        if ($nextJob != NULL) {    
            $nextJob = array(
                "id" => $nextJob["id"],
                "processor" => $nextJob["processor"],
                "args" => json_decode($nextJob["args"], TRUE)
            );
        }

        return $nextJob;
    }

    function terminateJobExecution($id) {
        global $databaseProvider;

        $databaseProvider
	        ->materializeViews();

        $databaseProvider
            ->statementBuilder("DELETE FROM queue_job WHERE id = ?")
            ->withParameters($id)
            ->execute();
    }
?>