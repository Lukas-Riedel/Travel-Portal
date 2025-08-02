<?php

    use Service\Service\Device\DeviceType;
    use Service\Service\Highlight\HighlightType;
        
    // TODO: Make sure messages contain as least information as possible (e.g., string placeId instead of PlaceIdentifier).
    // TODO: Go through every event and make sure it is fired meaningfuly (e.g., ForecastService shouldn't care about invalidating statistics)
    class EventPublisher {
        public function publishActualWeatherForecastUpdated($placeId, $start) {
            $this->publishLocalEvent(Event::ActualWeatherForecastUpdated, array("placeId" => $placeId, "start" => $start));
        }
        
        public function publishHistoricalWeatherForecastUpdated($placeId, $start) {
            $this->publishLocalEvent(Event::HistoricalWeatherForecastUpdated, array("placeId" => $placeId, "start" => $start));
        }
        
        public function publishDaylightForecastUpdated($placeId, $start, $end) {
            $this->publishLocalEvent(Event::DaylightForecastUpdated, array("placeId" => $placeId, "start" => $start, "end" => $end));
        }

        public function publishFitnessActivityDetectedEvent($start, $end) {
            $this->publishLocalEvent(Event::FitnessActivityDetected, array("start" => $start, "end" => $end));
        }

        public function publishCategoryCreatedEvent($categoryId) {
            $this->publishLocalEvent(Event::CategoryCreated, array("categoryId" => $categoryId));
        }

        public function publishAlbumInvalidatedEvent($albumId) {
            $this->publishLocalEvent(Event::AlbumInvalidated, array("albumId" => $albumId));
        }

        public function publishPhotoInvalidatedEvent($photoId) {
            $this->publishLocalEvent(Event::PhotoInvalidated, array("photoId" => $photoId));
        }

        public function publishCategoryInvalidatedEvent($categoryId) {
            $this->publishLocalEvent(Event::CategoryInvalidated, array("categoryId" => $categoryId));
        }

        public function publishAllAlbumsInvalidatedEvent() : void {
            $this->publishLocalEvent(Event::AllAlbumsInvalidated, NULL);
        }

        public function publishAllDynamicLabelsInvalidatedEvent() : void {
            $this->publishLocalEvent(Event::AllDynamicLabelsInvalidated, NULL);
        }

        public function publishAllHighlightsInvalidatedEvent() : void {
            $this->publishLocalEvent(Event::AllHighlightsInvalidated, NULL);
        }

        public function publishHighlightCreatedEvent(HighlightType $highlightType, string $entityId, string $highlightId) : void {
            $this->publishLocalEvent(Event::HighlightCreated, array("highlightType" => $highlightType->name, "entityId" => $entityId, "highlightId" => $highlightId));
        }        

        public function publishHighlightUpdatedEvent(HighlightType $highlightType, string $entityId, string $highlightId) : void {
            $this->publishLocalEvent(Event::HighlightUpdated, array("highlightType" => $highlightType->name, "entityId" => $entityId, "highlightId" => $highlightId));
        }

        public function publishHighlightRemovedEvent(HighlightType $highlightType, string $entityId, string $highlightId) : void {
            $this->publishLocalEvent(Event::HighlightRemoved, array("highlightType" => $highlightType->name, "entityId" => $entityId, "highlightId" => $highlightId));
        }

        public function publishAlbumUpdatedEvent($albumId) : void {
            $this->publishLocalEvent(Event::AlbumUpdated, array("albumId" => $albumId));
        }

        public function publishTripStatisticsInvalidatedEvent($tripId) : void {
            $this->publishLocalEvent(Event::TripStatisticsInvalidated, array("tripId" => $tripId));
        }
        
        public function publishYearStatisticsInvalidatedEvent($year) : void {
            $this->publishLocalEvent(Event::YearStatisticsInvalidated, array("year" => $year));
        }
        
        public function publishCategoryStatisticsInvalidatedEvent($categoryId) : void {
            $this->publishLocalEvent(Event::CategoryStatisticsInvalidated, array("categoryId" => $categoryId));
        }
        
        public function publishCategoryUpdatedEvent($categoryId) : void {
            $this->publishLocalEvent(Event::CategoryUpdated, array("categoryId" => $categoryId));
        }

        public function publishExpenseCreatedEvent($expenseId, $tripId) : void {
            $this->publishLocalEvent(Event::ExpenseCreated, array("expenseId" => $expenseId, "tripId" => $tripId));
        }

        public function publishExpenseUpdatedEvent($expenseId, $tripId) : void {
            $this->publishLocalEvent(Event::ExpenseUpdated, array("expenseId" => $expenseId, "tripId" => $tripId));
        }

        public function publishExpenseRemovedEvent($expenseId, $tripId) : void {
            $this->publishLocalEvent(Event::ExpenseRemoved, array("expenseId" => $expenseId, "tripId" => $tripId));
        }
        
        public function publishPlaceUpdatedEvent($placeId) : void {
            $this->publishLocalEvent(Event::PlaceUpdated, array("placeId" => $placeId));
        }
        
        public function publishPlaceCreatedEvent($placeId) : void {
            $this->publishLocalEvent(Event::PlaceCreated, array("placeId" => $placeId));
        }
        
        public function publishVacationResetEvent() : void {
            $this->publishLocalEvent(Event::VacationReset, NULL);
        }
        
        public function publishApplicationStartedEvent($tables) : void {
            $this->publishLocalEvent(Event::ApplicationStarted, array("tables" => $tables));
        }
        
        public function publishFlightEventCreatedEvent($tripId) : void {
            $this->publishLocalEvent(Event::FlightEventCreated, array("tripId" => $tripId));
        }
        
        public function publishFlightEventUpdatedEvent($tripId) : void {
            $this->publishLocalEvent(Event::FlightEventUpdated, array("tripId" => $tripId));
        }
        
        public function publishFlightEventDeletedEvent($tripId) : void {
            $this->publishLocalEvent(Event::FlightEventDeleted, array("tripId" => $tripId));
        }
        
        public function publishStayEventCreatedEvent($tripId) : void {
            $this->publishLocalEvent(Event::StayEventCreated, array("tripId" => $tripId));
        }
        
        public function publishStayEventUpdatedEvent($tripId) : void {
            $this->publishLocalEvent(Event::StayEventUpdated, array("tripId" => $tripId));
        }
        
        public function publishStayEventDeletedEvent($tripId) : void {
            $this->publishLocalEvent(Event::StayEventDeleted, array("tripId" => $tripId));
        }
        
        public function publishTripEventCreatedEvent($tripId) : void {
            $this->publishLocalEvent(Event::TripEventCreated, array("tripId" => $tripId));
        }
        
        public function publishTripEventUpdatedEvent($tripId) : void {
            $this->publishLocalEvent(Event::TripEventUpdated, array("tripId" => $tripId));
        }
        
        public function publishTripEventDeletedEvent($tripId) : void {
            $this->publishLocalEvent(Event::TripEventDeleted, array("tripId" => $tripId));
        }
        
        public function publishCalendarWatchRenewingEvent($calendar) : void {
            $this->publishLocalEvent(Event::CalendarWatchRenewing, array("calendar" => $calendar));
        }

        public function publishFitnessDataUpdatedEvent($start, $end) : void {
            $this->publishLocalEvent(Event::FitnessDataUpdated, array("start" => $start, "end" => $end));
        }

        public function publishSchedulerTriggeredEvent($action, $lastTriggered) : void {
            $this->publishLocalEvent(Event::SchedulerTriggered, array("action" => $action, "lastTriggered" => $lastTriggered));
        }

        public function publishFlightLoggedEvent($flight) : void {
            $this->publishLocalEvent(Event::FlightLogged, array("flight" => $flight));
        }

        public function publishFlightArrivedEvent($flight, $from, $to, $scheduledDeparture) : void {
            $this->publishLocalEvent(Event::FlightArrived, array("flight" => $flight, "from" => $from, "to" => $to, "scheduledDeparture" => $scheduledDeparture));
        }

        public function publishYearStatisticsUpdatedEvent($year) : void {
            $this->publishLocalEvent(Event::YearStatisticsUpdated, array("year" => $year));
        }

        public function publishCategoryStatisticsUpdatedEvent($categoryId) : void {
            $this->publishLocalEvent(Event::CategoryStatisticsUpdated, array("categoryId" => $categoryId));
        }

        public function publishTripStatisticsUpdatedEvent($tripId, $year) : void {
            $this->publishLocalEvent(Event::TripStatisticsUpdated, array("tripId" => $tripId, "year" => $year));
        }

        public function publishOverallStatisticsInvalidatedEvent() : void {
            $this->publishLocalEvent(Event::OverallStatisticsInvalidated, NULL);
        }

        public function publishTripUpdatedEvent($tripId) : void {
            $this->publishLocalEvent(Event::TripUpdated, array("tripId" => $tripId));
        }

        public function publishPlaceDeletedEvent($placeId) : void {
            $this->publishLocalEvent(Event::PlaceDeleted, array("placeId" => $placeId));
        }

        public function publishPlaceEventCreatedEvent($placeId) : void {
            $this->publishLocalEvent(Event::PlaceEventCreated, array("placeId" => $placeId));
        }

        public function publishPlaceEventUpdatedEvent($placeId) : void {
            $this->publishLocalEvent(Event::PlaceEventUpdated, array("placeId" => $placeId));
        }
        
        public function publishPlaceEventDeletedEvent($placeId) : void {
            $this->publishLocalEvent(Event::PlaceEventDeleted, array("placeId" => $placeId));
        }

        public function publishDataConsistencyScanTriggeredEvent() : void {
            $this->publishLocalEvent(Event::DataConsistencyScanTriggered, NULL);
        }

        public function publishInactiveDevicesInvalidatedEvent() : void {
            $this->publishLocalEvent(Event::InactiveDevicesInvalidated, NULL);
        }

        public function publishConfigurationEntryUpdated($key) : void {
            $this->publishLocalEvent(Event::ConfigurationEntryUpdated, array("key" => $key));
        }

        public function publishTimeTrackingEventsAuditTriggered() : void {
            $this->publishLocalEvent(Event::TimeTrackingEventsAuditTriggered, NULL);
        }

        public function publishEvent($event, $args) : void {
            switch ($event->getBroker()) {
                case EventBroker::CloudMessaging:
                    // TODO: Figure out how to resolve the device type and required roles - by the event type?
                    $this->publishCloudEvent($event, DeviceType::Portal, array("ADMIN"), $args);
                    break;
                case EventBroker::Local:
                    $this->publishLocalEvent($event, $args);
                    break;
            }
        }

        private function publishLocalEvent($event, $args) : void {
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

        private function publishCloudEvent($event, $deviceType, $requiredRoles, $args) {            
            global $cloudMessagingClient, $deviceService;
            $cloudMessagingClient->publishEvent($event, $args, array_map(fn($device) => $device->getToken(),
                $deviceService->getDevices($deviceType, $requiredRoles)));
        }
    }

    enum EventBroker {
        case Local;
        case CloudMessaging;
    }

    enum Event : int {
        // TODO: Invalidations first, then updates. Order this enum.
        // TODO: Unify Removed/Deleted.
        // TODO: Remove unused.
        case ApplicationStarted = -4;
        case HighlightCreated = -3;
        case DataConsistencyScanTriggered = -2;
        case HighlightUpdated = -1;
        case SchedulerTriggered = 0;
        case AllDynamicLabelsInvalidated = 1;
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
        case InactiveDevicesInvalidated = 48;
        case ConfigurationEntryUpdated = 49;
        case TimeTrackingEventsAuditTriggered = 50;

        case FitnessActivityDetected = 1000;

        case PhotosUploadingTriggered = 2000;
        case PhotoReplacingTriggered = 2001;

        case ProcessingStarted = 3000;
        case ProcessingEnded = 3001;

        public static function fromName($name) : ?Event {
            foreach (Event::cases() as $case) {
                if ($case->name === $name) {
                    return $case;
                }
            }
            return NULL;
        }

        public function getBroker() : EventBroker {
            if ($this->value >= 3000) {
                return EventBroker::CloudMessaging;
            }
            return EventBroker::Local;
        }
    }
?>