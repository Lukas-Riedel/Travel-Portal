<?php
    namespace Service\Service\Label;

    class LabelService {

        private readonly LabelMapper $labelMapper;

        public function __construct(\DatabaseProvider $databaseProvider) {
            $this->labelMapper = new LabelMapper($databaseProvider);
        }
        
        public function createLabel(string $placeId, string $labelName) : Label {
            $label = new Label($this->getOrCreateLabelId($labelName), $labelName);
            $this->labelMapper->insertLabel($placeId, $label->getId());
            return $label;
        }

        public function getLabels() : array {
            return $this->labelMapper->selectLabels();
        }

        public function getLabelsForPlace(string $placeId) : array {
            return $this->labelMapper->selectLabelsForPlace($placeId);
        }

        public function getPlaceIdsForLabelId(string $labelId) : array {
            return $this->labelMapper->selectPlaceIdsForLabelId($labelId);
        }

        public function getOrCreateLabelId(string $labelName) : string {
            $labelId = $this->labelMapper->selectLabelId($labelName);
            if ($labelId !== NULL) {
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
            return $this->labelMapper->deleteLabelForPlace($placeId, $labelId) > 0;
        }

        public function removeLabelForAllPlaces(string $labelId) : bool {
            return $this->labelMapper->deleteLabelForAllPlaces($labelId) > 0;
        }
    }
?>