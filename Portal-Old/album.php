<?php 
    session_start();
    $requiredFiles = array(
        "album.js",
        "component/albums.js"
    );
?>
<!DOCTYPE html>
<html>
    <head>
        <?php
            require_once(dirname(__FILE__) . "/php/header.php");
        ?> 
        <script>    
            loadPage(async () => await init("<?php echo $_GET['placeId']; ?>", "<?php echo $_GET['albumId']; ?>", <?php echo json_encode(isset($_COOKIE["accessToken"]) && in_array("ADMIN", json_decode($_COOKIE["accessToken"], TRUE)["roles"]) && in_array("ADMIN", json_decode($_COOKIE["accessToken"], TRUE)["roles"])); ?>));            
        </script>
    </head>
    <body>
        <div id="photos" class="component"></div>
    </body>
</html>