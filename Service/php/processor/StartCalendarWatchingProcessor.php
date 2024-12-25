<?php
    require_once(dirname(__FILE__) . "/../service/AuthenticationService.php");

    class StartCalendarWatchingProcessor extends Processor {        
        public function process($input) {
            global $configuration, $databaseProvider, $schedulingProvider, $googleApiClient;

            $databaseProvider
                ->statementBuilder("UPDATE configuration SET value = ? WHERE type = 'GOOGLE_CALENDAR_API' AND `key` = 'watchId'")
                ->withParameters($input["watchId"])
                ->execute();

            $authenticationService = new AuthenticationService();
            $authenticationResult = $authenticationService->authenticateAsAdmin($configuration["googleCalendarApi"]["ttl"]);

            $googleApiClient->watchCalendar($input["calendar"], $input["watchId"], 
                BASE_URL . "/jobs/schedule?action=UpdateCalendar&args[watchId]=" . $input["watchId"],
                "Bearer " . $authenticationResult->getAccessToken());
                
            $googleApiClient->watchCalendar($input["calendar"], $input["watchId"], BASE_URL . "/php/runner.php");

            $schedulingProvider
                ->scheduleJobExecution("UpdateCalendar", array(
                    "watchId" => $input["watchId"]), NULL);
                
            return TRUE;
        }

        public function getRequiredArguments() {
            return array("watchId", "calendar");
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }
    }
?>