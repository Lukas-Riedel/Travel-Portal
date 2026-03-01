<?php
    namespace Core\Service\Trip;

    use Core\Service\Index\DocumentBuffer;
    use Core\Service\Index\EntityIndexer;
    use Core\Service\Index\IndexableEntityType;
    use Core\Service\Index\IndexType;

    class TripIndexer implements EntityIndexer {

        private readonly TripService $tripService;

        public function __construct(TripService $tripService) {
            $this->tripService = $tripService;
        }

        public function index(DocumentBuffer $documentBuffer, IndexType $indexType, IndexableEntityType $entityType, ?string $entityId) : void {
            if ($indexType === IndexType::Composite && $entityType === IndexableEntityType::Trip) {
                $trips = $entityId !== null
                    ? array($this->tripService->getRegularTrip($entityId))
                    : $this->tripService->getRegularTrips(null, null, time(), array(TripIncludedEntity::Flights->value, TripIncludedEntity::Stays->value), TripSortingStrategy::OldestAscending);

                foreach ($trips as &$trip) {
                    $terms = array($trip->getFullName());

                    foreach ($trip->getCountries() as &$country) {
                        $terms[] = $country;
                    }

                    foreach ($trip->getFlights() as &$flight) {
                        $terms[] = $flight->getFlight();
                        $terms[] = $flight->getRegistration();
                        $terms[] = $flight->getAircraft();
                        $terms[] = $flight->getFrom()?->getLongName();
                        $terms[] = $flight->getTo()?->getLongName();
                        $terms[] = $flight->getAirline()?->getName();
                    }

                    foreach ($trip->getStays() as &$stay) {
                        $terms[] = $stay->getName();
                    }

                    $documentBuffer->add($trip->getId(), array_filter($terms));
                }
            }
        }
    }
?>