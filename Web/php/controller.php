<?php
    header('Content-Type: application/json');

    session_start();
    
    require_once(dirname(__FILE__) . "/provider/DatabaseProvider.php");
    require_once(dirname(__FILE__) . "/provider/LoggingProvider.php");
    require_once(dirname(__FILE__) . "/provider/ConfigurationProvider.php");
    require_once(dirname(__FILE__) . "/provider/SchedulingProvider.php");
    require_once(dirname(__FILE__) . "/provider/ProcessorProvider.php");
    require_once(dirname(__FILE__) . "/processor/Processor.php");

    $databaseProvider = new DatabaseProvider(TRUE);
    $configurationProvider = new ConfigurationProvider($databaseProvider);
    $configuration = $configurationProvider->get(PUBLIC_CONFIGURATION, PRIVATE_CONFIGURATION);
    $loggingProvider = new LoggingProvider($databaseProvider);
    $schedulingProvider = new SchedulingProvider($databaseProvider, $configuration);
    $processorProvider = new ProcessorProvider($databaseProvider, $schedulingProvider, $loggingProvider, isset($_COOKIE["accessToken"]) && in_array("ADMIN", explode(",", $_COOKIE["roles"])), TRUE, TRUE);

    $args = getProcessorArguments();

    if (isset($_GET["async"]) && $_GET["async"] == "true") {
        $schedulingProvider
            ->scheduleJobExecution($_GET["action"], $args, 0);
        require_once(dirname(__FILE__) . "/runner.php");
    }
    else {
        $result = $processorProvider->run($_GET["action"], $args);
        echo json_encode($result, JSON_HEX_QUOT | JSON_HEX_TAG);
    }
    
    function getProcessorArguments() {
        $args = array_merge($_GET, $_POST);
        unset($args["action"]);
        unset($args["async"]);
        unset($args["forceReload"]);
        return $args;
    }
?>