<?php 
    session_start();
    $requiredFiles = array(
        "points.js",
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
            loadPage(async () => await init("<?php echo $_GET['name']; ?>", "<?php echo $_GET['country']; ?>"));            
        </script>
    </head>
    <body>
        <div id="mobileMap" class="component" style="min-height: 50vh"></div>
        <div id="points" class="component"></div>
    </body>
</html>