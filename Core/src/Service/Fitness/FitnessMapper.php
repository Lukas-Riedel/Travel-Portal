<?php
    namespace Core\Service\Fitness;

    use Core\Client\Database\DatabaseClient;

    class FitnessMapper {

        private readonly DatabaseClient $databaseClient;
        private readonly int $updateThresholdDays;

        public function __construct(DatabaseClient $databaseClient, int $updateThresholdDays) {
            $this->databaseClient = $databaseClient;
            $this->updateThresholdDays = $updateThresholdDays;
        }

        public function selectAverageFitnessRecordForInterval(int $start, int $end) : Fitness { 
            $sql = <<<'SQL'
                SELECT ROUND(SUM(steps) / COUNT(DISTINCT FLOOR(timestamp / 86400))) AS steps,
                    ROUND(SUM(seconds) / COUNT(DISTINCT FLOOR(timestamp / 86400))) AS seconds,
                    ROUND(SUM(distance) / COUNT(DISTINCT FLOOR(timestamp / 86400))) AS distance
                FROM fitness
                WHERE timestamp >= ?
                    AND timestamp < ?
            SQL;

            $fitnessRow = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($start, $end)
                ->getSingleRow();

            return new Fitness(intval($fitnessRow["steps"]), intval($fitnessRow["seconds"]), doubleval($fitnessRow["distance"]));
        }
        
        public function selectTimeBasedFitnessRecordsPerDayForInterval(int $start, int $end, FitnessSortingStrategy $fitnessSortingStrategy) : array {
            $sql = <<<SQL
                SELECT *
                FROM (
                    SELECT timestamp - (timestamp % 86400) AS timestamp,
                        SUM(steps) AS steps,
                        SUM(seconds) AS seconds,
                        SUM(distance) AS distance
                    FROM fitness
                    GROUP BY timestamp - (timestamp % 86400)
                ) x
                WHERE timestamp >= ?
                    AND timestamp < ?
                {$fitnessSortingStrategy->getOrderByClause()}
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($start, $end)
                ->getMappedResultSet(function($fitnessRow) {
                    return new TimeBasedFitness(intval($fitnessRow["timestamp"]), intval($fitnessRow["steps"]), intval($fitnessRow["seconds"]), doubleval($fitnessRow["distance"]));
                });
        }

        public function selectFitnessRecordForInterval(int $start, int $end) : Fitness { 
            $sql = <<<'SQL'
                SELECT SUM(steps) AS steps,
                    SUM(seconds) AS seconds,
                    SUM(distance) AS distance
                FROM fitness
                WHERE timestamp >= ?
                    AND timestamp < ?
            SQL;

            $fitnessRow = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($start, $end)
                ->getSingleRow();

            return new Fitness(intval($fitnessRow["steps"]), intval($fitnessRow["seconds"]), doubleval($fitnessRow["distance"]));
        }

        public function selectConflictingFitnessRecords() : array {
            $sql = <<<'SQL'
                SELECT *
                FROM fitness_conflict
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->getMappedResultSet(function($fitnessRow) {
                    return new TimeBasedFitness(intval($fitnessRow["timestamp"]), intval($fitnessRow["steps"]), 
                        intval($fitnessRow["seconds"]), doubleval($fitnessRow["distance"]));
                });
        }

        public function selectFitnessRecord(int $timestamp) : ?Fitness {
            $sql = <<<'SQL'
                SELECT *
                FROM fitness
                WHERE timestamp = ?
            SQL;

            $fitnessRow = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($timestamp)
                ->getSingleRow();

            return $fitnessRow === null ? null : new Fitness(intval($fitnessRow["steps"]), intval($fitnessRow["seconds"]), doubleval($fitnessRow["distance"]));
        }

        public function selectMinimumDistancePerStep() : float {
            $sql = <<<'SQL'
                SELECT MIN(distance / steps) AS minimum_distance_per_step
                FROM fitness
                WHERE steps > 0
            SQL;

            $fitnessRow = $this->databaseClient
                ->statementBuilder($sql)
                ->getSingleRow();

            return doubleval($fitnessRow["minimum_distance_per_step"]);
        }

        public function selectMaximumDistancePerStep() : float {
            $sql = <<<'SQL'
                SELECT MAX(distance / steps) AS maximum_distance_per_step
                FROM fitness
                WHERE steps > 0
            SQL;

            $fitnessRow = $this->databaseClient
                ->statementBuilder($sql)
                ->getSingleRow();

            return doubleval($fitnessRow["maximum_distance_per_step"]);
        }

        public function selectAverageDistancePerStep() : float {
            $sql = <<<'SQL'
                SELECT AVG(distance / steps) AS average_distance_per_step
                FROM fitness
                WHERE steps > 0
            SQL;

            $fitnessRow = $this->databaseClient
                ->statementBuilder($sql)
                ->getSingleRow();

            return doubleval($fitnessRow["average_distance_per_step"]);
        }

        public function selectAllValidFitnessRecordTimestamps() : array {
            $sql = <<<'SQL'
                SELECT timestamp
                FROM fitness
                WHERE timestamp + ? * 86400 < last_update 
                    OR timestamp + ? * 86400 > ROUND(EXTRACT(EPOCH FROM NOW()))
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($this->updateThresholdDays, $this->updateThresholdDays)
                ->getResultSetForColumn("timestamp");
        }

        public function selectAllFitnessRecordTimestamps() : array {
            $sql = <<<'SQL'
                SELECT timestamp
                FROM fitness
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->getResultSetForColumn("timestamp");
        }

        // TODO: Switch to TimeBasedFitness.
        public function insertFitnessRecord(Fitness $fitness, int $timestamp) : bool {
            $sql = <<<'SQL'
                INSERT INTO fitness (
                    timestamp, 
                    last_update, 
                    steps, 
                    seconds, 
                    distance
                )
                VALUES (
                    ?, 
                    ROUND(EXTRACT(EPOCH FROM NOW())),
                    ?,
                    ?, 
                    ?
                )
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($timestamp, $fitness->getSteps(), $fitness->getSeconds(), $fitness->getDistance())
                ->execute() === 1;
        }

        // TODO: Switch to TimeBasedFitness.
        public function insertConflictingFitnessRecord(Fitness $fitness, int $timestamp) : bool {
            $sql = <<<'SQL'
                INSERT INTO fitness_conflict (
                    timestamp, 
                    steps, 
                    seconds, 
                    distance
                )
                VALUES (
                    ?, 
                    ?,
                    ?, 
                    ?
                )
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($timestamp, $fitness->getSteps(), $fitness->getSeconds(), $fitness->getDistance())
                ->execute() === 1;
        }

        public function updateFitnessRecordLastUpdate(int $timestamp) : bool {
            $sql = <<<'SQL'
                UPDATE fitness
                SET last_update = ROUND(EXTRACT(EPOCH FROM NOW()))
                WHERE timestamp = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($timestamp)
                ->execute() === 1;
        } 

        public function deleteFitnessRecord(int $timestamp) : int {
            $sql = <<<'SQL'
                DELETE
                FROM fitness
                WHERE timestamp = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($timestamp)
                ->execute();
        }

        public function deleteConflictingFitnessRecord(int $timestamp) : int {
            $sql = <<<'SQL'
                DELETE
                FROM fitness_conflict
                WHERE timestamp = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($timestamp)
                ->execute();
        }
    }
?>