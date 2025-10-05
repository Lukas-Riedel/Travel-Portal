<?php
    namespace Core\Service\Fitness;

    use Core\Common\CommonConstants;
    use Core\Service\Monitoring\DataConsistencyIssue;
    use Core\Service\Monitoring\DataConsistencyMonitor;

    class FitnessDataConsistencyMonitor implements DataConsistencyMonitor {

        private const CONFLICTING_FITNESS_RECORDS_ISSUE_NAME = "CONFLICTING_FITNESS_RECORDS";

        private readonly FitnessService $fitnessService;

        public function __construct(FitnessService $fitnessService) {
            $this->fitnessService = $fitnessService;
        }

        public function fetchDataConsistencyIssues() : array {
            $dataConsistencyIssues = array();

            $conflictingFitnessRecords = $this->fitnessService->getConflictingFitnessRecords();
            foreach ($conflictingFitnessRecords as &$conflictingFitnessRecord) {
                $existingFitnessRecord = $this->fitnessService->getFitnessRecordForInterval($conflictingFitnessRecord->getTimestamp(),
                    $conflictingFitnessRecord->getTimestamp() + CommonConstants::FITNESS_RECORD_DURATION_SECONDS);
                $dataConsistencyIssues[] = new DataConsistencyIssue(self::CONFLICTING_FITNESS_RECORDS_ISSUE_NAME,
                    new TimeBasedFitnessCollection($conflictingFitnessRecord->getTimestamp(), 
                        array($conflictingFitnessRecord->getFitness(), $existingFitnessRecord)), time());
            }

            return $dataConsistencyIssues;
        }
    }
?>