<?php
    session_start();
    
    require_once(dirname(__FILE__) . "/provider/DatabaseProvider.php");
    require_once(dirname(__FILE__) . "/provider/ConfigurationProvider.php");

    $databaseProvider = new DatabaseProvider(TRUE);
    $configurationProvider = new ConfigurationProvider($databaseProvider);
    $configuration = $configurationProvider->get(PUBLIC_CONFIGURATION, PRIVATE_CONFIGURATION);

    if (!isset($_SESSION["authToken"])) {
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