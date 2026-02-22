<?php
    namespace Core\Service\Expense;

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

    class ExpenseStatisticsProvider implements StatisticsProvider {

        private readonly ExpenseService $expenseService;

        private readonly TripService $tripService;

        public function __construct(ExpenseService $expenseService, TripService $tripService) {
            $this->expenseService = $expenseService;
            $this->tripService = $tripService;
        }

        public function fetchStatistics(StatisticsType $statisticsType, StatisticsKind $statisticsKind,
            int $start, int $end, ?string $categoryId, ?string $entityId) : array {
            $statistics = array();

            if ($statisticsKind === StatisticsKind::Fact) {
                $relevantTrips = $this->tripService->getRegularTrips(null, $start, $end, array(), TripSortingStrategy::OldestAscending);
                $totalCost = intval(array_sum(array_map(fn($trip) => $this->getTripCost($trip->getId()), $relevantTrips)));                
                if ($totalCost > 0) {                    
                    if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year) {
                        $statistics[] = new Statistics(StatisticsName::TotalExpenses, $totalCost, StatisticsUnit::MainCurrency);
                    }

                    if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year || $statisticsType === StatisticsType::Trip) {                        
                        $travelDaysCount = array_sum(array_map(fn($trip) => $trip->getDaysCount(), $relevantTrips));
                        $statistics[] = new Statistics(StatisticsName::AverageExpensesPerDay, intval($totalCost / $travelDaysCount), StatisticsUnit::MainCurrency);
                    }
                }
            }

            if ($statisticsKind === StatisticsKind::Standings) {
                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year) {
                    $mostExpensiveTrips = array_map(fn($trip) => new KeyValuePair($trip->getFullName(), intval($this->getTripCost($trip->getId()))), 
                        array_filter($this->tripService->getRegularTrips(null, $start, $end, array(), TripSortingStrategy::OldestAscending), fn($trip) => $this->getTripCost($trip->getId()) > 0));
                    usort($mostExpensiveTrips, fn($a, $b) => $b->getValue() <=> $a->getValue());
                    if (count($mostExpensiveTrips) > 0) {
                        $statistics[] = new Statistics(StatisticsName::MostExpensiveTrips, $mostExpensiveTrips, StatisticsUnit::MainCurrency);
                    }

                    $mostExpensiveTripsPerDay = array_map(fn($trip) => new KeyValuePair($trip->getFullName(), intval($this->getTripCost($trip->getId()) / $trip->getDaysCount())), 
                        array_filter($this->tripService->getRegularTrips(null, $start, $end, array(), TripSortingStrategy::OldestAscending), fn($trip) => $this->getTripCost($trip->getId()) > 0));
                    usort($mostExpensiveTripsPerDay, fn($a, $b) => $b->getValue() <=> $a->getValue());
                    if (count($mostExpensiveTripsPerDay) > 0) {
                        $statistics[] = new Statistics(StatisticsName::MostExpensiveTripsPerDay, $mostExpensiveTripsPerDay, StatisticsUnit::MainCurrency);
                        $statistics[] = new Statistics(StatisticsName::LeastExpensiveTripsPerDay, array_reverse($mostExpensiveTripsPerDay), StatisticsUnit::MainCurrency);
                    }
                }

                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year || $statisticsType === StatisticsType::Trip) {
                    $relevantTrips = $this->tripService->getRegularTrips(null, $start, $end,
                        array(TripIncludedEntity::Expenses->value, TripIncludedEntity::Stays->value), TripSortingStrategy::OldestAscending);

                    $mostExpensiveStaysPerNight = array_reduce($relevantTrips, function($carry, $trip) {
                        $stayNightsCounts = array_reduce($trip->getStays(), function($innerCarry, $stay) {
                            if (!isset($innerCarry[$stay->getName()])) {
                                $innerCarry[$stay->getName()] = 0;
                            }
                            $innerCarry[$stay->getName()] += $stay->getNightsCount();
                            return $innerCarry;
                        }, array());

                        return array_reduce(array_keys($stayNightsCounts),
                            function($innerCarry, $stayName) use($trip, &$stayNightsCounts) {
                                $stayCost = array_sum(array_map(fn($expense) => $expense->getMainCurrencyValue(), array_filter($trip->getExpenses(),
                                    fn($expense) => $expense->getType() === ExpenseType::Hotel && $expense->getDescription() === $stayName))); 
                                if ($stayCost > 0) {
                                    $innerCarry[$stayName] = new KeyValuePair($stayName, intval($stayCost / $stayNightsCounts[$stayName]));
                                }
                                return $innerCarry;
                            }, $carry);
                    }, array());
                    usort($mostExpensiveStaysPerNight, fn($a, $b) => $b->getValue() <=> $a->getValue());

                    if (count($mostExpensiveStaysPerNight) > 0) {
                        $statistics[] = new Statistics(StatisticsName::MostExpensiveHotelStaysPerNight, $mostExpensiveStaysPerNight, StatisticsUnit::MainCurrency);
                        $statistics[] = new Statistics(StatisticsName::LeastExpensiveHotelStaysPerNight, array_reverse($mostExpensiveStaysPerNight), StatisticsUnit::MainCurrency);
                    }
                }
            }

            return $statistics;
        }

        private function getTripCost(string $tripId) : float {
            return array_sum(array_map(fn($expense) => $expense->getMainCurrencyValue(), $this->expenseService->getExpensesForTrip($tripId)));
        }
    }
?>