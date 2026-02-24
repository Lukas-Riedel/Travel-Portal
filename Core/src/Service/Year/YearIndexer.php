<?php
    namespace Core\Service\Year;

    use Core\Service\Index\EntityIndexer;
    use Core\Service\Index\IndexableEntityType;

    class YearIndexer implements EntityIndexer {

        private readonly YearService $yearService;

        public function __construct(YearService $yearService) {
            $this->yearService = $yearService;
        }

        public function index(IndexableEntityType $entityType) : array {
            $result = array();

            if ($entityType === IndexableEntityType::Year) {
                $years = $this->yearService->getYears(array());

                foreach ($years as &$year) {
                    $result[$year->getId()] = array($year->getId());
                }
            }

            return $result;
        }
    }
?>