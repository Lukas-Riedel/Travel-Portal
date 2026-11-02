<?php
    namespace Core\Service\Label;

    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\TransactionManager;
    use Core\Client\Translation\TranslationClient;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Service\Highlight\HighlightService;
    use Core\Service\Index\IndexService;
    use Core\Service\Place\PlaceService;
    use Core\Service\Place\PlaceSortingStrategy;

    class LabelService {
        private const KEYWORD_LANGUAGE = "en";

        private readonly LabelMapper $labelMapper;
        private readonly HighlightService $highlightService;
        private readonly IndexService $indexService;
        private readonly TranslationClient $translationClient;
        private readonly EventPublisher $eventPublisher;
        private readonly TransactionManager $transactionManager;

        private ?PlaceService $placeService = null;

        public function __construct(DatabaseClient $databaseClient, HighlightService $highlightService, IndexService $indexService,
            TranslationClient $translationClient, EventPublisher $eventPublisher, float $minHighlightSimilarity) {
            $this->labelMapper = new LabelMapper($databaseClient, $highlightService, $minHighlightSimilarity);
            $this->highlightService = $highlightService;
            $this->indexService = $indexService;
            $this->translationClient = $translationClient;
            $this->eventPublisher = $eventPublisher;
            $this->transactionManager = $databaseClient;
        }

        public function setPlaceService(PlaceService $placeService) : void {
            $this->placeService = $placeService;
        }
        
        public function createLabel(string $labelName, ?LabelMetadata $metadata) : Label {
            $labelIdentifier = $this->createLabelIdentifier($labelName, $metadata);
            
            $this->eventPublisher->publish(Event::LabelCreated($labelIdentifier->getId()));
            return new Label($labelIdentifier->getId(), $labelIdentifier->getName(), $labelIdentifier->getMetadata(),
                $labelIdentifier->getMainHighlight(), array());
        }

        public function getOrCreateLabelIdentifier(string $labelName, ?LabelMetadata $metadata) : LabelIdentifier {
            $labelIdentifier = $this->labelMapper->selectLabelIdentifier($labelName);
            if ($labelIdentifier !== null) {
                return $labelIdentifier;
            }

            return $this->createLabelIdentifier($labelName, $metadata);
        }

        public function reassignLabelToHighlight(string $highlightId, string $labelId, float $similarity) : void {
            $this->transactionManager->executeAtomically(function() use(&$highlightId, &$labelId, &$similarity) {
                $this->labelMapper->deleteLabelForHighlight($highlightId, $labelId);
                $this->labelMapper->insertHighlightLabel($highlightId, $labelId, $similarity);
            });
        }

        public function getAllLabels(array $includedEntities) : array {
            return $this->labelMapper->selectLabels(null, $includedEntities);
        }

        public function getLabelIdentifiersForHighlights(array $highlightIds) : array {
            return $this->labelMapper->selectLabelIdentifiersForHighlights($highlightIds);
        }

        public function getHighlightIdsForLabel(string $labelId) : array {
            return $this->labelMapper->selectHighlightIdsForLabelId($labelId);
        }
        
        public function getLabel(string $labelId) : ?Label {
            $labels = $this->labelMapper->selectLabels($labelId, LabelIncludedEntity::values());
            return $labels[0] ?? null;
        }

        public function updateLabelName(string $labelId, string $name) : bool {
            return $this->labelMapper->updateLabelName($labelId, $name);
        }

        public function updateLabelKeywords(string $labelId, array $keywords) : bool {
            $wasUpdated = $this->labelMapper->updateLabelKeywords($labelId, $keywords);
            if ($wasUpdated) {
                $this->eventPublisher->publish(Event::LabelUpdated($labelId));
            }
            return $wasUpdated;
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

            $highlightPhotoIds = array_map(fn($highlight) => $highlight->getPhoto()->getId(), $this->highlightService->getHighlights($this->getHighlightIdsForLabel($labelId)));
            $places = $this->placeService->getRegularPlaces(null, $labelId, null, null, null, null, null, null,
                null, time(), null, null, array(), PlaceSortingStrategy::ScoreDescending);
            
            $keywords = $label->getMetadata()?->getKeywords();
            // TODO: Do not harcode the language here. The value depends on language that the label description/name is written in.
            $translatedKeywords = array_map(fn($keyword) => $this->translationClient->translate($keyword, "cs", self::KEYWORD_LANGUAGE), $keywords === null || empty($keywords) ? array($label->getName()) : $keywords);
            $selectedPhotoIds = $this->indexService->getSelectedPhotoIdsForLabel(array_map(fn($place) => $place->getId(), $places), $translatedKeywords, $count,
                $label->getMainHighlight()?->getPhoto()?->getId(), array_values(array_intersect($highlightPhotoIds, array_filter(array_map(fn($place) => $place->getMainHighlight()?->getPhoto()?->getId(), $places)))));

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
        
        public function removeLabelForAllHighlights(string $labelId) : bool {
            return $this->labelMapper->deleteLabelForAllHighlights($labelId) > 0;
        }

        public function removeLabel(string $labelId) : bool {
            return $this->labelMapper->deleteLabel($labelId) > 0;
        }

        private function createLabelIdentifier(string $labelName, ?LabelMetadata $metadata) : LabelIdentifier {
            $labelIdentifier = new LabelIdentifier(null, $labelName, $metadata, null);
            $this->labelMapper->insertLabelIdentifier($labelIdentifier);

            return $labelIdentifier;
        }
    }
?>
