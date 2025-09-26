<?php
    namespace Core\Client\Calendar;

    use Core\Client\Google\GoogleClient;
    use Core\Common\CommonConstants;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Service\Category\CategoryIdentifier;
    use Core\Service\Configuration\ConfigurationService;
    use ICal\ICal;

    class CalendarClient {
        
        private const GOOGLE_CALENDAR_WATCH_TTL_SECONDS = CommonConstants::ONE_DAY_SECONDS;

        private const WATCH_CALENDAR_CALLBACK_URL_FORMAT = "%s/events/webhook?eventId=%s";

        private const ATTRIBUTE_KEY_VALUE_DELIMITER = ":";

        private readonly GoogleClient $googleClient;

        private ?ConfigurationService $configurationService;

        private ?EventPublisher $eventPublisher;

        public function __construct(GoogleClient $googleClient) {
            $this->googleClient = $googleClient;
            $this->configurationService = null;
            $this->eventPublisher = null;
        }

        public function setConfigurationService(ConfigurationService $configurationService) : void {
            $this->configurationService = $configurationService;
        }

        public function setEventPublisher(EventPublisher $eventPublisher) : void {
            $this->eventPublisher = $eventPublisher;
        }
        
        public function watchCalendar(Calendar $calendar) : void {
            $event = Event::CalendarInvalidating($calendar->value, self::GOOGLE_CALENDAR_WATCH_TTL_SECONDS);
            $eventId = $this->eventPublisher->publish($event);

            $this->googleClient->watchCalendar($calendar, $calendar->value . "_" . time(),
                sprintf(self::WATCH_CALENDAR_CALLBACK_URL_FORMAT, BASE_URL, $eventId), self::GOOGLE_CALENDAR_WATCH_TTL_SECONDS);
        }

        public function getEvents(Calendar $calendar) : array {
            return $this->fetchEvents($this->configurationService->getConfigurationEntry("calendars")[$calendar->value]);
        }
    
        public function getPublicHolidaysForCategories(array $categories) : array {
            $holidays = array();

            foreach ($categories as &$category) {
                foreach ($this->getPublicHolidaysForCategory($category) as &$holiday) {
                    $holidays[strtotime($holiday->getDate())] = $holiday;
                }
            }

            ksort($holidays);

            return array_values($holidays);
        }

        public function getPublicHolidaysForDatesInCategories(callable $categoryDatesProvider, array $categories) : array {
            $holidays = array();

            foreach ($categories as &$category) {
                $categoryHolidays = array();
                foreach ($this->getPublicHolidaysForCategory($category) as &$categoryHoliday) {
                    $categoryHolidays[$categoryHoliday->getDate()] = $categoryHoliday;
                }

                foreach ($categoryDatesProvider($category) as &$categoryDate) {
                    if (array_key_exists($categoryDate, $categoryHolidays)) {
                        $holidays[] = new PublicHoliday($categoryHolidays[$categoryDate]->getName(), $category, $categoryDate);
                    }
                }
            }

            return $holidays;
        }

        public function getPublicHolidaysForCategory(CategoryIdentifier $categoryIdentifier) : array {
            $holidays = array();
            
            foreach ($this->getPublicHolidayEvents($categoryIdentifier) as &$event) {
                if ($event->getStart() > time()) {
                    $date = getdate($event->getStart());
                    $holidays[] = new PublicHoliday($event->getSummary(), $categoryIdentifier->getName(), $date["mday"] . "." . $date["mon"] . "." . $date["year"]);                    
                }
            }

            return $holidays;
        }

        private function getPublicHolidayEvents(CategoryIdentifier $categoryIdentifier) : array {
            if ($categoryIdentifier->getMetadata()?->getPublicHolidaysCalendar() === null) {
                return array();
            }

            return $this->fetchEvents($categoryIdentifier->getMetadata()->getPublicHolidaysCalendar());
        }

        private function fetchEvents(string $url) : array {
            $events = array();

            $ical = new ICal($url);
            if (isset($ical->cal["VEVENT"])) {
                foreach ($ical->cal["VEVENT"] as &$event) {
                    $events[] = new CalendarEvent($event["UID"],
                        html_entity_decode($event["SUMMARY"], ENT_QUOTES | ENT_HTML5), 
                        isset($event["LOCATION"]) ? html_entity_decode(str_replace("\\", "", $event["LOCATION"]), ENT_QUOTES | ENT_HTML5) : null,
                        $this->getEventTimestamp($event["DTSTART"]),
                        $this->getEventTimestamp($event["DTEND"]),
                        isset($event["DESCRIPTION"]) ? $this->getEventAttributes($event["DESCRIPTION"]) : array());
                }
            }

            return $events;
        }

        private function getEventTimestamp(string $date) : int {
            return (new \DateTime($date, new \DateTimeZone($this->configurationService->getConfigurationEntry("homeLocation")["timezone"])))->getTimestamp();
        }

        private function getEventAttributes(string $description) : array {
            $attributes = array();

            foreach (explode("\\n", $description) as &$descriptionEntry) {
                $tokens = explode(self::ATTRIBUTE_KEY_VALUE_DELIMITER, $descriptionEntry);
                $key = trim($tokens[0]);
                $value = count($tokens) == 1 ? "" : trim(str_replace("\xc2\xa0", " ", $tokens[1]));
                $attributes[$key] = $value == "" ? null : $value;
            }

            return $attributes;
        }
    }
?>