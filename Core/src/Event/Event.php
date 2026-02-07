<?php
    namespace Core\Event;

    use Common\Service\Authentication\UserRole;
    use Core\Client\Calendar\Calendar;
    use Core\Service\Device\DeviceType;
    use Core\Service\Highlight\HighlightType;

    abstract class Event implements \JsonSerializable {
        private readonly string $name;
        private readonly array $args;

        public function __construct(string $name, array $args) {
            $this->name = $name;
            $this->args = $args;
        }

        public function getName() : string {
            return $this->name;
        }

        public function getArgs() : array {
            return $this->args;
        }

        public static function SchedulerTriggered() : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Highest, array());
        }

        public static function CalendarWatchRenewing(string $calendar) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Highest, array("calendar" => Calendar::from($calendar)->value));
        }

        public static function CalendarInvalidated(string $calendar) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Highest, array("calendar" => Calendar::from($calendar)->value));
        }

        public static function CalendarInvalidating(string $calendar, int $ttl) : Event {
            return new WebhookEvent(Event::CalendarInvalidated($calendar), $ttl);
        }

        public static function HighlightCreated(string $highlightType, string $entityId, string $highlightId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Highest, array("highlightType" => HighlightType::from($highlightType)->value, "entityId" => $entityId, "highlightId" => $highlightId));
        }

        public static function HighlightAttributesSettingTriggered(string $highlightId) : Event {
            return new CortexEvent(Event::getEventName(), EventPriority::High, array("highlightId" => $highlightId));
        }

        public static function HighlightUpdated(string $highlightType, string $entityId, string $highlightId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::High, array("highlightType" => HighlightType::from($highlightType)->value, "entityId" => $entityId, "highlightId" => $highlightId));
        }

        public static function DataConsistencyScanTriggered() : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::High, array());
        }

        public static function ActualWeatherForecastUpdated(string $placeId, int $start) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::High, array("placeId" => $placeId, "start" => $start));
        }

        public static function DaylightForecastUpdated(string $placeId, int $start, int $end,) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::High, array("placeId" => $placeId, "start" => $start, "end" => $end));
        }

        public static function HistoricalWeatherForecastUpdated(string $placeId, int $start) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::High, array("placeId" => $placeId, "start" => $start));
        }

        public static function FlightArrived(string $flight, string $from, string $to, int $scheduledDeparture) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::High, array("flight" => $flight, "from" => $from, "to" => $to, "scheduledDeparture" => $scheduledDeparture));
        }

        public static function VacationReset() : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::High, array());
        }

        public static function AllDynamicLabelsInvalidated() : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Medium, array());
        }

        public static function PhotoInvalidated(string $photoId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Medium, array("photoId" => $photoId));
        }

        public static function AllHighlightsInvalidated() : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Medium, array());
        }

        public static function AlbumInvalidated(string $albumId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Medium, array("albumId" => $albumId));
        }

        public static function AllAlbumsInvalidated() : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Medium, array());
        }

        public static function AlbumUpdated(string $albumId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Medium, array("albumId" => $albumId));
        }

        public static function TripEventCreated(string $tripId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Medium, array("tripId" => $tripId));
        }

        public static function PlaceEventCreated(string $placeId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Medium, array("placeId" => $placeId));
        }

        public static function PlaceCreated(string $placeId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Medium, array("placeId" => $placeId));
        }

        public static function PlaceUpdated(string $placeId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Medium, array("placeId" => $placeId));
        }

        public static function FlightLogged(string $flight, string $from, string $to, int $scheduledDeparture,
            int $scheduledArrival, int $actualArrival, string $timezone) : Event {
            return (new CompositeEvent(Event::getEventName(), array("flight" => $flight, "from" => $from, "to" => $to, "scheduledDeparture" => $scheduledDeparture, 
                "scheduledArrival" => $scheduledArrival, "actualArrival" => $actualArrival, "timezone" => $timezone)))
                ->addCloudMessagingEvent(array(UserRole::EventFlightLoggedRead), array(DeviceType::Portal, DeviceType::BridgeX))
                ->addWorkerEvent(EventPriority::Medium);
        }

        public static function CategoryInvalidated(string $categoryId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Low, array("categoryId" => $categoryId));
        }

        public static function CategoryCreated(string $categoryId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Low, array("categoryId" => $categoryId));
        }

        public static function CategoryUpdated(string $categoryId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Low, array("categoryId" => $categoryId));
        }

        public static function CategoryRenamed(string $categoryId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Highest, array("categoryId" => $categoryId));
        }

        public static function ExpenseCreated(string $expenseId, string $tripId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Low, array("expenseId" => $expenseId, "tripId" => $tripId));
        }

        public static function ExpenseUpdated(string $expenseId, string $tripId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Low, array("expenseId" => $expenseId, "tripId" => $tripId));
        }

        public static function StayEventCreated(string $tripId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Low, array("tripId" => $tripId));
        }

        public static function StayEventUpdated(string $tripId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Low, array("tripId" => $tripId));
        }

        public static function TripUpdated(string $tripId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Low, array("tripId" => $tripId));
        }

        public static function TripEventUpdated(string $tripId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Low, array("tripId" => $tripId));
        }

        public static function PlaceEventUpdated(string $placeId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Low, array("placeId" => $placeId));
        }

        public static function ConfigurationEntryUpdated(string $key) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Low, array("key" => $key));
        }

        public static function FlightEventCreated(string $tripId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Low, array("tripId" => $tripId));
        }

        public static function TimeTrackingEventsAuditTriggered() : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Lowest, array());
        }

        public static function InactiveDevicesInvalidated() : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Lowest, array());
        }

        public static function CategoryStatisticsInvalidated(string $categoryId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Lowest, array("categoryId" => $categoryId));
        }

        public static function TripStatisticsInvalidated(string $tripId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Lowest, array("tripId" => $tripId));
        }

        public static function YearStatisticsInvalidated(int $year) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Lowest, array("year" => $year));
        }

        public static function OverallStatisticsInvalidated() : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Lowest, array());
        }

        public static function CategoryStatisticsUpdated(string $categoryId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Lowest, array("categoryId" => $categoryId));
        }

        public static function TripStatisticsUpdated(string $tripId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Lowest, array("tripId" => $tripId));
        }

        public static function YearStatisticsUpdated(int $year) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Lowest, array("year" => $year));
        }

        public static function HighlightRemoved(string $highlightType, string $entityId, string $highlightId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Lowest, array("highlightType" => HighlightType::from($highlightType)->value, "entityId" => $entityId, "highlightId" => $highlightId));
        }

        public static function StayEventRemoved(string $tripId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Lowest, array("tripId" => $tripId));
        }

        public static function FlightEventRemoved(string $tripId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Lowest, array("tripId" => $tripId));
        }

        public static function ExpenseRemoved(string $expenseId, string $tripId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Lowest, array("expenseId" => $expenseId, "tripId" => $tripId));
        }

        public static function TripEventRemoved(string $tripId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Lowest, array("tripId" => $tripId));
        }

        public static function PlaceEventRemoved(string $placeId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Lowest, array("placeId" => $placeId));
        }

        public static function FitnessDataUpdated(int $start, int $end) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Lowest, array("start" => $start, "end" => $end));
        }

        public static function PlaceRemoved(string $placeId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Lowest, array("placeId" => $placeId));
        }

        public static function FlightEventUpdated(string $placeId) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Lowest, array("placeId" => $placeId));
        }

        public static function OpenLineageEventPublished(mixed $event) : Event {
            return new WorkerEvent(Event::getEventName(), EventPriority::Lowest, array("event" => $event));
        }

        public static function PhotosUploadingTriggered(string $agentId, string $placeId, string $placeName, string $path, ?string $albumId = null, ?int $timestamp = null, ?int $mainPhotoPosition = null) : Event {
            return new AgentEvent(Event::getEventName(), $agentId, array("placeId" => $placeId, "placeName" => $placeName, "path" => $path, "albumId" => $albumId, "timestamp" => $timestamp, "mainPhotoPosition" => $mainPhotoPosition));
        }

        public static function PhotoReplacingTriggered(string $agentId, string $placeId, string $placeName, string $albumId, string $replacedPhotoId, string $path) : Event {
            return new AgentEvent(Event::getEventName(), $agentId, array("placeId" => $placeId, "placeName" => $placeName, "albumId" => $albumId, "replacedPhotoId" => $replacedPhotoId, "path" => $path));
        }

        public static function FolderSynchronizationRequested(string $agentId, string $path, int $expiration) : Event {
            return new AgentEvent(Event::getEventName(), $agentId, array("path" => $path, "expiration" => $expiration));
        }

        public static function HighlightsSelectingTriggered(string $highlightType, string $entityId, string $entityName, int $highlightsCount, ?bool $highlightsRemovalAllowed = null, ?bool $isExplicit = false) : Event {
            return new CortexEvent(Event::getEventName(), $isExplicit === true ?  EventPriority::Highest : EventPriority::Medium, array("highlightType" => $highlightType, "entityId" => $entityId, "entityName" => $entityName, "highlightsCount" => $highlightsCount, "highlightsRemovalAllowed" => $highlightsRemovalAllowed, "isExplicit" => $isExplicit));
        }

        public static function NewDataConsistencyIssuesDetected(int $count) : Event {
            return new CloudMessagingEvent(Event::getEventName(), array(UserRole::EventNewDataConsistencyIssueDetectedRead), array(DeviceType::Portal, DeviceType::BridgeX), array("count" => $count));
        }

        public static function ProcessingStarted(string $name, mixed $args) : Event {
            $requiredrole = (new \ReflectionEnum(UserRole::class))->getCase($name . "EventProcessingStartedRead")->getValue();
            return new CloudMessagingEvent(Event::getEventName(), array($requiredrole), array(DeviceType::Portal, DeviceType::BridgeX), array("name" => $name, "args" => $args));
        }

        public static function ProcessingEnded(string $name, mixed $args) : Event {
            $requiredrole = (new \ReflectionEnum(UserRole::class))->getCase($name . "EventProcessingEndedRead")->getValue();
            return new CloudMessagingEvent(Event::getEventName(), array($requiredrole), array(DeviceType::Portal, DeviceType::BridgeX), array("name" => $name, "args" => $args));
        }

        public static function ProcessingFailed(string $name, mixed $args) : Event {
            $requiredrole = (new \ReflectionEnum(UserRole::class))->getCase($name . "EventProcessingFailedRead")->getValue();
            return (new CompositeEvent(Event::getEventName(), array("name" => $name, "args" => $args)))
                ->addCloudMessagingEvent(array($requiredrole), array(DeviceType::Portal, DeviceType::BridgeX))
                ->addWorkerEvent(EventPriority::Highest);
        }

        public static function FitnessActivityDetected(array $intervals) : Event {
            return new CloudMessagingEvent(Event::getEventName(), array(UserRole::EventFitnessActivityDetectedRead), array(DeviceType::BridgeX), array("intervals" => $intervals));
        }

        public static function DeviceLogOnRequested() : Event {
            return new CloudMessagingEvent(Event::getEventName(), array(UserRole::EventDeviceLogOnRequestedRead), array(DeviceType::BridgeX), array());
        }

        private static function getEventName() : string {
            return debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]["function"];
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>