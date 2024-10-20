<?php 
    session_start();
    $requiredFiles = array(
        "component/map.js",
        "plans.js"
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
            loadPage(async () => await init(<?php echo isset($_GET["categoryId"]) ? "'" . $_GET["categoryId"] . "'" : "undefined"; ?>, <?php echo json_encode(isset($_COOKIE["accessToken"]) && in_array("ADMIN", explode(",", $_COOKIE["roles"]))); ?>));
        </script>
    </head>
    <body>
        <h1 id="title"></h1>
        <div class="mainMap" id="map"></div>
        <div id="main" class="component"></div>
        <div id="footer"></div>
    </body>
</html>