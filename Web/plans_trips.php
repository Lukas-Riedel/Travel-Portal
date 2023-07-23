<?php 
    session_start();
    $requiredFiles = array(
        "component/map.js",
        "component/calendar.js",
        "plans_trips.js"
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
            loadPage(async () => await init(<?php echo json_encode(isset($_SESSION["authToken"])); ?>));
        </script>
    </head>
    <body>
        <h1 id="title"></h1>
        <div class="mainMap" id="map"></div>
        <div id="main" class="component"></div>
    </body>
</html>