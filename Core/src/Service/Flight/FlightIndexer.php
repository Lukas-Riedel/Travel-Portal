<?php
    namespace Core\Service\Flight;

    use Core\Common\CommonConstants;
    use Core\Service\Index\DocumentBuffer;
    use Core\Service\Index\EntityIndexer;
    use Core\Service\Index\IndexableEntityType;
    use Core\Service\Index\IndexType;

    class FlightIndexer implements EntityIndexer {

        private readonly FlightService $flightService;

        public function __construct(FlightService $flightService) {
            $this->flightService = $flightService;
        }

        public function index(DocumentBuffer $documentBuffer, IndexType $indexType, IndexableEntityType $entityType, ?string $entityId) : void {            
            if ($indexType === IndexType::Composite) {
                $flights = $this->flightService->getLoggedFlightsForInterval(0, PHP_INT_MAX, FlightSortingStrategy::ScheduledDepartureTimeAscending);

                if ($entityType === IndexableEntityType::Airline) {
                    foreach ($flights as &$flight) {
                        $airline = $flight->getAirline();
                        if ($airline !== null && ($entityId === null || $flight->getAirline()->getId() === $entityId)) {
                            $data = array($airline->getName(), $flight->getFrom()?->getLongName(), $flight->getTo()?->getLongName(), $flight->getFlight(), $flight->getRegistration(), $flight->getAircraft(), date(CommonConstants::DMY_DATE_FORMAT, $flight->getStart()));
                            // TODO: Obtain airlines in a different way so that it also considers airlines without any flights.
                            $documentBuffer->add($airline->getId(), array_merge($result[$airline->getId()] ?? array(), array_filter($data)), false);
                        }
                    }
                }

                if ($entityType === IndexableEntityType::Airport) {
                    foreach ($flights as &$flight) {
                        $from = $flight->getFrom();
                        if ($from !== null && ($entityId === null || $flight->getFrom()->getId() === $entityId)) {
                            $data = array($from->getLongName(), $from->getCode(), $from->getCountry(), $flight->getAirline()?->getName(), $flight->getFlight(), $flight->getRegistration(), $flight->getAircraft(), date(CommonConstants::DMY_DATE_FORMAT, $flight->getStart()));
                            // TODO: Obtain airport in a different way so that it also considers airport without any flights.
                            $documentBuffer->add($from->getId(), array_merge($result[$from->getId()] ?? array(), array_filter($data)), false);
                        }

                        $to = $flight->getTo();
                        if ($to !== null && ($entityId === null || $flight->getTo()->getId() === $entityId)) {
                            $data = array($to->getLongName(), $to->getCode(), $to->getCountry(), $flight->getAirline()?->getName(), $flight->getFlight(), $flight->getRegistration(), $flight->getAircraft(), date(CommonConstants::DMY_DATE_FORMAT, $flight->getEnd()));
                            // TODO: Obtain airport in a different way so that it also considers airport without any flights.
                            $documentBuffer->add($to->getId(), array_merge($result[$to->getId()] ?? array(), array_filter($data)), false);
                        }
                    }
                }
            }
        }
    }
?>