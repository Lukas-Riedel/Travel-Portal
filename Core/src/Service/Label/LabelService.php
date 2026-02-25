<?php
    namespace Core\Service\Label;

    use Core\Service\Configuration\ConfigurationService;
    use Core\Client\Database\DatabaseClient;
    
    // TODO: Missing LabelUpdated event (the listener should be in IndexServiceListener).
    class LabelService {

        private readonly LabelMapper $labelMapper;

        public function __construct(DatabaseClient $databaseClient, ConfigurationService $configurationService) {
            $this->labelMapper = new LabelMapper($databaseClient, $configurationService);
        }
        
        public function createLabel(string $placeId, string $labelName) : Label {
            $label = new Label($this->getOrCreateLabelId($labelName), $labelName);
            $this->labelMapper->insertLabel($placeId, $label->getId());
            return $label;
        }

        public function getAllLabels() : array {
            return $this->labelMapper->selectAllLabels();
        }

        public function getLabelsForPlace(string $placeId) : array {
            return $this->labelMapper->selectLabelsForPlace($placeId);
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
            return $this->labelMapper->selectLabel($labelId);
        }

        public function updateLabelName(string $labelId, string $name) : bool {
            return $this->labelMapper->updateLabelName($labelId, $name);
        }

        public function removeLabelForPlace(string $placeId, string $labelId) : bool {
            $wasRemoved = $this->labelMapper->deleteLabelForPlace($placeId, $labelId) > 0;
            if ($wasRemoved) {
                $this->labelMapper->deleteStaleLabelIdentifiers();
            }
            return $wasRemoved;
        }

        public function removeLabelForAllPlaces(string $labelId) : bool {
            $wasRemoved = $this->labelMapper->deleteLabelForAllPlaces($labelId) > 0;
            if ($wasRemoved) {
                $this->labelMapper->deleteStaleLabelIdentifiers();
            }
            return $wasRemoved;
        }
    }
?>