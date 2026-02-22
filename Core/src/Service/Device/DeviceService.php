<?php
    namespace Core\Service\Device;

    use Common\Service\Authentication\UserRole;
    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\TransactionManager;
    use Core\Common\CommonConstants;
    use Core\Service\Authentication\AuthenticationService;

    class DeviceService {

        private const DEVICE_INACTIVITY_THRESHOLD_SECONDS = CommonConstants::ONE_MONTH_SECONDS;

        private readonly DeviceMapper $deviceMapper;

        private readonly TransactionManager $transactionManager;

        public function __construct(DatabaseClient $databaseClient, AuthenticationService $authenticationService) {
            $this->deviceMapper = new DeviceMapper($databaseClient, $authenticationService);
            $this->transactionManager = $databaseClient;
        }
        
        public function getDevices(?DeviceType $deviceType, ?UserRole $requiredRole) : array {
            return $this->deviceMapper->selectDevices($deviceType, $requiredRole);
        }

        public function getDevice(string $deviceId) : ?Device {
            return $this->deviceMapper->selectDevice($deviceId);
        }

        public function registerOrUpdateDevice(string $id, DeviceType $deviceType, string $name, mixed $data, string $userId) : Device {
            $device = new Device($id, $deviceType, $name, $data, $userId, time());
            $this->transactionManager->executeAtomically(function() use($device) {
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