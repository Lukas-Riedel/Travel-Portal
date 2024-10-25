<?php
    require_once(dirname(__FILE__) . "/GetCalendarIdentifierProcessor.php");
    require_once(dirname(__FILE__) . "/GetGoogleResponseProcessor.php");
    require_once(dirname(__FILE__) . "/../service/AuthenticationService.php");

    class StartCalendarWatchingProcessor extends Processor {        
        public function process($input) {
            global $configuration, $databaseProvider, $schedulingProvider;

            $databaseProvider
                ->statementBuilder("UPDATE configuration SET value = ? WHERE type = 'GOOGLE_CALENDAR_API' AND `key` = 'watchId'")
                ->withParameters($input["watchId"])
                ->execute();

            $getGoogleResponseProcessor = new GetGoogleResponseProcessor();
            $getCalendarIdentifierProcessor = new GetCalendarIdentifierProcessor();
        
            $calendarId = $getCalendarIdentifierProcessor
                ->process(array(
                    "name" => $input["calendar"]));

            if ($calendarId == NULL) {
                throw new InvalidArgumentException("Unable to start watching calendar  " . $input["calendar"] . ".");
            }

            $authenticationService = new AuthenticationService();
            $authenticationResult = $authenticationService->authenticateAsAdmin($configuration["googleCalendarApi"]["ttl"]);

            $payload = array(
                "id" => $input["watchId"],
                "type" => "web_hook",
                "token" => "Bearer " . $authenticationResult["accessToken"],
                "address" => "https://" . $configuration["hostName"] . "/api/jobs/schedule?action=UpdateCalendar&args[watchId]=" . $input["watchId"],
                "params" => array("ttl" => $configuration["googleCalendarApi"]["ttl"]));

            $getGoogleResponseProcessor
                ->process(array(
                    "method" => "POST", 
                    "url" => "https://www.googleapis.com/calendar/v3/calendars/" . $calendarId . "/events/watch", 
                    "payload" => json_encode($payload)));

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