<?php
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');
    
    session_start();

    require_once(dirname(__FILE__) . "/../vendor/autoload.php");
    
    require_once(dirname(__FILE__) . "/provider/DatabaseProvider.php");
    require_once(dirname(__FILE__) . "/provider/LoggingProvider.php");
    require_once(dirname(__FILE__) . "/provider/ConfigurationProvider.php");
    require_once(dirname(__FILE__) . "/rest/Handler.php");
    require_once(dirname(__FILE__) . "/model/TargetError.php");
    require_once(dirname(__FILE__) . "/exception/AuthenticationException.php");
    require_once(dirname(__FILE__) . "/exception/AuthorizationException.php");
    require_once(dirname(__FILE__) . "/exception/EntityNotFoundException.php");
    require_once(dirname(__FILE__) . "/service/AuthenticationService.php");
    require_once(dirname(__FILE__) . "/service/PlaceService.php");
    require_once(dirname(__FILE__) . "/service/HighlightService.php");
    require_once(dirname(__FILE__) . "/service/PhotoService.php");
    require_once(dirname(__FILE__) . "/service/TripService.php");
    require_once(dirname(__FILE__) . "/service/FlightService.php");
    require_once(dirname(__FILE__) . "/service/CategoryService.php");
    require_once(dirname(__FILE__) . "/service/ExpenseService.php");
    require_once(dirname(__FILE__) . "/service/YearService.php");
    require_once(dirname(__FILE__) . "/service/NoteService.php");
    require_once(dirname(__FILE__) . "/service/ConfigurationService.php");
    require_once(dirname(__FILE__) . "/service/TimeTrackingService.php");
    require_once(dirname(__FILE__) . "/service/FitnessService.php");
    require_once(dirname(__FILE__) . "/service/StatisticsService.php");
    require_once(dirname(__FILE__) . "/service/PlatformService.php");
    require_once(dirname(__FILE__) . "/client/GoogleApiClient.php");
    require_once(dirname(__FILE__) . "/client/ChatClient.php");
    require_once(dirname(__FILE__) . "/client/HttpClient.php");
    require_once(dirname(__FILE__) . "/client/CalendarClient.php");
    require_once(dirname(__FILE__) . "/service/GeocodingService.php");
    require_once(dirname(__FILE__) . "/service/StayService.php");
    require_once(dirname(__FILE__) . "/service/ForecastService.php");
    require_once(dirname(__FILE__) . "/event/Scheduler.php");
    require_once(dirname(__FILE__) . "/event/EventManager.php");
    require_once(dirname(__FILE__) . "/event/EventPublisher.php");

    $databaseProvider = new DatabaseProvider(TRUE);
    $configurationProvider = new ConfigurationProvider($databaseProvider);
    $configuration = $configurationProvider->get(PUBLIC_CONFIGURATION, PRIVATE_CONFIGURATION);
    $authenticationService = new AuthenticationService();
    $placeService = new PlaceService();
    $highlightService = new HighlightService();
    $tripService = new TripService();
    $yearService = new YearService();
    $noteService = new NoteService();
    $configurationService = new ConfigurationService();
    $timeTrackingService = new TimeTrackingService();
    $googleApiClient = new GoogleApiClient();
    $chatClient = new ChatClient();
    $httpClient = new HttpClient();
    $statisticsService = new StatisticsService();
    $geocodingService = new GeocodingService();
    $calendarClient = new CalendarClient();
    $stayService = new StayService();
    $platformService = new PlatformService();
    $eventManager = new EventManager();
    $eventPublisher = new EventPublisher();
    $scheduler = new Scheduler($databaseProvider, $eventPublisher);
    $categoryService = new CategoryService($databaseProvider, $configurationService, $highlightService, $statisticsService, $eventPublisher);
    $photoService = new PhotoService($databaseProvider, $googleApiClient, $configurationService, $eventPublisher, $scheduler);
    $expenseService = new ExpenseService($databaseProvider, $httpClient, $configurationService, $eventPublisher);
    $fitnessService = new FitnessService($databaseProvider, $configurationService, $eventPublisher, $scheduler);
    $flightService = new FlightService($databaseProvider, $geocodingService, $categoryService, $httpClient, $calendarClient, $googleApiClient, $eventPublisher, $scheduler);
    $forecastService = new ForecastService($databaseProvider, $httpClient, $configurationService, $eventPublisher, $scheduler);
    
    $onError = function($level, $message, $file, $line) {
        throw new RuntimeException($message);
    };

    if (!isset($_GET["path"])) {
        header("Location: " . BASE_URL); 
    }

    $path = $_GET["path"];
    unset($_GET["path"]);
    
    $requestBody = json_decode(file_get_contents('php://input'), TRUE);
    $input = array_merge(filterArrayKeys($_GET), filterArrayKeys($requestBody ?? []));
    
    $databaseProvider->beginTransaction();        
    try {            
        set_error_handler($onError);
        $loggingProvider = new LoggingProvider($databaseProvider);
    
        $handlers = array();
        foreach (array_diff(scandir(dirname(__FILE__) . "/rest"), array('.', '..', 'Handler.php')) as &$handlerFileName) {
            require_once(dirname(__FILE__) . "/rest/" . $handlerFileName);
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

                    if (!in_array($handler->getRequiredRole(), $accessToken->getRoles())) {
                        throw new AuthorizationException("The user is not authorized to perform this action.");
                    }
                }
    
                $response = $handler->handle($input);
                $databaseProvider->commit();
    
                http_response_code($response["code"]);
                echo json_encode($response["body"], JSON_HEX_QUOT | JSON_HEX_TAG);
    
                exit();
            }
        }
    } 
    catch (Throwable $e) {
        $databaseProvider->rollback();
        $error = new TargetError(getErrorCode($e), $e, $input);
        http_response_code($error->getCode());
        echo json_encode($error, JSON_HEX_QUOT | JSON_HEX_TAG);
        $loggingProvider->logError(json_encode($error, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG));
        exit();
    }
    finally {        
        restore_error_handler();
    }
    $databaseProvider->materializeViews();  

    header("Location: " . BASE_URL); 

    function getErrorCode($e) {
        if ($e instanceof EntityNotFoundException) {
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