<?php
    // TODO: Make sure messages contain as much information as possible (e.g., PlaceIdentifier instead of string placeId).
    class EventPublisher {
        public function publishActualWeatherForecastChanged($placeId, $start) {
            $this->publishEvent(Event::ActualWeatherForecastChanged, array("placeId" => $placeId, "start" => $start));
        }
        
        public function publishHistoricalWeatherForecastChanged($placeId, $start) {
            $this->publishEvent(Event::HistoricalWeatherForecastChanged, array("placeId" => $placeId, "start" => $start));
        }
        
        public function publishDaylightForecastChanged($placeId, $start, $end) {
            $this->publishEvent(Event::DaylightForecastChanged, array("placeId" => $placeId, "start" => $start, "end" => $end));
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

        public function publishAllHighlightsChangedEvent() : void {
            $this->publishEvent(Event::AllHighlightsChanged, NULL);
        }

        public function publishAlbumUpdatedEvent($albumId) : void {
            $this->publishEvent(Event::AlbumUpdated, array("albumId" => $albumId));
        }

        public function publishTripStatisticsChangedEvent($tripId) : void {
            $this->publishEvent(Event::StatisticsChanged, array("tripId" => $tripId));
        }
        
        public function publishYearStatisticsChangedEvent($year) : void {
            $this->publishEvent(Event::StatisticsChanged, array("year" => $year));
        }
        
        public function publishCategoryUpdatedEvent($categoryId) : void {
            $this->publishEvent(Event::CategoryUpdated, array("categoryId" => $categoryId));
        }
        
        public function publishPlaceUpdatedEvent($placeIdentifier) : void {
            $this->publishEvent(Event::PlaceUpdated, array("placeIdentifier" => $placeIdentifier));
        }
        
        public function publishStatisticsChangedEvent() : void {
            $this->publishEvent(Event::StatisticsChanged, NULL);
        }
        
        public function publishVacationResetEvent() : void {
            $this->publishEvent(Event::VacationReset, NULL);
        }
        
        public function publishApplicationStartedEvent($tables) : void {
            $this->publishEvent(Event::ApplicationStarted, array("tables" => $tables));
        }
        
        public function publishCalendarChangedEvent($calendar, $watchId) : void {
            $this->publishEvent(Event::CalendarChanged, array("calendar" => $calendar, "watchId" => $watchId));
        }
        
        public function publishCalendarWatchRenewingEvent($calendar, $watchId) : void {
            $this->publishEvent(Event::CalendarWatchRenewing, array("calendar" => $calendar, "watchId" => $watchId));
        }

        public function publishSchedulerTriggeredEvent($action, $timeSinceLastExecution) : void {
            $this->publishEvent(Event::SchedulerTriggered, array("action" => $action, "timeSinceLastExecution" => $timeSinceLastExecution));
        }

        public function publishFlightArrivedEvent($flight, $tripId, $from, $to, $scheduledDeparture) : void {
            $this->publishEvent(Event::FlightArrived, array("flight" => $flight, "tripId" => $tripId, "from" => $from, "to" => $to, "scheduledDeparture" => $scheduledDeparture));
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
        case ApplicationStarted = 0;
        case SchedulerTriggered = 1;
        case CalendarWatchRenewing = 2;
        case VacationReset = 3;
        case CalendarChanged = 4;
        case FlightArrived = 5;
        case ActualWeatherForecastChanged = 6;
        case DaylightForecastChanged = 7;
        case HistoricalWeatherForecastChanged = 8;
        case PhotoInvalidated = 9;
        case AllHighlightsChanged = 10;
        case AlbumInvalidated = 11;
        case AllAlbumsInvalidated = 12;
        case CategoryInvalidated = 13;
        case AlbumUpdated = 14;
        case CategoryCreated = 15;
        case CategoryUpdated = 16;
        case PlaceUpdated = 17;
        case StatisticsChanged = 18;

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