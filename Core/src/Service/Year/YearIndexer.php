<?php
    namespace Core\Service\Year;

    use Core\Service\Index\DocumentBuffer;
    use Core\Service\Index\EntityIndexer;
    use Core\Service\Index\IndexableEntityType;
    use Core\Service\Index\IndexType;

    class YearIndexer implements EntityIndexer {
        
        private const YEAR_FORMAT = "Y";

        private readonly YearService $yearService;

        public function __construct(YearService $yearService) {
            $this->yearService = $yearService;
        }

        public function index(DocumentBuffer $documentBuffer, IndexType $indexType, IndexableEntityType $entityType, ?string $entityId) : void {
            if ($indexType === IndexType::Composite && $entityType === IndexableEntityType::Year) {
                $years = $entityId !== null
                    ? array($this->yearService->getYear($entityId))
                    : $this->yearService->getYears(array());

                foreach ($years as &$year) {
                    $documentBuffer->add($year->getId(), array($year->getId()), $year->getId() > date(self::YEAR_FORMAT));
                }
            }
        }
    }
?>