<?php  
    require_once(dirname(__FILE__) . "/../model/Album.php");
    require_once(dirname(__FILE__) . "/GetGoogleResponseProcessor.php");
    require_once(dirname(__FILE__) . "/UpdateAlbumProcessor.php");
    require_once(dirname(__FILE__) . "/GetAlbumIdentifierProcessor.php");

    class AddAlbumProcessor extends Processor {   
        public function process($input) {
            global $databaseProvider;

            $placeName = $databaseProvider
                ->statementBuilder("SELECT * FROM place_identifier WHERE id = ?")
                ->withParameters($input["placeId"])
                ->getSingleColumn("name");
            
            if ($placeName == NULL) {
                throw new InvalidArgumentException("A place with the identifier " . $input["placeId"] . " does not exist.");
            }

            $albumName = $placeName . " " . date("j.n.Y", $input["timestamp"]);            
            $apiResponse = (new GetGoogleResponseProcessor())
                ->process(array(
                    "method" => "POST", 
                    "url" => "https://photoslibrary.googleapis.com/v1/albums", 
                    "payload" => json_encode(array(
                        "album" => array(
                            "title" => $albumName)))));
    
            if (isset($apiResponse["id"])) {
                $resolvedAlbumId = (new GetAlbumIdentifierProcessor())
                    ->process(array(
                        "externalId" => $apiResponse["id"]));

                (new UpdateAlbumProcessor())
                    ->process(array(
                        "albumId" => $resolvedAlbumId));

                $albumRow = $databaseProvider
                    ->statementBuilder("SELECT * FROM album WHERE id = ?")
                    ->withParameters($resolvedAlbumId)
                    ->getSingleRow();

                return new Album($albumRow["id"], $albumRow["name"], $albumRow["main_photo_id"], $albumRow["main_image_url"], $albumRow["permalink"], $albumRow["images_count"], $albumRow["indoor_images_count"]);
            }

            throw new RuntimeException("The album " . $albumName . " could not be added.");
        }

        public function getRequiredArguments() {
            return array("placeId", "timestamp");
        }

        public function requiresAdminRole() {
            return TRUE;
        }
    }
?>