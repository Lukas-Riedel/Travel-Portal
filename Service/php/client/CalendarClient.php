<?php
    require_once(dirname(__FILE__) . "/../model/CalendarEvent.php");
    
    use ICal\ICal;

    class CalendarClient {
        public function watchCalendar($calendar, $watchId) : void {
            global $configuration, $googleApiClient, $authenticationService;

            $authenticationResult = $authenticationService->authenticateAsAdmin($configuration["googleCalendarApi"]["ttl"]);

            $googleApiClient->watchCalendar($calendar, $watchId, 
                BASE_URL . "/events?name=CalendarChanged&args[watchId]=" . $watchId . "&args[calendar]=" . $calendar,
                "Bearer " . $authenticationResult->getAccessToken());
                
            $googleApiClient->watchCalendar($calendar, $watchId, BASE_URL . "/php/runner.php");
        }

        public function getEvents($calendar) : array {        
            global $configuration;

            return $this->fetchEvents($configuration["calendars"][$calendar]);
        }

        public function getPublicHolidayEvents($country) : array {            
            global $categoryService;

            $categoryIdentifier = $categoryService->getCategoryIdentifierByName($country);
            if ($categoryIdentifier === NULL || $categoryIdentifier->getMetadata() === NULL
                || $categoryIdentifier->getMetadata()->getPublicHolidaysCalendar() === NULL) {
                return array();
            }

            return $this->fetchEvents($categoryIdentifier->getMetadata()->getPublicHolidaysCalendar());
        }

        private function fetchEvents($url) : array {
            $events = array();

            $ical = new ICal($url);
            if (isset($ical->cal["VEVENT"])) {
                foreach ($ical->cal["VEVENT"] as &$event) {
                    $events[] = new CalendarEvent($event["UID"],
                        html_entity_decode($event["SUMMARY"], ENT_QUOTES | ENT_HTML5), 
                        isset($event["LOCATION"]) ? html_entity_decode(str_replace("\\", "", $event["LOCATION"]), ENT_QUOTES | ENT_HTML5) : NULL,
                        $this->getEventTimestamp($event["DTSTART"]),
                        $this->getEventTimestamp($event["DTEND"]),
                        isset($event["DESCRIPTION"]) ? $this->getEventAttributes($event["DESCRIPTION"]) : array());
                }
            }

            return $events;
        }

        private function getEventTimestamp($date) : int {
            global $configuration;

            return (new DateTime($date, new DateTimeZone($configuration["homeLocation"]["timezone"])))->getTimestamp();
        }

        private function getEventAttributes($description) : array {
            $attributes = array();

            foreach (explode("\\n", $description) as &$descriptionEntry) {
                $tokens = explode(":", $descriptionEntry);
                $key = trim($tokens[0]);
                $value = (count($tokens) == 1) ? "" : trim(str_replace("\xc2\xa0", " ", $tokens[1]));
                $attributes[$key] = ($value == "") ? NULL : $value;
            }

            return $attributes;
        }
    }
?>