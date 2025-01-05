<?php
    class EventPublisher {
        public function publishActualWeatherForecastChanged($placeId, $timestamp) {
            $this->publishEvent(Event::ActualWeatherForecastChanged, array("placeId" => $placeId, "timestamp" => $timestamp));
        }
        
        public function publishHistoricalWeatherForecastChanged($placeId, $timestamp) {
            $this->publishEvent(Event::HistoricalWeatherForecastChanged, array("placeId" => $placeId, "timestamp" => $timestamp));
        }
        
        public function publishDaylightForecastChanged($placeId, $timestamp) {
            $this->publishEvent(Event::DaylightForecastChanged, array("placeId" => $placeId, "timestamp" => $timestamp));
        }

        public function publishMovementDetectedEvent($start, $end) {
            $this->publishEvent(Event::MovementDetected, array("start" => $start, "end" => $end));
        }

        public function publishCategoryCreatedEvent($categoryId) {
            $this->publishEvent(Event::CategoryCreated, array("categoryId" => $categoryId));
        }

        public function publishAlbumChangedEvent($albumId) {
            $this->publishEvent(Event::AlbumChanged, array("albumId" => $albumId));
        }

        public function publishHighlightChangedEvent($photoId) {
            $this->publishEvent(Event::HighlightChanged, array("photoId" => $photoId));
        }

        public function publishPlaceCategoriesChangedEvent($placeId) {
            $this->publishEvent(Event::PlaceCategoriesChanged, array("placeId" => $placeId));
        }

        public function publishAllAlbumsChangedEvent() : void {
            $this->publishEvent(Event::AllAlbumsChanged, NULL);
        }

        public function publishAllHighlightsChangedEvent() : void {
            $this->publishEvent(Event::AllHighlightsChanged, NULL);
        }

        public function publishAlbumPhotosChangedEvent($albumId) : void {
            $this->publishEvent(Event::AlbumPhotosChanged, array("albumId" => $albumId));
        }

        public function publishTripStatisticsChangedEvent($tripId) : void {
            $this->publishEvent(Event::StatisticsChanged, array("tripId" => $tripId));
        }
        
        public function publishYearStatisticsChangedEvent($year) : void {
            $this->publishEvent(Event::StatisticsChanged, array("year" => $year));
        }
        
        public function publishCategoryStatisticsChangedEvent($categoryId) : void {
            $this->publishEvent(Event::StatisticsChanged, array("categoryId" => $categoryId));
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
        case VacationReset = 2;
        case CalendarChanged = 3;
        case FlightArrived = 4;
        case ActualWeatherForecastChanged = 5;
        case DaylightForecastChanged = 6;
        case HistoricalWeatherForecastChanged = 7;
        case HighlightChanged = 8;
        case AllHighlightsChanged = 9;
        case AlbumChanged = 10;
        case AllAlbumsChanged = 11;
        case PlaceCategoriesChanged = 12;
        case AlbumPhotosChanged = 13;
        case CategoryCreated = 14;
        case StatisticsChanged = 15;

        case MovementDetected = 100;

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