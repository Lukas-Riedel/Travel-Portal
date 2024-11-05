<?php
    require_once(dirname(__FILE__) . "/../model/Highlight.php");
    require_once(dirname(__FILE__) . "/../processor/UpdateHighlightProcessor.php");

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
                return NULL;
            }
            
            return new Highlight($highlightRow["id"], $highlightRow["thumbnail_url"], $highlightRow["full_url"], 
                $photo->getFocalLength(), $photo->getAperture(), $photo->getShutterSpeed(), $photo->getIso(), $photo->getTimestamp());
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

        public function createHighlight($entityId, $entityType, $photoId) : Highlight {
            global $databaseProvider;

            $highlightIdentifier = $this->getOrCreateHighlightIdentifier($photoId);
            $highlightTable = $this->resolveHighlightTable($entityType);

            // TODO: Remove the create-if-not-exists semantics.
            $highlightNotExists = $databaseProvider
                ->statementBuilder("SELECT * FROM " . $highlightTable . " WHERE id = ? AND highlight_id = ?")
                ->withParameters($entityId, $highlightIdentifier)
                ->getFirstRow() === NULL;

            if ($highlightNotExists) {
                $databaseProvider
                    ->statementBuilder("INSERT INTO " . $highlightTable . " (id, highlight_id) VALUES (?, ?)")
                    ->withParameters($entityId, $highlightIdentifier)
                    ->execute();

                $this->updateEntityMainHighlightIfNull($entityId, $entityType, $highlightIdentifier);

                (new UpdateHighlightProcessor())
                    ->process(array(
                        "highlightId" => $highlightIdentifier));
            }
            
           return $this->getHighlight($highlightIdentifier);
        }

        private function resolveHighlightTable($entityType) : string {
            if ($entityType === "place") {
                return "highlight_place";
            }
            if ($entityType === "trip") {
                return "highlight_trip";
            }
            if ($entityType === "category") {
                return "highlight_category";
            }
            if ($entityType === "year") {
                return "highlight_year";
            }
            throw new InvalidArgumentException("Unknown highlight type " . $entityType . ". Permitted values: place, trip, category, year");
        }

        private function updateEntityMainHighlightIfNull($entityId, $entityType, $highlightIdentifier) : void {
            global $placeService, $tripService, $categoryService, $yearService;

            if ($entityType === "place") {
                $placeIdentifier = $placeService->getPlaceIdentifierById($entityId);
                if ($placeIdentifier !== NULL && $placeIdentifier->getMainHighlight() === NULL) {
                    $placeService->updateMainHighlight($entityId, $highlightIdentifier);
                }
            }
            else if ($entityType === "trip") {
                $tripIdentifier = $tripService->getTripIdentifierById($entityId);
                if ($tripIdentifier !== NULL && $tripIdentifier->getMainHighlight() === NULL) {
                    $tripService->updateMainHighlight($entityId, $highlightIdentifier);
                }
            }
            else if ($entityType === "category") {
                $categoryIdentifier = $categoryService->getCategoryIdentifierById($entityId);
                if ($categoryIdentifier !== NULL && $categoryIdentifier->getMainHighlight() === NULL) {
                    $categoryService->updateMainHighlight($entityId, $highlightIdentifier);
                }
            }
            else if ($entityType === "year") {
                $yearIdentifier = $yearService->getYearIdentifier($entityId);
                if ($yearIdentifier !== NULL && $yearIdentifier->getMainHighlight() === NULL) {
                    $yearService->updateMainHighlight($entityId, $highlightIdentifier);
                }
            }
            else {
                throw new InvalidArgumentException("Unknown highlight type " . $entityType . ". Permitted values: place, trip, category, year");
            }
        }
    }
?>