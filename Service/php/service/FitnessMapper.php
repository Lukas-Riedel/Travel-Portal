<?php
    class FitnessMapper {

        private readonly DatabaseProvider $databaseProvider;

        public function __construct(DatabaseProvider $databaseProvider) {
            $this->databaseProvider = $databaseProvider;
        }

        public function selectFitnessRecordForInterval(int $start, int $end) : Fitness { 
            $sql = <<<'SQL'
                SELECT SUM(steps) AS steps,
                    SUM(minutes) AS minutes,
                    SUM(calories) AS calories,
                    SUM(distance) AS distance
                FROM fitness
                WHERE timestamp >= ?
                    AND timestamp < ?
            SQL;

            $fitnessRow = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($start, $end)
                ->getSingleRow();

            return new Fitness(intval($fitnessRow["steps"]), intval($fitnessRow["minutes"]),
                intval($fitnessRow["calories"]), doubleval($fitnessRow["distance"]));
        }

        public function selectFitnessRecord(int $timestamp) : ?Fitness {
            $sql = <<<'SQL'
                SELECT steps,
                    minutes,
                    calories,
                    distance
                FROM fitness
                WHERE timestamp = ?
            SQL;

            $fitnessRow = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($timestamp)
                ->getSingleRow();

            return $fitnessRow === NULL ? NULL : new Fitness(intval($fitnessRow["steps"]), intval($fitnessRow["minutes"]),
                intval($fitnessRow["calories"]), doubleval($fitnessRow["distance"]));
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

        // TODO: Drop fitness_sequence, implement in the application code.
        public function selectFitnessRecordTimestampsToUpdate() : array {
            $sql = <<<'SQL'
                SELECT x.start
                FROM (
                    SELECT s.seq AS start
                    FROM fitness_sequence s
                    JOIN (
                        SELECT *
                        FROM trip_event
                        WHERE trip_id NOT IN (
                            SELECT id
                            FROM trip_identifier
                            WHERE name = GET_CONFIGURATION_FOR_KEY('SPECIAL_TRIP_NAMES', 'dayTrips')
                            )
                        ) t
                    WHERE s.seq >= t.start
                        AND s.seq <= t.end
                        AND s.seq <= UNIX_TIMESTAMP()
                    UNION
                    SELECT s.seq AS start
                    FROM fitness_sequence s
                    JOIN (
                        SELECT ps.* 
                        FROM place_summary ps 
                        INNER JOIN trip_identifier ti
                            ON ps.trip_id = ti.id
                        WHERE ti.name = GET_CONFIGURATION_FOR_KEY('SPECIAL_TRIP_NAMES', 'dayTrips')
                            AND YEAR(FROM_UNIXTIME(ps.start)) = ti.year
                        ) p
                    WHERE s.seq >= p.start - (p.start % 86400)
                        AND s.seq <= 86400 + p.end - (p.end % 86400)
                        AND s.seq <= UNIX_TIMESTAMP()
                    ) x
                LEFT JOIN fitness f
                    ON x.start = f.timestamp                    
                WHERE f.timestamp IS NULL
                    OR f.timestamp + (7 * 86400) > f.last_update
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->getResultSetForColumn("start");
        }

        public function insertFitnessRecord(Fitness $fitness, int $timestamp) : bool {
            $sql = <<<'SQL'
                INSERT INTO fitness (
                    timestamp, 
                    last_update, 
                    steps, 
                    minutes, 
                    calories, 
                    distance
                )
                VALUES (
                    ?, 
                    UNIX_TIMESTAMP(),
                    ?,
                    ?, 
                    ?, 
                    ?
                )
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($timestamp, $fitness->getSteps(), $fitness->getMinutes(), $fitness->getCalories(), $fitness->getDistance())
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
    }
?>