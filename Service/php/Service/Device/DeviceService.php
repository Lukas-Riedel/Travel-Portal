<?php

    namespace Service\Service\Device;

    class DeviceService {

        private const DEVICE_INACTIVITY_THRESHOLD = 30 * 86400;

        private readonly DeviceMapper $deviceMapper;

        public function __construct(\DatabaseProvider $databaseProvider) {
            $this->deviceMapper = new DeviceMapper($databaseProvider);
        }
        
        public function getAllDevices(DeviceType $deviceType) : array {
            return $this->deviceMapper->selectAllDevices($deviceType);
        }

        public function registerOrUpdateDevice(DeviceType $deviceType, string $token) : Device {
            $device = new Device($deviceType, $token);
            $this->deviceMapper->deleteDevice($device);
            $this->deviceMapper->insertDevice($device);
            return $device;
        }

        public function unregisterInactiveDevices() {
            // TODO: Ping unregistered device so it can eventually register again.
            return $this->deviceMapper->deleteInactiveDevices(self::DEVICE_INACTIVITY_THRESHOLD);
        }
    }
?>