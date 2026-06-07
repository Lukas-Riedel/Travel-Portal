<?php
    namespace Core\Client\Database;

    use Common\Client\HealthCheckable;
    use Common\Client\Cache\CacheClient;
    use Core\Client\Messaging\ProgressReporter;
    use Core\Common\CommonConstants;
    use Core\OpenLineage\OpenLineageEventManager;
    use Monolog\Logger;
    use PgSql\Connection;
    use PHPSQLParser\PHPSQLParser;

    class PostgreSQLDatabaseClient implements DatabaseClient, HealthCheckable {

        private const CONNECTION_CONFIGURATION_STRING_FORMAT = "host=%s port=%d dbname=%s user=%s password=%s";
        private const DEFAULT_SCHEMA_NAME = "public";
        private const WHERE_CLAUSE_PLACEHOLDER = "WHERE :CONDITIONS";
        
        private const OPENLINEAGE_DATASET_NAMESPACE_FORMAT = "postgres://%s";
        private const OPENLINEAGE_DATASET_NAME_FORMAT = "%s.%s.%s";

        private const TABLE_COLUMNS_CACHE_KEY_FORMAT = "PostgreSQLDatabaseClient:TableColumns:%s";
        private const TABLE_COLUMNS_CACHE_TTL = CommonConstants::ONE_WEEK_SECONDS;

        private readonly CacheClient $distributedCacheClient;
        private readonly Logger $logger;

        private readonly Connection $connection;
        private readonly string $host;
        private readonly string $database;

        private ?ProgressReporter $progressReporter;
        private ?OpenLineageEventManager $openLineageEventManager;        
        private ?AtomicExecution $currentAtomicExecution;

        private array $preparedStatements;

        public function __construct(string $host, int $port, string $user, string $password, string $database, CacheClient $distributedCacheClient, Logger $logger) {
            $this->connection = \pg_connect(sprintf(self::CONNECTION_CONFIGURATION_STRING_FORMAT, $host, $port, $database, $user, $password));
            $this->host = $host;
            $this->database = $database;
            $this->distributedCacheClient = $distributedCacheClient;
            $this->logger = $logger;
            $this->progressReporter = null;
            $this->openLineageEventManager = null;
            $this->currentAtomicExecution = null;
            $this->preparedStatements = array();
        }

        public function __destruct() {
            \pg_close($this->connection);
        }

        public function setProgressReporter(ProgressReporter $progressReporter) : void {
            $this->progressReporter = $progressReporter;
        }

        public function setOpenLineageEventManager(OpenLineageEventManager $openLineageEventManager) : void {
            $this->openLineageEventManager = $openLineageEventManager;
        }

        public function getServiceName() : string {
            return "postgresql";
        }

        public function isHealthy() : bool {
            try {
                $result = \pg_query($this->connection, "SELECT 1");
                return $result && \pg_num_rows($result) > 0;
            }
            catch (\Throwable $e) {
                return false;
            }
        }

        public function query(string $sql) : mixed {
            $result = \pg_query($this->connection, $sql);
            if ($result === false) {
                return false;
            }

            if (\pg_num_fields($result) > 0) {
                return \pg_fetch_all($result) ?: array();
            }

            return \pg_affected_rows($result);
        }
        
        public function statementBuilder(string $sql, ?WhereClause $whereClause = null) : StatementBuilder {
            if ($whereClause !== null) {
                $sql = str_replace(self::WHERE_CLAUSE_PLACEHOLDER, $whereClause->getClause(), $sql);
            }

            $builder = new PostgreSQLStatementBuilder($this->progressReporter, $this->connection, $this->preparedStatements, $sql, $this->logger);
            if ($whereClause !== null) {
                $builder->withDeferredParameters(...$whereClause->getParameters());
            }

            try {
                $this->addOpenLineageDatasets($sql); 
            }
            catch (\Exception $e) {
                $this->logger->warning("Unable to add OpenLineage datasets. Reason: " . $e->getMessage(), array("exception" => $e));
            }
            
            return $builder;
        }
        
        public function whereClauseBuilder() : WhereClauseBuilder {
            return new WhereClauseBuilder();
        }
        
        public function getIsNullOrEqualTo(?string $var) : string {
            return $var === null ? "IS NULL" : ("= '" . \pg_escape_string($this->connection, $var) . "'");
        }
        
        public function getPlaceholdersSequence(int $count) : string {
            return $count === 0 ? "NULL" : implode(",", array_fill(0, $count, "?"));
        }

        public function getCurentAtomicExecution() : ?AtomicExecution {
            return $this->currentAtomicExecution;
        }

        public function executeAtomically(callable $callable) : void {
            if ($this->currentAtomicExecution !== null) {
                $callable();
            }
            else {
                $this->currentAtomicExecution = new AtomicExecution();
                \pg_query($this->connection, "BEGIN");
                try {
                    $callable();
                    \pg_query($this->connection, "COMMIT");
                    $this->currentAtomicExecution->commit();
                }
                catch (\Throwable $e) {
                    \pg_query($this->connection, "ROLLBACK");
                    throw $e;
                }   
                finally {
                    $this->currentAtomicExecution = null;
                } 
            }        
        }
        
        private function addOpenLineageDatasets(string $sql) : void {
            if ($this->openLineageEventManager === null) {
                return;
            }

            $parser = new PHPSQLParser($sql);
            $parsed = $parser->parsed;

            $inputTables = array();
            $outputTables = array();

            $collectTables = function($parsedPart) use(&$inputTables, &$collectTables) {
                if (empty($parsedPart)) {
                    return;
                }

                foreach ($parsedPart as $entry) {
                    if (isset($entry["expr_type"]) && $entry["expr_type"] === "table") {
                        $inputTables[$entry["table"]] = $entry["table"];
                    }
                    if (isset($entry["sub_tree"]) && !empty($entry["sub_tree"])) {
                        $collectTables($entry["sub_tree"]);
                    }
                    if (isset($entry["join"]) && !empty($entry["join"])) {
                        $collectTables($entry["join"]);
                    }
                }
            };

            if (isset($parsed["FROM"]) && !empty($parsed["FROM"])) {
                $collectTables($parsed["FROM"]);
            }

            foreach (array("INSERT", "UPDATE", "DELETE") as $type) {
                if (isset($parsed[$type]) && !empty($parsed[$type])) {
                    foreach ($parsed[$type] as $entry) {
                        if (isset($entry["expr_type"]) && $entry["expr_type"] === "table") {
                            $outputTables[$entry["table"]] = $entry["table"];
                        }                        
                        if (isset($entry["sub_tree"]) && !empty($entry["sub_tree"])) {
                            $collectTables($entry["sub_tree"]);
                        }
                        if (isset($entry["join"]) && !empty($entry["join"])) {
                            $collectTables($entry["join"]);
                        }
                    }
                }
            }

            $namespace = sprintf(self::OPENLINEAGE_DATASET_NAMESPACE_FORMAT, $this->host);

            foreach ($inputTables as $table) {
                $name = sprintf(self::OPENLINEAGE_DATASET_NAME_FORMAT, $this->database, self::DEFAULT_SCHEMA_NAME, $table);
                $columns = $this->fetchTableColumns($table);
                $this->openLineageEventManager->getCurrentEvent()?->addInput($namespace, $name, $this->getHierarchy($table), array_fill_keys($columns, null));
            }

            foreach ($outputTables as $table) {
                $name = sprintf(self::OPENLINEAGE_DATASET_NAME_FORMAT, $this->database, self::DEFAULT_SCHEMA_NAME, $table);
                $columns = $this->fetchTableColumns($table);
                $this->openLineageEventManager->getCurrentEvent()?->addOutput($namespace, $name, $this->getHierarchy($table), array_fill_keys($columns, null));
            }
        }

        private function fetchTableColumns(string $table) : array {
            $cacheKey = $this->getTableColumnsCacheKey($table);
            $tableColumns = $this->distributedCacheClient->get($cacheKey);
            if ($tableColumns !== null) {
                return $tableColumns;
            }

            $sql = <<<SQL
                SELECT column_name
                FROM information_schema.columns
                WHERE table_schema = ?
                    AND table_name = ?
                ORDER BY ordinal_position
            SQL;

            $tableColumns = (new PostgreSQLStatementBuilder($this->progressReporter, $this->connection, $this->preparedStatements, $sql, $this->logger))
                ->withParameters(self::DEFAULT_SCHEMA_NAME, $table)
                ->getResultSetForColumn("column_name");

            $this->distributedCacheClient->set($cacheKey, $tableColumns, self::TABLE_COLUMNS_CACHE_TTL);

            return $tableColumns;
        }

        private function getTableColumnsCacheKey(string $table) : string {
            return sprintf(self::TABLE_COLUMNS_CACHE_KEY_FORMAT, $table);
        }

        private function getHierarchy(string $table) : array {
            return array(
                array("type" => "Database", "name" => $this->database),
                array("type" => "Schema", "name" => self::DEFAULT_SCHEMA_NAME),
                array("type" => "Table", "name" => $table)
            );
        }
    }
?>
