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

    $databaseProvider = new DatabaseProvider(TRUE);
    $configurationProvider = new ConfigurationProvider($databaseProvider);
    $configuration = $configurationProvider->get(PUBLIC_CONFIGURATION, PRIVATE_CONFIGURATION);
    
    $onError = function($level, $message, $file, $line) {
        throw new RuntimeException($message);
    };
    
    try {            
        set_error_handler($onError);
        $loggingProvider = new LoggingProvider($databaseProvider);
        $schedulingProvider = new SchedulingProvider($databaseProvider, $configuration);
        $processorProvider = new ProcessorProvider($databaseProvider, $schedulingProvider, $loggingProvider, TRUE, TRUE, FALSE);
    
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

                if ($handler->isProtected()) {
                    $bearer = getBearerToken();
                    if ($bearer === NULL) {
                        throw new AuthenticationException("The access token was not provided.");
                    }

                    $decoded = base64_decode($bearer);
                    if ($decoded === FALSE) {
                        throw new AuthenticationException("The access token could not be read.");
                    }
            
                    $parts = explode('::', $decoded, 2);
                    if (count($parts) !== 2) {
                        throw new AuthenticationException("The access token could not be read.");
                    }
                    
                    list($encryptedData, $iv) = $parts;
                    $decrypted = openssl_decrypt($encryptedData, $configuration["bearerToken"]["cipher"], $configuration["bearerToken"]["privateKey"], 0, $iv);
                    if ($decrypted === FALSE) {
                        throw new AuthenticationException("The access token could not be read.");
                    }
                    
                    $decodedAccessToken = json_decode($decrypted, TRUE);
                    if ($decodedAccessToken === NULL) {
                        throw new AuthenticationException("The access token could not be read.");
                    }
            
                    if ($decodedAccessToken["expiration"] < time()) {
                        throw new AuthenticationException("The access token expired at " . $decodedAccessToken["expiration"] . ".");
                    }

                    if ($decodedAccessToken["version"] !== $configuration["bearerToken"]["version"]) {
                        setcookie("accessToken", "", time() - 3600, "/");
                        setcookie("roles", "", time() - 3600, "/");
                        throw new AuthenticationException("The access token version " . $decodedAccessToken["version"] . " is outdated.");
                    }
    
                    if (!in_array($handler->getRequiredRole(), $decodedAccessToken["roles"])) {
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
        $error = new TargetError($e, "API", array(), FALSE);
        http_response_code($error->getCode());
        echo json_encode($error, JSON_HEX_QUOT | JSON_HEX_TAG);
        exit();
    }
    finally {        
        restore_error_handler();
    }

    header("Location: https://" . $configuration["hostName"] . "/swagger");

    function getAuthorizationHeader() {
        $headers = NULL;
        if (isset($_SERVER['REDIRECT_GOOG_CHANNEL_TOKEN'])) {
            $headers = trim($_SERVER["REDIRECT_GOOG_CHANNEL_TOKEN"]);
        }
        if (isset($_SERVER['Authorization'])) {
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
?>