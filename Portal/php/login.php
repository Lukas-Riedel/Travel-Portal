<?php
    session_start();
    
    require_once(dirname(__FILE__) . "/../api/php/config/secrets.php");
    require_once(dirname(__FILE__) . "/../api/php/provider/DatabaseProvider.php");
    require_once(dirname(__FILE__) . "/../api/php/provider/ConfigurationProvider.php");
    require_once(dirname(__FILE__) . "/../api/php/service/AuthenticationService.php");
    require_once(dirname(__FILE__) . "/../api/php/service/ConfigurationService.php");

    $databaseProvider = new DatabaseProvider(TRUE);
    $configurationProvider = new ConfigurationProvider($databaseProvider);
    $configuration = $configurationProvider->get(PUBLIC_CONFIGURATION, PRIVATE_CONFIGURATION);
    $configurationService = new ConfigurationService();
    $authenticationService = new AuthenticationService($databaseProvider, $configurationService);

    if (!isset($_COOKIE["accessToken"]) || $_COOKIE["accessToken"] === NULL) {
        if (isset($_GET["apiKey"])) {
            try {
                $accessTokenResponse = $authenticationService->authenticateWithApiKey($_GET["apiKey"]);
                setcookie("accessToken", json_encode($accessTokenResponse), time() + $accessTokenResponse->getValidity(), "/");
                setcookie("refreshToken", $accessTokenResponse->getRefreshToken(), 0, "/");
    
                header("Location: " . $_SERVER["REQUEST_URI"]);
                exit();
            }
            catch (Exception $e) {
                // Do nothing.
            }
        }
        
        if (isset($_COOKIE["refreshToken"]) && $_COOKIE["refreshToken"] !== NULL) {
            try {
                $accessTokenResponse = $authenticationService->authenticateWithRefreshToken($_COOKIE["refreshToken"]);
                setcookie("accessToken", json_encode($accessTokenResponse), time() + $accessTokenResponse->getValidity(), "/");
                setcookie("refreshToken", $accessTokenResponse->getRefreshToken(), 0, "/");
    
                header("Location: " . $_SERVER["REQUEST_URI"]);
                exit();
            }
            catch (Exception $e) {
                // Do nothing.
            }
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