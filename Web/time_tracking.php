<?php 
    session_start();
    $requiredFiles = array(
        "time_tracking.js"
    );
?>
<!DOCTYPE html>
<html>
    <head>
        <?php
            require_once(dirname(__FILE__) . "/php/header.php");
        ?> 
        <title>Sledování času</title>
        <script>
            loadPage(async () => await init(<?php echo json_encode(isset($_SESSION["authToken"])); ?>));
        </script>
    </head>
    <body>
        <div id="tracking" class="component"></div>
        <div id="footer"></div>
    </body>
</html>