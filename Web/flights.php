<?php 
    session_start();
    $requiredFiles = array(
        "component/map.js",
        "flights.js"
    );
?>
<!DOCTYPE html>
<html>
    <head>
        <?php
            require_once(dirname(__FILE__) . "/php/header.php");
        ?> 
        <title>Cestovní portál</title>
        <script>
            loadPage(async () => await init(<?php echo json_encode(isset($_COOKIE["accessToken"]) && in_array("ADMIN", json_decode($_COOKIE["accessToken"], TRUE)["roles"])); ?>));
        </script>
    </head>
    <body>
        <div id="mainMenu"></div>
        <h1 id="title"></h1>
        <div class="mainMap" id="map"></div>
        <div id="main" class="component"></div>
        <div id="footer"></div>
    </body>
</html>