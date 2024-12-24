<?php
    require_once(dirname(__FILE__) . "/../model/Photo.php");
    require_once(dirname(__FILE__) . "/../processor/GetGoogleResponseProcessor.php");

    class PhotoService {
        public function getPhotoIdentifier($externalId) : ?string {
            global $databaseProvider;
            
            return $databaseProvider
                ->statementBuilder("SELECT id FROM photo_identifier WHERE external_id = ?")
                ->withParameters($externalId)
                ->getFirstColumn("id");
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
            global $databaseProvider, $schedulingProvider, $albumService, $googleApiClient;

            $photos = array();

            $externalAlbumId = $albumService->getExternalIdentifier($albumId);
            $apiResponse = $googleApiClient->getMediaItemsResponse($externalAlbumId);

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
                    ? $googleApiClient->getMediaItemsResponse($externalAlbumId, $apiResponse["nextPageToken"])
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

            if (count($photos) != $previousCount) {
                $schedulingProvider
                    ->scheduleJobExecution("UpdateAlbum", array(
                        "albumId" => $albumId), NULL);
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
            global $databaseProvider;

            if ($position === NULL && $replacedPhotoId === NULL) {
                throw new InvalidArgumentException("Either the photo position or the identifier of the photo being replaced must be specified.");
            }
        
            $uploadToken = (new GetGoogleResponseProcessor())
                ->process(array(
                    "method" => "POST", 
                    "url" => "https://photoslibrary.googleapis.com/v1/uploads",
                    "contentType" => "application/octet-stream",
                    "headers" => array(
                        "X-Goog-Upload-Content-Type" => "image/jpeg",
                        "X-Goog-Upload-Protocol" => "raw"),
                    "payload" => base64_decode($data)));    
        
            if ($uploadToken === NULL) {
                throw new RuntimeException("The photo " . $fileName . " was not uploaded.");
            }

            return $databaseProvider
                ->statementBuilder("INSERT INTO photo_pending (album_id, file_name, position, replaced_photo_id, upload_token) VALUES (?, ?, ?, ?, ?)")
                ->withParameters($albumId, $fileName, $position, $replacedPhotoId, $uploadToken)
                ->execute() === 1;
        }
    }
?>