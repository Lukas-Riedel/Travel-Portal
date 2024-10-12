<?php
    require_once(dirname(__FILE__) . "/GetGoogleResponseProcessor.php");
    require_once(dirname(__FILE__) . "/GetAlbumIdentifierProcessor.php");
    require_once(dirname(__FILE__) . "/GetPhotoIdentifierProcessor.php");
    require_once(dirname(__FILE__) . "/GetMediaItemsProcessor.php");
    require_once(dirname(__FILE__) . "/AddHighlightProcessor.php");

    class UpdateAlbumProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider, $configuration, $schedulingProvider;

            $getAlbumIdentifierProcessor = new GetAlbumIdentifierProcessor();
            $getPhotoIdentifierProcessor = new GetPhotoIdentifierProcessor();
            $addHighlightProcessor = new AddHighlightProcessor();

            $albumCachePath = dirname(__FILE__) . "/../../" . $configuration["cachePath"]["albumThumbnail"];

            $albumId = isset($input["albumId"]) ? $input["albumId"] : NULL;
        
            $actuallyUsedImages = array();
            $albums = array();

            // Create pending photos.
            $whereClauseBuilder = $databaseProvider->whereClauseBuilder();
            if ($albumId != NULL) {
                $whereClauseBuilder->withClause("album_id = ?", $albumId);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();
            
            $pendingAlbumIds = $databaseProvider
                ->statementBuilder("SELECT album_id FROM photo_pending {{WHERE CLAUSE}} GROUP BY album_id", $whereClause)
                ->getResultSetForColumn("album_id");

            foreach ($pendingAlbumIds as &$pendingAlbumId) {
                $pendingPhotos = $databaseProvider
                    ->statementBuilder("SELECT * FROM photo_pending WHERE album_id = ? ORDER BY position LIMIT 50")
                    ->withParameters($pendingAlbumId)
                    ->getResultSet();
                
                while (count($pendingPhotos) > 0) {
                    $newMediaItems = array();
                    foreach ($pendingPhotos as &$pendingPhoto) {
                        $newMediaItems[] = array(
                            "description" => "",
                            "simpleMediaItem" => array(
                                "uploadToken" => $pendingPhoto["upload_token"],
                                "fileName" => $pendingPhoto["file_name"]));

                        $databaseProvider
                            ->statementBuilder("DELETE FROM photo_pending WHERE id = ?")
                            ->withParameters($pendingPhoto["id"])
                            ->execute();
                    }

                    $this->createGooglePhotos($pendingAlbumId, $newMediaItems);
                    
                    $pendingPhotos = $databaseProvider
                        ->statementBuilder("SELECT * FROM photo_pending WHERE album_id = ? ORDER BY position LIMIT 50")
                        ->withParameters($pendingAlbumId)
                        ->getResultSet();
                }
            }

            // Update album main photo.
            if (isset($input["mainPhotoPosition"])) {
                if ($albumId == NULL) {
                    throw new InvalidArgumentException("Cannot update main photo because the album identifier was not specified.");
                }

                $photos = (new GetMediaItemsProcessor())
                    ->process(array(
                        "albumId" => $albumId));

                $mainPhotoPosition = $input["mainPhotoPosition"] - 1;
                if ($mainPhotoPosition < 0 || $mainPhotoPosition >= count($photos)) {
                    throw new RuntimeException("Cannot set main photo because there are only " . count($photos) . " photos in the album.");
                }

                $mainPhoto = $photos[$mainPhotoPosition];

                $this->setAlbumMainPhoto($albumId, $mainPhoto->getId());
            }
        
            // Fetch albums.
            $response = $this->getGooglePhotosAlbumResponse($albumId);
            while (isset($response["albums"])) {
                foreach ($response["albums"] as &$album) {
                    $mainPhotoId = NULL;
                    $mainImageUrl = "";
                    if (isset($album["coverPhotoMediaItemId"])) {
                        $fileName = $album["coverPhotoMediaItemId"] . ".jpg";
                        $filePath = $albumCachePath . "/" . $fileName;
            
                        if ((isset($input["forceOverwrite"]) && $input["forceOverwrite"] == "true") || !file_exists($filePath)) {
                            file_put_contents($filePath, file_get_contents($album["coverPhotoBaseUrl"] . "=w" . $configuration["albumThumbnailImageSize"]["width"] . "-h" . $configuration["albumThumbnailImageSize"]["height"]));
                        }
            
                        $actuallyUsedImages[] = $filePath;
                        $mainImageUrl = "https://" . $configuration["hostName"] . "/" . $configuration["cachePath"]["albumThumbnail"] . "/" . $fileName;
                        
                        $mainPhotoId = $getPhotoIdentifierProcessor
                            ->process(array(
                                "externalId" => $album["coverPhotoMediaItemId"]));
                    }
        
                    $imagesCount = 0;
                    if (isset($album["mediaItemsCount"])) {
                        $imagesCount = intval($album["mediaItemsCount"]);
                    }

                    $resolvedAlbumId = $getAlbumIdentifierProcessor
                        ->process(array(
                            "externalId" => $album["id"]));
        
                    $albums[] = array(
                        "id" => $resolvedAlbumId,
                        "name" => $album["title"],
                        "mainPhotoId" => $mainPhotoId,
                        "mainImageUrl" => $mainImageUrl,
                        "imagesCount" => $imagesCount,
                        "permalink" => $album["productUrl"]);

                    // This is temporary until there's a proper support for highlights.
                    if (isset($album["coverPhotoMediaItemId"])) {
                        $placeRow = $databaseProvider
                            ->statementBuilder("SELECT *, YEAR(FROM_UNIXTIME(start)) AS year FROM place_summary WHERE album_id = ?")
                            ->withParameters($resolvedAlbumId)
                            ->getFirstRow();
    
                        if ($placeRow != NULL) {
                            $addHighlightProcessor
                                ->process(array(
                                    "type" => "place",
                                    "id" => $placeRow["place_id"], 
                                    "photoId" => $mainPhotoId));
    
                            if ($placeRow["trip_id"] != NULL) {
                                $addHighlightProcessor
                                    ->process(array(
                                        "type" => "trip",
                                        "id" => $placeRow["trip_id"], 
                                        "photoId" => $mainPhotoId));
                            }
    
                            $addHighlightProcessor
                                ->process(array(
                                    "type" => "year",
                                    "id" => $placeRow["year"], 
                                    "photoId" => $mainPhotoId));
    
                            foreach (explode(",", $placeRow["category_ids"]) as &$categoryId) {
                                $addHighlightProcessor
                                    ->process(array(
                                        "type" => "category",
                                        "id" => $categoryId, 
                                        "photoId" => $mainPhotoId));
                            }
                        }
                    }  
                    // End of temporary code.                          
                }
        
                if (isset($response["nextPageToken"])) {
                    $response = $this->getGooglePhotosAlbumResponse($albumId, $response["nextPageToken"]);
                }
                else {
                    $response = array();
                }
            }
        
            // Cache into a database table.
            $databaseProvider
                ->statementBuilder("DROP TEMPORARY TABLE IF EXISTS old_place_summary")
                ->execute();
            $databaseProvider
                ->statementBuilder("CREATE TEMPORARY TABLE old_place_summary AS SELECT * FROM _place_summary")
                ->execute();
                
            $whereClauseBuilder = $databaseProvider->whereClauseBuilder();
            if ($albumId != NULL) {
                $whereClauseBuilder->withClause("id = ?", $albumId);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            $databaseProvider
                ->statementBuilder("DELETE FROM album {{WHERE CLAUSE}}", $whereClause)
                ->execute();
        
            foreach ($albums as &$album) {
                $databaseProvider
                    ->statementBuilder("INSERT INTO album (name, id, main_photo_id, main_image_url, images_count, indoor_images_count, permalink) VALUES (?, ?, ?, ?, ?, GET_INDOOR_IMAGES_COUNT(?), ?)")
                    ->withParameters($album["name"], $album["id"], $album["mainPhotoId"], $album["mainImageUrl"], $album["imagesCount"], $album["id"], $album["permalink"])
                    ->execute();
            }

            // Process places where a number of photos has changed. 
            $changedAlbumRows = array();            
            if ($albumId == NULL) {
                $changedAlbumRows = $databaseProvider
                    ->statementBuilder("SELECT nps.place_id, nps.trip_id FROM _place_summary nps LEFT JOIN old_place_summary ops ON ops.start = nps.start AND ops.name = nps.name WHERE ops.album_images_count <> nps.album_images_count")
                    ->getResultSet();
            }
            else {
                $changedAlbumRows = $databaseProvider
                    ->statementBuilder("SELECT place_id, trip_id FROM place_summary WHERE album_id = ?")
                    ->withParameters($albumId)
                    ->getResultSet();
            }

            foreach ($changedAlbumRows as &$changedAlbumRow) {
                $schedulingProvider
                    ->scheduleJobExecution("UpdateStats", array(
                        "type" => "TRIP", 
                        "id" => $changedAlbumRow["trip_id"]), NULL);

                $categoryIds = $databaseProvider
                    ->statementBuilder("SELECT category_id FROM category WHERE place_id = ?")
                    ->withParameters($changedAlbumRow["place_id"])
                    ->getResultSetForColumn("category_id");

                foreach ($categoryIds as &$categoryId) {
                    $schedulingProvider
                        ->scheduleJobExecution("UpdateStats", array(
                            "type" => "CATEGORY", 
                            "id" => $categoryId), NULL);
                }
            }
                    
            // Delete unused images on the server.
            if ($albumId == NULL) {
                $downloadedImages = array_filter((array) glob($albumCachePath . "/*"));
                $unusedImages = array_diff($downloadedImages, $actuallyUsedImages);    
                array_map("unlink", $unusedImages);
            }

            // Schedule album photo updates.
            if ($albumId == NULL) {
                $albumRowsToUpdate = $databaseProvider
                    ->statementBuilder("SELECT DISTINCT ps.place_id AS placeId, ps.album_id AS albumId FROM place_summary ps WHERE ps.album_id IS NOT NULL AND ps.album_images_count <> (SELECT COUNT(*) FROM photo p WHERE p.album_id = ps.album_id)")
                    ->getResultSet();
                
                foreach ($albumRowsToUpdate as &$albumRowToUpdate) {
                    $schedulingProvider
                        ->scheduleJobExecution("GetMediaItems", $albumRowToUpdate, NULL);
                }
            }
            else {
                $schedulingProvider
                    ->scheduleJobExecution("GetMediaItems", array(
                        "albumId" => $albumId), NULL);
            }

            return TRUE;
        }

        public function getRequiredArguments() {
            return array();
        }
        
        public function requiresAdminRole() {
            return FALSE;
        }
    
        private function getGooglePhotosAlbumResponse($albumId, $pageToken = NULL) {
            global $databaseProvider;

            $queryParameters = "";
    
            if ($albumId == NULL) {
                $queryParameters .= "?pageSize=50";
        
                if ($pageToken != NULL) {
                    $queryParameters .= "&pageToken=" . $pageToken;
                }
            }
            else {
                $externalAlbumId = $databaseProvider
                    ->statementBuilder("SELECT external_id FROM album_identifier WHERE id = ?")
                    ->withParameters($albumId)
                    ->getFirstColumn("external_id");

                if ($externalAlbumId == NULL) {
                    throw new InvalidArgumentException("The album " + $albumId + " was not found.");
                }

                $queryParameters .= "/" . $externalAlbumId;
            }

            $apiResponse = (new GetGoogleResponseProcessor())
                ->process(array(
                    "method" => "GET", 
                    "url" => "https://photoslibrary.googleapis.com/v1/albums" . $queryParameters));

            if (isset($apiResponse["error"])) {
                throw new RuntimeException($apiResponse["error"]["message"]);
            }
    
            if ($albumId == NULL) {
                return $apiResponse;
            }
            else {    
                return array("albums" => array($apiResponse));
            }
        }

        private function createGooglePhotos($albumId, $newMediaItems) {  
            global $databaseProvider;

            $externalAlbumId = $databaseProvider
                ->statementBuilder("SELECT external_id FROM album_identifier WHERE id = ?")
                ->withParameters($albumId)
                ->getFirstColumn("external_id");

            if ($externalAlbumId == NULL) {
                throw new InvalidArgumentException("An album with the identifier " . $albumId . " does not exist.");
            }

            $apiResponse = (new GetGoogleResponseProcessor())
                ->process(array(
                    "method" => "POST", 
                    "url" => "https://photoslibrary.googleapis.com/v1/mediaItems:batchCreate",
                    "payload" => json_encode(array(
                        "albumId" => $externalAlbumId,
                        "newMediaItems" => $newMediaItems))));

            if (isset($apiResponse["newMediaItemResults"][0]["status"]["message"]) && $apiResponse["newMediaItemResults"][0]["status"]["message"] != "Success") {
                throw new RuntimeException($apiResponse["newMediaItemResults"][0]["status"]["message"]);
            }   
        }

        private function setAlbumMainPhoto($albumId, $photoId) {
            global $databaseProvider;
            
            $externalAlbumId = $databaseProvider
                ->statementBuilder("SELECT external_id FROM album_identifier WHERE id = ?")
                ->withParameters($albumId)
                ->getFirstColumn("external_id");
            
            $externalPhotoId = $databaseProvider
                ->statementBuilder("SELECT external_id FROM photo_identifier WHERE id = ?")
                ->withParameters($photoId)
                ->getFirstColumn("external_id");
                          
            (new GetGoogleResponseProcessor())
                ->process(array(
                    "method" => "PATCH", 
                    "url" => "https://photoslibrary.googleapis.com/v1/albums/" . $externalAlbumId . "?updateMask=coverPhotoMediaItemId", 
                    "payload" => json_encode(array(
                        "coverPhotoMediaItemId" => $externalPhotoId))));
        }
    }
?>