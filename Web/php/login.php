<?php
    session_start();
    
    require_once(dirname(__FILE__) . "/provider/DatabaseProvider.php");
    require_once(dirname(__FILE__) . "/provider/ConfigurationProvider.php");
    require_once(dirname(__FILE__) . "/processor/Processor.php");
    require_once(dirname(__FILE__) . "/processor/GetHttpResponseProcessor.php");

    $databaseProvider = new DatabaseProvider(TRUE);
    $configurationProvider = new ConfigurationProvider($databaseProvider);
    $configuration = $configurationProvider->get(PUBLIC_CONFIGURATION, PRIVATE_CONFIGURATION);

    if (isset($_GET["apiKey"])) {
        $accessTokenResponse = (new GetHttpResponseProcessor())
            ->process(array(
                "method" => "POST", 
                "url" => "https://" . $configuration["hostName"] . "/iam",
                "payload" => json_encode(array(
                    "apiKey" => $_GET["apiKey"]))));

        if (isset($accessTokenResponse["accessToken"])) {
            setcookie("accessToken", $accessTokenResponse["accessToken"], time() + $accessTokenResponse["validity"]);
            setcookie("roles", implode(",", $accessTokenResponse["roles"]), time() + $accessTokenResponse["validity"]);

            $_COOKIE["accessToken"] = $accessTokenResponse["accessToken"];
            $_COOKIE["roles"] = implode(",", $accessTokenResponse["roles"]);
        }
    }

    if (!isset($_COOKIE["accessToken"]) || !isset($_COOKIE["roles"]) || !in_array("ADMIN", explode(",", $_COOKIE["roles"]))) {
        $originUrl = substr($_SERVER['PHP_SELF'], strlen($configuration["fileSystemDir"] . "/"));

        if (!empty($_SERVER['QUERY_STRING'])) {
            $originUrl .= "?" . $_SERVER['QUERY_STRING'];
        }

        $authTokenParameter = "";
        if (isset($_GET["authToken"])) {
            $authTokenParameter = "&authToken=" . $_GET["authToken"];
        }

        header("Location: https://" . $configuration["hostName"] . "/login.php?origin=" . rawurlencode($originUrl) . $authTokenParameter); 
        exit;
    }
?>