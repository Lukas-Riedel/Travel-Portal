<?php
    require_once(dirname(__FILE__) . "/HighlightMapper.php");
    require_once(dirname(__FILE__) . "/../model/Highlight.php");

    class HighlightService {
        
        private const FETCH_HIGHLIGHTS_ACTION_NAME = "FETCH_HIGHLIGHTS";
        private const FETCH_HIGHLIGHTS_ACTION_INTERVAL = 21600;
        
        private const JPG_FILE_EXTENSION = ".jpg";

        private readonly HighlightMapper $highlightMapper;

        private readonly PhotoService $photoService;

        private readonly ConfigurationService $configurationService;
        
        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        public function __construct(DatabaseProvider $databaseProvider, PhotoService $photoService,
            ConfigurationService $configurationService, EventPublisher $eventPublisher, Scheduler $scheduler) {
            $this->highlightMapper = new HighlightMapper($databaseProvider, $photoService);
            $this->photoService = $photoService;
            $this->configurationService = $configurationService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function getHighlight(?string $highlightId) : ?Highlight {
            return $highlightId === NULL ? NULL : $this->highlightMapper->selectHighlight($highlightId);
        }

        public function getPlaceHighlights(string $placeId) : array {
            return $this->getHighlights(HighlightType::Place, $placeId);
        }

        public function getCategoryHighlights(string $categoryId) : array {
            return $this->getHighlights(HighlightType::Category, $categoryId);
        }

        public function getYearHighlights(int $year) : array {
            return $this->getHighlights(HighlightType::Year, $year);
        }

        public function getTripHighlights(string $tripId) : array {
            return $this->getHighlights(HighlightType::Trip, $tripId);
        }

        public function createPlaceHighlight(string $placeId, string $photoId) : Highlight {
            return $this->createHighlight(HighlightType::Place, $placeId, $photoId);
        }

        public function createTripHighlight(string $tripId, string $photoId) : Highlight {
            return $this->createHighlight(HighlightType::Trip, $tripId, $photoId);
        }

        public function createCategoryHighlight(string $categoryId, string $photoId) : Highlight {
            return $this->createHighlight(HighlightType::Category, $categoryId, $photoId);
        }

        public function createYearHighlight(int $year, string $photoId) : Highlight {
            return $this->createHighlight(HighlightType::Year, $year, $photoId);
        }

        public function removePlaceHighlight(string $placeId, string $highlightId) : bool {
            return $this->removeHighlight(HighlightType::Place, $placeId, $highlightId);
        }

        public function removeTripHighlight(string $tripId, string $highlightId) : bool {
            return $this->removeHighlight(HighlightType::Trip, $tripId, $highlightId);
        }

        public function removeCategoryHighlight(string $categoryId, string $highlightId) : bool {
            return $this->removeHighlight(HighlightType::Category, $categoryId, $highlightId);
        }

        public function removeYearHighlight(int $year, string $highlightId) : bool {
            return $this->removeHighlight(HighlightType::Year, $year, $highlightId);
        }
        
        public function getOrCreateHighlightId(string $photoId) : string {
            $highlightId = $this->highlightMapper->selectHighlightId($photoId);
            if ($highlightId !== NULL) {
                return $highlightId;
            }

            $this->highlightMapper->insertHighlightId($photoId);

            return $this->highlightMapper->selectHighlightId($photoId);
        }

        public function updateHighlights() : void {
            // TODO: Do the same also for the full size.
            $thumbnailFilePaths = $this->doUpdateHighlights(HighlightSize::Thumbnail, NULL, NULL, FALSE);
            $this->unlinkUnusedFiles($thumbnailFilePaths, HighlightSize::Thumbnail);
        }

        public function updateHighlight(string $highlightId) : void {
            // TODO: Do the same also for the full size.
            $this->doUpdateHighlights(HighlightSize::Thumbnail, $highlightId, NULL, TRUE);
        }

        public function updateHighlightForPhoto(string $photoId) : void {
            // TODO: Do the same also for the full size.
            $this->doUpdateHighlights(HighlightSize::Thumbnail, NULL, $photoId, TRUE);
        }
        
        private function getHighlights(HighlightType $highlightType, string $entityId) : array {
            return $this->highlightMapper->selectHighlights($highlightType, $entityId);
        }

        private function createHighlight(HighlightType $highlightType, string $entityId, string $photoId) : Highlight {
            $highlightId = $this->getOrCreateHighlightId($photoId);

            // TODO: Remove the create-if-not-exists semantics.
            $highlightNotExists = TRUE;
            foreach ($this->getHighlights($highlightType, $entityId) as &$entityHighlight) {
                if ($entityHighlight->getId() === $highlightId) {
                    $highlightNotExists = FALSE;
                    break;
                }
            }

            if ($highlightNotExists) {
                $this->highlightMapper->insertHighlight($highlightType, $entityId, $highlightId);

                $this->eventPublisher->publishHighlightCreatedEvent($highlightType, $entityId, $highlightId);
                $this->updateHighlight($highlightId);
            }
            
           return $this->getHighlight($highlightId);
        }

        private function removeHighlight(HighlightType $highlightType, string $entityId, string $highlightId) : bool {
            $wasRemoved = $this->highlightMapper->deleteHighlight($highlightType, $entityId, $highlightId) === 1;
            if ($wasRemoved) {
                $this->eventPublisher->publishHighlightRemovedEvent($highlightType, $entityId, $highlightId);
            }        
            return $wasRemoved;
        }

        private function doUpdateHighlights(HighlightSize $highlightSize, ?string $highlightId, ?string $photoId, bool $forceOverwrite) : array {
            $filePaths = array();

            $highlights = $this->highlightMapper->selectAllHighlights($highlightId, $photoId);
            foreach ($highlights as &$highlight) {
                $fileName = $highlight->getId() . self::JPG_FILE_EXTENSION;
                $filePath = $this->getPhysicalCachePath($highlightSize) . "/" . $fileName;
    
                if ($forceOverwrite || !file_exists($filePath)) {
                    $photoId = $this->highlightMapper->selectPhotoId($highlight->getId());

                    if ($photoId !== NULL) {
                        $photo = $this->photoService->getPhoto($photoId);

                        if ($photo !== NULL) {
                            file_put_contents($filePath, file_get_contents($photo->getUrl()
                                . "=w" . $highlightSize->getWidth()
                                . "-h" . $highlightSize->getHeight()));
                        }
                    }
                }
    
                $filePaths[] = $filePath;
                $imageUrl = $this->configurationService->getBaseUrl()
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
            return dirname(__FILE__) . "/../../" . $highlightSize->getCachePath();
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($message["action"] === self::FETCH_HIGHLIGHTS_ACTION_NAME
                && $message["timeSinceLastExecution"] > self::FETCH_HIGHLIGHTS_ACTION_INTERVAL) {
                $this->eventPublisher->publishAllHighlightsInvalidatedEvent();                
                $this->scheduler->recordEventsTriggered(self::FETCH_HIGHLIGHTS_ACTION_NAME);
            }
        }

        public function onAllHighlightsInvalidated(mixed $message) : void {
            $this->updateHighlights();
        }

        public function onHighlightRemovedChanged(mixed $message) : void {
            $this->updateHighlights();
        }
        
        public function onPhotoInvalidated(mixed $message) : void {
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

    enum HighlightSize {
        case Full;
        case Thumbnail;

        public function getUrlColumnName() : string {
            return match($this) {
                self::Full => "full_url",
                self::Thumbnail => "thumbnail_url"
            };
        }

        public function getWidth() : int {
            return match($this) {
                self::Full => 6000,
                self::Thumbnail => 350
            };
        }

        public function getHeight() : int {
            return match($this) {
                self::Full => 4000,
                self::Thumbnail => 233
            };
        }

        public function getCachePath() : string {
            return match($this) {
                self::Full => "cache/highlight/full",
                self::Thumbnail => "cache/highlight/thumbnail"
            };
        }
    }
?>