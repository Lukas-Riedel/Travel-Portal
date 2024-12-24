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

        public function getExternalIdentifier($albumId) : ?string {
            global $databaseProvider;
            
            return $databaseProvider
                ->statementBuilder("SELECT external_id FROM album_identifier WHERE id = ?")
                ->withParameters($albumId)
                ->getFirstColumn("external_id");
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
            global $placeService, $googleApiClient;

            $place = $placeService->getRegularPlace($placeId);
            if ($place === NULL) {            
                throw new EntityNotFoundException("place", $placeId);
            }

            $albumName = $this->getAlbumName($place->getName(), $timestamp);
            $createdAlbumExternalId = $googleApiClient->createAlbum($albumName);
            $resolvedAlbumId = $this->getOrCreateAlbumIdentifier($createdAlbumExternalId);
            $this->updateAlbum($resolvedAlbumId);

            return $this->getAlbum($resolvedAlbumId);
        }

        public function updateAlbum($albumId) : bool {
            return (new UpdateAlbumProcessor())
                ->process(array(
                    "albumId" => $albumId));
        }

        private function getAlbumName($placeName, $timestamp) : string {
            return $placeName . " " . date("j.n.Y", $timestamp);
        }
    }
?>