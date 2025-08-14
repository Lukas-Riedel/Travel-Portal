<?php
    namespace Core\Service\Highlight;

use RuntimeException;
use Core\Service\Photo\PhotoService;
use Core\Service\Place\PlaceIncludedEntity;
use Core\Service\Place\PlaceSortingStrategy;
use Core\Service\Trip\TripIncludedEntity;
use Core\Service\Trip\TripSortingStrategy;

    class HighlightService {
        
        private const JPG_FILE_EXTENSION = ".jpg";

        private readonly HighlightMapper $highlightMapper;

        private readonly PhotoService $photoService;
        
        private readonly \EventPublisher $eventPublisher;

        public function __construct(\DatabaseProvider $databaseProvider, PhotoService $photoService, \EventPublisher $eventPublisher) {
            $this->highlightMapper = new HighlightMapper($databaseProvider, $photoService);
            $this->photoService = $photoService;
            $this->eventPublisher = $eventPublisher;
        }

        public function getHighlight(?string $highlightId) : ?Highlight {
            return $highlightId === null ? null : $this->highlightMapper->selectHighlight($highlightId);
        }

        public function getPlaceHighlights(string $placeId) : array {
            return $this->highlightMapper->selectHighlights(HighlightType::Place, $placeId);
        }

        public function getTripHighlights(string $tripId) : array {
            return $this->highlightMapper->selectHighlights(HighlightType::Trip, $tripId);
        }

        public function getCategoryHighlights(string $categoryId) : array {
            // TODO: Introduce a property for PlaceService $placeService.
            global $placeService;

            $highlights = array();
            $deletedHighlightIds = array_map(fn($highlight) => $highlight->getId(),
                $this->highlightMapper->selectHighlights(HighlightType::Category, $categoryId));

            foreach ($placeService->getRegularPlaces($categoryId, null, null, null, null, null, null, null, null,
                array(PlaceIncludedEntity::Highlights->value), PlaceSortingStrategy::Default) as &$categoryPlace) {
                    foreach ($categoryPlace->getHighlights() as &$categoryHighlightCandidate) {
                        if (!in_array($categoryHighlightCandidate->getId(), $deletedHighlightIds)) {
                            $highlights[] = $categoryHighlightCandidate;
                        }
                    }
                }

            return $highlights;
        }

        public function getYearHighlights(int $year) : array {
            // TODO: Introduce a property for TripService $tripService.
            global $tripService;

            $highlights = array();
            $deletedHighlightIds = array_map(fn($highlight) => $highlight->getId(),
                $this->highlightMapper->selectHighlights(HighlightType::Year, $year));

            foreach ($tripService->getRegularTrips($year, null, null, array(TripIncludedEntity::Highlights->value),
                TripSortingStrategy::Default) as &$yearTrip) {
                    foreach ($yearTrip->getHighlights() as &$yearHighlightCandidate) {
                        if (!in_array($yearHighlightCandidate->getId(), $deletedHighlightIds)) {
                            $highlights[] = $yearHighlightCandidate;
                        }
                    }
                }

            return $highlights;
        }

        public function createPlaceHighlight(string $placeId, string $photoId) : Highlight {
            // TODO: Introduce a property for PlaceService $placeService.
            global $placeService;

            $highlightId = $this->getOrCreateHighlightId($photoId);

            // TODO: Remove the create-if-not-exists semantics.
            $highlightNotExists = true;
            foreach ($this->getPlaceHighlights($placeId) as &$entityHighlight) {
                if ($entityHighlight->getId() == $highlightId) {
                    $highlightNotExists = false;
                    break;
                }
            }

            if ($highlightNotExists) {
                $this->highlightMapper->insertHighlight(HighlightType::Place, $placeId, $highlightId);

                $this->eventPublisher->publishHighlightCreatedEvent(HighlightType::Place, $placeId, $highlightId);
                foreach ($placeService->getRegularPlace($placeId)->getCategories() as &$category) {
                    $this->eventPublisher->publishHighlightCreatedEvent(HighlightType::Category, $category->getId(), $highlightId);                    
                }

                $this->updateHighlight($highlightId);
            }
            
           return $this->getHighlight($highlightId);
        }

        public function createTripHighlight(string $tripId, string $photoId) : Highlight {
            // TODO: Introduce a property for TripService $tripService.
            global $tripService;

            $highlightId = $this->getOrCreateHighlightId($photoId);

            // TODO: Remove the create-if-not-exists semantics.
            $highlightNotExists = true;
            foreach ($this->getTripHighlights($tripId) as &$entityHighlight) {
                if ($entityHighlight->getId() == $highlightId) {
                    $highlightNotExists = false;
                    break;
                }
            }

            if ($highlightNotExists) {
                $this->highlightMapper->insertHighlight(HighlightType::Trip, $tripId, $highlightId);

                $this->eventPublisher->publishHighlightCreatedEvent(HighlightType::Trip, $tripId, $highlightId);                
                $this->eventPublisher->publishHighlightCreatedEvent(HighlightType::Year, $tripService->getRegularTrip($tripId)->getYear(), $highlightId);            

                $this->updateHighlight($highlightId);
            }
            
           return $this->getHighlight($highlightId);
        }

        public function createCategoryHighlight(string $categoryId, string $photoId) : Highlight {
            $highlightId = $this->highlightMapper->selectHighlightId($photoId);
            if ($highlightId === null) {
                throw new RuntimeException("Cannot create a highlight for the category. Does a related place highlight exist?");
            }

            $wasCreated = $this->highlightMapper->deleteHighlight(highlightType::Category, $categoryId, $highlightId) > 0;
            if ($wasCreated) {
                $this->eventPublisher->publishHighlightCreatedEvent(HighlightType::Category, $categoryId, $highlightId);
            }   
            return $this->getHighlight($highlightId);
        }

        public function createYearHighlight(int $year, string $photoId) : Highlight {
            $highlightId = $this->highlightMapper->selectHighlightId($photoId);
            if ($highlightId === null) {
                throw new RuntimeException("Cannot create a highlight for the year. Does a related trip highlight exist?");
            }

            $wasCreated = $this->highlightMapper->deleteHighlight(highlightType::Year, $year, $highlightId) > 0;
            if ($wasCreated) {
                $this->eventPublisher->publishHighlightCreatedEvent(HighlightType::Year, $year, $highlightId);
            }   
            return $this->getHighlight($highlightId);
        }

        public function removePlaceHighlight(string $placeId, string $highlightId) : bool {
            $wasRemoved = $this->highlightMapper->deleteHighlight(HighlightType::Place, $placeId, $highlightId) > 0;
            if ($wasRemoved) {
                $this->eventPublisher->publishHighlightRemovedEvent(HighlightType::Place, $placeId, $highlightId);
                $this->highlightMapper->deleteStaleHighlightIdentifiers();
            }        
            return $wasRemoved;
        }

        public function removeTripHighlight(string $tripId, string $highlightId) : bool {
            $wasRemoved = $this->highlightMapper->deleteHighlight(HighlightType::Trip, $tripId, $highlightId) > 0;
            if ($wasRemoved) {
                $this->eventPublisher->publishHighlightRemovedEvent(HighlightType::Trip, $tripId, $highlightId);
                $this->highlightMapper->deleteStaleHighlightIdentifiers();
            }
            return $wasRemoved;
        }

        public function removeCategoryHighlight(string $categoryId, string $highlightId) : bool {
            $wasRemoved = $this->highlightMapper->insertHighlight(HighlightType::Category, $categoryId, $highlightId);
            if ($wasRemoved) {
                $this->eventPublisher->publishHighlightRemovedEvent(HighlightType::Category, $categoryId, $highlightId);
            }
            return $wasRemoved;
        }

        public function removeYearHighlight(int $year, string $highlightId) : bool {
            $wasRemoved = $this->highlightMapper->insertHighlight(HighlightType::Year, $year, $highlightId);
            if ($wasRemoved) {
                $this->eventPublisher->publishHighlightRemovedEvent(HighlightType::Year, $year, $highlightId);
            }
            return $wasRemoved;
        }

        public function updateHighlights() : void {      
            foreach (HighlightSize::cases() as &$highlightSize) {
                $filePaths = $this->doUpdateHighlights($highlightSize, null, null, false);
                $this->unlinkUnusedFiles($filePaths, $highlightSize);
            }
        }

        public function updateHighlight(string $highlightId) : void {
            foreach (HighlightSize::cases() as &$highlightSize) {
                $this->doUpdateHighlights($highlightSize, $highlightId, null, true);
            }
        }

        public function updateHighlightForPhoto(string $photoId) : void {
            foreach (HighlightSize::cases() as &$highlightSize) {
                $this->doUpdateHighlights($highlightSize, null, $photoId, true);
            }
        }

        public function updateHighlightComposition(string $highlightId, int $composition) : bool {
            $wasUpdated = $this->highlightMapper->updateHighlightComposition($highlightId, $composition);
            if ($wasUpdated) {
                $this->publishHighlightUpdatedEvents($highlightId);
            }
            return $wasUpdated;
        }

        public function updateHighlightSky(string $highlightId, int $sky) : bool {
            $wasUpdated = $this->highlightMapper->updateHighlightSky($highlightId, $sky);
            if ($wasUpdated) {
                $this->publishHighlightUpdatedEvents($highlightId);
            }
            return $wasUpdated;
        }

        public function updateHighlightShadows(string $highlightId, int $shadows) : bool {
            $wasUpdated = $this->highlightMapper->updateHighlightShadows($highlightId, $shadows);
            if ($wasUpdated) {
                $this->publishHighlightUpdatedEvents($highlightId);
            }
            return $wasUpdated;
        }

        public function updateHighlightCircumstances(string $highlightId, int $circumstances) : bool {
            $wasUpdated = $this->highlightMapper->updateHighlightCircumstances($highlightId, $circumstances);
            if ($wasUpdated) {
                $this->publishHighlightUpdatedEvents($highlightId);
            }
            return $wasUpdated;
        }

        public function updateHighlightAtmosphere(string $highlightId, int $atmosphere) : bool {
            $wasUpdated = $this->highlightMapper->updateHighlightAtmosphere($highlightId, $atmosphere);
            if ($wasUpdated) {
                $this->publishHighlightUpdatedEvents($highlightId);
            }
            return $wasUpdated;
        }

        private function publishHighlightUpdatedEvents(string $highlightId) : void {
            foreach (HighlightType::cases() as &$highlightType) {
                foreach ($this->getEntityIdsForHighlightId($highlightType, $highlightId) as &$entityId) {
                    $this->eventPublisher->publishHighlightUpdatedEvent($highlightType, $entityId, $highlightId);
                }
            }
        }

        private function getEntityIdsForHighlightId(HighlightType $highlightType, string $highlightId) : array {
            // TODO: Introduce a property for TripService $tripService and PlaceService $placeService.
            global $tripService, $placeService;

            if ($highlightType === HighlightType::Category) {
                $excludedCategoryIds = $this->highlightMapper->selectEntityIdsForHighlightId($highlightType, $highlightId);
                $placesForHighlightId = array_map(fn($placeId) => $placeService->getRegularPlace($placeId),
                    $this->highlightMapper->selectEntityIdsForHighlightId(HighlightType::Place, $highlightId));
                return array_filter(array_unique(array_map(fn($category) => $category->getId(), 
                    array_merge(...array_map(fn($place) => $place->getCategories(), $placesForHighlightId)))),
                    fn($categoryId) => !in_array($categoryId, $excludedCategoryIds));
            }
            if ($highlightType === HighlightType::Year) {
                $excludedYears = $this->highlightMapper->selectEntityIdsForHighlightId($highlightType, $highlightId);
                $tripsForHighlightId = array_map(fn($tripId) => $tripService->getRegularTrip($tripId),
                    $this->highlightMapper->selectEntityIdsForHighlightId(HighlightType::Trip, $highlightId));
                return array_filter(array_unique(array_map(fn($trip) => $trip->getYear(), $tripsForHighlightId)),
                    fn($year) => !in_array($year, $excludedYears));
            }
            return $this->highlightMapper->selectEntityIdsForHighlightId($highlightType, $highlightId);
        }
        
        private function getOrCreateHighlightId(string $photoId) : string {
            $highlightId = $this->highlightMapper->selectHighlightId($photoId);
            if ($highlightId !== null) {
                return $highlightId;
            }

            $this->highlightMapper->insertHighlightId($photoId);

            return $this->highlightMapper->selectHighlightId($photoId);
        }

        private function doUpdateHighlights(HighlightSize $highlightSize, ?string $highlightId, ?string $photoId, bool $forceOverwrite) : array {
            $filePaths = array();

            $highlights = $this->highlightMapper->selectAllHighlights($highlightId, $photoId);
            foreach ($highlights as &$highlight) {
                $fileName = $highlight->getId() . self::JPG_FILE_EXTENSION;
                $filePath = $this->getPhysicalCachePath($highlightSize) . "/" . $fileName;
    
                if ($forceOverwrite || !file_exists($filePath)) {
                    $photoId = $this->highlightMapper->selectPhotoId($highlight->getId());

                    if ($photoId !== null) {
                        $photo = $this->photoService->getPhoto($photoId);

                        if ($photo !== null) {
                            file_put_contents($filePath, file_get_contents($photo->getUrl()
                                . "=w" . $highlightSize->getWidth()
                                . "-h" . $highlightSize->getHeight()));
                        }
                    }
                }
    
                $filePaths[] = $filePath;
                $imageUrl = BASE_URL
                    . "/" . $highlightSize->getCachePath()
                    . "/" . $fileName;

                $this->highlightMapper->updateHighlightImageUrl($highlightSize, $highlight->getId(), $imageUrl);
            }
            
            return $filePaths;
        }

        private function unlinkUnusedFiles(array $usedFilePaths, HighlightSize $highlightSize) : void {
            $existingFilePaths = array_filter((array) glob($this->getPhysicalCachePath($highlightSize) . "/*"));
            $unusedFilePaths = array_diff($existingFilePaths, $usedFilePaths);    
            array_map("unlink", $unusedFilePaths);
        }

        private function getPhysicalCachePath(HighlightSize $highlightSize) : string {
            return __DIR__ . "/../../../../" . $highlightSize->getCachePath();
        }
    }
?>