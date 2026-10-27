<?php
    namespace Core\Service\Lifestyle;

    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\WhereClauseBuilder;

    class LifestyleMapper {

        private readonly DatabaseClient $databaseClient;

        public function __construct(DatabaseClient $databaseClient) {
            $this->databaseClient = $databaseClient;
        }

        public function selectLifestyleEvents(?LifestyleEventType $type, ?string $from, ?string $to) : array {
            $sql = <<<'SQL'
                SELECT *
                FROM lifestyle
                WHERE :CONDITIONS
                ORDER BY timestamp DESC,
                    type DESC
            SQL;

            $whereClauseBuilder = new WhereClauseBuilder();
            if ($from !== null) {
                $whereClauseBuilder->withClause("timestamp >= ?", $from);
            }
            if ($to !== null) {
                $whereClauseBuilder->withClause("timestamp < ?", $to);
            }
            if ($type !== null) {
                $whereClauseBuilder->withClause("type = ?", $type->value);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            return $this->databaseClient
                ->statementBuilder($sql, $whereClause)
                ->getMappedResultSet(function($lifestyleEventRow) {
                    return new LifestyleEvent($lifestyleEventRow["id"], LifestyleEventType::from($lifestyleEventRow["type"]), intval($lifestyleEventRow["timestamp"]),
                        $lifestyleEventRow["content"], $lifestyleEventRow["calories"] === null ? null : floatval($lifestyleEventRow["calories"]));
                });
        }       

        public function insertLifestyleEvent(LifestyleEvent $lifestyleEvent) : bool {
            $sql = <<<'SQL'
                INSERT INTO lifestyle (
                    timestamp,
                    type,
                    content,
                    calories
                )
                VALUES (
                    ?,
                    ?,
                    ?,
                    ?
                )
                RETURNING id
            SQL;

            $id = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($lifestyleEvent->getTimestamp(), $lifestyleEvent->getType()->value, $lifestyleEvent->getContent(), $lifestyleEvent->getCalories())
                ->getSingleColumn("id");
                
            if ($id === null) {
                return false;
            }

            $lifestyleEvent->setId($id);
            return true;
        }

        public function deleteLifestyleEvent(string $eventId) : int {
            $sql = <<<'SQL'
                DELETE
                FROM lifestyle
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($eventId)
                ->execute();
        }

        public function deleteStaleLifestyleEvents(int $lifestyleRetentionDays) : int {
            $sql = <<<'SQL'
                DELETE
                FROM lifestyle
                WHERE timestamp <= (ROUND(EXTRACT(EPOCH FROM NOW())) - ? * 86400)
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($lifestyleRetentionDays)
                ->execute();
        }
    }
?>
