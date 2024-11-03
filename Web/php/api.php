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
    require_once(dirname(__FILE__) . "/exception/AuthenticationException.php");
    require_once(dirname(__FILE__) . "/exception/AuthorizationException.php");
    require_once(dirname(__FILE__) . "/exception/EntityNotFoundException.php");
    require_once(dirname(__FILE__) . "/service/AuthenticationService.php");
    require_once(dirname(__FILE__) . "/service/PlaceService.php");
    require_once(dirname(__FILE__) . "/service/HighlightService.php");
    require_once(dirname(__FILE__) . "/service/PhotoService.php");
    require_once(dirname(__FILE__) . "/service/TripService.php");
    require_once(dirname(__FILE__) . "/service/AlbumService.php");
    require_once(dirname(__FILE__) . "/service/CategoryService.php");
    require_once(dirname(__FILE__) . "/service/ExpenseService.php");

    $databaseProvider = new DatabaseProvider(TRUE);
    $configurationProvider = new ConfigurationProvider($databaseProvider);
    $configuration = $configurationProvider->get(PUBLIC_CONFIGURATION, PRIVATE_CONFIGURATION);
    $authenticationService = new AuthenticationService();
    $placeService = new PlaceService();
    $highlightService = new HighlightService();
    $photoService = new PhotoService();
    $tripService = new TripService();
    $albumService = new AlbumService();
    $categoryService = new CategoryService();
    $expenseService = new ExpenseService();
    
    $onError = function($level, $message, $file, $line) {
        throw new RuntimeException($message);
    };    
    
    if (!isset($_GET["path"])) {
        header("Location: https://" . $configuration["hostName"] . "/swagger"); 
    }

    $path = $_GET["path"];
    unset($_GET["path"]);
    
    $requestBody = json_decode(file_get_contents('php://input'), TRUE);
    $input = array_merge(filterArrayKeys($_GET), filterArrayKeys($requestBody ?? []));
    
    try {            
        set_error_handler($onError);
        $loggingProvider = new LoggingProvider($databaseProvider);
        $schedulingProvider = new SchedulingProvider($databaseProvider, $configuration);
        $processorProvider = new ProcessorProvider($databaseProvider, $schedulingProvider, $loggingProvider, TRUE, TRUE, FALSE);
    
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
            if (preg_match("#" . $argValuesRegex . "#", $path, $argValues)) {
                $argNamesRegex = "^" . preg_replace("#\{[^{}]+\}#", "{([^{}]+)}", str_replace("/", "\/", $handler->getPath())) . "(\?.+)?$";
    
                $argNames = array();
                preg_match("#" . $argNamesRegex . "#", $handler->getPath(), $argNames);
    
                for ($i = 1; $i < count($argValues); ++$i) {
                    $input[$argNames[$i]] = $argValues[$i];
                }

                if ($handler->isProtected()) {
                    $accessToken = $authenticationService->getAccessToken(getBearerToken());

                    if (!in_array($handler->getRequiredRole(), $accessToken["roles"])) {
                        throw new AuthorizationException("The user is not authorized to perform this action.");
                    }
                }
    
                $response = $handler->handle($input);
    
                http_response_code($response["code"]);
                echo json_encode($response["body"], JSON_HEX_QUOT | JSON_HEX_TAG);
    
                exit();
            }
        }
    } 
    catch (Throwable $e) {
        $error = new TargetError(getErrorCode($e), $e, $input);
        http_response_code($error->getCode());
        echo json_encode($error, JSON_HEX_QUOT | JSON_HEX_TAG);
        $loggingProvider->logError(json_encode($error, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG));
        exit();
    }
    finally {        
        restore_error_handler();
    }

    header("Location: https://" . $configuration["hostName"] . "/swagger");

    function getErrorCode($e) {
        if ($e instanceof EntryNotFoundException) {
            return 404;
        }
        if ($e instanceof AuthenticationException) {
            return 401;
        }
        if ($e instanceof AuthorizationException) {
            return 403;
        }
        return 400;
    }

    function getAuthorizationHeader() {
        $headers = NULL;
        if (isset($_SERVER['REDIRECT_GOOG_CHANNEL_TOKEN'])) {
            $headers = trim($_SERVER["REDIRECT_GOOG_CHANNEL_TOKEN"]);
        }
        else if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        }
        else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        }
        else if (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }
    
    function getBearerToken() {
        $headers = getAuthorizationHeader();
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return NULL;
    }

    function filterArrayKeys($array) {
        return array_filter($array, function($key) {
            return is_string($key);
        }, ARRAY_FILTER_USE_KEY);
    }
?>