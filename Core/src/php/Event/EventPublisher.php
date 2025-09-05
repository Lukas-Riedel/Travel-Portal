<?php

    use Core\Service\Device\DeviceType;
    use Core\Service\Highlight\HighlightType;
        
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

        public function publishFitnessActivityDetectedEvent($intervals) {
            $this->publishEvent(Event::FitnessActivityDetected, array("intervals" => $intervals));
        }

        public function publishLocationUpdateDetectedEvent() : void {
            $this->publishEvent(Event::LocationUpdateDetected, null);
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
            $this->publishEvent(Event::AllAlbumsInvalidated, null);
        }

        public function publishAllDynamicLabelsInvalidatedEvent() : void {
            $this->publishEvent(Event::AllDynamicLabelsInvalidated, null);
        }

        public function publishAllHighlightsInvalidatedEvent() : void {
            $this->publishEvent(Event::AllHighlightsInvalidated, null);
        }

        public function publishHighlightCreatedEvent(HighlightType $highlightType, string $entityId, string $highlightId) : void {
            $this->publishEvent(Event::HighlightCreated, array("highlightType" => $highlightType->name, "entityId" => $entityId, "highlightId" => $highlightId));
        }        

        public function publishHighlightUpdatedEvent(HighlightType $highlightType, string $entityId, string $highlightId) : void {
            $this->publishEvent(Event::HighlightUpdated, array("highlightType" => $highlightType->name, "entityId" => $entityId, "highlightId" => $highlightId));
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
            $this->publishEvent(Event::VacationReset, null);
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
        
        public function publishFlightEventRemovedEvent($tripId) : void {
            $this->publishEvent(Event::FlightEventRemoved, array("tripId" => $tripId));
        }
        
        public function publishStayEventCreatedEvent($tripId) : void {
            $this->publishEvent(Event::StayEventCreated, array("tripId" => $tripId));
        }
        
        public function publishStayEventUpdatedEvent($tripId) : void {
            $this->publishEvent(Event::StayEventUpdated, array("tripId" => $tripId));
        }
        
        public function publishStayEventRemovedEvent($tripId) : void {
            $this->publishEvent(Event::StayEventRemoved, array("tripId" => $tripId));
        }
        
        public function publishTripEventCreatedEvent($tripId) : void {
            $this->publishEvent(Event::TripEventCreated, array("tripId" => $tripId));
        }
        
        public function publishTripEventUpdatedEvent($tripId) : void {
            $this->publishEvent(Event::TripEventUpdated, array("tripId" => $tripId));
        }
        
        public function publishTripEventRemovedEvent($tripId) : void {
            $this->publishEvent(Event::TripEventRemoved, array("tripId" => $tripId));
        }
        
        public function publishCalendarWatchRenewingEvent($calendar) : void {
            $this->publishEvent(Event::CalendarWatchRenewing, array("calendar" => $calendar));
        }

        public function publishFitnessDataUpdatedEvent($start, $end) : void {
            $this->publishEvent(Event::FitnessDataUpdated, array("start" => $start, "end" => $end));
        }

        public function publishSchedulerTriggeredEvent() : void {
            $this->publishEvent(Event::SchedulerTriggered, array());
        }

        public function publishFlightLoggedEvent($flight) : void {
            $this->publishEvent(Event::FlightLogged, array("flight" => $flight));
        }

        public function publishFlightArrivedEvent($flight, $from, $to, $scheduledDeparture) : void {
            $this->publishEvent(Event::FlightArrived, array("flight" => $flight, "from" => $from, "to" => $to, "scheduledDeparture" => $scheduledDeparture));
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
            $this->publishEvent(Event::OverallStatisticsInvalidated, null);
        }

        public function publishTripUpdatedEvent($tripId) : void {
            $this->publishEvent(Event::TripUpdated, array("tripId" => $tripId));
        }

        public function publishPlaceRemovedEvent($placeId) : void {
            $this->publishEvent(Event::PlaceRemoved, array("placeId" => $placeId));
        }

        public function publishPlaceEventCreatedEvent($placeId) : void {
            $this->publishEvent(Event::PlaceEventCreated, array("placeId" => $placeId));
        }

        public function publishPlaceEventUpdatedEvent($placeId) : void {
            $this->publishEvent(Event::PlaceEventUpdated, array("placeId" => $placeId));
        }
        
        public function publishPlaceEventRemovedEvent($placeId) : void {
            $this->publishEvent(Event::PlaceEventRemoved, array("placeId" => $placeId));
        }

        public function publishDataConsistencyScanTriggeredEvent() : void {
            $this->publishEvent(Event::DataConsistencyScanTriggered, null);
        }

        public function publishInactiveDevicesInvalidatedEvent() : void {
            $this->publishEvent(Event::InactiveDevicesInvalidated, null);
        }

        public function publishConfigurationEntryUpdated($key) : void {
            $this->publishEvent(Event::ConfigurationEntryUpdated, array("key" => $key));
        }

        public function publishTimeTrackingEventsAuditTriggered() : void {
            $this->publishEvent(Event::TimeTrackingEventsAuditTriggered, null);
        }

        public function publishEvent($event, $args) : void {
            global $cloudMessagingClient, $deviceService, $messagingClient, $databaseProvider;
            
            switch ($event->getTarget()) {
                case EventTarget::CloudMessaging:
                    // TODO: Figure out how to resolve the device type and required roles - introduce more "bit masks" to the event type backing value.
                    $cloudMessagingClient->publishEvent($event, $args, array_map(fn($device) => $device->getToken(),
                        $deviceService->getDevices(($event === Event::FitnessActivityDetected || $event === Event::LocationUpdateDetected) 
                            ? DeviceType::BridgeX : DeviceType::Portal, array("ADMIN"))));
                    break;
                case EventTarget::WorkerQueue:
                    $messagingClient->publishEvent($event, $args, WORKER_QUEUE_NAME);
                    break;
                case EventTarget::AgentQueue:
                    $messagingClient->publishEvent($event, $args, AGENT_QUEUE_NAME);
                    break;
            }
        }
    }

    enum EventTarget : int {
        case WorkerQueue = 1;
        case AgentQueue = 2;
        case CloudMessaging = 3;
    }

    enum Event : int {
        // RMQ
        case ApplicationStarted = 1400;
        case SchedulerTriggered = 1401;
        case CalendarWatchRenewing = 1402;
        case CalendarInvalidated = 1403;
        case HighlightCreated = 1404;

        case HighlightUpdated = 1305;
        case DataConsistencyScanTriggered = 1300;
        case ActualWeatherForecastUpdated = 1301;
        case DaylightForecastUpdated = 1302;
        case HistoricalWeatherForecastUpdated = 1303;
        case FlightArrived = 1304;
        case VacationReset = 1306;

        case AllDynamicLabelsInvalidated = 1200;
        case PhotoInvalidated = 1201;
        case AllHighlightsInvalidated = 1202;
        case AlbumInvalidated = 1203;
        case AllAlbumsInvalidated = 1204;
        case AlbumUpdated = 1205;
        case TripEventCreated = 1206;
        case PlaceEventCreated = 1207;
        case PlaceCreated = 1208;
        case PlaceUpdated = 1209;
        case FlightLogged = 1210;

        case CategoryInvalidated = 1100;
        case CategoryCreated = 1101;
        case CategoryUpdated = 1102;
        case ExpenseCreated = 1103;
        case ExpenseUpdated = 1104;
        case StayEventCreated = 1105;
        case StayEventUpdated = 1106;
        case TripUpdated = 1107;
        case TripEventUpdated = 1108;
        case PlaceEventUpdated = 1109;
        case ConfigurationEntryUpdated = 1110;
        case FlightEventCreated = 1111;

        case TimeTrackingEventsAuditTriggered = 1000;
        case InactiveDevicesInvalidated = 1001;
        case CategoryStatisticsInvalidated = 1002;
        case TripStatisticsInvalidated = 1003;
        case YearStatisticsInvalidated = 1004;
        case OverallStatisticsInvalidated = 1005;
        case CategoryStatisticsUpdated = 1006;
        case TripStatisticsUpdated = 1007;
        case YearStatisticsUpdated = 1008;
        case HighlightRemoved = 1009;
        case StayEventRemoved = 1010;
        case FlightEventRemoved = 1011;
        case ExpenseRemoved = 1012;
        case TripEventRemoved = 1013;
        case PlaceEventRemoved = 1014;
        case FitnessDataUpdated = 1015;
        case PlaceRemoved = 1016;
        case FlightEventUpdated = 1017;

        // Agent
        case PhotosUploadingTriggered = 2000;
        case PhotoReplacingTriggered = 2001;

        // FCM
        case ProcessingStarted = 3000;
        case ProcessingEnded = 3001;
        case FitnessActivityDetected = 3100;
        case LocationUpdateDetected = 3101;
        
        public function getTarget() : EventTarget {
            return EventTarget::from(floor($this->value / 1000));
        }

        public function getPriority() : int {
            return floor($this->value / 100) % 10;
        }

        public static function fromName($name) : ?Event {
            foreach (Event::cases() as $case) {
                if ($case->name === $name) {
                    return $case;
                }
            }
            return null;
        }
    }
?>