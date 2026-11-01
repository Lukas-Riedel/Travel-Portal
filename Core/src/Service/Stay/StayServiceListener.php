<?php
    namespace Core\Service\Stay;

    use Core\Client\Calendar\CalendarClient;
    use Core\Service\Trip\TripService;

    class StayServiceListener {

        private readonly StayService $stayService;
        private readonly TripService $tripService;
        private readonly CalendarClient $calendarClient;

        public function __construct(StayService $stayService, TripService $tripService, CalendarClient $calendarClient) {
            $this->stayService = $stayService;
            $this->tripService = $tripService;
            $this->calendarClient = $calendarClient;
        }

        public function onCalendarInvalidated(mixed $message) : void {
            foreach (StayType::cases() as &$stayType) {
                if ($stayType->getCalendar()->value === $message["calendar"]) {
                    $this->stayService->refreshCalendar(array($stayType), $this->tripService);
                }
            }
        }

        public function onCalendarWatchRenewing(mixed $message) : void {
            foreach (StayType::cases() as &$stayType) {
                if ($stayType->getCalendar()->value === $message["calendar"]) {
                    $this->calendarClient->watchCalendar($stayType->getCalendar());
                }
            }
        }
    }
?>