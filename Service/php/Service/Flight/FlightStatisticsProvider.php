<?php
    namespace Service\Service\Flight;
    
    use Service\Service\Statistics\KeyValuePair;
    use Service\Service\Statistics\Statistics;
    use Service\Service\Statistics\StatisticsKind;
    use Service\Service\Statistics\StatisticsProvider;
    use Service\Service\Statistics\StatisticsType;
    use Service\Service\Statistics\StatisticsUnit;

    class FlightStatisticsProvider implements StatisticsProvider {

        private const DMY_DATE_FORMAT = "j.n.Y";
        
        private const TOTAL_FLIGHTS_COUNT_STATISTICS_NAME = "TOTAL_FLIGHTS_COUNT";
        private const TOTAL_AIRBORNE_DISTANCE_STATISTICS_NAME = "TOTAL_AIRBORNE_DISTANCE";
        private const TOTAL_AIRBORNE_TIME_STATISTICS_NAME = "TOTAL_AIRBORNE_TIME";
        private const AVERAGE_FLIGHT_DURATION_STATISTICS_NAME = "AVERAGE_FLIGHT_DURATION";
        private const TOTAL_VISITED_AIRPORTS_COUNT_STATISTICS_NAME = "TOTAL_VISITED_AIRPORTS_COUNT";
        private const MOST_USED_AIRCRAFTS_STATISTICS_NAME = "MOST_USED_AIRCRAFTS";
        private const MOST_USED_AIRLINES_STATISTICS_NAME = "MOST_USED_AIRLINES";
        private const SHORTEST_FLIGHTS_STATISTICS_NAME = "SHORTEST_FLIGHTS";
        private const LONGEST_FLIGHTS_STATISTICS_NAME = "LONGEST_FLIGHTS";
        private const MOST_USED_AIRPORTS_STATISTICS_NAME = "MOST_USED_AIRPORTS";
        private const MOST_USED_FLIGHTS_STATISTICS_NAME = "MOST_USED_FLIGHTS";
        private const MOST_USED_AIRCRAFT_REGISTRATIONS_STATISTICS_NAME = "MOST_USED_AIRCRAFT_REGISTRATIONS";
        private const MOST_DELAYED_FLIGHTS_STATISTICS_NAME = "MOST_DELAYED_FLIGHTS";

        private const FLIGHT_CODE_STATISTICS_FORMAT = "%s - %s (%s)";
        private const FLIGHT_DATE_STATISTICS_FORMAT = "%s - %s @ %s";
        private const AIRCRAFT_REGISTRATION_STATISTICS_FORMAT = "%s @ %s";
        private const AIRPORT_STATISTICS_FORMAT = "%s (%s)";

        private readonly FlightService $flightService;

        public function __construct(FlightService $flightService) {
            $this->flightService = $flightService;
        }
        
        public function fetchStatistics(StatisticsType $statisticsType, StatisticsKind $statisticsKind,
            int $start, int $end, ?string $categoryId, ?string $entityId) : array {
            $statistics = array();

            $flights = $this->flightService->getLoggedFlightsForInterval($start, $end, FlightSortingStrategy::Default);

            if ($statisticsKind === StatisticsKind::Fact) {                
                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year) {
                    if (count($flights) > 0) {
                        $statistics[] = new Statistics(self::TOTAL_FLIGHTS_COUNT_STATISTICS_NAME, count($flights), StatisticsUnit::Flights);
                    }
                    
                    $visitedAirports = array_unique(array_merge(array_map(fn($flight) => $flight->getFrom(), $flights), array_map(fn($flight) => $flight->getTo(), $flights)), SORT_REGULAR);
                    if (count($visitedAirports) > 0) {
                        $statistics[] = new Statistics(self::TOTAL_VISITED_AIRPORTS_COUNT_STATISTICS_NAME, count($visitedAirports), StatisticsUnit::Airports);
                    }
                }

                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year || $statisticsType === StatisticsType::Trip) {                    
                    $totalAirborneDistance = intval(array_sum(array_map(fn($flight) => $flight->getDistance(), $flights)));
                    if ($totalAirborneDistance > 0) {
                        $statistics[] = new Statistics(self::TOTAL_AIRBORNE_DISTANCE_STATISTICS_NAME, $totalAirborneDistance, StatisticsUnit::Kilometers);
                    }
                    

                    $totalAirborneTime = array_sum(array_map(fn($flight) => $flight->getDuration(), $flights));
                    if ($totalAirborneTime > 0) {
                        $statistics[] = new Statistics(self::TOTAL_AIRBORNE_TIME_STATISTICS_NAME, $totalAirborneTime, StatisticsUnit::Duration);
                    }
                    
                    $averageFlightDuration = $totalAirborneTime / max(count($flights), 1);
                    if ($averageFlightDuration > 0) {
                        $statistics[] = new Statistics(self::AVERAGE_FLIGHT_DURATION_STATISTICS_NAME, $averageFlightDuration, StatisticsUnit::Duration);
                    }
                }
            }
            
            if ($statisticsKind === StatisticsKind::Standings) {                
                if ($statisticsType === StatisticsType::Overall) {
                    $mostUsedFlights = $this->getStandingsStatistics($flights, fn($flight) => sprintf(self::FLIGHT_CODE_STATISTICS_FORMAT, 
                        $flight->getFrom()->getName(), $flight->getTo()->getName(), $flight->getFlight()));
                    if (count($mostUsedFlights) > 0) {
                        $statistics[] = new Statistics(self::MOST_USED_FLIGHTS_STATISTICS_NAME, $mostUsedFlights, StatisticsUnit::Flights);
                    }
                    
                    $mostUsedAircraftRegistrations = $this->getStandingsStatistics($flights, fn($flight) => sprintf(self::AIRCRAFT_REGISTRATION_STATISTICS_FORMAT,
                        $flight->getRegistration(), $this->flightService->getAirlineForFlight($flight->getFlight())->getName()));
                    if (count($mostUsedAircraftRegistrations) > 0) {
                        $statistics[] = new Statistics(self::MOST_USED_AIRCRAFT_REGISTRATIONS_STATISTICS_NAME, $mostUsedAircraftRegistrations, StatisticsUnit::Flights);
                    }
                }

                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year) {
                    $mostUsedAircrafts = $this->getStandingsStatistics($flights, fn($flight) => $flight->getAircraft());
                    if (count($mostUsedAircrafts) > 0) {
                        $statistics[] = new Statistics(self::MOST_USED_AIRCRAFTS_STATISTICS_NAME, $mostUsedAircrafts, StatisticsUnit::Flights);
                    }
                    
                    $mostUsedAirlines = $this->getStandingsStatistics($flights, fn($flight) => $this->flightService->getAirlineForFlight($flight->getFlight())->getName());
                    if (count($mostUsedAirlines) > 0) {
                        usort($mostUsedAirlines, fn($a, $b) => $b->getValue() - $a->getValue());
                        $statistics[] = new Statistics(self::MOST_USED_AIRLINES_STATISTICS_NAME, $mostUsedAirlines, StatisticsUnit::Flights);
                    }
                    
                    $mostUsedAirports = $this->getStandingsStatistics($flights,
                        fn($flight) => sprintf(self::AIRPORT_STATISTICS_FORMAT, $flight->getFrom()->getName(), $flight->getFrom()->getCode()),
                        fn($flight) => sprintf(self::AIRPORT_STATISTICS_FORMAT, $flight->getTo()->getName(), $flight->getTo()->getCode()));
                    if (count($mostUsedAirports) > 0) {
                        $statistics[] = new Statistics(self::MOST_USED_AIRPORTS_STATISTICS_NAME, $mostUsedAirports, StatisticsUnit::Flights);
                    }

                    $longestFlights = array_map(fn($flight) => new KeyValuePair(sprintf(self::FLIGHT_DATE_STATISTICS_FORMAT,
                        $flight->getFrom()->getName(), $flight->getTo()->getName(), date(self::DMY_DATE_FORMAT, $flight->getStart())), $flight->getDuration()),
                        $this->flightService->getLoggedFlightsForInterval($start, $end, FlightSortingStrategy::DurationDescending));
                    if (count($longestFlights) > 0) {
                        $statistics[] = new Statistics(self::LONGEST_FLIGHTS_STATISTICS_NAME, $longestFlights, StatisticsUnit::Duration);
                    }

                    $shortestFlights = array_map(fn($flight) => new KeyValuePair(sprintf(self::FLIGHT_DATE_STATISTICS_FORMAT,
                        $flight->getFrom()->getName(), $flight->getTo()->getName(), date(self::DMY_DATE_FORMAT, $flight->getStart())), $flight->getDuration()),
                        $this->flightService->getLoggedFlightsForInterval($start, $end, FlightSortingStrategy::DurationAscending));
                    if (count($shortestFlights) > 0) {
                        $statistics[] = new Statistics(self::SHORTEST_FLIGHTS_STATISTICS_NAME, $shortestFlights, StatisticsUnit::Duration);
                    }

                    $mostDelayedFlights = array_map(fn($flight) => new KeyValuePair(sprintf(self::FLIGHT_DATE_STATISTICS_FORMAT,
                        $flight->getFrom()->getName(), $flight->getTo()->getName(), date(self::DMY_DATE_FORMAT, $flight->getStart())), $flight->getDelay()),
                        $this->flightService->getLoggedFlightsForInterval($start, $end, FlightSortingStrategy::DelayDescending));
                    if (count($mostDelayedFlights) > 0) {
                        $statistics[] = new Statistics(self::MOST_DELAYED_FLIGHTS_STATISTICS_NAME, $mostDelayedFlights, StatisticsUnit::Duration);
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