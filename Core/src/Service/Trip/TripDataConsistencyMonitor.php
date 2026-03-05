<?php
    namespace Core\Service\Trip;

    use Core\Common\CommonConstants;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Service\Monitoring\DataConsistencyIssue;
    use Core\Service\Monitoring\DataConsistencyMonitor;

    class TripDataConsistencyMonitor implements DataConsistencyMonitor {

        private const HOURS_MINUTES_TIME_FORMAT = "H:i";
        private const MIDNIGHT_IN_HOURS_MINUTES_TIME_FORMAT = "00:00";

        private const TRIP_WITHOUT_TIME_THRESHOLD_SECONDS = 2 * CommonConstants::ONE_MONTH_SECONDS;

        private const TRIP_WITHOUT_TIME_ISSUE_NAME = "TRIP_WITHOUT_TIME";

        private readonly TripService $tripService;
        private readonly ConfigurationService $configurationService;

        public function __construct(TripService $tripService, ConfigurationService $configurationService) {
            $this->tripService = $tripService;
            $this->configurationService = $configurationService;
        }

        public function fetchDataConsistencyIssues() : array {
            $dataConsistencyIssues = array();

            $relevantTrips = $this->tripService->getRegularTrips(null, null, time() + self::TRIP_WITHOUT_TIME_THRESHOLD_SECONDS, array(), TripSortingStrategy::OldestAscending);
            
            $tripsWithoutTime = array_filter($relevantTrips, fn($trip) => $this->isMidnight($trip->getStart()) && $this->isMidnight($trip->getEnd()));
            foreach ($tripsWithoutTime as &$tripWithoutTime) {
                $dataConsistencyIssues[] = new DataConsistencyIssue(self::TRIP_WITHOUT_TIME_ISSUE_NAME, $tripWithoutTime->getId(),
                    $tripWithoutTime, time());
            }

            return $dataConsistencyIssues;
        }

        private function isMidnight(int $timestamp) : bool {
            $homeTimezone = $this->configurationService->getConfigurationEntry("homeLocation")["timezone"];
            return (new \DateTimeImmutable("@" . $timestamp))
                ->setTimezone(new \DateTimeZone($homeTimezone))
                ->format(self::HOURS_MINUTES_TIME_FORMAT) === self::MIDNIGHT_IN_HOURS_MINUTES_TIME_FORMAT;
        }
    }
?>