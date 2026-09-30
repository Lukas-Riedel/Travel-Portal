<?php
    namespace Core\Service\Label;

    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\TransactionManager;
    use Core\Client\GenerativeContent\GenerativeContentClient;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Service\Highlight\HighlightService;
    use Core\Service\Index\IndexService;
    use Core\Service\Place\Place;
    use Core\Service\Place\PlaceIncludedEntity;
    use Core\Service\Place\PlaceService;
    use Core\Service\Place\PlaceSortingStrategy;
    use Monolog\Logger;

    class LabelService {

        private const AUTO_ASSIGNMENT_BATCH_SIZE = 15;
        private const AUTO_ASSIGNMENT_RESPONSE_SCHEMA = array(
            "type" => "array",
            "items" => array(
                "type" => "object",
                "properties" => array(
                    "labelId"  => array("type" => "string", "description" => "The identifier of the label being evaluated (if available)."),
                    "placeId"  => array("type" => "string", "description" => "The identifier of the place being evaluated (if available)."),
                    "reason" => array("type" => "string", "description" => "One sentence explaining why the answer is true or false."),
                    "answer" => array("type" => "boolean", "description" => "True only if the place is clearly and prominently known for this label, false otherwise.")
                ),
                "required" => array("reason", "answer")
            )
        );

        private readonly LabelMapper $labelMapper;
        private readonly HighlightService $highlightService;
        private readonly IndexService $indexService;
        private readonly ConfigurationService $configurationService;
        private readonly EventPublisher $eventPublisher;
        private readonly TransactionManager $transactionManager;
        private readonly GenerativeContentClient $generativeContentClient;
        private readonly Logger $logger;

        private ?PlaceService $placeService = null;

        public function __construct(DatabaseClient $databaseClient, ConfigurationService $configurationService, HighlightService $highlightService, IndexService $indexService,
            EventPublisher $eventPublisher, GenerativeContentClient $generativeContentClient, Logger $logger) {
            $this->labelMapper = new LabelMapper($databaseClient, $configurationService, $highlightService);
            $this->highlightService = $highlightService;
            $this->indexService = $indexService;
            $this->configurationService = $configurationService;
            $this->eventPublisher = $eventPublisher;
            $this->transactionManager = $databaseClient;
            $this->generativeContentClient = $generativeContentClient;
            $this->logger = $logger;
        }

        public function setPlaceService(PlaceService $placeService) : void {
            $this->placeService = $placeService;
        }
        
        public function createLabel(string $placeId, string $labelName) : Label {
            $label = new Label($this->getOrCreateLabelId($labelName), $labelName, null, null, array());
            $this->labelMapper->insertLabel($placeId, $label->getId());
            $this->eventPublisher->publish(Event::LabelUpdated($label->getId()));
            return $label;
        }

        public function getAllLabels(array $includedEntities) : array {
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

            $labelId = null;
            $this->transactionManager->executeAtomically(function() use($labelName, &$labelId) {
                $labelId = $this->labelMapper->insertLabelId($labelName);
                $this->eventPublisher->publish(Event::LabelCreated($labelId));
            });

            return $labelId;
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
            $label = $this->getLabel($labelId);
            if ($label === null) {
                return;
            }

            $places = $this->placeService->getRegularPlaces(null, $labelId, null, null, null, null, null, null,
                null, time(), null, null, array(), PlaceSortingStrategy::ScoreDescending);
            $selectedPhotoIds = $this->indexService->getSelectedPhotoIdsForLabel(array_map(fn($place) => $place->getId(), $places), $count,
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

        public function assignLabelsToPlace(Place $place) : void {
            $dynamicLabelNames = array_column($this->configurationService->getConfigurationEntry("dynamicLabels"), "name");
            $labels = array_filter($this->getAllLabels(array()), fn($label) => !in_array($label->getName(), $dynamicLabelNames));
            
            foreach (array_chunk(array_values($labels), self::AUTO_ASSIGNMENT_BATCH_SIZE) as &$labels) {
                $labelsList = implode(", ", array_map(fn($i, $label) => ($i + 1) . ". " . $label->getName() . " (labelId: " . $label->getId() . ")", array_keys($labels), $labels));
                $answers = $this->getAutoAssignmentResponse("labelAutoAssignmentForPlace", "labelId", array("name" => $place->getName(), "country" => $place->getCountry() ?? "UNKNOWN", "region" => $this->getPlaceRegion($place) ?? "UNKNOWN", "labels" => $labelsList));

                foreach ($labels as &$label) {
                    if ($answers[$label->getId()] ?? false) {
                        $this->transactionManager->executeAtomically(function() use(&$place, &$label) {
                            $this->labelMapper->deleteLabelForPlace($place->getId(), $label->getId());
                            $this->labelMapper->insertLabel($place->getId(), $label->getId());
                            $this->eventPublisher->publish(Event::LabelUpdated($label->getId()));
                        });
                    }
                }
            }
        }

        public function assignPlacesToLabel(Label $label) : void {
            $places = array_merge($this->placeService->getRegularPlaces(null, null, null, null, null, null, null, null, null, time(), null, null, array(PlaceIncludedEntity::Categories->value), PlaceSortingStrategy::OldestAscending), $this->placeService->getCandidatePlaces(null, null, null, null, array(PlaceIncludedEntity::Categories->value)));

            foreach (array_chunk($places, self::AUTO_ASSIGNMENT_BATCH_SIZE) as $places) {
                $placesList = implode(", ", array_map(fn($i, $place) => ($i + 1) . ". " . $place->getName() . ", " . ($this->getPlaceRegion($place) ?? "UNKNOWN") . ", " . ($place->getCountry() ?? "UNKNOWN") . " (placeId: " . $place->getId() . ")", array_keys($places), $places));
                $answers = $this->getAutoAssignmentResponse("labelAutoAssignmentForLabel", "placeId", array("label" => $label->getName(), "places" => $placesList));

                foreach ($places as &$place) {
                    if ($answers[$place->getId()] ?? false) {
                        $this->transactionManager->executeAtomically(function() use(&$place, &$label) {
                            $this->labelMapper->deleteLabelForPlace($place->getId(), $label->getId());
                            $this->labelMapper->insertLabel($place->getId(), $label->getId());
                        });
                    }
                }

                // Update the label continuously as it can take hundereds of requests to assign all places.
                $this->eventPublisher->publish(Event::LabelUpdated($label->getId()));
            }
        }

        private function getAutoAssignmentResponse(string $promptKey, string $resultKeySelector, array $context) : array {
            $prompt = $this->configurationService->getConfigurationEntry("generativeContentPrompt")[$promptKey];

            $response = $this->generativeContentClient->getResponse($prompt, $context, self::AUTO_ASSIGNMENT_RESPONSE_SCHEMA);
            if ($response === null) {
                $this->logger->error("The auto-assignment request was not successful. Response: null");
                return array();
            }

            $decoded = json_decode($response, true);
            if (!is_array($decoded)) {
                $this->logger->error("The auto-assignment request was not successful. Response: " . $response);
                return array();
            }

            $result = array();
            foreach ($decoded as &$item) {                
                if (isset($item[$resultKeySelector]) && is_bool($item["answer"])) {
                    $result[$item[$resultKeySelector]] = $item["answer"];
                }
            }

            return $result;
        }

        private function getPlaceRegion(Place $place) : ?string {
            $placeCategories = $place->getCategories();
            if (empty($placeCategories)) {
                return null;
            }

            return $placeCategories[count($placeCategories) - 1]->getName();
        }
    }
?>