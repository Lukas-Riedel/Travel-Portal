<?php 
    session_start();
    $requiredFiles = array(
        "trip.js",
        "component/calendar.js",
        "component/expensify.js",
        "component/albums.js",
        "component/map.js",
        "component/stats.js"
    );
?>
<!DOCTYPE html>
<html>
    <head>
        <?php
            require_once(dirname(__FILE__) . "/php/header.php");
        ?>
        <script>    
            loadPage(async () => await init("<?php echo $_GET['tripId']; ?>", <?php echo json_encode(isset($_COOKIE["accessToken"]) && in_array("ADMIN", json_decode($_COOKIE["accessToken"], TRUE)["roles"])); ?>));            
        </script>
    </head>
    <body>
        <div id="header" class="horizontal">
            <div id="info">
                <h1 id="name"></h1>
                <div id="dates"></div>
                <div id="hotels"></div>
                <div id="flights"></div>
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
        <div id="expensify" class="component"></div>
        <div id="albums" class="component"></div>
        <div id="stats" class="component"></div>
    </body>
</html>