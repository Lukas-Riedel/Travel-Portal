<?php
    namespace Service\Service\Device;

    class DeviceMapper {
        
        private readonly \DatabaseProvider $databaseProvider;

        public function __construct(\DatabaseProvider $databaseProvider) {
            $this->databaseProvider = $databaseProvider;
        }

        public function selectAllDevices(DeviceType $deviceType) : array {
            $sql = <<<'SQL'
                SELECT *
                FROM device
                WHERE type = ?
            SQL;
            
            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($deviceType->value)
                ->getMappedResultSet(function($deviceRow) {
                    return new Device(DeviceType::from($deviceRow["type"]), $deviceRow["token"]);
                });
        }

        public function insertDevice(Device $device) : bool {
            $sql = <<<'SQL'
                INSERT INTO device (
                    type,
                    token,
                    last_seen
                )
                VALUES (
                    ?,
                    ?,
                    UNIX_TIMESTAMP()
                )
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($device->getType()->value, $device->getToken())
                ->execute() === 1;
        }

        public function deleteDevice(Device $device) : int {
            $sql = <<<'SQL'
                DELETE
                FROM device
                WHERE type = ?
                    AND token = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($device->getType()->value, $device->getToken())
                ->execute();
        }

        public function deleteInactiveDevices(int $inactivityThreshold) : int {
            $sql = <<<'SQL'
                DELETE
                FROM device
                WHERE last_seen + ? < UNIX_TIMESTAMP()
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($inactivityThreshold)
                ->execute();            
        }
    }