<?php
    namespace Core\Service\CarRental;

    use Core\Client\Calendar\Calendar;
    use Core\Client\Calendar\CalendarClient;
    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\TransactionManager;
    use Core\Client\Google\GoogleClient;
    use Core\Common\CommonConstants;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Service\Trip\TripService;

    class CarRentalService {

        private const OLD_CAR_RENTAL_EVENT_TEMPORARY_TABLE = "old_car_rental_event";

        private readonly CarRentalMapper $carRentalMapper;
        private readonly CalendarClient $calendarClient;
        private readonly GoogleClient $googleClient;
        private readonly EventPublisher $eventPublisher;
        private readonly TransactionManager $transactionManager;

        public function __construct(DatabaseClient $databaseClient, CalendarClient $calendarClient,
            GoogleClient $googleClient, EventPublisher $eventPublisher) {
            $this->carRentalMapper = new CarRentalMapper($databaseClient);
            $this->calendarClient = $calendarClient;
            $this->googleClient = $googleClient;
            $this->eventPublisher = $eventPublisher;
            $this->transactionManager = $databaseClient;
        }
        
        public function getCarRentalsForTrip(string $tripId) : array {
            return $this->carRentalMapper->selectCarRentalsForTrip($tripId);
        }

        public function refreshCalendar(TripService $tripService) : void {
            $this->carRentalMapper->createCarRentalEventTemporaryTable(self::OLD_CAR_RENTAL_EVENT_TEMPORARY_TABLE);

            $carRentalEvents = $this->calendarClient->getEvents(Calendar::CarRentals);

            $this->transactionManager->executeAtomically(function() use(&$carRentalEvents, &$tripService) {
                $this->carRentalMapper->deleteAllCarRentalEvents();
                foreach ($carRentalEvents as &$carRentalEvent) {
                    $resolvedTripIdentifier = $tripService->getTripIdentifierForEntity($carRentalEvent->getStart(), $carRentalEvent->getEnd());
                    $carRental = new CarRental($carRentalEvent->getSummary(), $carRentalEvent->getStart(), $carRentalEvent->getEnd());

                    $this->carRentalMapper->insertCarRentalEvent($carRental, $carRentalEvent->getId(), $resolvedTripIdentifier?->getId());

                    if (!$carRentalEvent->isAllDay()) {
                        $this->googleClient->updateCalendarEventAllDayDates(Calendar::CarRentals, $carRentalEvent->getId(),
                            // If the event ends at 14:00, make it ends at midnight the next day.
                            $carRentalEvent->getStart(), $carRentalEvent->getEnd() + CommonConstants::ONE_DAY_SECONDS - 1);
                    }
                }
            });

            $affectedTripIds = $this->carRentalMapper->selectTripIdsForCreatedCarRentalEvents(self::OLD_CAR_RENTAL_EVENT_TEMPORARY_TABLE);
            foreach ($affectedTripIds as &$affectedTripId) {
                $this->eventPublisher->publish(Event::CarRentalEventCreated($affectedTripId));
            }
            
            $affectedTripIds = $this->carRentalMapper->selectTripIdsForUpdatedCarRentalEvents(self::OLD_CAR_RENTAL_EVENT_TEMPORARY_TABLE);
            foreach ($affectedTripIds as &$affectedTripId) {
                $this->eventPublisher->publish(Event::CarRentalEventUpdated($affectedTripId));
            }
            
            $affectedTripIds = $this->carRentalMapper->selectTripIdsForDeletedCarRentalEvents(self::OLD_CAR_RENTAL_EVENT_TEMPORARY_TABLE);
            foreach ($affectedTripIds as &$affectedTripId) {
                $this->eventPublisher->publish(Event::CarRentalEventRemoved($affectedTripId));
            }
        }
    }
?>
