<?php
    namespace Core\Service\Label;

    use Core\Service\Index\EntityIndexer;
    use Core\Service\Index\IndexableEntityType;
    use Core\Service\Index\IndexType;

    class LabelIndexer implements EntityIndexer {

        private readonly LabelService $labelService;

        public function __construct(LabelService $labelService) {
            $this->labelService = $labelService;
        }

        public function index(IndexType $indexType, IndexableEntityType $entityType, ?string $entityId) : array {
            $result = array();

            if ($indexType === IndexType::Composite && $entityType === IndexableEntityType::Label) {
                $labels = $entityId !== null
                    ? array($this->labelService->getLabel($entityId))
                    : $this->labelService->getAllLabels();

                foreach ($labels as &$label) {
                    $result[$label->getId()] = array($label->getName());
                }
            }

            return $result;
        }
    }
?>