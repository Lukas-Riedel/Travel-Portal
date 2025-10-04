<?php

    namespace Core\Service\Device;

    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\TransactionManager;
    use Core\Common\CommonConstants;
    use Core\Service\Authentication\AuthenticationService;
    use Core\Service\Geocoding\GeocodingService;

    class DeviceService {

        private const DEVICE_INACTIVITY_THRESHOLD_SECONDS = CommonConstants::ONE_MONTH_SECONDS;

        private readonly DeviceMapper $deviceMapper;

        private readonly TransactionManager $transactionManager;

        private readonly GeocodingService $geocodingService;

        public function __construct(DatabaseClient $databaseClient, AuthenticationService $authenticationService,
            GeocodingService $geocodingService) {
            $this->deviceMapper = new DeviceMapper($databaseClient, $authenticationService);
            $this->transactionManager = $databaseClient;
            $this->geocodingService = $geocodingService;
        }
        
        public function getDevices(?DeviceType $deviceType, ?string $requiredRole) : array {
            return $this->deviceMapper->selectDevices($deviceType, $requiredRole);
        }

        public function getDevice(string $deviceId) : ?Device {
            return $this->deviceMapper->selectDevice($deviceId);
        }

        public function registerOrUpdateDevice(string $id, DeviceType $deviceType, string $name, mixed $data, string $userId) : Device {
            if (isset($data["latitude"]) && isset($data["longitude"]) && !isset($data["address"])) {
                $data["address"] = $this->geocodingService->getAddress($data["latitude"], $data["longitude"])?->getAddress();
            }

            $device = new Device($id, $deviceType, $name, $data, $userId, time());
            $this->transactionManager->executeAtomically(function() use ($device) {
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