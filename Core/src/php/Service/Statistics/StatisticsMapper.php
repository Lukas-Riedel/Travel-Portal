<?php
    namespace Core\Service\Statistics;

    class StatisticsMapper {

        private readonly \DatabaseProvider $databaseProvider;

        public function __construct(\DatabaseProvider $databaseProvider) {
            $this->databaseProvider = $databaseProvider;
        }

        public function selectStatisticsRecords(StatisticsType $statisticsType, ?string $entityId) : array {
            $sql = <<<SQL
                SELECT *
                FROM {$statisticsType->getTableName()}
                WHERE :CONDITIONS
            SQL;
            
            $whereClauseBuilder = $this->databaseProvider->whereClauseBuilder();
            if ($entityId !== null) {
                $whereClauseBuilder->withClause("id = ?", $entityId);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();
            
            return $this->databaseProvider
                ->statementBuilder($sql, $whereClause)
                ->getMappedResultSet(function($statisticsRow) {
                    return new Statistics($statisticsRow["name"], json_decode($statisticsRow["value"], true), StatisticsUnit::from($statisticsRow["unit"]));
                });
        }

        public function insertStatisticsRecord(StatisticsType $statisticsType, Statistics $statistics, ?string $entityId) : bool {
            if ($entityId === null) {
                $sql = <<<SQL
                    INSERT INTO {$statisticsType->getTableName()} (
                        name, 
                        value, 
                        unit,
                        last_update
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
                    ->withParameters($statistics->getName(), json_encode($statistics->getValue(), JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG),
                        $statistics->getUnit()->value)
                    ->execute() === 1;
            }
            else {
                $sql = <<<SQL
                    INSERT INTO {$statisticsType->getTableName()} (
                        id,
                        name, 
                        value, 
                        unit,
                        last_update
                    ) 
                    VALUES (
                        ?, 
                        ?, 
                        ?,
                        ?,
                        UNIX_TIMESTAMP()
                    )
                SQL;
    
                return $this->databaseProvider
                    ->statementBuilder($sql)
                    ->withParameters($entityId, $statistics->getName(), json_encode($statistics->getValue(), 
                        JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG), $statistics->getUnit()->value)
                    ->execute() === 1;
            }
        }

        public function deleteAllStatisticsRecords(StatisticsType $statisticsType, ?string $entityId) : int {
            if ($entityId === null) {
                $sql = <<<SQL
                    DELETE
                    FROM {$statisticsType->getTableName()}
                SQL;
                
                return $this->databaseProvider
                    ->statementBuilder($sql)
                    ->execute();
            }
            else {
                $sql = <<<SQL
                    DELETE
                    FROM {$statisticsType->getTableName()}
                    WHERE id = ?
                SQL;

                return $this->databaseProvider
                    ->statementBuilder($sql)
                    ->withParameters($entityId)
                    ->execute();
            }
        }
    }
?>