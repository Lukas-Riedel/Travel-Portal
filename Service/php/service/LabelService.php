<?php
    require_once(dirname(__FILE__) . "/LabelMapper.php");
    require_once(dirname(__FILE__) . "/../model/Label.php");

    class LabelService {

        private readonly LabelMapper $labelMapper;

        private readonly ConfigurationService $configurationService;

        public function __construct(DatabaseProvider $databaseProvider, ConfigurationService $configurationService) {
            $this->labelMapper = new LabelMapper($databaseProvider);
            $this->configurationService = $configurationService;
        }
        
        public function createLabel(string $placeId, string $name) : Label {
            if (!in_array($name, $this->configurationService->getConfigurationForTypeAndKey("labels", "public"))
                && !in_array($name, $this->configurationService->getConfigurationForTypeAndKey("labels", "private"))) {
                throw new InvalidArgumentException("The label name '" . $name . "' is not allowed.");
            }

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