<?php
    namespace Core\Service\Label;

    use Core\Client\Database\DatabaseClient;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Service\Highlight\HighlightService;

    class LabelService {

        private readonly LabelMapper $labelMapper;
        private readonly EventPublisher $eventPublisher;

        public function __construct(DatabaseClient $databaseClient, HighlightService $highlightService, EventPublisher $eventPublisher) {
            $this->labelMapper = new LabelMapper($databaseClient, $highlightService);
            $this->eventPublisher = $eventPublisher;
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

        public function assignLabelToPlace(string $placeId, string $labelId) : bool {
            return $this->labelMapper->insertPlaceLabel($placeId, $labelId) > 0;
        }

        public function getAllLabels(array $includedEntities) : array {
            return $this->labelMapper->selectLabels(null, $includedEntities);
        }

        public function getLabelIdentifiersForPlace(string $placeId) : array {
            return $this->labelMapper->selectLabelIdentifiersForPlace($placeId);
        }

        public function getPlaceIdsForLabel(string $labelId) : array {
            return $this->labelMapper->selectPlaceIdsForLabelId($labelId);
        }
        
        public function getLabel(string $labelId) : ?Label {
            $labels = $this->labelMapper->selectLabels($labelId, LabelIncludedEntity::values());
            return $labels[0] ?? null;
        }

        public function updateLabelName(string $labelId, string $name) : bool {
            $wasUpdated = $this->labelMapper->updateLabelName($labelId, $name);
            if ($wasUpdated) {
                $this->eventPublisher->publish(Event::LabelInvalidated($labelId));
            }
            return $wasUpdated;
        }

        public function updateLabelUnicode(string $labelId, string $unicode) : bool {
            return $this->labelMapper->updateLabelUnicode($labelId, $unicode);
        }

        public function updateLabelMainHighlight(string $labelId, ?string $highlightIdentifier) : bool {
            return $this->labelMapper->updateLabelMainHighlight($labelId, $highlightIdentifier);
        }

        public function removeLabelForPlace(string $placeId, string $labelId) : bool {
            return $this->labelMapper->deleteLabelForPlace($placeId, $labelId) > 0;
        }
        
        public function removeLabelForAllPlaces(string $labelId) : bool {
            return $this->labelMapper->deleteLabelForAllPlaces($labelId) > 0;
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
