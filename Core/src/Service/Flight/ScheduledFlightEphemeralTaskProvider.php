<?php
    namespace Core\Service\Flight;

    use Core\Service\Task\EphemeralTaskCandidate;
    use Core\Service\Task\EphemeralTaskProvider;

    class ScheduledFlightEphemeralTaskProvider implements EphemeralTaskProvider {

        private const HI_TIME_FORMAT = "H:i";
        private const FLIGHTRADAR_URL_FORMAT = "https://www.flightradar24.com/data/flights/%s";

        private readonly FlightService $flightService;

        public function __construct(FlightService $flightService) {
            $this->flightService = $flightService;
        }

        public function getSource() : string {
            return "flight.scheduled";
        }

        public function getCandidates() : array {
            $candidates = array();

            foreach ($this->flightService->getAllNonLoggedFlights() as &$flight) {
                if ($flight->getStart() < time()) {
                    continue;
                }
                
                $formattedTime = (new \DateTime())
                    ->setTimestamp($flight->getStart())
                    ->setTimezone(new \DateTimeZone($flight->getFrom()->getTimezone()))
                    ->format(self::HI_TIME_FORMAT);

                $candidates[] = new EphemeralTaskCandidate($flight->getStart(), array("flight" => $flight->getFlight(), "formattedTime" => $formattedTime),
                    $this->flightService->getTripIdForFlight($flight), sprintf(self::FLIGHTRADAR_URL_FORMAT, $flight->getFlight()));
            }

            return $candidates;
        }
    }
?>
