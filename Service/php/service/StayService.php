<?php
    require_once(dirname(__FILE__) . "/../model/Stay.php");

    class StayService {
        
        public function getStaysForTrip($tripId) : array {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("SELECT * FROM stay_event WHERE trip_id = ? ORDER BY start")
                ->withParameters($tripId)
                ->getMappedResultSet(function ($stayRow) {
                    return new Stay($stayRow["name"], $stayRow["address"], $stayRow["start"], $stayRow["end"]);
                });
        }

        public function refreshCalendar() : void {
            global $databaseProvider, $calendarClient, $tripService, $eventPublisher;
            
            $databaseProvider
                ->statementBuilder("DROP TEMPORARY TABLE IF EXISTS old_stay_event")
                ->execute();

            $databaseProvider
                ->statementBuilder("CREATE TEMPORARY TABLE old_stay_event AS SELECT * FROM stay_event")
                ->execute();

            $databaseProvider
                ->statementBuilder("DELETE FROM stay_event")
                ->execute();
                
            foreach ($calendarClient->getEvents("stays") as &$stayEvent) {
                $resolvedTripIdentifier = $tripService->getOrCreateTripIdentifierForEntity($stayEvent->getStart(), $stayEvent->getEnd());

                $databaseProvider
                    ->statementBuilder("INSERT INTO stay_event (id, name, trip_id, start, end, address) VALUES (?, ?, ?, ?, ?, ?)")
                    ->withParameters($stayEvent->getId(), $stayEvent->getSummary(), $resolvedTripIdentifier->getId(), $stayEvent->getStart(), $stayEvent->getEnd(), $stayEvent->getLocation())
                    ->execute();
            }

            // Process new and renamed stays.
            $newStayRows = $databaseProvider
                ->statementBuilder("SELECT ns.trip_id FROM stay_event ns LEFT JOIN old_stay_event os ON os.id = ns.id WHERE os.name IS NULL")
                ->getResultSet();

            foreach ($newStayRows as &$newStayRow) {      
                $eventPublisher->publishTripStatisticsChangedEvent($newStayRow["trip_id"]);
            }

            // Process removed stays.
            $removedStayRows = $databaseProvider
                ->statementBuilder("SELECT os.trip_id FROM old_stay_event os LEFT JOIN stay_event ns ON os.id = ns.id WHERE ns.id IS NULL")
                ->getResultSet();

            foreach ($removedStayRows as &$removedStayRow) {
                if ($removedStayRow["trip_id"] != NULL) {
                    $eventPublisher->publishTripStatisticsChangedEvent($removedStayRow["trip_id"]);
                }
            }
        }

        public function onCalendarChanged($message) {
            global $configuration;

            if ($message["calendar"] === "stays" && $message["watchId"] === $configuration["googleCalendarApi"]["watchId"]) {
                $this->refreshCalendar();
            }
        }

        public function onCalendarWatchRenewing($message) {
            global $calendarClient;

            if ($message["calendar"] === "stays") {
                $calendarClient->watchCalendar($message["calendar"], $message["watchId"]);
            }
        }
    }
?>