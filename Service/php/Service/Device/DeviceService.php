<?php

    namespace Service\Service\Device;

    class DeviceService {

        private const DEVICE_INACTIVITY_THRESHOLD = 30 * 86400;

        private readonly DeviceMapper $deviceMapper;

        public function __construct(\DatabaseProvider $databaseProvider) {
            $this->deviceMapper = new DeviceMapper($databaseProvider);
        }
        
        public function getDevices(DeviceType $deviceType, array $requiredRoles) : array {
            return $this->deviceMapper->selectDevices($deviceType, $requiredRoles);
        }

        public function registerOrUpdateDevice(DeviceType $deviceType, string $token, array $roles) : Device {
            $device = new Device($deviceType, $token, $roles);
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