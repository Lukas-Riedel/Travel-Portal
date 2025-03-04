<?php
    require_once(dirname(__FILE__) . "/../model/Highlight.php");

    class HighlightService {
        public function getHighlight($highlightId) : ?Highlight {
            global $databaseProvider, $photoService;            
                
            $highlightRow = $databaseProvider
                ->statementBuilder("SELECT * FROM highlight_identifier WHERE id = ?")
                ->withParameters($highlightId)
                ->getSingleRow();

            if ($highlightRow === NULL) {
                return NULL;
            }

            $photo = $photoService->getPhoto($highlightRow["photo_id"]);
            if ($photo === NULL) {
                return new Highlight($highlightRow["id"], $highlightRow["thumbnail_url"], $highlightRow["full_url"], NULL, NULL, NULL, NULL, NULL);;
            }
            
            return new Highlight($highlightRow["id"], $highlightRow["thumbnail_url"], $highlightRow["full_url"], 
                $photo->getFocalLength(), $photo->getAperture(), $photo->getShutterSpeed(), $photo->getIso(), $photo->getTimestamp());
        }

        public function getPlaceHighlights($placeId) : array {
            return $this->getHighlights(HighlightType::Place, $placeId);
        }

        public function getCategoryHighlights($categoryId) : array {
            return $this->getHighlights(HighlightType::Category, $categoryId);
        }

        public function getYearHighlights($year) : array {
            return $this->getHighlights(HighlightType::Year, $year);
        }

        public function getTripHighlights($tripId) : array {
            return $this->getHighlights(HighlightType::Trip, $tripId);
        }
        
        private function getHighlights($highlightType, $entityId) : array {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("SELECT hi.*, p.focal_length, p.aperture, p.shutter_speed, p.iso, p.timestamp FROM " . $highlightType->getTableName() . " ht INNER JOIN highlight_identifier hi ON ht.highlight_id = hi.id LEFT JOIN photo p ON hi.photo_id = p.id WHERE ht.id = ?")
                ->withParameters($entityId)
                ->getMappedResultSet(function($highlightRow) { 
                    return new Highlight($highlightRow["id"], $highlightRow["thumbnail_url"], $highlightRow["full_url"], $highlightRow["focal_length"], 
                        $highlightRow["aperture"], $highlightRow["shutter_speed"], $highlightRow["iso"], $highlightRow["timestamp"]);
                });

        }

        public function getHighlightIdentifier($photoId) : ?string {
            global $databaseProvider;
            
            return $databaseProvider
                ->statementBuilder("SELECT id FROM highlight_identifier WHERE photo_id = ?")
                ->withParameters($photoId)
                ->getFirstColumn("id");
        }
        
        public function getOrCreateHighlightIdentifier($photoId) : string {
            global $databaseProvider;

            $highlightIdentifier = $this->getHighlightIdentifier($photoId);
            if ($highlightIdentifier !== NULL) {
                return $highlightIdentifier;
            }

            $databaseProvider
                ->statementBuilder("INSERT INTO highlight_identifier (photo_id) VALUES (?)")
                ->withParameters($photoId)
                ->execute();

            return $this->getHighlightIdentifier($photoId);
        }

        public function createPlaceHighlight($placeId, $photoId) : Highlight {
            return $this->createHighlight(HighlightType::Place, $placeId, $photoId);
        }

        public function createTripHighlight($tripId, $photoId) : Highlight {
            return $this->createHighlight(HighlightType::Trip, $tripId, $photoId);
        }

        public function createCategoryHighlight($categoryId, $photoId) : Highlight {
            return $this->createHighlight(HighlightType::Category, $categoryId, $photoId);
        }

        public function createYearHighlight($year, $photoId) : Highlight {
            return $this->createHighlight(HighlightType::Year, $year, $photoId);
        }

        private function createHighlight($highlightType, $entityId, $photoId) : Highlight {
            global $databaseProvider;

            $highlightIdentifier = $this->getOrCreateHighlightIdentifier($photoId);

            // TODO: Remove the create-if-not-exists semantics.
            $highlightNotExists = $databaseProvider
                ->statementBuilder("SELECT * FROM " . $highlightType->getTableName() . " WHERE id = ? AND highlight_id = ?")
                ->withParameters($entityId, $highlightIdentifier)
                ->getFirstRow() === NULL;

            if ($highlightNotExists) {
                $databaseProvider
                    ->statementBuilder("INSERT INTO " . $highlightType->getTableName() . " (id, highlight_id) VALUES (?, ?)")
                    ->withParameters($entityId, $highlightIdentifier)
                    ->execute();

                $this->updateEntityMainHighlightIfNull($entityId, $highlightType, $highlightIdentifier);

                $this->updateHighlight($highlightIdentifier);
            }
            
           return $this->getHighlight($highlightIdentifier);
        }

        public function removePlaceHighlight($placeId, $highlightId) : bool {
            return $this->removeHighlight(HighlightType::Place, $placeId, $highlightId);
        }

        public function removeTripHighlight($tripId, $highlightId) : bool {
            return $this->removeHighlight(HighlightType::Trip, $tripId, $highlightId);
        }

        public function removeCategoryHighlight($categoryId, $highlightId) : bool {
            return $this->removeHighlight(HighlightType::Category, $categoryId, $highlightId);
        }

        public function removeYearHighlight($year, $highlightId) : bool {
            return $this->removeHighlight(HighlightType::Year, $year, $highlightId);
        }

        private function removeHighlight($highlightType, $entityId, $highlightId) : bool {
            global $databaseProvider, $eventPublisher;

            $wasDeleted = $databaseProvider
                ->statementBuilder("DELETE FROM " . $highlightType->getTableName() . " WHERE id = ? AND highlight_id = ?")
                ->withParameters($entityId, $highlightId)
                ->execute();

            $eventPublisher->publishAllHighlightsChangedEvent();
                
            return $wasDeleted;
        }

        public function updateHighlights() : void {
            global $configuration;

            $thumbnailFilePaths = $this->doUpdateHighlights(NULL, NULL, FALSE, $configuration["cachePath"]["highlightThumbnail"],
                $configuration["highlightThumbnailImageSize"], "thumbnail_url");
            $this->unlinkUnusedFiles($thumbnailFilePaths, $configuration["cachePath"]["highlightThumbnail"]);
        }

        public function updateHighlight($highlightId) : void {
            global $configuration;

            $this->doUpdateHighlights($highlightId, NULL, TRUE, $configuration["cachePath"]["highlightThumbnail"],
                $configuration["highlightThumbnailImageSize"], "thumbnail_url");
        }

        public function updateHighlightForPhoto($photoId) : void {
            global $configuration;

            $this->doUpdateHighlights(NULL, $photoId, TRUE, $configuration["cachePath"]["highlightThumbnail"],
                $configuration["highlightThumbnailImageSize"], "thumbnail_url");
        }

        private function doUpdateHighlights($highlightId, $photoId, $forceOverwrite, $cachePath, $imageSize, $urlColumnName) : array {
            global $databaseProvider, $googleApiClient;

            $filePaths = array();

            $whereClauseBuilder = $databaseProvider->whereClauseBuilder();
            if ($highlightId !== NULL) {
                $whereClauseBuilder->withClause("hi.id = ?", $highlightId);
            }
            if ($photoId !== NULL) {
                $whereClauseBuilder->withClause("hi.photo_id = ?", $photoId);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();
            
            $highlightRows = $databaseProvider
                ->statementBuilder("SELECT hi.*, pi.external_id FROM highlight_identifier hi INNER JOIN photo_identifier pi ON hi.photo_id = pi.id {{WHERE CLAUSE}}", $whereClause)
                ->getResultSet();

            foreach ($highlightRows as &$highlightRow) {
                $fileName = $highlightRow["external_id"] . ".jpg";
                $filePath = $this->getPhysicalCachePath($cachePath) . "/" . $fileName;
    
                if ($forceOverwrite || !file_exists($filePath)) {
                    $baseUrl = $googleApiClient->getMediaItem($highlightRow["external_id"])["baseUrl"];
                    file_put_contents($filePath, file_get_contents($baseUrl . "=w" . $imageSize["width"] . "-h" . $imageSize["height"]));
                }
    
                $filePaths[] = $filePath;
                $imageUrl = BASE_URL . "/" . $cachePath . "/" . $fileName;

                $databaseProvider
                    ->statementBuilder("UPDATE highlight_identifier SET " . $urlColumnName . " = ? WHERE id = ?")
                    ->withParameters($imageUrl, $highlightRow["id"])
                    ->execute();
            }
            
            return $filePaths;
        }

        private function unlinkUnusedFiles($usedFilePaths, $cachePath) : void {
            $existingFilePaths = array_filter((array) glob($this->getPhysicalCachePath($cachePath) . "/*"));
            $unusedFilePaths = array_diff($existingFilePaths, $usedFilePaths);    
            array_map("unlink", $unusedFilePaths);
        }

        private function getPhysicalCachePath($cachePath) : string {
            return dirname(__FILE__) . "/../../" . $cachePath;
        }

        private function updateEntityMainHighlightIfNull($entityId, $highlightType, $highlightIdentifier) : bool {
            global $placeService, $tripService, $categoryService, $yearService;

            if ($highlightType === HighlightType::Place) {
                $placeIdentifier = $placeService->getPlaceIdentifierById($entityId);
                if ($placeIdentifier !== NULL && $placeIdentifier->getMainHighlight() === NULL) {
                    return $placeService->updatePlaceMainHighlight($entityId, $highlightIdentifier);
                }
            }
            else if ($highlightType === HighlightType::Trip) {
                $tripIdentifier = $tripService->getTripIdentifierById($entityId);
                if ($tripIdentifier !== NULL && $tripIdentifier->getMainHighlight() === NULL) {
                    return $tripService->updateTripMainHighlight($entityId, $highlightIdentifier);
                }
            }
            else if ($highlightType === HighlightType::Category) {
                $categoryIdentifier = $categoryService->getCategoryIdentifierById($entityId);
                if ($categoryIdentifier !== NULL && $categoryIdentifier->getMainHighlight() === NULL) {
                    return $categoryService->updateCategoryMainHighlight($entityId, $highlightIdentifier);
                }
            }
            else if ($highlightType === HighlightType::Year) {
                $yearIdentifier = $yearService->getYearIdentifier($entityId);
                if ($yearIdentifier !== NULL && $yearIdentifier->getMainHighlight() === NULL) {
                    return $yearService->updateYearMainHighlight($entityId, $highlightIdentifier);
                }
            }
            else {
                throw new InvalidArgumentException("Unknown highlight type " . $highlightType . ".");
            }

            return FALSE;
        }

        public function onSchedulerTriggered($message) : void {
            global $eventPublisher, $scheduler;

            if ($message["action"] === "FETCH_HIGHLIGHTS" && $message["timeSinceLastExecution"] > 21600) {
                $eventPublisher->publishAllHighlightsChangedEvent();
                
                $scheduler->recordEventsTriggered($message["action"]);
            }
        }

        public function onAllHighlightsChanged($message) {
            $this->updateHighlights();
        }
        
        public function onPhotoInvalidated($message) {
            $this->updateHighlightForPhoto($message["photoId"]);
        }
    }

    enum HighlightType {
        case Place;
        case Trip;
        case Category;
        case Year;

        public function getTableName() : string {
            return match ($this) {
                self::Place => "highlight_place",
                self::Trip => "highlight_trip",
                self::Category => "highlight_category",
                self::Year => "highlight_year"
            };
        }
    }
?>