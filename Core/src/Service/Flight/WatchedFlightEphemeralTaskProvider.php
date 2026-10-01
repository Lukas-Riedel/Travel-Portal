<?php
    namespace Core\Service\Flight;

    use Core\Service\Task\EphemeralTaskCandidate;
    use Core\Service\Task\EphemeralTaskProvider;
    use Core\Service\Trip\TripIncludedEntity;
    use Core\Service\Trip\TripService;
    use Core\Service\Trip\TripSortingStrategy;

    class WatchedFlightEphemeralTaskProvider implements EphemeralTaskProvider {

        private const DMY_HI_DATE_TIME_FORMAT = "d.m.Y H:i";
        private const GOOGLE_FLIGHTS_URL_FORMAT = "https://www.google.com/travel/flights?q=One way flight from %s to %s on %s";

        private readonly TripService $tripService;

        public function __construct(TripService $tripService) {
            $this->tripService = $tripService;
        }

        public function getSource() : string {
            return "flight.watched";
        }

        public function getCandidates() : array {
            $candidates = array();

            foreach ($this->tripService->getRegularTrips(null, time(), null, array(TripIncludedEntity::WatchedFlights->value), TripSortingStrategy::OldestAscending) as &$trip) {
                foreach ($trip->getWatchedFlights() as &$flight) {
                    if ($flight->getStart() < time()) {
                        continue;
                    }
                    
                    $formattedTimestamp = (new \DateTime())
                        ->setTimestamp($flight->getStart())
                        ->setTimezone(new \DateTimeZone($flight->getFrom()->getTimezone()))
                        ->format(self::DMY_HI_DATE_TIME_FORMAT);

                    $candidates[] = new EphemeralTaskCandidate($flight->getStart(), array("from" => $flight->getFrom()->getShortName(), "to" => $flight->getTo()->getShortName(), "formattedTimestamp" => $formattedTimestamp),
                        $trip->getId(), sprintf(self::GOOGLE_FLIGHTS_URL_FORMAT, $flight->getFrom()->getShortName(), $flight->getTo()->getShortName(), $formattedTimestamp));
                }
            }

            return $candidates;
        }
    }
?>
