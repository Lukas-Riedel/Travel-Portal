<?php
    class UpdateCalendarProcessor extends Processor {
        public function process($input) {
            global $configuration, $tripService, $placeService, $stayService, $flightService;

            if ($input["watchId"] != $configuration["googleCalendarApi"]["watchId"]) {
                return;
            }

            if (!isset($input["calendar"]) || $input["calendar"] === "trips") {
                $tripService->deleteAllDayTripsTrips();
                $tripService->refreshCalendar();
                $placeService->refreshCalendar();
                $stayService->refreshCalendar();
                $flightService->refreshCalendar();
            }
            else if ($input["calendar"] === "places") {
                $placeService->refreshCalendar();
            }
            else if ($input["calendar"] === "stays") {
                $stayService->refreshCalendar();
            }
            else if ($input["calendar"] === "flights" || $input["calendar"] === "watchedFlights") {
                $flightService->refreshCalendar();
            }

            foreach ($tripService->getTripIdentifiersForDayTrips() as &$tripIdentifier) {
                $start = strtotime("31.12." . $tripIdentifier->getYear());
                $end = strtotime("1.1." . $tripIdentifier->getYear());

                foreach ($placeService->getRegularPlaces(NULL, $tripIdentifier->getId(), NULL, NULL, NULL, NULL, FALSE, FALSE, FALSE) as &$place) {
                    foreach ($place->getDates() as &$date) {
                        if ($date->getStart() < $start) {
                            $start = $date->getStart();
                        }
                        if ($date->getEnd() > $end) {
                            $end = $date->getEnd();
                        }
                    }
                }

                foreach ($stayService->getStaysForTrip($tripIdentifier->getId()) as &$stay) {
                    if ($stay->getStart() < $start) {
                        $start = $stay->getStart();
                    }
                    if ($stay->getEnd() > $end) {
                        $end = $stay->getEnd();
                    }
                }

                foreach ($flightService->getFlightsForTrip($tripIdentifier->getId()) as &$flight) {
                    if ($flight->getStart() < $start) {
                        $start = $flight->getStart();
                    }
                    if ($flight->getEnd() > $end) {
                        $end = $flight->getEnd();
                    }
                }

                $tripService->updateDayTripsTripDates($tripIdentifier->getId(), $start, $end);
            }
        }

        public function getRequiredArguments() {
            return array("watchId");
        }
        
        public function requiresAdminRole() {
            return FALSE;
        }

    }
?>