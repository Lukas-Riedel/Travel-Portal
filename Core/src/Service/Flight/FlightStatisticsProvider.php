<?php
    namespace Core\Service\Flight;

    use Core\Common\CommonConstants;
    use Core\Service\Statistics\KeyValuePair;
    use Core\Service\Statistics\Statistics;
    use Core\Service\Statistics\StatisticsKind;
    use Core\Service\Statistics\StatisticsName;
    use Core\Service\Statistics\StatisticsProvider;
    use Core\Service\Statistics\StatisticsType;
    use Core\Service\Statistics\StatisticsUnit;

    class FlightStatisticsProvider implements StatisticsProvider {

        private const FLIGHT_CODE_STATISTICS_FORMAT = "%s - %s (%s)";
        private const FLIGHT_DATE_STATISTICS_FORMAT = "%s - %s @ %s";
        private const AIRCRAFT_REGISTRATION_STATISTICS_FORMAT = "%s @ %s";

        private readonly FlightService $flightService;

        public function __construct(FlightService $flightService) {
            $this->flightService = $flightService;
        }
        
        public function fetchStatistics(StatisticsType $statisticsType, StatisticsKind $statisticsKind,
            int $start, int $end, ?string $categoryId, ?string $entityId) : array {
            $statistics = array();

            $flights = $this->flightService->getLoggedFlightsForInterval($start, $end, FlightSortingStrategy::ScheduledDepartureTimeAscending);

            if ($statisticsKind === StatisticsKind::Fact) {                
                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year) {
                    if (count($flights) > 0) {
                        $statistics[] = new Statistics(StatisticsName::TotalFlightsCount, count($flights), StatisticsUnit::Flights);
                    }
                    
                    $visitedAirports = array_unique(array_merge(array_map(fn($flight) => $flight->getFrom(), $flights), array_map(fn($flight) => $flight->getTo(), $flights)), SORT_REGULAR);
                    if (count($visitedAirports) > 0) {
                        $statistics[] = new Statistics(StatisticsName::TotalVisitedAirportsCount, count($visitedAirports), StatisticsUnit::Airports);
                    }
                }

                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year || $statisticsType === StatisticsType::Trip) {                    
                    $totalAirborneDistance = intval(array_sum(array_map(fn($flight) => $flight->getDistance(), $flights)));
                    if ($totalAirborneDistance > 0) {
                        $statistics[] = new Statistics(StatisticsName::TotalAirborneDistance, $totalAirborneDistance, StatisticsUnit::Kilometers);
                    }
                    

                    $totalAirborneTime = array_sum(array_map(fn($flight) => $flight->getDuration(), $flights));
                    if ($totalAirborneTime > 0) {
                        $statistics[] = new Statistics(StatisticsName::TotalAirborneTime, $totalAirborneTime, StatisticsUnit::Duration);
                    }
                    
                    $averageFlightDuration = $totalAirborneTime / max(count($flights), 1);
                    if ($averageFlightDuration > 0) {
                        $statistics[] = new Statistics(StatisticsName::AverageFlightDuration, $averageFlightDuration, StatisticsUnit::Duration);
                    }
                }
            }
            
            if ($statisticsKind === StatisticsKind::Standings) {                
                if ($statisticsType === StatisticsType::Overall) {
                    $mostUsedFlights = $this->getStandingsStatistics($flights, fn($flight) => sprintf(self::FLIGHT_CODE_STATISTICS_FORMAT, 
                        $flight->getFrom()->getShortName(), $flight->getTo()->getShortName(), $flight->getFlight()));
                    if (count($mostUsedFlights) > 0) {
                        $statistics[] = new Statistics(StatisticsName::MostUsedFlights, $mostUsedFlights, StatisticsUnit::Flights);
                    }
                    
                    $mostUsedAircraftRegistrations = $this->getStandingsStatistics($flights, fn($flight) => sprintf(self::AIRCRAFT_REGISTRATION_STATISTICS_FORMAT,
                        $flight->getRegistration(), $this->flightService->getAirlineForFlight($flight->getFlight())->getName()));
                    if (count($mostUsedAircraftRegistrations) > 0) {
                        $statistics[] = new Statistics(StatisticsName::MostUsedAircraftRegistrations, $mostUsedAircraftRegistrations, StatisticsUnit::Flights);
                    }
                }

                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year) {
                    $mostUsedAircrafts = $this->getStandingsStatistics($flights, fn($flight) => $flight->getAircraft());
                    if (count($mostUsedAircrafts) > 0) {
                        $statistics[] = new Statistics(StatisticsName::MostUsedAircrafts, $mostUsedAircrafts, StatisticsUnit::Flights);
                    }
                    
                    $mostUsedAirlines = $this->getStandingsStatistics($flights, fn($flight) => $this->flightService->getAirlineForFlight($flight->getFlight())->getName());
                    if (count($mostUsedAirlines) > 0) {
                        usort($mostUsedAirlines, fn($a, $b) => $b->getValue() - $a->getValue());
                        $statistics[] = new Statistics(StatisticsName::MostUsedAirlines, $mostUsedAirlines, StatisticsUnit::Flights);
                    }
                    
                    $mostUsedAirports = $this->getStandingsStatistics($flights,
                        fn($flight) => $flight->getFrom()->getLongName() !== null ? $flight->getFrom()->getLongName() : $flight->getFrom()->getShortName(), 
                        fn($flight) => $flight->getTo()->getLongName() !== null ? $flight->getTo()->getLongName() : $flight->getTo()->getShortName());
                    if (count($mostUsedAirports) > 0) {
                        $statistics[] = new Statistics(StatisticsName::MostUsedAirports, $mostUsedAirports, StatisticsUnit::Flights);
                    }

                    $longestFlights = array_map(fn($flight) => new KeyValuePair(sprintf(self::FLIGHT_DATE_STATISTICS_FORMAT,
                        $flight->getFrom()->getShortName(), $flight->getTo()->getShortName(), date(CommonConstants::DMY_DATE_FORMAT, $flight->getStart())), $flight->getDuration()),
                        $this->flightService->getLoggedFlightsForInterval($start, $end, FlightSortingStrategy::DurationDescending));
                    if (count($longestFlights) > 0) {
                        $statistics[] = new Statistics(StatisticsName::LongestFlights, $longestFlights, StatisticsUnit::Duration);
                    }

                    $shortestFlights = array_map(fn($flight) => new KeyValuePair(sprintf(self::FLIGHT_DATE_STATISTICS_FORMAT,
                        $flight->getFrom()->getShortName(), $flight->getTo()->getShortName(), date(CommonConstants::DMY_DATE_FORMAT, $flight->getStart())), $flight->getDuration()),
                        $this->flightService->getLoggedFlightsForInterval($start, $end, FlightSortingStrategy::DurationAscending));
                    if (count($shortestFlights) > 0) {
                        $statistics[] = new Statistics(StatisticsName::ShortestFlights, $shortestFlights, StatisticsUnit::Duration);
                    }

                    $mostDelayedFlights = array_map(fn($flight) => new KeyValuePair(sprintf(self::FLIGHT_DATE_STATISTICS_FORMAT,
                        $flight->getFrom()->getShortName(), $flight->getTo()->getShortName(), date(CommonConstants::DMY_DATE_FORMAT, $flight->getStart())), $flight->getDelay()),
                        $this->flightService->getLoggedFlightsForInterval($start, $end, FlightSortingStrategy::DelayDescending));
                    if (count($mostDelayedFlights) > 0) {
                        $statistics[] = new Statistics(StatisticsName::MostDelayedFlights, $mostDelayedFlights, StatisticsUnit::Duration);
                    }
                }
            }

            return $statistics;
        }

        private function getStandingsStatistics(array $flights, callable ...$keySelectors) : array {
            $standings = array_reduce($flights, function($carry, $flight) use(&$keySelectors) {
                foreach ($keySelectors as &$keySelector) {
                    $key = $keySelector($flight);
                    $carry[$key] = isset($carry[$key])
                        ? $carry[$key]->withValue($carry[$key]->getValue() + 1)
                        : new KeyValuePair($key, 1);
                }
                return $carry;
            }, array());
            usort($standings, fn($a, $b) => $b->getValue() <=> $a->getValue());
            return $standings;
        }
    }
?>