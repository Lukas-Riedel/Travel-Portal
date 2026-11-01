<?php
    namespace Core\Service\CarRental;

    use Core\Client\Calendar\Calendar;
    use Core\Client\Calendar\CalendarClient;
    use Core\Service\Trip\TripService;

    class CarRentalServiceListener {

        private readonly CarRentalService $carRentalService;
        private readonly TripService $tripService;
        private readonly CalendarClient $calendarClient;

        public function __construct(CarRentalService $carRentalService, TripService $tripService, CalendarClient $calendarClient) {
            $this->carRentalService = $carRentalService;
            $this->tripService = $tripService;
            $this->calendarClient = $calendarClient;
        }

        public function onCalendarInvalidated(mixed $message) : void {
            if ($message["calendar"] === Calendar::CarRentals->value) {
                $this->carRentalService->refreshCalendar($this->tripService);
            }
        }

        public function onCalendarWatchRenewing(mixed $message) : void {
            if ($message["calendar"] === Calendar::CarRentals->value) {
                $this->calendarClient->watchCalendar(Calendar::CarRentals);
            }
        }
    }
?>
