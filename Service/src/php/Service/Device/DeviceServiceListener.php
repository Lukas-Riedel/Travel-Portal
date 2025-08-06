<?php
    namespace Service\Service\Device;

    class DeviceServiceListener {
        
        private const UNREGISTER_INACTIVE_DEVICES_ACTION_NAME = "UNREGISTER_INACTIVE_DEVICES";
        private const UNREGISTER_INACTIVE_DEVICES_ACTION_INTERVAL = 86400;

        private readonly DeviceService $deviceService;

        private readonly \EventPublisher $eventPublisher;
        private readonly \Scheduler $scheduler;

        public function __construct(DeviceService $deviceService, \EventPublisher $eventPublisher, \Scheduler $scheduler) {
            $this->deviceService = $deviceService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function onInactiveDevicesInvalidated(mixed $message) : void {
            $this->deviceService->unregisterInactiveDevices();
        }

        public function onSchedulerTriggered(mixed $message) : void {
            foreach ($message["actions"] as &$action) {
                if ($action["name"] === self::UNREGISTER_INACTIVE_DEVICES_ACTION_NAME 
                    && time() - $action["lastTriggered"] > self::UNREGISTER_INACTIVE_DEVICES_ACTION_INTERVAL) {
                    $this->eventPublisher->publishInactiveDevicesInvalidatedEvent();
                    $this->scheduler->recordEventsTriggered(self::UNREGISTER_INACTIVE_DEVICES_ACTION_NAME);
                }
            }
        }
    }
?>