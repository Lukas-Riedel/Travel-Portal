<?php
    namespace Core\Service\Fitness;

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
                    FitnessService::FITNESS_RECORD_DURATION);
                $dataConsistencyIssues[] = new DataConsistencyIssue(self::CONFLICTING_FITNESS_RECORDS_ISSUE_NAME,
                    new TimeBasedFitnessCollection($conflictingFitnessRecord->getTimestamp(), 
                        array($conflictingFitnessRecord, $existingFitnessRecord)), time());
            }

            return $dataConsistencyIssues;
        }
    }
?>