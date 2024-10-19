<?php
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');
    
    require_once(dirname(__FILE__) . "/provider/DatabaseProvider.php");
    require_once(dirname(__FILE__) . "/provider/ConfigurationProvider.php");
    require_once(dirname(__FILE__) . "/model/TargetError.php");
    require_once(dirname(__FILE__) . "/exception/AuthenticationException.php");

    $databaseProvider = new DatabaseProvider(TRUE);
    $configurationProvider = new ConfigurationProvider($databaseProvider);
    $configuration = $configurationProvider->get(PUBLIC_CONFIGURATION, PRIVATE_CONFIGURATION);

    $requestBody = json_decode(file_get_contents('php://input'), TRUE);

    try {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            throw new ErrorException("Invalid HTTP method.");
        }

        $roles = array();
        if (isset($requestBody["apiKey"])) {
            $roles = explode(",", $databaseProvider
                ->statementBuilder("SELECT roles FROM users WHERE api_key = ?")
                ->withParameters($requestBody["apiKey"])
                ->getSingleColumn("roles"));

            if ($roles == NULL) {
                throw new AuthenticationException("No user for the provided API key was found.");
            }
        }
        else if (isset($requestBody["username"]) && isset($requestBody["password"])) {
            $userRow = $databaseProvider
                ->statementBuilder("SELECT * FROM users WHERE username = ?")
                ->withParameters($requestBody["username"])
                ->getSingleRow();

            if ($userRow == NULL) {
                throw new AuthenticationException("The user '" . $requestBody["username"] . "' was not found.");
            }

            if (!password_verify($requestBody["password"], $userRow["password"])) {
                throw new AuthenticationException("Passowrd for the user '" . $requestBody["username"] . "' is invalid.");
            }

            $roles = explode(",", $userRow["roles"]);
        }
        else {
            throw new InvalidArgumentException("Some of the required arguments are missing.");
        }

        $result = array(
            "roles" => $roles,
            "expiration" => time() + $configuration["bearerToken"]["validity"]);

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($configuration["bearerToken"]["cipher"]));
        $encrypted = openssl_encrypt(json_encode($result), $configuration["bearerToken"]["cipher"], $configuration["bearerToken"]["privateKey"], 0, $iv);
        $accessToken = base64_encode($encrypted . '::' . $iv);

        echo json_encode(array(
            "accessToken" => $accessToken,
            "roles" => $roles,
            "validity" => $configuration["bearerToken"]["validity"]), JSON_HEX_QUOT | JSON_HEX_TAG);
    }
    catch (Throwable $e) {
        $error = new TargetError($e, "IAM", $requestBody, FALSE);
        http_response_code($error->getCode());
        echo json_encode($error, JSON_HEX_QUOT | JSON_HEX_TAG);
    }
?>