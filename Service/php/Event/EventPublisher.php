<?php
    use Service\Service\Highlight\HighlightType;
    
    // TODO: Make sure messages contain as least information as possible (e.g., string placeId instead of PlaceIdentifier).
    // TODO: Go through every event and make sure it is fired meaningfuly (e.g., ForecastService shouldn't care about invalidating statistics)
    class EventPublisher {
        public function publishActualWeatherForecastUpdated($placeId, $start) {
            $this->publishEvent(Event::ActualWeatherForecastUpdated, array("placeId" => $placeId, "start" => $start));
        }
        
        public function publishHistoricalWeatherForecastUpdated($placeId, $start) {
            $this->publishEvent(Event::HistoricalWeatherForecastUpdated, array("placeId" => $placeId, "start" => $start));
        }
        
        public function publishDaylightForecastUpdated($placeId, $start, $end) {
            $this->publishEvent(Event::DaylightForecastUpdated, array("placeId" => $placeId, "start" => $start, "end" => $end));
        }

        public function publishFitnessActivityDetectedEvent($start, $end) {
            $this->publishEvent(Event::FitnessActivityDetected, array("start" => $start, "end" => $end));
        }

        public function publishCategoryCreatedEvent($categoryId) {
            $this->publishEvent(Event::CategoryCreated, array("categoryId" => $categoryId));
        }

        public function publishAlbumInvalidatedEvent($albumId) {
            $this->publishEvent(Event::AlbumInvalidated, array("albumId" => $albumId));
        }

        public function publishPhotoInvalidatedEvent($photoId) {
            $this->publishEvent(Event::PhotoInvalidated, array("photoId" => $photoId));
        }

        public function publishCategoryInvalidatedEvent($categoryId) {
            $this->publishEvent(Event::CategoryInvalidated, array("categoryId" => $categoryId));
        }

        public function publishAllAlbumsInvalidatedEvent() : void {
            $this->publishEvent(Event::AllAlbumsInvalidated, NULL);
        }

        public function publishAllHighlightsInvalidatedEvent() : void {
            $this->publishEvent(Event::AllHighlightsInvalidated, NULL);
        }

        public function publishHighlightCreatedEvent(HighlightType $highlightType, string $entityId, string $highlightId) : void {
            $this->publishEvent(Event::HighlightCreated, array("highlightType" => $highlightType->name, "entityId" => $entityId, "highlightId" => $highlightId));
        }

        public function publishHighlightRemovedEvent(HighlightType $highlightType, string $entityId, string $highlightId) : void {
            $this->publishEvent(Event::HighlightRemoved, array("highlightType" => $highlightType->name, "entityId" => $entityId, "highlightId" => $highlightId));
        }

        public function publishAlbumUpdatedEvent($albumId) : void {
            $this->publishEvent(Event::AlbumUpdated, array("albumId" => $albumId));
        }

        public function publishTripStatisticsInvalidatedEvent($tripId) : void {
            $this->publishEvent(Event::TripStatisticsInvalidated, array("tripId" => $tripId));
        }
        
        public function publishYearStatisticsInvalidatedEvent($year) : void {
            $this->publishEvent(Event::YearStatisticsInvalidated, array("year" => $year));
        }
        
        public function publishCategoryStatisticsInvalidatedEvent($categoryId) : void {
            $this->publishEvent(Event::CategoryStatisticsInvalidated, array("categoryId" => $categoryId));
        }
        
        public function publishCategoryUpdatedEvent($categoryId) : void {
            $this->publishEvent(Event::CategoryUpdated, array("categoryId" => $categoryId));
        }

        public function publishExpenseCreatedEvent($expenseId, $tripId) : void {
            $this->publishEvent(Event::ExpenseCreated, array("expenseId" => $expenseId, "tripId" => $tripId));
        }

        public function publishExpenseUpdatedEvent($expenseId, $tripId) : void {
            $this->publishEvent(Event::ExpenseUpdated, array("expenseId" => $expenseId, "tripId" => $tripId));
        }

        public function publishExpenseRemovedEvent($expenseId, $tripId) : void {
            $this->publishEvent(Event::ExpenseRemoved, array("expenseId" => $expenseId, "tripId" => $tripId));
        }
        
        public function publishPlaceUpdatedEvent($placeId) : void {
            $this->publishEvent(Event::PlaceUpdated, array("placeId" => $placeId));
        }
        
        public function publishPlaceCreatedEvent($placeId) : void {
            $this->publishEvent(Event::PlaceCreated, array("placeId" => $placeId));
        }
        
        public function publishVacationResetEvent() : void {
            $this->publishEvent(Event::VacationReset, NULL);
        }
        
        public function publishApplicationStartedEvent($tables) : void {
            $this->publishEvent(Event::ApplicationStarted, array("tables" => $tables));
        }
        
        public function publishFlightEventCreatedEvent($tripId) : void {
            $this->publishEvent(Event::FlightEventCreated, array("tripId" => $tripId));
        }
        
        public function publishFlightEventUpdatedEvent($tripId) : void {
            $this->publishEvent(Event::FlightEventUpdated, array("tripId" => $tripId));
        }
        
        public function publishFlightEventDeletedEvent($tripId) : void {
            $this->publishEvent(Event::FlightEventDeleted, array("tripId" => $tripId));
        }
        
        public function publishStayEventCreatedEvent($tripId) : void {
            $this->publishEvent(Event::StayEventCreated, array("tripId" => $tripId));
        }
        
        public function publishStayEventUpdatedEvent($tripId) : void {
            $this->publishEvent(Event::StayEventUpdated, array("tripId" => $tripId));
        }
        
        public function publishStayEventDeletedEvent($tripId) : void {
            $this->publishEvent(Event::StayEventDeleted, array("tripId" => $tripId));
        }
        
        public function publishTripEventCreatedEvent($tripId) : void {
            $this->publishEvent(Event::TripEventCreated, array("tripId" => $tripId));
        }
        
        public function publishTripEventUpdatedEvent($tripId) : void {
            $this->publishEvent(Event::TripEventUpdated, array("tripId" => $tripId));
        }
        
        public function publishTripEventDeletedEvent($tripId) : void {
            $this->publishEvent(Event::TripEventDeleted, array("tripId" => $tripId));
        }
        
        public function publishCalendarWatchRenewingEvent($calendar) : void {
            $this->publishEvent(Event::CalendarWatchRenewing, array("calendar" => $calendar));
        }

        public function publishFitnessDataUpdatedEvent($start, $end) : void {
            $this->publishEvent(Event::FitnessDataUpdated, array("start" => $start, "end" => $end));
        }

        public function publishSchedulerTriggeredEvent($action, $lastTriggered) : void {
            $this->publishEvent(Event::SchedulerTriggered, array("action" => $action, "lastTriggered" => $lastTriggered));
        }

        public function publishFlightLoggedEvent($flight, $tripId) : void {
            $this->publishEvent(Event::FlightLogged, array("flight" => $flight, "tripId" => $tripId));
        }

        public function publishFlightArrivedEvent($flight, $tripId, $from, $to, $scheduledDeparture) : void {
            $this->publishEvent(Event::FlightArrived, array("flight" => $flight, "tripId" => $tripId, "from" => $from, "to" => $to, "scheduledDeparture" => $scheduledDeparture));
        }

        public function publishYearStatisticsUpdatedEvent($year) : void {
            $this->publishEvent(Event::YearStatisticsUpdated, array("year" => $year));
        }

        public function publishCategoryStatisticsUpdatedEvent($categoryId) : void {
            $this->publishEvent(Event::CategoryStatisticsUpdated, array("categoryId" => $categoryId));
        }

        public function publishTripStatisticsUpdatedEvent($tripId, $year) : void {
            $this->publishEvent(Event::TripStatisticsUpdated, array("tripId" => $tripId, "year" => $year));
        }

        public function publishOverallStatisticsInvalidatedEvent() : void {
            $this->publishEvent(Event::OverallStatisticsInvalidated, NULL);
        }

        public function publishTripUpdatedEvent($tripId) : void {
            $this->publishEvent(Event::TripUpdated, array("tripId" => $tripId));
        }

        public function publishPlaceDeletedEvent($placeId) : void {
            $this->publishEvent(Event::PlaceDeleted, array("placeId" => $placeId));
        }

        public function publishPlaceEventCreatedEvent($placeId) : void {
            $this->publishEvent(Event::PlaceEventCreated, array("placeId" => $placeId));
        }

        public function publishPlaceEventUpdatedEvent($placeId) : void {
            $this->publishEvent(Event::PlaceEventUpdated, array("placeId" => $placeId));
        }
        
        public function publishPlaceEventDeletedEvent($placeId) : void {
            $this->publishEvent(Event::PlaceEventDeleted, array("placeId" => $placeId));
        }

        public function publishEvent($event, $args) : void {
            global $databaseProvider;

            $argsJson = json_encode($args, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG);

            $databaseProvider->beginTransaction();

            $databaseProvider
                ->statementBuilder("DELETE FROM queue_event WHERE event = ? AND args = ?")
                ->withParameters($event->name, $argsJson)
                ->execute();
                
            $databaseProvider
                ->statementBuilder("INSERT INTO queue_event (event, args, priority) VALUES (?, ?, ?)")
                ->withParameters($event->name, $argsJson, $event->value)
                ->execute();

            $databaseProvider->commit();
        }
    }

    enum Event : int {
        // TODO: Invalidations first, then updates. Order this enum.
        // TODO: Unify Removed/Deleted.
        // TODO: Remove unused.
        case ApplicationStarted = -1;
        case HighlightCreated = 0;
        case SchedulerTriggered = 1;
        case CalendarWatchRenewing = 2;
        case VacationReset = 3;
        case CalendarInvalidated = 4;
        case FlightArrived = 5;
        case ActualWeatherForecastUpdated = 6;
        case DaylightForecastUpdated = 7;
        case HistoricalWeatherForecastUpdated = 8;
        case PhotoInvalidated = 9;
        case AllHighlightsInvalidated = 10;
        case AlbumInvalidated = 11;
        case AllAlbumsInvalidated = 12;
        case CategoryInvalidated = 13;
        case AlbumUpdated = 14;
        case CategoryCreated = 15;
        case CategoryUpdated = 16;
        case ExpenseCreated = 17;
        case ExpenseUpdated = 18;
        case ExpenseRemoved = 19;
        case PlaceUpdated = 20;
        case FitnessDataUpdated = 21;
        case FlightLogged = 22;
        case FlightEventCreated = 23;
        case FlightEventUpdated = 24;
        case FlightEventDeleted = 25;
        case StayEventCreated = 26;
        case StayEventUpdated = 27;
        case StayEventDeleted = 28;
        case HighlightRemoved = 30;
        case YearStatisticsUpdated = 32;
        case TripStatisticsUpdated = 33;
        case CategoryStatisticsUpdated = 34;
        case OverallStatisticsInvalidated = 35;
        case YearStatisticsInvalidated = 36;
        case TripStatisticsInvalidated = 37;
        case CategoryStatisticsInvalidated = 38;
        case TripUpdated = 39;
        case TripEventCreated = 40;
        case TripEventUpdated = 41;
        case TripEventDeleted = 42;
        case PlaceDeleted = 43;
        case PlaceEventCreated = 44;
        case PlaceEventUpdated = 45;
        case PlaceEventDeleted = 46;
        case PlaceCreated = 47;

        case FitnessActivityDetected = 100;

        case PhotosUploading = 200;
        case PhotoReplacing = 201;

        public static function fromName($name) : ?Event {
            foreach (Event::cases() as $case) {
                if ($case->name === $name) {
                    return $case;
                }
            }
            return NULL;
        }
    }
?>