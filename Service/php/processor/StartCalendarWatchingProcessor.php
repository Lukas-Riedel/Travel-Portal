<?php
    class StartCalendarWatchingProcessor extends Processor {        
        public function process($input) {
            global $configurationService, $calendarClient;

            $configurationService->updateGoogleCalendarWatchId($input["watchId"]);
            $calendarClient->watchCalendar($input["calendar"], $input["watchId"]);
        }

        public function getRequiredArguments() {
            return array("watchId", "calendar");
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }
    }
?>