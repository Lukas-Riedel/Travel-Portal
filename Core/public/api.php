<?php
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json");
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");

    use Core\Service\Authentication\AuthenticationException;
    
    require_once(__DIR__ . "/src/php/bootstrap.php");
    
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
    
        $handlers = array();
        foreach (array_diff(scandir(__DIR__ . "/src/php/Rest"), array('.', '..', 'Handler.php')) as &$handlerFileName) {
            require_once(__DIR__ . "/src/php/Rest/" . $handlerFileName);
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
                        throw new \Core\Routing\AuthorizationException($accessToken);
                    }
                }

                $userId = NULL;
                $roles = array();
                if (isset($accessToken)) {
                    $userId = $accessToken->getUserId();
                    $roles = $accessToken->getRoles();
                }
    
                $r = $handler->handle($input);
                $databaseProvider->commit();
    
                http_response_code($r["code"]);
                echo json_encode($r["body"], JSON_HEX_QUOT | JSON_HEX_TAG);
    
                break;
            }
        }
    } 
    catch (Throwable $e) {
        $databaseProvider->rollback();
        $error = new TargetError(getErrorCode($e), $e, $input);
        http_response_code($error->getCode());
        echo json_encode($error, JSON_HEX_QUOT | JSON_HEX_TAG);
    }
    finally {        
        restore_error_handler();
    }
    $databaseProvider->materializeViews();  

    function getErrorCode($e) {
        // TODO: Differentiate between 4xx and 5xx
        if ($e instanceof EntityNotFoundException) {
            return 404;
        }
        if ($e instanceof AuthenticationException) {
            return 401;
        }
        if ($e instanceof \Core\Routing\AuthorizationException) {
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