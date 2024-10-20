<?php 
    session_start();
    $requiredFiles = array(
        "place.js",
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
            loadPage(async () => await init("<?php echo $_GET['name']; ?>", "<?php echo $_GET['country']; ?>", <?php echo json_encode(isset($_COOKIE["accessToken"]) && in_array("ADMIN", explode(",", $_COOKIE["roles"])) && in_array("ADMIN", explode(",", $_COOKIE["roles"]))); ?>));            
        </script>
    </head>
    <body>
        <div id="header" class="horizontal">
            <div id="info">
                <h1 id="name"></h1>
                <div id="dates"></div>
                <div id="categories"></div>
            </div>
            <div id="map"></div>
        </div>
        <div id="albums" class="component"></div>
        <div id="nearbyPlaces" class="component"></div>
        <div id="footer"></div>
    </body>
</html>