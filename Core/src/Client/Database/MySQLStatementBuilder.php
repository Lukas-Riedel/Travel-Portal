<?php
    namespace Core\Client\Database;

    use Monolog\Logger;

    class MySQLStatementBuilder implements StatementBuilder {

        private const DURATION_THRESHOLD_MILLISECONDS = 100;
        
        private readonly \mysqli $mysqli;

        private readonly string $sql;
        private array $params;
        private array $deferredParams;

        private readonly Logger $logger;

        public function __construct(\mysqli $mysqli, string $sql, Logger $logger) {
            $this->mysqli = $mysqli;
            $this->sql = $sql;
            $this->params = array();
            $this->deferredParams = array();
            $this->logger = $logger;
        }
        
        public function withParameters(mixed ...$params) : StatementBuilder {
            $this->params = array_merge($this->params, $params);
            return $this;
        }

        public function withDeferredParameters(mixed ...$deferredParams) : StatementBuilder {
            $this->deferredParams = array_merge($this->deferredParams, $deferredParams);
            return $this;
        }

        public function execute() : int {
            $statement = $this->doExecute(true);
            if (!$statement) {
                return 0;
            }

            return $statement->affected_rows;
        }

        public function getResultSet() : array {
            $statement = $this->doExecute(false);
            if (!$statement) {
                return array();
            }

            return $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        public function getMappedResultSet(callable $fn) : array {
            return array_filter(array_map($fn, $this->getResultSet()), fn($v) => !is_null($v));
        }

        public function getResultSetForColumn(string $column) : array {
            return array_column($this->getResultSet(), $column);
        }

        public function getMappedResultSetForColumn(string $column, callable $fn) : array {
            return array_filter(array_map($fn, $this->getResultSetForColumn($column)), fn($v) => !is_null($v));
        }

        public function getSingleRow() : mixed {
            $resultSet = $this->getResultSet();
            return count($resultSet) === 1 ? $resultSet[0] : null;
        }

        public function getFirstRow() : mixed {
            return $this->getResultSet()[0] ?? null;
        }

        public function getSingleColumn(string $column) : mixed {
            return $this->getSingleRow()[$column] ?? null;
        }

        public function getFirstColumn(string $column) : mixed {
            return $this->getFirstRow()[$column] ?? null;
        }

        private function doExecute(bool $logStatement) : \mysqli_stmt|false {
            $statement = $this->mysqli->prepare($this->sql);

            if (!$statement) {
                return false;
            }

            $params = array_merge($this->params, $this->deferredParams);            
            $start = microtime(true);
            try {                
                if (empty($params)) {
                    $statement->execute();
                }
                else {
                    $statement->execute($params);
                }
            }
            catch (\Exception $e) {
                $this->logger->warning("Unable to execute query: " . trim(preg_replace('/\s+/', ' ', $this->sql)) . "", array("parameters" => $params));
                throw $e;
            }

            $duration = round((microtime(true) - $start) * 1000);
            if ($duration > self::DURATION_THRESHOLD_MILLISECONDS) {
                $this->logger->debug("Took " . $duration . " milliseconds: " . trim(preg_replace('/\s+/', ' ', $this->sql)) . "", array("parameters" => $params));
            }

            if ($logStatement && !str_contains($this->sql, "INSERT INTO") && $statement->affected_rows > 0) {
                $this->logger->debug("Affected " . $statement->affected_rows . " rows: " . trim(preg_replace('/\s+/', ' ', $this->sql)) . "", array("parameters" => $params));
            }

            return $statement;
        }
    }
?>