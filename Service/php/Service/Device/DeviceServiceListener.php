<?php
    namespace Service\Service\Device;

    use Scheduler;
    use Service\Service\Highlight\HighlightType;
    use Service\Service\Place\PlaceIdentifier;
    use Service\Service\Place\PlaceService;

    class DeviceServiceListener {
        
        private const UNREGISTER_INACTIVE_DEVICES_ACTION_NAME = "UNREGISTER_INACTIVE_DEVICES";
        private const UNREGISTER_INACTIVE_DEVICES_ACTION_INTERVAL = 86400;

        private readonly DeviceService $deviceService;

        private readonly Scheduler $scheduler;

        public function __construct(DeviceService $deviceService, Scheduler $scheduler) {
            $this->deviceService = $deviceService;
            $this->scheduler = $scheduler;
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($message["action"] === self::UNREGISTER_INACTIVE_DEVICES_ACTION_NAME 
                && time() - $message["lastTriggered"] > self::UNREGISTER_INACTIVE_DEVICES_ACTION_INTERVAL) {
                $this->deviceService->unregisterInactiveDevices();
                $this->scheduler->recordEventsTriggered(self::UNREGISTER_INACTIVE_DEVICES_ACTION_NAME);
            }
        }
    }
?>