<?php
    require_once(dirname(__FILE__) . "/GetGoogleResponseProcessor.php");
    require_once(dirname(__FILE__) . "/GetPhotoIdentifierProcessor.php");
    require_once(dirname(__FILE__) . "/../model/Photo.php");

    class GetMediaItemsProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider, $schedulingProvider;

            $getPhotoIdentifierProcessor = new GetPhotoIdentifierProcessor();

            $externalAlbumId = $databaseProvider
                ->statementBuilder("SELECT external_id FROM album_identifier WHERE id = ?")
                ->withParameters($input["albumId"])
                ->getFirstColumn("external_id");

            $result = array();

            $apiResponse = $this->getGooglePhotosAlbumContentsResponse($externalAlbumId);
            while (isset($apiResponse["mediaItems"] )) {
                foreach ($apiResponse["mediaItems"] as &$mediaItem) {
                    $photoId = $getPhotoIdentifierProcessor
                        ->process(array(
                            "externalId" => $mediaItem["id"]));                            
                    $result[] = new Photo($photoId, $mediaItem["baseUrl"], isset($mediaItem["mediaMetadata"]["photo"]["focalLength"]) ? $mediaItem["mediaMetadata"]["photo"]["focalLength"] : NULL,
                        isset($mediaItem["mediaMetadata"]["photo"]["apertureFNumber"]) ? $mediaItem["mediaMetadata"]["photo"]["apertureFNumber"] : NULL, isset($mediaItem["mediaMetadata"]["photo"]["exposureTime"]) ? doubleval(rtrim($mediaItem["mediaMetadata"]["photo"]["exposureTime"], "s")) : NULL,
                        isset($mediaItem["mediaMetadata"]["photo"]["isoEquivalent"]) ? $mediaItem["mediaMetadata"]["photo"]["isoEquivalent"] : NULL, strtotime($mediaItem["mediaMetadata"]["creationTime"]));
                }
        
                if (isset($apiResponse["nextPageToken"])) {
                    $apiResponse = $this->getGooglePhotosAlbumContentsResponse($externalAlbumId, $apiResponse["nextPageToken"]);
                }
                else {
                    $apiResponse = array();
                }
            }

            $previousCount = $databaseProvider
                ->statementBuilder("SELECT COUNT(*) AS count FROM photo WHERE album_id = ?")
                ->withParameters($input["albumId"])
                ->getSingleColumn("count");

            $databaseProvider
                ->statementBuilder("DELETE FROM photo WHERE album_id = ?")
                ->withParameters($input["albumId"])
                ->execute();

            foreach ($result as &$photo) {
                $databaseProvider
                    ->statementBuilder("INSERT INTO photo (id, album_id, focal_length, aperture, shutter_speed, iso, timestamp) VALUES (?, ?, ?, ?, ?, ?, ?)")
                    ->withParameters($photo->getId(), $input["albumId"], $photo->getFocalLength(), $photo->getAperture(), $photo->getShutterSpeed(), $photo->getIso(), $photo->getTimestamp())
                    ->execute();
            }

            if (count($result) != $previousCount) {
                $schedulingProvider
                    ->scheduleJobExecution("UpdateAlbum", array(
                        "albumId" => $input["albumId"]), NULL);
            }

            return $result;
        }

        public function getRequiredArguments() {
            return array("albumId");
        }
        
        public function requiresAuthentication() {
            return FALSE;
        }
            
        private function getGooglePhotosAlbumContentsResponse($id, $pageToken = NULL) {
            $payload = array("albumId" => $id);
            if ($pageToken != NULL) {
                $payload["pageToken"] = $pageToken;
            }

            $apiResponse = (new GetGoogleResponseProcessor())
                ->process(array(
                    "method" => "POST", 
                    "url" => "https://photoslibrary.googleapis.com/v1/mediaItems:search", 
                    "payload" => json_encode($payload)));
                    
            if (isset($apiResponse["error"])) {
                throw new RuntimeException($apiResponse["error"]["message"]);
            }

            return $apiResponse;
        }
    }
?>