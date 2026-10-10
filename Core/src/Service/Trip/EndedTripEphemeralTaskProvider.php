<?php
    namespace Core\Service\Trip;

    use Core\Service\Task\EphemeralTaskCandidate;
    use Core\Service\Task\EphemeralTaskProvider;
    use Core\Service\Trip\TripSortingStrategy;

    class EndedTripEphemeralTaskProvider implements EphemeralTaskProvider {

        private readonly TripService $tripService;

        public function __construct(TripService $tripService) {
            $this->tripService = $tripService;
        }

        public function getSource() : string {
            return "trip.ended";
        }

        public function getCandidates() : array {
            $candidates = array();

            foreach ($this->tripService->getRegularTrips(null, null, null, array(), TripSortingStrategy::OldestAscending) as &$trip) {
                if ($trip->getEnd() < time()) {
                    continue;
                }

                $candidates[] = new EphemeralTaskCandidate($trip->getEnd(), array("countries" => implode(", ", $trip->getCountries())), $trip->getId(), null);
            }

            return $candidates;
        }
    }
?>
