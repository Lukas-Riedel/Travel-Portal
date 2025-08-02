<?php
    use Service\Service\Authentication\AuthenticationService;

    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');
    
    $delayViewMaterializationIfNeeded = TRUE;
    require_once(__DIR__ . "/bootstrap.php");

    $requestBody = json_decode(file_get_contents('php://input'), TRUE);

    try {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            if (isset($_GET["code"])) {
                $authenticationService->fetchGoogleApiRefreshToken($_GET["code"]);
                header("Location: " . BASE_URL);
            }
            else {       
                header("Location: https://accounts.google.com/o/oauth2/v2/auth?client_id=" 
                    . GOOGLE_API_CLIENT_ID . "&prompt=consent&redirect_uri=" 
                    . IAM_BASE_URL . "&response_type=code&access_type=offline&scope=" 
                    . implode(" ", AuthenticationService::GOOGLE_API_AUTHORIZATION_SCOPES));
            }
            exit;
        }

        $authenticationResult = NULL;
        if (isset($requestBody["apiKey"])) {
            $authenticationResult = $authenticationService->authenticateWithApiKey($requestBody["apiKey"]);
        }
        else if (isset($requestBody["username"]) && isset($requestBody["password"])) {
            $authenticationResult = $authenticationService->authenticateWithCredentials($requestBody["username"], $requestBody["password"]);
        }
        else if (isset($requestBody["refreshToken"])) {
            $authenticationResult = $authenticationService->authenticateWithRefreshToken($requestBody["refreshToken"]);
        }
        else {
            throw new InvalidArgumentException("Some of the required arguments is missing.");
        }

        echo json_encode($authenticationResult, JSON_HEX_QUOT | JSON_HEX_TAG);
    }
    catch (Throwable $e) {
        $error = new TargetError(401, $e, $requestBody);
        http_response_code($error->getCode());
        echo json_encode($error, JSON_HEX_QUOT | JSON_HEX_TAG);
    }
?>