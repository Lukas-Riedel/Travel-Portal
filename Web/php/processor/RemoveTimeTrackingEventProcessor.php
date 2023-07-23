<?php
    class RemoveTimeTrackingEventProcessor extends Processor {        
        public function process($input) {
            global $configuration, $databaseProvider, $schedulingProvider;

            $deletedRowsCount = $databaseProvider
                ->statementBuilder("DELETE FROM tracking WHERE id = ?")
                ->withParameters($input["eventId"])
                ->execute();

            // Schedule calendar to materialize the trip summary view and recompute vacation numbers.
            $schedulingProvider
                ->scheduleJobExecution("UpdateCalendar", array(
                    "uuid" => $configuration["googleCalendarApiWatchUuid"]), NULL);

            return $deletedRowsCount == 1;
        }

        public function getRequiredArguments() {
            return array("eventId");
        }
        
        public function requiresAuthentication() {
            return TRUE;
        }
    }
?>