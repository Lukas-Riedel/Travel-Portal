<?php
    namespace Core\Service\Stay;

    use Core\Client\Calendar\Calendar;
    use Core\Service\Trip\TripService;
    use Core\Client\Calendar\CalendarClient;

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
            if ($message["calendar"] === Calendar::Stays->value) {
                $this->stayService->refreshCalendar($this->tripService);
            }
        }

        public function onCalendarWatchRenewing(mixed $message) : void {
            if ($message["calendar"] === Calendar::Stays->value) {
                $this->calendarClient->watchCalendar(Calendar::Stays);
            }
        }
    }
?>