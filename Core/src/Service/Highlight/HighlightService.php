<?php
    namespace Core\Service\Highlight;

    use Common\Client\Http\HttpMethod;
    use Core\Client\CloudStorage\CloudStorageClient;
    use Core\Common\CommonConstants;
    use RuntimeException;
    use Core\Service\Photo\PhotoService;
    use Core\Service\Place\PlaceIncludedEntity;
    use Core\Service\Place\PlaceSortingStrategy;
    use Core\Service\Trip\TripIncludedEntity;
    use Core\Service\Trip\TripSortingStrategy;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\TransactionManager;
    use Core\Client\Http\HttpClient;

    class HighlightService {

        private readonly HighlightMapper $highlightMapper;

        private readonly PhotoService $photoService;
        
        private readonly EventPublisher $eventPublisher;

        private readonly CloudStorageClient $cloudStorageClient;

        private readonly HttpClient $httpClient;

        private readonly TransactionManager $transactionManager;

        public function __construct(DatabaseClient $databaseClient, PhotoService $photoService, EventPublisher $eventPublisher, CloudStorageClient $cloudStorageClient, HttpClient $httpClient) {
            $this->highlightMapper = new HighlightMapper($databaseClient, $photoService);
            $this->photoService = $photoService;
            $this->cloudStorageClient = $cloudStorageClient;
            $this->eventPublisher = $eventPublisher;
            $this->transactionManager = $databaseClient;
            $this->httpClient = $httpClient;
        }

        public function getHighlight(?string $highlightId) : ?Highlight {
            return $highlightId === null ? null : $this->highlightMapper->selectHighlight($highlightId);
        }

        public function getHighlights(array $highlightIds) : array {
            return $this->highlightMapper->selectHighlightsByIds($highlightIds);
        }

        public function getPlaceHighlights(string $placeId) : array {
            return $this->highlightMapper->selectHighlightsForEntity(HighlightType::Place, $placeId);
        }

        public function getTripHighlights(string $tripId) : array {
            return $this->highlightMapper->selectHighlightsForEntity(HighlightType::Trip, $tripId);
        }

        public function getCategoryHighlights(string $categoryId) : array {
            // TODO: Introduce a property for PlaceService $placeService.
            global $placeService;

            $highlights = array();
            $deletedHighlightIds = array_map(fn($highlight) => $highlight->getId(),
                $this->highlightMapper->selectHighlightsForEntity(HighlightType::Category, $categoryId));

            foreach ($placeService->getRegularPlaces($categoryId, null, null, null, null, null, null, null, null, null, null,
                array(PlaceIncludedEntity::Highlights->value), PlaceSortingStrategy::OldestAscending) as &$categoryPlace) {
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

            $allYearHighlights = $this->highlightMapper->selectHighlightsForEntity(HighlightType::Year, $year);
            $deletedHighlightIds = array_map(fn($highlight) => $highlight->getId(), array_filter($allYearHighlights,
                fn($highlight) => !empty($this->highlightMapper->selectEntityIdsForHighlightId(HighlightType::Trip, $highlight->getId()))));

            $standaloneHighlights = array_values(array_filter($allYearHighlights,
                fn($highlight) => empty($this->highlightMapper->selectEntityIdsForHighlightId(HighlightType::Trip, $highlight->getId()))));

            $tripHighlights = array();
            foreach ($tripService->getRegularTrips($year, null, null, array(TripIncludedEntity::Highlights->value),
                TripSortingStrategy::OldestAscending) as &$yearTrip) {
                    foreach ($yearTrip->getHighlights() as &$yearHighlightCandidate) {
                        if (!in_array($yearHighlightCandidate->getId(), $deletedHighlightIds)) {
                            $tripHighlights[] = $yearHighlightCandidate;
                        }
                    }
                }

            return array_merge($tripHighlights, $standaloneHighlights);
        }

        public function createPlaceHighlight(string $placeId, string $photoId) : Highlight {
            // TODO: Introduce a property for PlaceService $placeService, or move publishing CategoryHighlightCreated to onHighlightCreated.
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
                $this->transactionManager->executeAtomically(function() use(&$placeId, &$highlightId, &$placeService) {
                    $this->highlightMapper->insertHighlight(HighlightType::Place, $placeId, $highlightId);

                    $this->eventPublisher->publish(Event::HighlightCreated(HighlightType::Place->value, $placeId, $highlightId));   
                    // TODO: Move this to onHighlightCreated.
                    foreach ($placeService->getRegularPlace($placeId)->getCategories() as &$category) {
                        $this->eventPublisher->publish(Event::HighlightCreated(HighlightType::Category->value, $category->getId(), $highlightId));                    
                    }
                });

                $this->updateHighlight($highlightId);
            }
            
           return $this->getHighlight($highlightId);
        }

        public function createTripHighlight(string $tripId, string $photoId) : Highlight {
            // TODO: Introduce a property for TripService $tripService, or move publishing YearHighlightCreated to onHighlightCreated.
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
                $this->transactionManager->executeAtomically(function() use(&$tripId, &$highlightId, &$tripService) {
                    $this->highlightMapper->insertHighlight(HighlightType::Trip, $tripId, $highlightId);

                    $this->eventPublisher->publish(Event::HighlightCreated(HighlightType::Trip->value, $tripId, $highlightId));         
                    // TODO: Move this to onHighlightCreated.       
                    $this->eventPublisher->publish(Event::HighlightCreated(HighlightType::Year->value, $tripService->getRegularTrip($tripId)->getYear(), $highlightId));   
                });         

                $this->updateHighlight($highlightId);
            }
            
           return $this->getHighlight($highlightId);
        }

        public function createCategoryHighlight(string $categoryId, string $photoId) : Highlight {
            $highlightId = $this->highlightMapper->selectHighlightId($photoId);
            if ($highlightId === null) {
                throw new RuntimeException("Cannot create a highlight for the category. Does a related place highlight exist?");
            }
            if (empty($this->highlightMapper->selectEntityIdsForHighlightId(HighlightType::Place, $highlightId))) {
                throw new RuntimeException("Cannot create a highlight for the category. Does a related place highlight exist?");                
            }

            $wasCreated = true;
            $this->transactionManager->executeAtomically(function() use(&$categoryId, &$highlightId, &$wasCreated) {
                $wasCreated &= $this->highlightMapper->deleteHighlight(HighlightType::Category, $categoryId, $highlightId) > 0;
                if ($wasCreated) {
                    $this->eventPublisher->publish(Event::HighlightCreated(HighlightType::Category->value, $categoryId, $highlightId));
                }                   
            });

            return $this->getHighlight($highlightId);
        }

        public function createYearHighlight(int $year, string $photoId) : Highlight {
            $highlightId = $this->getOrCreateHighlightId($photoId);
            $tripHighlightExists = !empty($this->highlightMapper->selectEntityIdsForHighlightId(HighlightType::Trip, $highlightId));

            $wasCreated = true;
            $this->transactionManager->executeAtomically(function() use(&$year, &$highlightId, &$wasCreated, &$tripHighlightExists) {
                if ($tripHighlightExists) {
                    $wasCreated &= $this->highlightMapper->deleteHighlight(HighlightType::Year, $year, $highlightId) > 0;
                }
                else {
                    $wasCreated &= $this->highlightMapper->insertHighlight(HighlightType::Year, $year, $highlightId);
                    $this->updateHighlight($highlightId);
                }

                if ($wasCreated) {
                    $this->eventPublisher->publish(Event::HighlightCreated(HighlightType::Year->value, $year, $highlightId));
                }                  
            });

            return $this->getHighlight($highlightId);
        }

        public function removePlaceHighlight(string $placeId, string $highlightId) : bool {
            $wasRemoved = true;
            $this->transactionManager->executeAtomically(function() use(&$placeId, &$highlightId, &$wasRemoved) {
                $wasRemoved &= $this->highlightMapper->deleteHighlight(HighlightType::Place, $placeId, $highlightId) > 0;
                if ($wasRemoved) {
                    $this->eventPublisher->publish(Event::HighlightRemoved(HighlightType::Place->value, $placeId, $highlightId));
                }                    
            });  

            if ($wasRemoved) {                
                $this->highlightMapper->deleteStaleHighlightIdentifiers();
            }  

            return $wasRemoved;
        }

        public function removeTripHighlight(string $tripId, string $highlightId) : bool {
            $wasRemoved = true;
            $this->transactionManager->executeAtomically(function() use(&$tripId, &$highlightId, &$wasRemoved) {            
                $wasRemoved &= $this->highlightMapper->deleteHighlight(HighlightType::Trip, $tripId, $highlightId) > 0;
                if ($wasRemoved) {
                    $this->eventPublisher->publish(Event::HighlightRemoved(HighlightType::Trip->value, $tripId, $highlightId));
                }
            });  

            if ($wasRemoved) {
                $this->highlightMapper->deleteStaleHighlightIdentifiers();
            }

            return $wasRemoved;
        }

        public function removeCategoryHighlight(string $categoryId, string $highlightId) : bool {
            $wasRemoved = true;
            $this->transactionManager->executeAtomically(function() use(&$categoryId, &$highlightId, &$wasRemoved) {
                $wasRemoved &= $this->highlightMapper->insertHighlight(HighlightType::Category, $categoryId, $highlightId);
                if ($wasRemoved) {
                    $this->eventPublisher->publish(Event::HighlightRemoved(HighlightType::Category->value, $categoryId, $highlightId));
                }
            });

            return $wasRemoved;
        }

        public function removeYearHighlight(int $year, string $highlightId) : bool {
            $tripHighlightExists = !empty($this->highlightMapper->selectEntityIdsForHighlightId(HighlightType::Trip, $highlightId));

            $wasRemoved = true;            
            $this->transactionManager->executeAtomically(function() use(&$year, &$highlightId, &$wasRemoved, &$tripHighlightExists) {
                if ($tripHighlightExists) {
                    $wasRemoved &= $this->highlightMapper->insertHighlight(HighlightType::Year, $year, $highlightId);
                }
                else {
                    $wasRemoved &= $this->highlightMapper->deleteHighlight(HighlightType::Year, $year, $highlightId) > 0;
                }

                if ($wasRemoved) {
                    $this->eventPublisher->publish(Event::HighlightRemoved(HighlightType::Year->value, $year, $highlightId));
                }
            });

            return $wasRemoved;
        }

        public function updateHighlights() : void {      
            foreach (HighlightSize::cases() as &$highlightSize) {
                $objectKeys = $this->doUpdateHighlights($highlightSize, null, null, false);
                $this->pruneUnusedObjects($objectKeys, $highlightSize);
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
            $wasUpdated = true;
            $this->transactionManager->executeAtomically(function() use(&$highlightId, &$composition, &$wasUpdated) {
                $wasUpdated &= $this->highlightMapper->updateHighlightComposition($highlightId, $composition);
                if ($wasUpdated) {
                    $this->publishHighlightUpdatedEvents($highlightId);
                }
            });
            return $wasUpdated;
        }

        public function updateHighlightSky(string $highlightId, int $sky) : bool {
            $wasUpdated = true;
            $this->transactionManager->executeAtomically(function() use(&$highlightId, &$sky, &$wasUpdated) {
                $wasUpdated &= $this->highlightMapper->updateHighlightSky($highlightId, $sky);
                if ($wasUpdated) {
                    $this->publishHighlightUpdatedEvents($highlightId);
                }
            });
            return $wasUpdated;
        }

        public function updateHighlightShadows(string $highlightId, int $shadows) : bool {
            $wasUpdated = true;
            $this->transactionManager->executeAtomically(function() use(&$highlightId, &$shadows, &$wasUpdated) {
                $wasUpdated &= $this->highlightMapper->updateHighlightShadows($highlightId, $shadows);
                if ($wasUpdated) {
                    $this->publishHighlightUpdatedEvents($highlightId);
                }
            });
            return $wasUpdated;
        }

        public function updateHighlightCircumstances(string $highlightId, int $circumstances) : bool {
            $wasUpdated = true;
            $this->transactionManager->executeAtomically(function() use(&$highlightId, &$circumstances, &$wasUpdated) {
                $wasUpdated &= $this->highlightMapper->updateHighlightCircumstances($highlightId, $circumstances);
                if ($wasUpdated) {
                    $this->publishHighlightUpdatedEvents($highlightId);
                }
            });
            return $wasUpdated;
        }

        public function updateHighlightAtmosphere(string $highlightId, int $atmosphere) : bool {
            $wasUpdated = true;
            $this->transactionManager->executeAtomically(function() use(&$highlightId, &$atmosphere, &$wasUpdated) {
                $wasUpdated &= $this->highlightMapper->updateHighlightAtmosphere($highlightId, $atmosphere);
                if ($wasUpdated) {
                    $this->publishHighlightUpdatedEvents($highlightId);
                }
            });
            return $wasUpdated;
        }

        public function deleteHighlightObject(string $highlightId) : void {
            foreach (HighlightSize::cases() as &$highlightSize) {
                $objectKey = $this->getHighlightObjectKey($highlightId);
                $this->cloudStorageClient->delete($highlightSize->getBucket(), $objectKey);
            }
        }

        private function publishHighlightUpdatedEvents(string $highlightId) : void {
            foreach (HighlightType::cases() as &$highlightType) {
                foreach ($this->getEntityIdsForHighlightId($highlightType, $highlightId) as &$entityId) {
                    $this->eventPublisher->publish(Event::HighlightUpdated($highlightType->value, $entityId, $highlightId));
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

        private function doUpdateHighlights(HighlightSize $highlightSize, ?string $highlightId, ?string $photoId, bool $overwrite) : array {
            $objectKeys = array();

            $highlights = $this->highlightMapper->selectAllHighlights($highlightId, $photoId);
            foreach ($highlights as &$highlight) {
                $objectKey = $this->getHighlightObjectKey($highlight->getId());
    
                if ($overwrite || !$this->cloudStorageClient->exists($highlightSize->getBucket(), $objectKey)) {
                    $photoId = $this->highlightMapper->selectPhotoId($highlight->getId());

                    if ($photoId !== null) {
                        $photo = $this->photoService->getPhoto($photoId);

                        if ($photo !== null) {
                            $data = $this->httpClient->executeRequest(HttpMethod::GET,
                                $photo->getUrl() . "=w" . $highlightSize->getWidth() . "-h" . $highlightSize->getHeight());
                            $this->cloudStorageClient->put($highlightSize->getBucket(), $objectKey, $data);
                        }
                    }
                }
    
                $objectKeys[] = $objectKey;
                $imageUrl = $this->cloudStorageClient->getPath($highlightSize->getBucket(), $objectKey);

                $this->highlightMapper->updateHighlightImageUrl($highlightSize, $highlight->getId(), $imageUrl);
            }
            
            return $objectKeys;
        }

        private function getHighlightObjectKey(string $highlightId) : string {
            return $highlightId . CommonConstants::JPG_FILE_EXTENSION;
        }
        
        private function pruneUnusedObjects(array $usedObjectKeys, HighlightSize $highlightSize) : void {
            $existingObjectKeys = $this->cloudStorageClient->list($highlightSize->getBucket());
            $unusedObjectKeys = array_diff($existingObjectKeys, $usedObjectKeys);
            foreach ($unusedObjectKeys as $objectKey) {
                $this->cloudStorageClient->delete($highlightSize->getBucket(), $objectKey);
            }
        }
    }
?>