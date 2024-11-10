<?php
    require_once(dirname(__FILE__) . "/provider/DatabaseProvider.php");
    require_once(dirname(__FILE__) . "/provider/ConfigurationProvider.php");

    $databaseProvider = new DatabaseProvider(TRUE);
    $configurationProvider = new ConfigurationProvider($databaseProvider);
    $configuration = $configurationProvider->get(PUBLIC_CONFIGURATION, PRIVATE_CONFIGURATION);
?>
<meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" href="img/icon.jpg"/>
        <script>
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/js/service-worker.js');
            }
        </script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/js-cookie@3.0.5/dist/js.cookie.min.js"></script>
        <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo $configuration["googleMapsApiKeys"]["website"] ?>&v=weekly&callback=Function.prototype" defer></script>
        <?php
    if (!isset($requiredFiles)) {
        $requiredFiles = array();
    }
    $rootPath = dirname(__FILE__) . "/../js";
    $commonFilesPath = $rootPath . "/common";
    $commonFiles = array_diff(scandir($commonFilesPath), array('.', '..'));
    $version = filemtime(dirname(__FILE__) . "/../css/main.css")
        + filemtime(dirname(__FILE__) . "/../json/manifest.json") 
        + filemtime(dirname(__FILE__) . "/../js/service-worker.js");
    foreach ($commonFiles as &$file) {
        $version += filemtime($commonFilesPath . "/" . $file);
    }
    foreach ($requiredFiles as &$file) {
        $version += filemtime($rootPath . "/" . $file);
    }
    $version = "?v=" . $version;
    foreach ($commonFiles as &$file) {
        ?><script src="https://<?php echo $configuration["hostName"]; ?>/js/common/<?php echo $file . $version; ?>"></script>
        <?php
    }
    foreach ($requiredFiles as &$file) {
        ?><script src="https://<?php echo $configuration["hostName"]; ?>/js/<?php echo $file . $version; ?>"></script>
        <?php
    }
?>
<link rel="stylesheet" href="https://<?php echo $configuration["hostName"]; ?>/css/main.css<?php echo $version; ?>">
<link rel="manifest" href="json/manifest.json<?php echo $version; ?>">