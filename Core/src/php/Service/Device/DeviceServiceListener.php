<?php
    namespace Core\Service\Device;

    use Core\Common\CommonConstants;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;
    use Core\Service\Trip\TripService;
    use Core\Service\Trip\TripSortingStrategy;

    class DeviceServiceListener {
        
        private const UNREGISTER_INACTIVE_DEVICES_ACTION_NAME = "UNREGISTER_INACTIVE_DEVICES";
        private const UNREGISTER_INACTIVE_DEVICES_ACTION_INTERVAL = CommonConstants::ONE_DAY_SECONDS;

        private const REQUEST_DEVICE_LOG_ON_ACTION_NAME = "REQUEST_DEVICE_LOG_ON";
        private const REQUEST_DEVICE_LOG_ON_ACTION_INTERVAL = 15 * 60;

        private readonly DeviceService $deviceService;

        private readonly TripService $tripService;

        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        public function __construct(DeviceService $deviceService, TripService $tripService, EventPublisher $eventPublisher, Scheduler $scheduler) {
            $this->deviceService = $deviceService;
            $this->tripService = $tripService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function onInactiveDevicesInvalidated(mixed $message) : void {
            $this->deviceService->unregisterInactiveDevices();
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($this->scheduler->requestExecution(self::UNREGISTER_INACTIVE_DEVICES_ACTION_NAME, self::UNREGISTER_INACTIVE_DEVICES_ACTION_INTERVAL)) {
                $this->eventPublisher->publish(Event::InactiveDevicesInvalidated());
            }

            if ($this->scheduler->requestExecution(self::REQUEST_DEVICE_LOG_ON_ACTION_NAME, self::REQUEST_DEVICE_LOG_ON_ACTION_INTERVAL)) {
                $trips = $this->tripService->getRegularTrips(null, null, null, array(), TripSortingStrategy::OldestDescending);
                foreach ($trips as $trip) {
                    if ($trip->isCurrent() && !$this->tripService->isDayTripsTrip($trip)) {
                        $this->eventPublisher->publish(Event::DeviceLogOnRequested());
                        break;
                    }
                }
            }
        }
    }
?>