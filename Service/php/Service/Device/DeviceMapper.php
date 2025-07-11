<?php
    namespace Service\Service\Device;

    class DeviceMapper {
        
        private readonly \DatabaseProvider $databaseProvider;

        public function __construct(\DatabaseProvider $databaseProvider) {
            $this->databaseProvider = $databaseProvider;
        }

        public function selectDevices(DeviceType $deviceType, array $requiredRoles) : array {
            $sql = <<<'SQL'
                SELECT *
                FROM device
                WHERE :CONDITIONS
            SQL;
            
            $whereClauseBuilder = $this->databaseProvider->whereClauseBuilder()
                ->withClause("type = ?", $deviceType->value);
            foreach ($requiredRoles as &$requiredRole) {
                $whereClauseBuilder->withClause("FIND_IN_SET(?, roles)", $requiredRole);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();
            
            return $this->databaseProvider
                ->statementBuilder($sql, $whereClause)
                ->getMappedResultSet(function($deviceRow) {
                    return new Device(DeviceType::from($deviceRow["type"]), $deviceRow["token"], explode(",", $deviceRow["roles"]));
                });
        }

        public function insertDevice(Device $device) : bool {
            $sql = <<<'SQL'
                INSERT INTO device (
                    type,
                    token,
                    roles,
                    last_seen
                )
                VALUES (
                    ?,
                    ?,
                    ?,
                    UNIX_TIMESTAMP()
                )
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($device->getType()->value, $device->getToken(), implode(",", $device->getRoles()))
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