<?php
    session_start();
    
    require_once(dirname(__FILE__) . "/php/provider/DatabaseProvider.php");
    require_once(dirname(__FILE__) . "/php/provider/ConfigurationProvider.php");
    require_once(dirname(__FILE__) . "/php/service/AuthenticationService.php");
    require_once(dirname(__FILE__) . "/php/processor/Processor.php");
    require_once(dirname(__FILE__) . "/php/processor/GetHttpResponseProcessor.php");

    $databaseProvider = new DatabaseProvider(TRUE);
    $configurationProvider = new ConfigurationProvider($databaseProvider);
    $configuration = $configurationProvider->get(PUBLIC_CONFIGURATION, PRIVATE_CONFIGURATION);
    $authenticationService = new AuthenticationService();

    if (isset($_POST["username"]) && isset($_POST["password"])) {
        try {
            $accessTokenResponse = $authenticationService->authenticateWithCredentials($_POST["username"], $_POST["password"]);
            setcookie("accessToken", json_encode($accessTokenResponse), time() + $accessTokenResponse["validity"], "/");

            if (isset($_GET["origin"])) {
                header("Location: " . $_GET["origin"]);
                exit;
            }
    
            header("Location: index.php");
            exit;
        }
        catch (Exception $e) {
            // Do nothing.
        }
    }

    if (isset($_GET["cookies"])) {
        foreach (explode(",", $_GET["cookies"]) as &$cookie) {
            setcookie($cookie, 1, time() + 365 * 86400);
        }
        header("Location: index.php");
    }

    $action = "login.php";
    if (isset($_GET["origin"])) {
        $action .= "?origin=" . rawurlencode($_GET["origin"]);
    }
?>

<!DOCTYPE html>
<html>
    <head>        
        <title>Cestovní portál - Přihlášení</title>
        <?php 
            require_once(dirname(__FILE__) . "/php/header.php");
        ?>
    </head>
    <body style="text-align: center">
        <h1>Login Required</h1>
        <form method="post" action="<?php echo $action; ?>">
            <input type="text" name="username">
            <br><br>
            <input type="password" name="password">
            <br><br>
            <input type="submit" value="Login">
        </form>  
    </body>
</html>