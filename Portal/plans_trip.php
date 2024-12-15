<?php 
    session_start();
    $requiredFiles = array(
        "component/map.js",
        "component/calendar.js",
        "plans_trip.js"
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
            loadPage(async () => await init("<?php echo $_GET['tripId']; ?>", <?php echo json_encode(isset($_COOKIE["accessToken"]) && in_array("ADMIN", json_decode($_COOKIE["accessToken"], TRUE)["roles"])); ?>));
        </script>
    </head>
    <body>
        <div id="header" class="horizontal">
            <div id="info">
                <h1 id="name"></h1>
                <div id="notes"></div>
                <div id="holidays"></div>
            </div>
            <div id="map"></div>
        </div>
        <div id="calendar" class="component"></div>
        <div id="footer" class="horizontal">
            <div id="timezone"></div>
            <div id="utilities"></div>
        </div>
    </body>
</html>