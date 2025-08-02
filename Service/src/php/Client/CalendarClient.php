<?php
    require_once(__DIR__ . "/../Model/CalendarEvent.php");
    require_once(__DIR__ . "/../Model/PublicHoliday.php");
    
    use ICal\ICal;

    class CalendarClient {
        private const GOOGLE_CALENDAR_WATCH_TTL_SECONDS = 86400;
        
        // TODO: Change string $calendar to Calendar $calendar and update usages.
        public function watchCalendar(string $calendar) : void {
            global $googleApiClient, $authenticationService;

            $authenticationResult = $authenticationService->authenticateAsAdmin(self::GOOGLE_CALENDAR_WATCH_TTL_SECONDS);

            $googleApiClient->watchCalendar($calendar, $calendar . "_" . time(),
                BASE_URL . "/events?name=" . Event::CalendarInvalidated->name . "&args[calendar]=" . $calendar, self::GOOGLE_CALENDAR_WATCH_TTL_SECONDS,
                "Bearer " . $authenticationResult->getAccessToken(),);
        }

        // TODO: Change string $calendar to Calendar $calendar and update usages.
        public function getEvents($calendar) : array {        
            global $configurationService;

            return $this->fetchEvents($configurationService->getConfigurationEntry("calendars")[$calendar]);
        }
    
        public function getPublicHolidaysForCountries($countries) : array {
            $holidays = array();

            foreach ($countries as &$country) {
                foreach ($this->getPublicHolidaysForCountry($country) as &$holiday) {
                    $holidays[strtotime($holiday->getDate())] = $holiday;
                }
            }

            ksort($holidays);

            return array_values($holidays);
        }

        public function getPublicHolidaysForDatesInCountries(callable $countryDatesProvider, $countries) {
            $holidays = array();

            foreach ($countries as &$country) {
                $countryHolidays = array();
                foreach ($this->getPublicHolidaysForCountry($country) as &$countryHoliday) {
                    $countryHolidays[$countryHoliday->getDate()] = $countryHoliday;
                }

                foreach ($countryDatesProvider($country) as &$countryDate) {
                    if (array_key_exists($countryDate, $countryHolidays)) {
                        $holidays[] = new PublicHoliday($countryHolidays[$countryDate]->getName(), $country, $countryDate);
                    }
                }
            }

            return $holidays;
        }

        public function getPublicHolidaysForCountry($country) : array {
            $holidays = array();
            
            foreach ($this->getPublicHolidayEvents($country) as &$event) {               
                if ($event->getStart() > time()) {
                    $date = getdate($event->getStart());
                    $holidays[] = new PublicHoliday($event->getSummary(), $country, $date["mday"] . "." . $date["mon"] . "." . $date["year"]);                    
                }
            }

            return $holidays;
        }

        private function getPublicHolidayEvents($categoryName) : array {            
            global $categoryService;

            $categoryIdentifier = $categoryService->getCategoryIdentifier($categoryName);
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
            global $configurationService;

            return (new DateTime($date, new DateTimeZone($configurationService->getConfigurationEntry("homeLocation")["timezone"])))->getTimestamp();
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

    enum Calendar : string {
        case Trips = "trips";
        case Places = "places";
        case Stays = "stays";
        case Flights = "flights";
        case WatchedFlights = "watchedFlights";
    }
?>