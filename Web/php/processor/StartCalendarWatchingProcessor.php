<?php
    require_once(dirname(__FILE__) . "/GetCalendarIdentifierProcessor.php");
    require_once(dirname(__FILE__) . "/GetGoogleResponseProcessor.php");

    class StartCalendarWatchingProcessor extends Processor {        
        public function process($input) {
            global $configuration, $databaseProvider, $schedulingProvider;

            $databaseProvider
                ->statementBuilder("UPDATE configuration SET value = ? WHERE type = 'GOOGLE_CALENDAR_API_WATCH_UUID'")
                ->withParameters($input["uuid"])
                ->execute();

            $getGoogleResponseProcessor = new GetGoogleResponseProcessor();
            $getCalendarIdentifierProcessor = new GetCalendarIdentifierProcessor();
        
            $calendarId = $getCalendarIdentifierProcessor
                ->process(array(
                    "name" => $input["calendar"]));

            if ($calendarId == NULL) {
                throw new InvalidArgumentException("Unable to start watching calendar  " . $input["calendar"] . ".");
            }

            $payload = array(
                "id" => $input["uuid"],
                "type" => "web_hook",
                "address" => "https://" . $configuration["hostName"] . "/php/controller.php?action=UpdateCalendar&uuid=" . $input["uuid"] . "&async=true",
                "params" => array("ttl" => 86400));

            $getGoogleResponseProcessor
                ->process(array(
                    "method" => "POST", 
                    "url" => "https://www.googleapis.com/calendar/v3/calendars/" . $calendarId . "/events/watch", 
                    "payload" => json_encode($payload)));

            $schedulingProvider
                ->scheduleJobExecution("UpdateCalendar", array(
                    "uuid" => $configuration["googleCalendarApiWatchUuid"]), NULL);
                
            return TRUE;
        }

        public function getRequiredArguments() {
            return array("uuid", "calendar");
        }
        
        public function requiresAuthentication() {
            return TRUE;
        }
    }
?>