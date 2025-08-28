<?php

    namespace Core\Service\Device;

    use Core\Service\Authentication\AuthenticationService;

    class DeviceService {

        private const DEVICE_INACTIVITY_THRESHOLD = 30 * 86400;

        private readonly DeviceMapper $deviceMapper;

        public function __construct(\DatabaseProvider $databaseProvider, AuthenticationService $authenticationService) {
            $this->deviceMapper = new DeviceMapper($databaseProvider, $authenticationService);
        }
        
        public function getDevices(?DeviceType $deviceType, array $requiredRoles) : array {
            return $this->deviceMapper->selectDevices($deviceType, $requiredRoles);
        }

        public function registerOrUpdateDevice(DeviceType $deviceType, string $name, string $token, string $userId) : Device {
            $device = new Device($deviceType, $name, $token, $userId, time());
            $this->deviceMapper->deleteDevice($device);
            $this->deviceMapper->insertDevice($device);
            return $device;
        }

        public function unregisterInactiveDevices() : int {
            // TODO: Ping unregistered device so it can eventually register again.
            return $this->deviceMapper->deleteInactiveDevices(self::DEVICE_INACTIVITY_THRESHOLD);
        }
    }
?>