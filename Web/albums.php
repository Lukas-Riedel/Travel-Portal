<?php 
    require_once(dirname(__FILE__) . "/php/login.php");
    $requiredFiles = array();
?>
<!DOCTYPE html>
<html>
    <head>
        <?php
            require_once(dirname(__FILE__) . "/php/header.php");
        ?>
        <title>Cestovní portál</title>
        <script>
            const apiClient = new Api("<?php echo $configuration["hostName"]; ?>");
        </script>
    </head>
    <body>
        <a href="php/runner.php" target="_blank">Spustit runner.php</a><br>
        <a onclick='apiClient.runJob("UpdateAlbum", {}).then(alertConfirmation)'>Aktualizovat alba</a><br>
        <a onclick='apiClient.runJob("PruneDatabase", {}).then(alertConfirmation)'>Pročistit databázi</a><br>
        <h2>Zmigrovaná nesmazaná alba</h2>
        <ol>
            <?php
                $albumsToDelete = $databaseProvider
                    ->statementBuilder("SELECT a.* FROM album a INNER JOIN album_identifier ai ON a.id = ai.id WHERE ai.replacement_album_id IS NOT NULL AND ai.replacement_album_id <> ai.id")
                    ->getResultSet();
                foreach ($albumsToDelete as &$album) {
                    ?><li><a href="<?php echo $album["permalink"]; ?>" target="_blank"><?php echo $album["name"]; ?></a></li><?php
                }
            ?>
        </ol>
        <h2>Alba k migraci</h2>
        <ol>
            <?php
                $albumsToDelete = $databaseProvider
                    ->statementBuilder("SELECT ps.* FROM place_summary ps INNER JOIN album_identifier ai ON ps.album_id = ai.id WHERE ai.replacement_album_id IS NULL")
                    ->getResultSet();
                foreach ($albumsToDelete as &$album) {
                    ?><li><?php echo $album["name"] . " " . date("j.n.Y", $album["start"]); ?> - <a onclick='apiClient.scheduleJob("ReuploadPhotos", <?php echo json_encode(array("placeId" => $album["place_id"], "albumId" => $album["album_id"])); ?>).then(alertConfirmation)'>Zahájit migraci</a> (<?php echo $album["album_images_count"]; ?> fotek, pošle <?php echo ceil($album["album_images_count"] / 100) * 4 + ceil($album["album_images_count"] / 50) + 4; ?> requestů)</li><?php
                }
            ?>
        </ol>
    </body>
</html>