<?php
    session_start();
    
    require_once(dirname(__FILE__) . "/../api/php/provider/DatabaseProvider.php");
    require_once(dirname(__FILE__) . "/../api/php/provider/ConfigurationProvider.php");
    require_once(dirname(__FILE__) . "/../api/php/processor/Processor.php");
    require_once(dirname(__FILE__) . "/../api/php/service/AuthenticationService.php");

    $databaseProvider = new DatabaseProvider(TRUE);
    $configurationProvider = new ConfigurationProvider($databaseProvider);
    $configuration = $configurationProvider->get(PUBLIC_CONFIGURATION, PRIVATE_CONFIGURATION);
    $authenticationService = new AuthenticationService();

    if (isset($_GET["apiKey"]) && (!isset($_COOKIE["accessToken"]) || $_COOKIE["accessToken"] === NULL)) {
        try {
            $accessTokenResponse = $authenticationService->authenticateWithApiKey($_GET["apiKey"]);
            setcookie("accessToken", json_encode($accessTokenResponse), time() + $accessTokenResponse->getValidity(), "/");

            header("Location: " . $_SERVER["REQUEST_URI"]);
            exit();
        }
        catch (Exception $e) {
            // Do nothing.
        }
    }

    if (!isset($_COOKIE["accessToken"]) || $_COOKIE["accessToken"] === NULL || !in_array("ADMIN", json_decode($_COOKIE["accessToken"], TRUE)["roles"])) {
        $originUrl = substr($_SERVER['PHP_SELF'], strlen($configuration["fileSystemDir"] . "/"));

        if (!empty($_SERVER['QUERY_STRING'])) {
            $originUrl .= "?" . $_SERVER['QUERY_STRING'];
        }

        $authTokenParameter = "";
        if (isset($_GET["apiKey"])) {
            $authTokenParameter = "&apiKey=" . $_GET["apiKey"];
        }

        header("Location: https://" . $_SERVER["HTTP_HOST"] . "/login.php?origin=" . rawurlencode($originUrl) . $authTokenParameter); 
        exit;
    }
?>