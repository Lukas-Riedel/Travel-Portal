<?php
    namespace Core\Service\CarRental;

    use Core\Common\CommonConstants;
    use Core\Service\Statistics\KeyValuePair;
    use Core\Service\Statistics\Statistics;
    use Core\Service\Statistics\StatisticsKind;
    use Core\Service\Statistics\StatisticsName;
    use Core\Service\Statistics\StatisticsProvider;
    use Core\Service\Statistics\StatisticsType;
    use Core\Service\Statistics\StatisticsUnit;
    use Core\Service\Trip\TripIncludedEntity;
    use Core\Service\Trip\TripService;
    use Core\Service\Trip\TripSortingStrategy;

    class CarRentalStatisticsProvider implements StatisticsProvider {

        private const CAR_RENTAL_STATISTICS_FORMAT = "%s @ %s - %s";

        private readonly TripService $tripService;

        public function __construct(TripService $tripService) {
            $this->tripService = $tripService;
        }

        public function fetchStatistics(StatisticsType $statisticsType, StatisticsKind $statisticsKind,
            int $start, int $end, ?string $categoryId, ?string $entityId) : array {
            $statistics = array();

            if ($statisticsKind === StatisticsKind::Fact) {
                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year) {
                    $carRentals = array_merge(...array_map(fn($trip) => $trip->getCarRentals(),
                        $this->tripService->getRegularTrips(null, $start, $end, array(TripIncludedEntity::CarRentals->value), TripSortingStrategy::OldestAscending)));
                    $totalCarRentalDays = intval(array_sum(array_map(fn($carRental) => ceil(($carRental->getEnd() - $carRental->getStart()) / CommonConstants::ONE_DAY_SECONDS),
                        array_filter($carRentals, fn($carRental) => $carRental->getEnd() > $carRental->getStart()))));
                    if ($totalCarRentalDays > 0) {
                        $statistics[] = new Statistics(StatisticsName::TotalCarRentalDays, $totalCarRentalDays, StatisticsUnit::Days);
                    }
                }
            }

            if ($statisticsKind === StatisticsKind::Standings) {
                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year) {
                    $carRentals = array_merge(...array_map(fn($trip) => $trip->getCarRentals(),
                        $this->tripService->getRegularTrips(null, $start, $end, array(TripIncludedEntity::CarRentals->value), TripSortingStrategy::OldestAscending)));
                    $longestCarRentals = array_map(fn($carRental) => new KeyValuePair(
                        sprintf(self::CAR_RENTAL_STATISTICS_FORMAT, $carRental->getModel(), date(CommonConstants::DMY_DATE_FORMAT, $carRental->getStart()), date(CommonConstants::DMY_DATE_FORMAT, $carRental->getEnd() - 1)),
                        intval(ceil(($carRental->getEnd() - $carRental->getStart()) / CommonConstants::ONE_DAY_SECONDS))),
                        array_filter($carRentals, fn($carRental) => $carRental->getEnd() > $carRental->getStart()));
                    usort($longestCarRentals, fn($a, $b) => $b->getValue() <=> $a->getValue());
                    if (count($longestCarRentals) > 0) {
                        $statistics[] = new Statistics(StatisticsName::LongestCarRentals, $longestCarRentals, StatisticsUnit::Days);
                    }
                }
            }

            return $statistics;
        }
    }
?>
