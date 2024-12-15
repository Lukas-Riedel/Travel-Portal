<?php 
    session_start();
    $requiredFiles = array(
        "year.js",
        "component/expensify.js",
        "component/stats.js",
        "component/albums.js",
        "component/map.js"
    );
?>
<!DOCTYPE html>
<html>
    <head>
        <?php
            require_once(dirname(__FILE__) . "/php/header.php");
        ?> 
        <script>
            loadPage(async () => await init(<?php echo $_GET['year']; ?>, <?php echo json_encode(isset($_COOKIE["accessToken"]) && in_array("ADMIN", json_decode($_COOKIE["accessToken"], TRUE)["roles"])); ?>));
        </script>
    </head>
    <body>
        <h1 id="title"></h1>
        <div class="mainMap" id="map"></div>
        <div id="main" class="component"></div>
        <div id="expensify" class="component"></div>
        <div id="albums" class="component"></div>
        <div id="stats" class="component"></div>
        <div id="footer"></div>
    </body>
</html>