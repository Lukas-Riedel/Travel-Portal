<?php
    namespace Core\Service\Flight;

    use Core\Service\Monitoring\DataConsistencyIssue;
    use Core\Service\Monitoring\DataConsistencyMonitor;

    class FlightDataConsistencyMonitor implements DataConsistencyMonitor {

        private const AIRLINE_CODE_WITHOUT_AIRLINE_ISSUE_NAME = "AIRLINE_CODE_WITHOUT_AIRLINE";
        private const NON_LOGGED_FLIGHT_ISSUE_NAME = "NON_LOGGED_FLIGHT";
        private const LOGGED_FLIGHT_WITHOUT_FLIGHT_EVENT_ISSUE_NAME = "LOGGED_FLIGHT_WITHOUT_FLIGHT_EVENT";
        private const AIRPORT_WITHOUT_LONG_NAME_ISSUE_NAME = "AIRPORT_WITHOUT_LONG_NAME";
        private const AIRLINE_WITHOUT_LOGO_ISSUE_NAME = "AIRLINE_WITHOUT_LOGO";

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
                $dataConsistencyIssues[] = new DataConsistencyIssue(self::LOGGED_FLIGHT_WITHOUT_FLIGHT_EVENT_ISSUE_NAME, $loggedFlightWithoutEvent, time());
            }

            $airportsWithoutLongName = array_filter($this->flightService->getAllAirports(), fn($airport) => $airport->getCode() !== null && $airport->getLongName() === null);
            foreach ($airportsWithoutLongName as &$airportWithoutLongName) {
                $dataConsistencyIssues[] = new DataConsistencyIssue(self::AIRPORT_WITHOUT_LONG_NAME_ISSUE_NAME, $airportWithoutLongName, time());
            }

            $airlinesWithoutLogo = array_filter($this->flightService->getAllAirlines(), fn($airline) => $airline->getLogo() === null);
            foreach ($airlinesWithoutLogo as &$airlineWithoutLogo) {
                $dataConsistencyIssues[] = new DataConsistencyIssue(self::AIRLINE_WITHOUT_LOGO_ISSUE_NAME, $airlineWithoutLogo, time());
            }

            return $dataConsistencyIssues;
        }
    }
?>