<?php
    require_once(dirname(__FILE__) . "/../model/Album.php");
    require_once(dirname(__FILE__) . "/../exception/EntityNotFoundException.php");

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
            $albumId = $this->getOrCreateAlbumIdentifier($createdAlbumExternalId);
            $this->updateAlbum($albumId);

            return $this->getAlbum($albumId);
        }

        public function updateAlbums() : void {
            $filePaths = $this->doUpdateAlbums(NULL, FALSE);
            $this->unlinkUnusedFiles($filePaths);
        }

        public function updateAlbum($albumId, $mainPhotoPosition = NULL) : void {
            global $photoService;
            
            $photoService->createPendingPhotos($albumId);

            if ($mainPhotoPosition !== NULL) {
                $photos = $photoService->getPhotos($albumId);

                $mainPhotoPosition = $mainPhotoPosition - 1;
                if ($mainPhotoPosition < 0 || $mainPhotoPosition >= count($photos)) {
                    throw new RuntimeException("Cannot set main photo because there are only " . count($photos) . " photos in the album.");
                }

                $this->setAlbumMainPhoto($albumId, $photos[$mainPhotoPosition]->getId());
            }

            $this->doUpdateAlbums($albumId, TRUE);
        }

        private function unlinkUnusedFiles($usedFilePaths) : void {
            $existingFilePaths = array_filter((array) glob($this->getPhysicalCachePath() . "/*"));
            $unusedFilePaths = array_diff($existingFilePaths, $usedFilePaths);    
            array_map("unlink", $unusedFilePaths);
        }
        
        public function doUpdateAlbums($albumId, $forceOverwrite) : array {
            global $databaseProvider, $configuration, $eventPublisher, $highlightService, $photoService;
        
            $filePaths = array();
            $albums = array();
        
            // Fetch albums.
            $response = $this->getGooglePhotosAlbumResponse($albumId);
            while (isset($response["albums"])) {
                foreach ($response["albums"] as &$album) {
                    $mainPhotoId = NULL;
                    $mainImageUrl = NULL;

                    if (isset($album["coverPhotoMediaItemId"])) {
                        $fileName = $album["coverPhotoMediaItemId"] . ".jpg";
                        $filePath = $this->getPhysicalCachePath() . "/" . $fileName;
            
                        if ($forceOverwrite || !file_exists($filePath)) {
                            file_put_contents($filePath, file_get_contents($album["coverPhotoBaseUrl"] . "=w" . $configuration["albumThumbnailImageSize"]["width"] . "-h" . $configuration["albumThumbnailImageSize"]["height"]));
                        }
            
                        $filePaths[] = $filePath;
                        $mainImageUrl = BASE_URL . "/" . $configuration["cachePath"]["albumThumbnail"] . "/" . $fileName;
                        
                        $mainPhotoId = $photoService->getOrCreatePhotoIdentifier($album["coverPhotoMediaItemId"]);
                    }
        
                    $imagesCount = 0;
                    if (isset($album["mediaItemsCount"])) {
                        $imagesCount = intval($album["mediaItemsCount"]);
                    }

                    $currentAlbumId = $this->getOrCreateAlbumIdentifier($album["id"]);
        
                    $albums[] = array(
                        "id" => $currentAlbumId,
                        "name" => $album["title"],
                        "mainPhotoId" => $mainPhotoId,
                        "mainImageUrl" => $mainImageUrl,
                        "imagesCount" => $imagesCount,
                        "permalink" => $album["productUrl"]
                    );

                    // TODO: This is temporary until there's a proper support for highlights.
                    if (isset($album["coverPhotoMediaItemId"])) {
                        $placeRow = $databaseProvider
                            ->statementBuilder("SELECT *, YEAR(FROM_UNIXTIME(start)) AS year FROM place_summary WHERE album_id = ?")
                            ->withParameters($currentAlbumId)
                            ->getFirstRow();
    
                        if ($placeRow !== NULL) {
                            $highlightService->createPlaceHighlight($placeRow["place_id"], $mainPhotoId);
    
                            if ($placeRow["trip_id"] !== NULL) {
                                $highlightService->createTripHighlight($placeRow["trip_id"], $mainPhotoId);
                                $highlightService->createYearHighlight($placeRow["year"], $mainPhotoId);
                            }    
    
                            foreach (explode(",", $placeRow["category_ids"]) as &$categoryId) {
                                $highlightService->createCategoryHighlight($categoryId, $mainPhotoId);
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
            $whereClauseBuilder = $databaseProvider->whereClauseBuilder();
            if ($albumId !== NULL) {
                $whereClauseBuilder->withClause("id = ?", $albumId);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            $databaseProvider
                ->statementBuilder("DELETE FROM album {{WHERE CLAUSE}}", $whereClause)
                ->execute();
        
            foreach ($albums as &$album) {
                $databaseProvider
                    ->statementBuilder("INSERT INTO album (name, id, main_photo_id, thumbnail_url, images_count, indoor_images_count, permalink) VALUES (?, ?, ?, ?, ?, GET_INDOOR_IMAGES_COUNT(?), ?)")
                    ->withParameters($album["name"], $album["id"], $album["mainPhotoId"], $album["mainImageUrl"], $album["imagesCount"], $album["id"], $album["permalink"])
                    ->execute();
            }

            // Process albums where a number of photos has changed. 
            $changedAlbumIds = array($albumId);            
            if ($albumId === NULL) {
                $changedAlbumIds = $databaseProvider
                    ->statementBuilder("SELECT id FROM album a WHERE images_count <> (SELECT COUNT(*) FROM photo p WHERE p.album_id = a.id)")
                    ->getResultSetForColumn("id");
            }

            foreach ($changedAlbumIds as &$changedAlbumId) {
                $eventPublisher->publishAlbumPhotosChangedEvent($changedAlbumId);
            }

            return $filePaths;
        }

        private function getPhysicalCachePath() : string {
            global $configuration;

            return dirname(__FILE__) . "/../../" . $configuration["cachePath"]["albumThumbnail"];
        }

        private function getAlbumName($placeName, $timestamp) : string {
            return $placeName . " " . date("j.n.Y", $timestamp);
        }
    
        private function getGooglePhotosAlbumResponse($albumId, $pageToken = NULL) : array {
            global $albumService, $googleApiClient;

            if ($albumId === NULL) {
                return $googleApiClient->getAlbums($pageToken);
            }
            else {
                $externalAlbumId = $albumService->getExternalIdentifier($albumId);
                if ($externalAlbumId === NULL) {
                    throw new InvalidArgumentException("An album with the identifier " . $albumId . " does not exist.");
                }

                $album = $googleApiClient->getAlbum($externalAlbumId);
                return array("albums" => array($album));
            }
        }

        private function setAlbumMainPhoto($albumId, $photoId) {
            global $photoService, $albumService, $googleApiClient;
            
            $externalAlbumId = $albumService->getExternalIdentifier($albumId);       
            if ($externalAlbumId === NULL) {
                throw new InvalidArgumentException("An album with the identifier " . $albumId . " does not exist.");
            }

            $externalPhotoId = $photoService->getExternalIdentifier($photoId);
            if ($externalPhotoId === NULL) {
                throw new InvalidArgumentException("A photo with the identifier " . $photoId . " does not exist.");
            }

            $googleApiClient->updateAlbumMainPhoto($externalAlbumId, $externalPhotoId);
        }

        public function onAllAlbumsChanged($message) {
            $this->updateAlbums();
        }
        
        public function onAlbumChanged($message) {
            $this->updateAlbum($message["albumId"]);
        }

        public function onSchedulerTriggered($message) : void {
            global $eventPublisher, $scheduler;

            if ($message["action"] === "FETCH_ALBUMS" && $message["timeSinceLastExecution"] > 21600) {
                $eventPublisher->publishAllAlbumsChangedEvent();                
                $scheduler->recordEventsTriggered($message["action"]);
            }
        }
    }
?>