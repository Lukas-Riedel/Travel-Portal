<?php
    namespace Core\Service\Flight;

    use Core\Common\CommonConstants;
    use Core\Service\Index\EntityIndexer;
    use Core\Service\Index\IndexableEntityType;

    class FlightIndexer implements EntityIndexer {

        private readonly FlightService $flightService;

        public function __construct(FlightService $flightService) {
            $this->flightService = $flightService;
        }

        public function index(IndexableEntityType $entityType) : array {
            $result = array();

            $flights = $this->flightService->getLoggedFlightsForInterval(0, PHP_INT_MAX, FlightSortingStrategy::ScheduledDepartureTimeAscending);

            if ($entityType === IndexableEntityType::Airline) {
                foreach ($flights as &$flight) {
                    $airline = $flight->getAirline();
                    if ($airline !== null) {
                        $data = array($airline->getName(), $flight->getFrom()?->getLongName(), $flight->getTo()?->getLongName(), $flight->getFlight(), $flight->getRegistration(), $flight->getAircraft(), date(CommonConstants::DMY_DATE_FORMAT, $flight->getStart()));
                        $result[$airline->getId()] = array_merge($result[$airline->getId()] ?? array(), array_filter($data));
                    }
                }
            }

            if ($entityType === IndexableEntityType::Airport) {
                foreach ($flights as &$flight) {
                    $from = $flight->getFrom();
                    if ($from !== null) {
                        $data = array($from->getLongName(), $from->getCode(), $from->getCountry(), $flight->getAirline()?->getName(), $flight->getFlight(), $flight->getRegistration(), $flight->getAircraft(), date(CommonConstants::DMY_DATE_FORMAT, $flight->getStart()));
                        $result[$from->getId()] = array_merge($result[$from->getId()] ?? array(), array_filter($data));
                    }

                    $to = $flight->getTo();
                    if ($to !== null) {
                        $data = array($to->getLongName(), $to->getCode(), $to->getCountry(), $flight->getAirline()?->getName(), $flight->getFlight(), $flight->getRegistration(), $flight->getAircraft(), date(CommonConstants::DMY_DATE_FORMAT, $flight->getEnd()));
                        $result[$to->getId()] = array_merge($result[$to->getId()] ?? array(), array_filter($data));
                    }
                }
            }

            return $result;
        }
    }
?>