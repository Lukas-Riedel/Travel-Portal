<?php
    namespace Core\Service\Device;

    use Core\Service\Authentication\AuthenticationService;

    class DeviceMapper {
        
        private readonly \DatabaseProvider $databaseProvider;

        private readonly AuthenticationService $authenticationService;

        public function __construct(\DatabaseProvider $databaseProvider, AuthenticationService $authenticationService) {
            $this->databaseProvider = $databaseProvider;
            $this->authenticationService = $authenticationService;
        }

        public function selectDevices(?DeviceType $deviceType, array $requiredRoles) : array {
            $sql = <<<'SQL'
                SELECT *
                FROM device
                WHERE :CONDITIONS
            SQL;

            $whereClauseBuilder = $this->databaseProvider->whereClauseBuilder()
                ->withClause("FIND_IN_SET(user_id, ?)", implode(",", array_map(fn($user) => $user->getId(), $this->authenticationService->getUsersWithRoles($requiredRoles))));
            if ($deviceType !== null) {
                $whereClauseBuilder->withClause("type = ?", $deviceType->value);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();
            
            return $this->databaseProvider
                ->statementBuilder($sql, $whereClause)
                ->getMappedResultSet(function($deviceRow) {
                    return new Device(DeviceType::from($deviceRow["type"]), $deviceRow["name"], $deviceRow["token"], $deviceRow["user_id"], $deviceRow["last_seen"]);
                });
        }

        public function insertDevice(Device $device) : bool {
            $sql = <<<'SQL'
                INSERT INTO device (
                    type,
                    name,
                    token,
                    user_id,
                    last_seen
                )
                VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($device->getType()->value, $device->getName(), $device->getToken(), $device->getUserId(), $device->getLastSeen())
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