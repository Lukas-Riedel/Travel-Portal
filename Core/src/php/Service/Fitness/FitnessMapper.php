<?php
    namespace Core\Service\Fitness;

    use Core\Service\Configuration\ConfigurationService;

    class FitnessMapper {

        private readonly \DatabaseProvider $databaseProvider;
        private readonly ConfigurationService $configurationService;

        public function __construct(\DatabaseProvider $databaseProvider, ConfigurationService $configurationService) {
            $this->databaseProvider = $databaseProvider;
            $this->configurationService = $configurationService;
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

            $fitnessRow = $this->databaseProvider
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
                {$fitnessSortingStrategy->value}
            SQL;

            return $this->databaseProvider
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

            $fitnessRow = $this->databaseProvider
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

            return $this->databaseProvider
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

            $fitnessRow = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($timestamp)
                ->getSingleRow();

            return $fitnessRow === null ? null : new Fitness(intval($fitnessRow["steps"]), intval($fitnessRow["seconds"]), doubleval($fitnessRow["distance"]));
        }

        public function selectMinimumDistancePerStep() : float {
            $sql = <<<'SQL'
                SELECT MIN(distance / steps) AS minimum_distance_per_step
                FROM fitness
            SQL;

            $fitnessRow = $this->databaseProvider
                ->statementBuilder($sql)
                ->getSingleRow();

            return doubleval($fitnessRow["minimum_distance_per_step"]);
        }

        public function selectMaximumDistancePerStep() : float {
            $sql = <<<'SQL'
                SELECT MAX(distance / steps) AS maximum_distance_per_step
                FROM fitness
            SQL;

            $fitnessRow = $this->databaseProvider
                ->statementBuilder($sql)
                ->getSingleRow();

            return doubleval($fitnessRow["maximum_distance_per_step"]);
        }

        public function selectAverageDistancePerStep() : float {
            $sql = <<<'SQL'
                SELECT AVG(distance / steps) AS average_distance_per_step
                FROM fitness
            SQL;

            $fitnessRow = $this->databaseProvider
                ->statementBuilder($sql)
                ->getSingleRow();

            return doubleval($fitnessRow["average_distance_per_step"]);
        }

        // TODO: Drop this crazy SQL, implement in the application code.
        // TODO: This references tables of other services which it shouldn't.
        public function selectFitnessRecordTimestampsToUpdate() : array {
            $sql = <<<'SQL'
                SELECT x.start
                FROM (
                    SELECT s.seq AS start
                    FROM (
                        SELECT (
                            SELECT MIN(start)
                            FROM trip_event
                        ) + ? * seq AS seq
                        FROM seq_0_to_200000
                    ) s
                    JOIN (
                        SELECT *
                        FROM trip_event
                        WHERE trip_id NOT IN (
                            SELECT id
                            FROM trip_identifier
                            WHERE name = ?
                        )
                    ) t
                    WHERE s.seq >= t.start
                        AND s.seq <= t.end
                        AND s.seq <= UNIX_TIMESTAMP() + ?
                    UNION
                    SELECT s.seq AS start
                    FROM (
                        SELECT (
                            SELECT MIN(start) - 86400
                            FROM place_event
                        ) + ? * seq AS seq
                        FROM seq_0_to_200000
                    ) s
                    JOIN (
                        SELECT pe.* 
                        FROM place_event pe 
                        INNER JOIN trip_identifier ti
                            ON pe.trip_id = ti.id
                        WHERE ti.name = ?
                            AND YEAR(FROM_UNIXTIME(pe.start)) = ti.year
                        ) p
                    WHERE s.seq >= p.start - (p.start % 86400)
                        AND s.seq <= 86400 + p.end - (p.end % 86400)
                        AND s.seq <= UNIX_TIMESTAMP() + ?
                    ) x
                LEFT JOIN fitness f
                    ON x.start = f.timestamp                    
                WHERE f.timestamp IS null
                    OR (
                        -- TODO: Find a better way of how to update fitness records multiple times when getting rid of this query.
                        f.timestamp + (7 * 86400) > f.last_update
                        AND f.last_update + 86400 < UNIX_TIMESTAMP()
                    )
            SQL;

            $dayTripsTripName = $this->configurationService->getConfigurationEntry("trips")["dayTripsName"];
            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters(FitnessService::FITNESS_RECORD_DURATION, $dayTripsTripName, FitnessService::FITNESS_RECORD_DURATION, 
                    FitnessService::FITNESS_RECORD_DURATION, $dayTripsTripName, FitnessService::FITNESS_RECORD_DURATION)
                ->getResultSetForColumn("start");
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
                    UNIX_TIMESTAMP(),
                    ?,
                    ?, 
                    ?
                )
            SQL;

            return $this->databaseProvider
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

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($timestamp, $fitness->getSteps(), $fitness->getSeconds(), $fitness->getDistance())
                ->execute() === 1;
        }

        public function updateFitnessRecordLastUpdate(int $timestamp) : bool {
            $sql = <<<'SQL'
                UPDATE fitness
                SET last_update = UNIX_TIMESTAMP()
                WHERE timestamp = ?
            SQL;

            return $this->databaseProvider
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

            return $this->databaseProvider
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

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($timestamp)
                ->execute();
        }

        // TODO: Drop this crazy SQL, implement in the application code.
        // TODO: This references tables of other services which it shouldn't.
        public function deleteStaleFitnessRecords() : int {
            $sql = <<<'SQL'
                DELETE f 
                FROM fitness f 
                LEFT JOIN (
                    SELECT s.seq AS start 
                    FROM (
                        SELECT (
                            SELECT MIN(start)
                            FROM trip_event
                        ) + ? * seq AS seq
                        FROM seq_0_to_200000
                    ) s 
                    JOIN (
                        SELECT * 
                        FROM trip_event 
                        WHERE trip_id NOT IN (
                            SELECT id 
                            FROM trip_identifier 
                            WHERE name = ?
                        )
                    ) t 
                    WHERE s.seq >= t.start 
                        AND s.seq <= t.end 
                        AND s.seq <= UNIX_TIMESTAMP() 
                    UNION 
                    SELECT s.seq AS start
                    FROM (
                        SELECT (
                            SELECT MIN(start) - 86400
                            FROM place_event
                        ) + ? * seq AS seq
                        FROM seq_0_to_200000
                    ) s 
                    JOIN (
                        SELECT pe.* 
                        FROM place_event pe
                        INNER JOIN trip_identifier ti 
                            ON pe.trip_id = ti.id 
                        WHERE ti.name = ?
                            AND YEAR(FROM_UNIXTIME(pe.start)) = ti.year
                    ) p 
                    WHERE s.seq >= p.start - (p.start % 86400) 
                        AND s.seq <= 86400 + p.end - (p.end % 86400) 
                        AND s.seq <= UNIX_TIMESTAMP()
                ) x 
                    ON x.start = f.timestamp 
                    WHERE x.start IS null
            SQL;

            $dayTripsTripName = $this->configurationService->getConfigurationEntry("trips")["dayTripsName"];
            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters(FitnessService::FITNESS_RECORD_DURATION, $dayTripsTripName, FitnessService::FITNESS_RECORD_DURATION, $dayTripsTripName)
                ->execute();
        }
    }
?>