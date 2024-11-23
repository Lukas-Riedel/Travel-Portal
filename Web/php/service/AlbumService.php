<?php
    require_once(dirname(__FILE__) . "/../model/Album.php");
    require_once(dirname(__FILE__) . "/../exception/EntityNotFoundException.php");
    require_once(dirname(__FILE__) . "/../processor/GetGoogleResponseProcessor.php");
    require_once(dirname(__FILE__) . "/../processor/UpdateAlbumProcessor.php");

    class AlbumService {
        public function getAlbum($albumId) : ?Album {
            global $databaseProvider;

            $albumRow = $databaseProvider
                ->statementBuilder("SELECT * FROM album WHERE id = ?")
                ->withParameters($albumId)
                ->getSingleRow();
            
            if ($albumRow === NULL) {                
                return NULL;
            }

            return new Album($albumRow["id"], $albumRow["name"], $albumRow["main_photo_id"], $albumRow["thumbnail_url"],
                $albumRow["permalink"], $albumRow["images_count"], $albumRow["indoor_images_count"]);
        }

        public function getAlbumIdentifier($externalId) : ?string {
            global $databaseProvider;
            
            return $databaseProvider
                ->statementBuilder("SELECT id FROM album_identifier WHERE external_id = ?")
                ->withParameters($externalId)
                ->getFirstColumn("id");
        }
        
        public function getOrCreateAlbumIdentifier($externalId) : string {
            global $databaseProvider;

            $albumIdentifier = $this->getAlbumIdentifier($externalId);
            if ($albumIdentifier !== NULL) {
                return $albumIdentifier;
            }

            $databaseProvider
                ->statementBuilder("INSERT INTO album_identifier (external_id) VALUES (?)")
                ->withParameters($externalId)
                ->execute();

            return $this->getAlbumIdentifier($externalId);
        }

        public function createAlbum($placeId, $timestamp) : Album {
            global $placeService;

            $place = $placeService->getRegularPlace($placeId);
            if ($place === NULL) {            
                throw new EntityNotFoundException("place", $placeId);
            }

            $apiResponse = (new GetGoogleResponseProcessor())
                ->process(array(
                    "method" => "POST", 
                    "url" => "https://photoslibrary.googleapis.com/v1/albums", 
                    "payload" => json_encode(array(
                        "album" => array(
                            "title" => $this->getAlbumName($place->getName(), $timestamp))))));
    
            if (!isset($apiResponse["id"])) {
                throw new RuntimeException("The album could not be created.");
            }

            $resolvedAlbumId = $this->getOrCreateAlbumIdentifier($apiResponse["id"]);

            (new UpdateAlbumProcessor())
                ->process(array(
                    "albumId" => $resolvedAlbumId));

            return $this->getAlbum($resolvedAlbumId);
        }

        private function getAlbumName($placeName, $timestamp) : string {
            return $placeName . " " . date("j.n.Y", $timestamp);
        }
    }
?>