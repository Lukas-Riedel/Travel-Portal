<?php
    require_once(dirname(__FILE__) . "/LabelMapper.php");
    require_once(dirname(__FILE__) . "/../model/Label.php");

    class LabelService {

        private readonly LabelMapper $labelMapper;

        public function __construct(DatabaseProvider $databaseProvider) {
            $this->labelMapper = new LabelMapper($databaseProvider);
        }
        
        public function createLabel(string $placeId, string $name) : Label {
            $label = new Label(NULL, $name);
            $this->labelMapper->insertLabel($label, $placeId);
            return $label;
        }

        public function getLabelsForPlace(string $placeId) : array {
            return $this->labelMapper->selectLabelsForPlace($placeId);
        }

        public function removeLabel(string $labelId) : bool {
            return $this->labelMapper->deleteLabel($labelId) > 0;
        }
    }
?>