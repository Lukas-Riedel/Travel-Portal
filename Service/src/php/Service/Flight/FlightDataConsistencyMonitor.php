<?php
    namespace Service\Service\Flight;

    use Service\Service\Monitoring\DataConsistencyIssue;
    use Service\Service\Monitoring\DataConsistencyMonitor;

    class FlightDataConsistencyMonitor implements DataConsistencyMonitor {

        private const AIRLINE_CODE_WITHOUT_AIRLINE_ISSUE_NAME = "AIRLINE_CODE_WITHOUT_AIRLINE";
        private const NON_LOGGED_FLIGHT_ISSUE_NAME = "NON_LOGGED_FLIGHT";
        private const LOGGED_FLIGHTS_WITHOUT_FLIGHT_EVENT_ISSUE_NAME = "LOGGED_FLIGHTS_WITHOUT_FLIGHT_EVENT";

        private readonly FlightService $flightService;

        public function __construct(FlightService $flightService) {
            $this->flightService = $flightService;
        }

        public function fetchDataConsistencyIssues() : array {
            $dataConsistencyIssues = array();

            $unassignedAirlineCodes = $this->flightService->getUnassignedAirlineCodes();
            foreach ($unassignedAirlineCodes as &$unassignedAirlineCode) {
                $dataConsistencyIssues[] = new DataConsistencyIssue(self::AIRLINE_CODE_WITHOUT_AIRLINE_ISSUE_NAME, $unassignedAirlineCode, time());
            }

            $pastNonLoggedFlights = array_filter($this->flightService->getAllNonLoggedFlights(), fn($flight) => $flight->getStart() < time());
            foreach ($pastNonLoggedFlights as &$pastNonLoggedFlight) {
                $dataConsistencyIssues[] = new DataConsistencyIssue(self::NON_LOGGED_FLIGHT_ISSUE_NAME, $pastNonLoggedFlight, time());
            }

            $loggedFlightsWithoutEvent = $this->flightService->getLoggedFlightsWithoutEvent();
            foreach ($loggedFlightsWithoutEvent as &$loggedFlightWithoutEvent) {
                $dataConsistencyIssues[] = new DataConsistencyIssue(self::LOGGED_FLIGHTS_WITHOUT_FLIGHT_EVENT_ISSUE_NAME, $loggedFlightWithoutEvent, time());
            }

            return $dataConsistencyIssues;
        }
    }
?>