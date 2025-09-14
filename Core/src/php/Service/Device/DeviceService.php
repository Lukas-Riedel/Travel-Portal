<?php

    namespace Core\Service\Device;

    use Core\Common\CommonConstants;
    use Core\Service\Authentication\AuthenticationService;

    class DeviceService {

        private const DEVICE_INACTIVITY_THRESHOLD_SECONDS = CommonConstants::ONE_MONTH_SECONDS;

        private readonly DeviceMapper $deviceMapper;

        private readonly \DatabaseProvider $databaseProvider;

        public function __construct(\DatabaseProvider $databaseProvider, AuthenticationService $authenticationService) {
            $this->deviceMapper = new DeviceMapper($databaseProvider, $authenticationService);
            $this->databaseProvider = $databaseProvider;
        }
        
        public function getDevices(?DeviceType $deviceType, ?string $requiredRole) : array {
            return $this->deviceMapper->selectDevices($deviceType, $requiredRole);
        }

        public function registerOrUpdateDevice(string $id, DeviceType $deviceType, string $name, mixed $data, string $userId) : Device {
            $device = new Device($id, $deviceType, $name, $data, $userId, time());
            $this->databaseProvider->executeAtomically(function() use ($device) {
                $this->deviceMapper->deleteDevice($device);
                $this->deviceMapper->insertDevice($device);
            });
            return $device;
        }

        public function unregisterInactiveDevices() : int {
            return $this->deviceMapper->deleteInactiveDevices(self::DEVICE_INACTIVITY_THRESHOLD_SECONDS);
        }
    }
?>