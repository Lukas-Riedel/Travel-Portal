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
                return new Highlight($highlightRow["id"], $highlightRow["thumbnail_url"], $highlightRow["full_url"], NULL, NULL, NULL, NULL, NULL);;
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
            $highlightTable = $this->resolveHighlightTable($highlightType);

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

                $this->updateEntityMainHighlightIfNull($entityId, $highlightType, $highlightIdentifier);

                (new UpdateHighlightProcessor())
                    ->process(array(
                        "highlightId" => $highlightIdentifier));
            }
            
           return $this->getHighlight($highlightIdentifier);
        }

        private function resolveHighlightTable($highlightType) : string {
            if ($highlightType === HighlightType::Place) {
                return "highlight_place";
            }
            if ($highlightType === HighlightType::Trip) {
                return "highlight_trip";
            }
            if ($highlightType === HighlightType::Category) {
                return "highlight_category";
            }
            if ($highlightType === HighlightType::Year) {
                return "highlight_year";
            }
            throw new InvalidArgumentException("Unknown highlight type " . $highlightType . ".");
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
                $categoryIdentifier = $categoryService->getCategory($entityId);
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
    }

    enum HighlightType {
        case Place;
        case Trip;
        case Category;
        case Year;
    }
?>