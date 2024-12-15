<?php 
    require_once(dirname(__FILE__) . "/php/login.php");
    $requiredFiles = array(
        "acp.js",
        "component/calendar.js",
        "component/expensify.js"
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
            loadPage(async () => await init());            
        </script>
    </head>
    <body>
        <div id="navigation" class="component"></div>
        <div id="trip" class="component"></div>
        <div id="expensify" class="component"></div>
        <div id="utils" class="component"></div>
        <div id="problems" class="component"></div>
        <div id="flights" class="component"></div>
        <div id="configuration" class="component"></div>
    </body>
</html>