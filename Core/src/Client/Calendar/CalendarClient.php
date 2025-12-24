<?php
    namespace Core\Client\Calendar;

    use Core\Client\Cache\CacheClient;
    use Core\Client\Google\GoogleClient;
    use Core\Common\CommonConstants;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Service\Category\CategoryIdentifier;
    use ICal\ICal;
    use Monolog\Logger;

    class CalendarClient {
        
        private const PUBLIC_HOLIDAYS_CACHE_KEY_FORMAT = "CalendarClient:PublicHolidays:%s";
        private const PUBLIC_HOLIDAYS_CACHE_TTL = CommonConstants::ONE_WEEK_SECONDS;
        
        private const GOOGLE_CALENDAR_WATCH_TTL_SECONDS = CommonConstants::ONE_DAY_SECONDS;
        private const WATCH_CALENDAR_CALLBACK_URL_FORMAT = "%s/events/webhook?eventId=%s";

        private const ATTRIBUTE_KEY_VALUE_DELIMITER = ":";

        private readonly GoogleClient $googleClient;

        private readonly CacheClient $cacheClient;
        
        private readonly string $coreBaseUrl;

        private readonly Logger $logger;

        private ?EventPublisher $eventPublisher;

        public function __construct(GoogleClient $googleClient, CacheClient $cacheClient, Logger $logger, string $coreBaseUrl) {
            $this->googleClient = $googleClient;
            $this->cacheClient = $cacheClient;
            $this->logger = $logger;
            $this->coreBaseUrl = $coreBaseUrl;
            $this->eventPublisher = null;
        }

        public function setEventPublisher(EventPublisher $eventPublisher) : void {
            $this->eventPublisher = $eventPublisher;
        }
        
        public function watchCalendar(Calendar $calendar) : void {
            $event = Event::CalendarInvalidating($calendar->value, self::GOOGLE_CALENDAR_WATCH_TTL_SECONDS);
            $eventId = $this->eventPublisher->publish($event);

            $this->googleClient->watchCalendar($calendar, $calendar->value . "_" . time(),
                sprintf(self::WATCH_CALENDAR_CALLBACK_URL_FORMAT, $this->coreBaseUrl, $eventId), self::GOOGLE_CALENDAR_WATCH_TTL_SECONDS);
        }

        public function getEvents(Calendar $calendar) : array {
            $events = array();

            $response = $this->googleClient->getCalendarEvents($calendar);
            while (isset($response["items"])) {
                foreach ($response["items"] as &$item) {
                    $events[] = new CalendarEvent(
                        $item["id"],
                        $item["summary"],
                        $item["location"] ?? null,
                        $this->getEventTimestamp($item["start"]["dateTime"] ?? $item["start"]["date"]),
                        $this->getEventTimestamp($item["end"]["dateTime"] ?? $item["end"]["date"]),
                        $item["start"]["dateTime"] ?? $item["start"]["date"],
                        $item["end"]["dateTime"] ?? $item["end"]["date"],
                        isset($item["start"]["timeZone"]) ? $item["start"]["timeZone"] : null,
                        isset($item["end"]["timeZone"]) ? $item["end"]["timeZone"] : null,
                        isset($item["description"]) ? $this->getEventAttributes($item["description"]) : array(),
                        isset($item["start"]["date"]) && isset($item["end"]["date"])
                    );
                }
                
                if (isset($response["nextPageToken"])) {
                    $response = $this->googleClient->getCalendarEvents($calendar, $response["nextPageToken"]);
                }
                else {
                    $response = array();
                }
            }

            return $events;
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
                        $holidays[] = new PublicHoliday($categoryHolidays[$categoryDate]->getName(), $category->getName(), $categoryDate);
                    }
                }
            }

            return $holidays;
        }

        public function getPublicHolidaysForCategory(CategoryIdentifier $categoryIdentifier) : array {
            $cacheKey = $this->getPublicHolidaysCacheKey($categoryIdentifier);
            $cachedHolidays = $this->cacheClient->get($cacheKey);
            if ($cachedHolidays !== null) {
                return array_map(fn($publicHoliday) => new PublicHoliday($publicHoliday["name"], $publicHoliday["category"], $publicHoliday["date"]), $cachedHolidays);
            }

            $fetchedHolidays = array();
            $fetchedHolidaysValiditySeconds = self::PUBLIC_HOLIDAYS_CACHE_TTL;
            
            try {
                foreach ($this->getPublicHolidayEvents($categoryIdentifier) as &$event) {
                    if ($event->getStart() > time()) {
                        $date = getdate($event->getStart());
                        $fetchedHolidays[] = new PublicHoliday($event->getSummary(), $categoryIdentifier->getName(), $date["mday"] . "." . $date["mon"] . "." . $date["year"]);                    
                    }
                }
            }
            catch (\Throwable $t) {
                $this->logger->error("Unable to fetch public holidays for category " . $categoryIdentifier->getName() . ": " . $t->getMessage());
                $fetchedHolidaysValiditySeconds = CommonConstants::ONE_HOUR_SECONDS;
            }

            $this->cacheClient->set($cacheKey, $fetchedHolidays, $fetchedHolidaysValiditySeconds);
            return $fetchedHolidays;
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
                    $events[] = new CalendarEvent(
                        $event["UID"],
                        html_entity_decode($event["SUMMARY"], ENT_QUOTES | ENT_HTML5), 
                        isset($event["LOCATION"]) ? html_entity_decode(str_replace("\\", "", $event["LOCATION"]), ENT_QUOTES | ENT_HTML5) : null,
                        $this->getEventTimestamp($event["DTSTART"]),
                        $this->getEventTimestamp($event["DTEND"]),
                        $event["DTSTART"],
                        $event["DTEND"],
                        null,
                        null,
                        isset($event["DESCRIPTION"]) ? $this->getEventAttributes($event["DESCRIPTION"]) : array(),
                        ($this->getEventTimestamp($event["DTEND"]) - $this->getEventTimestamp($event["DTSTART"])) % CommonConstants::ONE_DAY_SECONDS === 0
                    );
                }
            }

            return $events;
        }

        private function getEventTimestamp(string $date) : int {
            return (new \DateTime($date, new \DateTimeZone(date_default_timezone_get())))->getTimestamp();
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

        private function getPublicHolidaysCacheKey(CategoryIdentifier $categoryIdentifier) : string {
            return sprintf(self::PUBLIC_HOLIDAYS_CACHE_KEY_FORMAT, $categoryIdentifier->getName());
        }
    }
?>