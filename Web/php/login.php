<?php
    session_start();
    
    require_once(dirname(__FILE__) . "/provider/DatabaseProvider.php");
    require_once(dirname(__FILE__) . "/provider/ConfigurationProvider.php");
    require_once(dirname(__FILE__) . "/processor/Processor.php");
    require_once(dirname(__FILE__) . "/service/AuthenticationService.php");

    $databaseProvider = new DatabaseProvider(TRUE);
    $configurationProvider = new ConfigurationProvider($databaseProvider);
    $configuration = $configurationProvider->get(PUBLIC_CONFIGURATION, PRIVATE_CONFIGURATION);
    $authenticationService = new AuthenticationService();

    if (isset($_GET["apiKey"]) && !isset($_COOKIE["accessToken"])) {
        try {
            $accessTokenResponse = $authenticationService->authenticateWithApiKey($_GET["apiKey"]);

            setcookie("accessToken", $accessTokenResponse["accessToken"], time() + $accessTokenResponse["validity"], "/");
            setcookie("roles", implode(",", $accessTokenResponse["roles"]), time() + $accessTokenResponse["validity"], "/");

            header("Location: " . $_SERVER["REQUEST_URI"]);
            exit();
        }
        catch (Exception $e) {
            // Do nothing.
        }
    }

    if (!isset($_COOKIE["accessToken"]) || !isset($_COOKIE["roles"]) || !in_array("ADMIN", explode(",", $_COOKIE["roles"]))) {
        $originUrl = substr($_SERVER['PHP_SELF'], strlen($configuration["fileSystemDir"] . "/"));

        if (!empty($_SERVER['QUERY_STRING'])) {
            $originUrl .= "?" . $_SERVER['QUERY_STRING'];
        }

        $authTokenParameter = "";
        if (isset($_GET["apiKey"])) {
            $authTokenParameter = "&apiKey=" . $_GET["apiKey"];
        }

        header("Location: https://" . $configuration["hostName"] . "/login.php?origin=" . rawurlencode($originUrl) . $authTokenParameter); 
        exit;
    }
?>