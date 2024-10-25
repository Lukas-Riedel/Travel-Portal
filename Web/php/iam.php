<?php
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');
    
    require_once(dirname(__FILE__) . "/provider/DatabaseProvider.php");
    require_once(dirname(__FILE__) . "/provider/ConfigurationProvider.php");
    require_once(dirname(__FILE__) . "/model/TargetError.php");
    require_once(dirname(__FILE__) . "/service/AuthenticationService.php");

    $databaseProvider = new DatabaseProvider(TRUE);
    $configurationProvider = new ConfigurationProvider($databaseProvider);
    $configuration = $configurationProvider->get(PUBLIC_CONFIGURATION, PRIVATE_CONFIGURATION);
    $authenticationService = new AuthenticationService();

    $requestBody = json_decode(file_get_contents('php://input'), TRUE);

    try {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            throw new ErrorException("Invalid HTTP method.");
        }

        $authenticationResult = NULL;
        if (isset($requestBody["apiKey"])) {
            $authenticationResult = $authenticationService->authenticateWithApiKey($requestBody["apiKey"]);
        }
        else if (isset($requestBody["username"]) && isset($requestBody["password"])) {
            $authenticationResult = $authenticationService->authenticateWithCredentials($requestBody["username"], $requestBody["password"]);
        }
        else {
            throw new InvalidArgumentException("Some of the required arguments are missing.");
        }

        echo json_encode($authenticationResult, JSON_HEX_QUOT | JSON_HEX_TAG);
    }
    catch (Throwable $e) {
        $error = new TargetError($e, "IAM", $requestBody, FALSE);
        http_response_code($error->getCode());
        echo json_encode($error, JSON_HEX_QUOT | JSON_HEX_TAG);
    }
?>