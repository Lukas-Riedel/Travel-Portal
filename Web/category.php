<?php 
    session_start();
    $requiredFiles = array(
        "category.js",
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
            loadPage(async () => await init("<?php echo $_GET['categoryId']; ?>", <?php echo json_encode(isset($_COOKIE["accessToken"]) && in_array("ADMIN", explode(",", $_COOKIE["roles"]))); ?>));
        </script>
    </head>
    <body>
        <h1 id="title"></h1>
        <div class="mainMap" id="map"></div>
        <div id="albums" class="component"></div>
        <div id="stats" class="component"></div>
        <div id="footer"></div>
    </body>
</html>