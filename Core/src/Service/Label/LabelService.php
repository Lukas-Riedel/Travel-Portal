<?php
    namespace Core\Service\Label;

    use Core\Client\Database\DatabaseClient;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Service\Highlight\HighlightService;
    use Core\Service\Index\IndexService;
    use Core\Service\Place\PlaceSortingStrategy;
    
    class LabelService {

        private readonly LabelMapper $labelMapper;
        private readonly HighlightService $highlightService;
        private readonly IndexService $indexService;
        private readonly ConfigurationService $configurationService;
        private readonly EventPublisher $eventPublisher;

        public function __construct(DatabaseClient $databaseClient, ConfigurationService $configurationService, HighlightService $highlightService, IndexService $indexService, EventPublisher $eventPublisher) {
            $this->labelMapper = new LabelMapper($databaseClient, $configurationService, $highlightService);
            $this->highlightService = $highlightService;
            $this->indexService = $indexService;
            $this->configurationService = $configurationService;
            $this->eventPublisher = $eventPublisher;
        }
        
        public function createLabel(string $placeId, string $labelName) : Label {
            $label = new Label($this->getOrCreateLabelId($labelName), $labelName, null, null, array());
            $this->labelMapper->insertLabel($placeId, $label->getId());
            $this->eventPublisher->publish(Event::LabelUpdated($label->getId()));
            return $label;
        }

        public function getAllLabels(array $includedEntities = array()) : array {
            return $this->labelMapper->selectLabels(null, null, $includedEntities);
        }

        public function getLabelsForPlace(string $placeId) : array {
            return $this->labelMapper->selectLabels(null, $placeId, array());
        }

        public function getPlaceIdsForLabelId(string $labelId) : array {
            return $this->labelMapper->selectPlaceIdsForLabelId($labelId);
        }

        public function getOrCreateLabelId(string $labelName) : string {
            $labelId = $this->labelMapper->selectLabelId($labelName);
            if ($labelId !== null) {
                return $labelId;
            }

            $this->labelMapper->insertLabelId($labelName);

            return $this->labelMapper->selectLabelId($labelName);
        }
        
        public function getLabel(string $labelId) : ?Label {
            $labels = $this->labelMapper->selectLabels($labelId, null, LabelIncludedEntity::values());
            return $labels[0] ?? null;
        }

        public function updateLabelName(string $labelId, string $name) : bool {
            return $this->labelMapper->updateLabelName($labelId, $name);
        }

        public function updateLabelUnicode(string $labelId, string $unicode) : bool {
            return $this->labelMapper->updateLabelUnicode($labelId, $unicode);
        }

        public function updateLabelMainHighlight(string $labelId, ?string $highlightIdentifier) : bool {
            return $this->labelMapper->updateLabelMainHighlight($labelId, $highlightIdentifier);
        }

        public function refreshLabelHighlights(string $labelId, int $count) : void {
            // TODO: Introduce a property for PlaceService $placeService.
            global $placeService;

            $label = $this->getLabel($labelId);
            if ($label === null) {
                return;
            }

            $places = $placeService->getRegularPlaces(null, $labelId, null, null, null, null, null, null,
                null, time(), null, null, array(), PlaceSortingStrategy::ScoreDescending);

            $prompt = $this->configurationService->getConfigurationEntry("generativeContentPrompt")["categoryHighlightsSelecting"];

            $selectedPhotoIds = $this->indexService->getSelectedPhotoIdsForLabel(array_map(fn($place) => $place->getId(), $places), $label->getName(), $count,
                $label->getMainHighlight()?->getPhoto()?->getId(), array_filter(array_map(fn($place) => $place->getMainHighlight()?->getPhoto()?->getId(), $places)));

            foreach ($label->getHighlights() as &$highlight) {
                if (!in_array($highlight->getPhoto()->getId(), $selectedPhotoIds)) {
                    $this->highlightService->removeLabelHighlight($labelId, $highlight->getId());
                }
            }

            $existingHighlightPhotoIds = array_map(fn($highlight) => $highlight->getPhoto()->getId(), $label->getHighlights());
            foreach ($selectedPhotoIds as &$photoId) {
                if (!in_array($photoId, $existingHighlightPhotoIds)) {
                    $this->highlightService->createLabelHighlight($labelId, $photoId);
                }
            }
        }

        public function removeLabelForPlace(string $placeId, string $labelId) : bool {
            $wasRemoved = $this->labelMapper->deleteLabelForPlace($placeId, $labelId) > 0;
            if ($wasRemoved) {
                $this->labelMapper->deleteStaleLabelIdentifiers();
                $this->eventPublisher->publish(Event::LabelUpdated($labelId));
            }
            return $wasRemoved;
        }

        public function removeLabelForAllPlaces(string $labelId) : bool {
            $wasRemoved = $this->labelMapper->deleteLabelForAllPlaces($labelId) > 0;
            if ($wasRemoved) {
                $this->labelMapper->deleteStaleLabelIdentifiers();
                $this->eventPublisher->publish(Event::LabelUpdated($labelId));
            }
            return $wasRemoved;
        }

        public function reassignLabelForPlaces(string $labelId, array $placeIds) : void {
            $this->labelMapper->deleteLabelForAllPlaces($labelId);
            foreach ($placeIds as &$placeId) {
                $this->labelMapper->insertLabel($placeId, $labelId);
            }
            $this->labelMapper->deleteStaleLabelIdentifiers();
            $this->eventPublisher->publish(Event::LabelUpdated($labelId));
        }
    }
?>