<?php
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');
    
    session_start();
    
    require_once(dirname(__FILE__) . "/provider/DatabaseProvider.php");
    require_once(dirname(__FILE__) . "/provider/LoggingProvider.php");
    require_once(dirname(__FILE__) . "/provider/ConfigurationProvider.php");
    require_once(dirname(__FILE__) . "/provider/SchedulingProvider.php");
    require_once(dirname(__FILE__) . "/provider/ProcessorProvider.php");
    require_once(dirname(__FILE__) . "/processor/Processor.php");
    require_once(dirname(__FILE__) . "/handler/Handler.php");
    require_once(dirname(__FILE__) . "/model/TargetError.php");

    $databaseProvider = new DatabaseProvider();
    $configurationProvider = new ConfigurationProvider($databaseProvider);
    $configuration = $configurationProvider->get(PUBLIC_CONFIGURATION, PRIVATE_CONFIGURATION);

    // Temporary before proper authentication.
    if (isset($_COOKIE["authToken"]) && in_array($_COOKIE["authToken"], $configuration["passwords"])) {
        $_SESSION['authToken'] = $_COOKIE["authToken"];
    }

    $loggingProvider = new LoggingProvider($databaseProvider);
    $schedulingProvider = new SchedulingProvider($databaseProvider, $configuration);
    $processorProvider = new ProcessorProvider($databaseProvider, $schedulingProvider, $loggingProvider, isset($_SESSION["authToken"]), TRUE, FALSE);

    if (!isset($_GET["path"])) {
        header("Location: https://" . $configuration["hostName"] . "/swagger"); 
    }

    $handlers = array();
    foreach (array_diff(scandir(dirname(__FILE__) . "/handler"), array('.', '..', 'Handler.php')) as &$handlerFileName) {
        require_once(dirname(__FILE__) . "/handler/" . $handlerFileName);
        $handlerFileNameTokens = explode(".", $handlerFileName);
        $handler = new $handlerFileNameTokens[0];
        if ($handler->getMethod() == $_SERVER["REQUEST_METHOD"]) {
            $handlers[$handler->getPath()] = $handler;
        } 
    }

    krsort($handlers);

    foreach (array_values($handlers) as &$handler) {
        $argValuesRegex = "^" . preg_replace("#\{[^{}]+\}#", "([^\/]+)", str_replace("/", "\/", $handler->getPath())) . "(\?.+)?$";

        $argValues = array();
        if (preg_match("#" . $argValuesRegex . "#", $_GET["path"], $argValues)) {
            $argNamesRegex = "^" . preg_replace("#\{[^{}]+\}#", "{([^{}]+)}", str_replace("/", "\/", $handler->getPath())) . "(\?.+)?$";

            $argNames = array();
            preg_match("#" . $argNamesRegex . "#", $handler->getPath(), $argNames);

            $requestBody = json_decode(file_get_contents('php://input'), TRUE);
            $input = array_merge($_GET, $requestBody == NULL ? array() : $requestBody);
            unset($input["path"]);

            for ($i = 1; $i < count($argValues); ++$i) {
                $input[$argNames[$i]] = $argValues[$i];
            }

            $response = $handler->handle($input);

            http_response_code($response["code"]);
            echo json_encode($response["body"], JSON_HEX_QUOT | JSON_HEX_TAG);

            exit();
        }
    }

    header("Location: https://" . $configuration["hostName"] . "/swagger");
?>