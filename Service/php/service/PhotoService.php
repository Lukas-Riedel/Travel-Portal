<?php
    require_once(dirname(__FILE__) . "/../model/Photo.php");

    // TODO: Merge all into AlbumService and rename back to PhotoService?
    class PhotoService {
        public function getPhotoIdentifier($externalId) : ?string {
            global $databaseProvider;
            
            return $databaseProvider
                ->statementBuilder("SELECT id FROM photo_identifier WHERE external_id = ?")
                ->withParameters($externalId)
                ->getFirstColumn("id");
        }

        public function getExternalId($photoId) : ?string {
            global $databaseProvider;
            
            return $databaseProvider
                ->statementBuilder("SELECT external_id FROM photo_identifier WHERE id = ?")
                ->withParameters($photoId)
                ->getFirstColumn("external_id");
        }
        
        public function getOrCreatePhotoIdentifier($externalId) : string {
            global $databaseProvider;

            $albumIdentifier = $this->getPhotoIdentifier($externalId);
            if ($albumIdentifier !== NULL) {
                return $albumIdentifier;
            }

            $databaseProvider
                ->statementBuilder("INSERT INTO photo_identifier (external_id) VALUES (?)")
                ->withParameters($externalId)
                ->execute();

            return $this->getPhotoIdentifier($externalId);
        }

        public function getPhotos($albumId) : array {
            global $databaseProvider, $eventPublisher, $albumService, $googleApiClient;

            $photos = array();

            $externalAlbumId = $albumService->getExternalId($albumId);
            if ($externalAlbumId === NULL) {
                throw new InvalidArgumentException("An album with the identifier " . $albumId . " does not exist.");
            }

            $apiResponse = $googleApiClient->getMediaItems($externalAlbumId);

            while (isset($apiResponse["mediaItems"] )) {
                foreach ($apiResponse["mediaItems"] as &$mediaItem) {
                    $photos[] = new Photo(
                        $this->getOrCreatePhotoIdentifier($mediaItem["id"]), 
                        $mediaItem["baseUrl"],
                        $mediaItem["productUrl"],
                        isset($mediaItem["mediaMetadata"]["photo"]["focalLength"]) ? $mediaItem["mediaMetadata"]["photo"]["focalLength"] : NULL,
                        isset($mediaItem["mediaMetadata"]["photo"]["apertureFNumber"]) ? $mediaItem["mediaMetadata"]["photo"]["apertureFNumber"] : NULL,
                        isset($mediaItem["mediaMetadata"]["photo"]["exposureTime"]) ? doubleval(rtrim($mediaItem["mediaMetadata"]["photo"]["exposureTime"], "s")) : NULL,
                        isset($mediaItem["mediaMetadata"]["photo"]["isoEquivalent"]) ? $mediaItem["mediaMetadata"]["photo"]["isoEquivalent"] : NULL,
                        strtotime($mediaItem["mediaMetadata"]["creationTime"]));
                }
        
                $apiResponse = isset($apiResponse["nextPageToken"]) 
                    ? $googleApiClient->getMediaItems($externalAlbumId, $apiResponse["nextPageToken"])
                    : array();
            }

            $previousCount = $databaseProvider
                ->statementBuilder("DELETE FROM photo WHERE album_id = ?")
                ->withParameters($albumId)
                ->execute();

            foreach ($photos as &$photo) {
                $databaseProvider
                    ->statementBuilder("INSERT INTO photo (id, album_id, focal_length, aperture, shutter_speed, iso, timestamp, permalink) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                    ->withParameters($photo->getId(), $albumId, $photo->getFocalLength(), $photo->getAperture(), $photo->getShutterSpeed(), $photo->getIso(), $photo->getTimestamp(), $photo->getPermalink())
                    ->execute();
            }

            if (count($photos) !== $previousCount) {
                $eventPublisher->publishAlbumInvalidatedEvent($albumId);
            }

            return $photos;
        }

        public function getPhoto($photoId) : ?Photo {
            global $databaseProvider;            
                
            $photoRow = $databaseProvider
                ->statementBuilder("SELECT * FROM photo WHERE id = ?")
                ->withParameters($photoId)
                ->getSingleRow();

            if ($photoRow === NULL) {
                return NULL;
            }

            // TODO: URL
            return new Photo($photoId, NULL, $photoRow["permalink"], $photoRow["focal_length"], $photoRow["aperture"],
                $photoRow["shutter_speed"], $photoRow["iso"], $photoRow["timestamp"]);
        }

        public function uploadPhoto($fileName, $albumId, $position, $replacedPhotoId, $data) : bool {
            global $databaseProvider, $googleApiClient;

            if ($position === NULL && $replacedPhotoId === NULL) {
                throw new InvalidArgumentException("Either the photo position or the identifier of the photo being replaced must be specified.");
            }
        
            $uploadToken = $googleApiClient->uploadPhoto($data);

            return $databaseProvider
                ->statementBuilder("INSERT INTO photo_pending (album_id, file_name, position, replaced_photo_id, upload_token) VALUES (?, ?, ?, ?, ?)")
                ->withParameters($albumId, $fileName, $position, $replacedPhotoId, $uploadToken)
                ->execute() === 1;
        }

        private function createGooglePhotos($albumId, $newPhotos, $replacedPhotoId) : array {  
            global $googleApiClient, $albumService, $photoService;

            $externalAlbumId = $albumService->getExternalId($albumId);
            if ($externalAlbumId === NULL) {
                throw new InvalidArgumentException("An album with the identifier " . $albumId . " does not exist.");
            }

            $externalReplacedPhotoId = NULL;
            if ($replacedPhotoId !== NULL) {
                $externalReplacedPhotoId = $photoService->getExternalId($replacedPhotoId);    
                if ($externalReplacedPhotoId == NULL) {
                    throw new InvalidArgumentException("A photo with the identifier " . $externalReplacedPhotoId . " does not exist.");
                }
            }  
            
            $createdPhotos = $googleApiClient->createPhotos($externalAlbumId, $newPhotos, $externalReplacedPhotoId);

            if (isset($createdPhotos[0]["status"]["message"]) && $createdPhotos[0]["status"]["message"] !== "Success") {
                throw new RuntimeException($createdPhotos[0]["status"]["message"]);
            }   

            return $createdPhotos;
        }

        public function createPendingPhotos($albumId) : void {
            global $databaseProvider, $eventPublisher;
            
            $pendingPhotos = $databaseProvider
                ->statementBuilder("SELECT * FROM photo_pending WHERE album_id = ? AND position IS NOT NULL ORDER BY position LIMIT 50")
                ->withParameters($albumId)
                ->getResultSet();
        
            while (count($pendingPhotos) > 0) {
                $newPhotos = array();
                foreach ($pendingPhotos as &$pendingPhoto) {
                    $newPhotos[] = array(
                        "uploadToken" => $pendingPhoto["upload_token"],
                        "fileName" => $pendingPhoto["file_name"]
                    );

                    $databaseProvider
                        ->statementBuilder("DELETE FROM photo_pending WHERE id = ?")
                        ->withParameters($pendingPhoto["id"])
                        ->execute();
                }

                $this->createGooglePhotos($albumId, $newPhotos, NULL);
                
                $pendingPhotos = $databaseProvider
                    ->statementBuilder("SELECT * FROM photo_pending WHERE album_id = ? AND position IS NOT NULL ORDER BY position LIMIT 50")
                    ->withParameters($albumId)
                    ->getResultSet();
            }
            
            $pendingPhotos = $databaseProvider
                ->statementBuilder("SELECT * FROM photo_pending WHERE album_id = ? AND replaced_photo_id IS NOT NULL")
                ->withParameters($albumId)
                ->getResultSet();
            
            foreach ($pendingPhotos as &$pendingPhoto) {
                $newPhoto = array(
                    "uploadToken" => $pendingPhoto["upload_token"],
                    "fileName" => $pendingPhoto["file_name"]
                );

                $databaseProvider
                    ->statementBuilder("DELETE FROM photo_pending WHERE id = ?")
                    ->withParameters($pendingPhoto["id"])
                    ->execute();
                    
                $createdMediaItemId = $this->createGooglePhotos($albumId, array($newPhoto), $pendingPhoto["replaced_photo_id"])[0]["mediaItem"]["id"];

                $databaseProvider
                    ->statementBuilder("DELETE FROM photo WHERE id = ?")
                    ->withParameters($pendingPhoto["replaced_photo_id"])
                    ->execute();

                $databaseProvider
                    ->statementBuilder("UPDATE photo_identifier SET external_id = ? WHERE id = ?")
                    ->withParameters($createdMediaItemId, $pendingPhoto["replaced_photo_id"])
                    ->execute();

                $eventPublisher->publishHighlightChangedEvent($pendingPhoto["replaced_photo_id"]);
            }
        }

        public function onAlbumUpdated($message) : void {
            global $placeService, $albumService, $eventPublisher;

            $album = $albumService->getAlbum($message["albumId"]);
            if ($album !== NULL) {
                $photos = $this->getPhotos($message["albumId"]);
    
                if (count($photos) !== $album->getImagesCount()) {
                    $places = $placeService->getRegularPlaces(NULL, NULL, NULL, $message["albumId"], NULL, NULL, FALSE, FALSE, FALSE);
    
                    foreach ($places as &$place) {
                        foreach ($place->getDates() as &$date) {
                            $trip = $date->getTrip();
                            if ($trip !== NULL) {
                                $eventPublisher->publishTripStatisticsChangedEvent($trip->getId());
                            }
                        }
    
                        foreach ($place->getCategories() as &$category) {
                            $eventPublisher->publishCategoryStatisticsChangedEvent($category->getId());
                        }
                    }
                }
            }
        }
    }
?>