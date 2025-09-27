<?php
    namespace Core\Service\Device;

    use Core\Client\Database\DatabaseClient;
    use Core\Service\Authentication\AuthenticationService;

    class DeviceMapper {
        
        private readonly DatabaseClient $databaseClient;

        private readonly AuthenticationService $authenticationService;

        public function __construct(DatabaseClient $databaseClient, AuthenticationService $authenticationService) {
            $this->databaseClient = $databaseClient;
            $this->authenticationService = $authenticationService;
        }

        public function selectDevices(?DeviceType $deviceType, ?string $requiredRole) : array {
            $sql = <<<'SQL'
                SELECT *
                FROM device
                WHERE :CONDITIONS
                ORDER BY last_seen DESC
            SQL;

            $whereClauseBuilder = $this->databaseClient->whereClauseBuilder();
            if ($requiredRole !== null) {
                $whereClauseBuilder->withClause("FIND_IN_SET(user_id, ?)", implode(",", $this->authenticationService->getUserIdsWithRole($requiredRole)));
            }
            if ($deviceType !== null) {
                $whereClauseBuilder->withClause("type = ?", $deviceType->value);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();
            
            return $this->databaseClient
                ->statementBuilder($sql, $whereClause)
                ->getMappedResultSet(function($deviceRow) {
                    return new Device($deviceRow["id"], DeviceType::from($deviceRow["type"]), $deviceRow["name"],
                        json_decode($deviceRow["data"], true), $deviceRow["user_id"], $deviceRow["last_seen"]);
                });
        }

        public function insertDevice(Device $device) : bool {
            $sql = <<<'SQL'
                INSERT INTO device (
                    id,
                    type,
                    name,
                    data,
                    user_id,
                    last_seen
                )
                VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($device->getId(), $device->getType()->value, $device->getName(), json_encode($device->getData()),
                    $device->getUserId(), $device->getLastSeen())
                ->execute() === 1;
        }

        public function deleteDevice(Device $device) : int {
            $sql = <<<'SQL'
                DELETE
                FROM device
                WHERE id = ?
                    AND type = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($device->getId(), $device->getType()->value)
                ->execute();
        }

        public function deleteInactiveDevices(int $inactivityThreshold) : int {
            $sql = <<<'SQL'
                DELETE
                FROM device
                WHERE last_seen + ? < UNIX_TIMESTAMP()
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($inactivityThreshold)
                ->execute();            
        }
    }