<?php
    namespace Core\Service\Stay;

    use Core\Service\Task\EphemeralTaskCandidate;
    use Core\Service\Task\EphemeralTaskProvider;
    use Core\Service\Trip\TripService;
    use Core\Service\Trip\TripSortingStrategy;

    class WatchedStayEphemeralTaskProvider implements EphemeralTaskProvider {

        private const DMY_DATE_FORMAT = "d.m.Y";
        private const GOOGLE_HOTELS_URL_FORMAT = "https://www.google.com/travel/hotels?q=%s";

        private readonly TripService $tripService;
        private readonly StayService $stayService;

        public function __construct(TripService $tripService, StayService $stayService) {
            $this->tripService = $tripService;
            $this->stayService = $stayService;
        }

        public function getSource() : string {
            return "stay.watched";
        }

        public function getCandidates() : array {
            $candidates = array();

            foreach ($this->tripService->getRegularTrips(null, time(), null, array(), TripSortingStrategy::OldestAscending) as &$trip) {
                foreach ($this->stayService->getWatchedStaysForTrip($trip->getId()) as &$stay) {
                    if ($stay->getStart() < time()) {
                        continue;
                    }

                    $formattedStart = (new \DateTime())
                        ->setTimestamp($stay->getStart())
                        ->format(self::DMY_DATE_FORMAT);

                    $formattedEnd = (new \DateTime())
                        ->setTimestamp($stay->getEnd() - 1)
                        ->format(self::DMY_DATE_FORMAT);

                    $candidates[] = new EphemeralTaskCandidate($stay->getStart(), array("name" => $stay->getName(), "formattedStart" => $formattedStart, "formattedEnd" => $formattedEnd),
                        $trip->getId(), sprintf(self::GOOGLE_HOTELS_URL_FORMAT, urlencode($stay->getName())));
                }
            }

            return $candidates;
        }
    }
?>
