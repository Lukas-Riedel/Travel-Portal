<?php
    session_start();
    
    require_once(dirname(__FILE__) . "/php/provider/DatabaseProvider.php");
    require_once(dirname(__FILE__) . "/php/provider/ConfigurationProvider.php");

    $databaseProvider = new DatabaseProvider(TRUE);
    $configurationProvider = new ConfigurationProvider($databaseProvider);
    $configuration = $configurationProvider->get(PUBLIC_CONFIGURATION, PRIVATE_CONFIGURATION);
    $passwords = $configuration["passwords"];

    if (isset($_GET["authToken"]) && in_array($_GET["authToken"], $passwords)) {
        $_SESSION['authToken'] = $_GET["authToken"];
    }

    if (isset($_POST["password"]) && array_key_exists($_POST["password"], $passwords)) {
        $_SESSION['authToken'] = $passwords[$_POST["password"]];
    }
    
    if (isset($_SESSION['authToken'])) {
        if (isset($_GET["origin"])) {
            header("Location: " . $_GET["origin"]);
            exit;
        }

        header("Location: index.php");
        exit;
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
            <input type="password" name="password"><br><br>
            <input type="submit" value="Login">
        </form>  
    </body>
</html>