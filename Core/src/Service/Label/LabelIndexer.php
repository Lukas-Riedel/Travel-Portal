<?php
    namespace Core\Service\Label;

    use Core\Service\Index\EntityIndexer;
    use Core\Service\Index\IndexableEntityType;

    class LabelIndexer implements EntityIndexer {

        private readonly LabelService $labelService;

        public function __construct(LabelService $labelService) {
            $this->labelService = $labelService;
        }

        public function index(IndexableEntityType $entityType) : array {
            $result = array();

            if ($entityType === IndexableEntityType::Label) {
                $labels = $this->labelService->getAllLabels();

                foreach ($labels as &$label) {
                    $result[$label->getId()] = array($label->getName());
                }
            }

            return $result;
        }
    }
?>