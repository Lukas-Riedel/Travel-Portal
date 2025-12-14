<?php
    namespace Core\Client\Database;

    use Monolog\Logger;
    use PgSql\Connection;
use PgSql\Result;

    class PostgreSQLStatementBuilder implements StatementBuilder {

        private const DURATION_THRESHOLD_MILLISECONDS = 100;
        
        private readonly Connection $connection;

        private readonly string $sql;
        private array $params;
        private array $deferredParams;

        private readonly Logger $logger;

        public function __construct(Connection $connection, string $sql, Logger $logger) {
            $this->connection = $connection;
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
            $result = $this->doExecute(true);
            if ($result === false) {
                return 0;
            }

            return \pg_affected_rows($result);
        }

        public function getResultSet() : array {
            $result = $this->doExecute(false);
            if ($result === false) {
                return array();
            }

            return \pg_fetch_all($result) ?: array();
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

        private function doExecute(bool $logStatement) : Result|false {
            $params = array_merge($this->params, $this->deferredParams);            
            $start = microtime(true);
            $statementName = uniqid("", true);
            
            $result = $this->doGetResult($statementName, $params);

            $duration = round((microtime(true) - $start) * 1000);
            if ($duration > self::DURATION_THRESHOLD_MILLISECONDS) {
                $this->logger->debug("Took " . $duration . " milliseconds: " . trim(preg_replace('/\s+/', ' ', $this->sql)) . "", array("parameters" => $params));
            }

            $affectedRows = \pg_affected_rows($result);
            if ($logStatement && !str_contains($this->sql, "INSERT INTO") && $affectedRows > 0) {
                $this->logger->debug("Affected " . $affectedRows . " rows: " . trim(preg_replace('/\s+/', ' ', $this->sql)) . "", array("parameters" => $params));
            }

            return $result;
        }

        private function doGetResult(string $statementName, array $params) : Result|false {
            try {
                \pg_prepare($this->connection, $statementName, $this->convertPlaceholders($this->sql));
                return \pg_execute($this->connection, $statementName, $params);
            }
            catch (\Exception $e) {
                $this->logger->warning("Unable to execute query: " . trim(preg_replace('/\s+/', ' ', $this->sql)) . "", array("parameters" => $params));
                throw $e;
            }
        }

        private function convertPlaceholders(string $sql) : string {
            $out = "";
            $index = 1;

            for ($i = 0; $i < strlen($sql); $i++) {
                if ($sql[$i] === "?") {
                    $out .= "$" . $index++;
                }
                else {
                    $out .= $sql[$i];
                }
            }

            return $out;
        }
    }
?>