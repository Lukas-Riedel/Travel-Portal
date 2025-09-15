<?php
    namespace Core\Client\Database;

    use Core\OpenLineage\OpenLineageEventManager;
    use Monolog\Logger;
    use PHPSQLParser\PHPSQLParser;

    class MySQLDatabaseClient implements DatabaseClient {

        private const DEFAULT_CHARSET = "utf8mb4";
        private const WHERE_CLAUSE_PLACEHOLDER = "WHERE :CONDITIONS";
        
        private const OPENLINEAGE_DATASET_NAMESPACE_FORMAT = "mysql://%s";
        private const OPENLINEAGE_DATASET_NAME_FORMAT = "%s.%s";

        private readonly \mysqli $mysqli;
        private readonly string $host;
        private readonly string $database;

        private ?OpenLineageEventManager $openLineageEventManager;

        private readonly Logger $logger;
        
        private bool $isInAtomicExecution;

        public function __construct(string $host, string $user, string $password, string $database, Logger $logger) {
            $this->mysqli = new \mysqli($host, $user, $password, $database);
            $this->mysqli->set_charset(self::DEFAULT_CHARSET);
            $this->host = $host;
            $this->database = $database;
            $this->openLineageEventManager = null;
            $this->logger = $logger;
            $this->isInAtomicExecution = false;
        }

        public function setOpenLineageEventManager(OpenLineageEventManager $openLineageEventManager) : void {
            $this->openLineageEventManager = $openLineageEventManager;
        }

        public function isDatabaseInitialized() : bool {
            $sql = <<<'SQL'
                SELECT COUNT(*) AS count 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_NAME = 'configuration'
            SQL;
            
            // Keep it as much low-level as possible.
            return $this->mysqli->query($sql)->fetch_assoc()["count"] > 0;
        }

        public function query(string $sql) : mixed {
            $result = $this->mysqli->query($sql);
            if ($result === false) {
                return false;
            }
            if ($result instanceof \mysqli_result) {
                return $result->fetch_all(MYSQLI_ASSOC);
            }
            return $this->mysqli->affected_rows;
        }
        
        public function statementBuilder(string $sql, ?WhereClause $whereClause = null) : StatementBuilder {
            if ($whereClause !== null) {
                $sql = str_replace(self::WHERE_CLAUSE_PLACEHOLDER, $whereClause->getClause(), $sql);
            }

            $builder = new MySQLStatementBuilder($this->mysqli, $sql, $this->logger);
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
        
        public function getIsNullOrEqualTo(mixed $var) : string {
            return $var == null ? "IS NULL" : ("= '" . $this->mysqli->real_escape_string($var) . "'");
        }
        
        public function getLastInsertedId() : mixed {
            return $this->mysqli->insert_id;
        }

        public function executeAtomically(callable $callable) : void {
            if ($this->isInAtomicExecution) {
                $callable();
            }
            else {
                $this->isInAtomicExecution = true;
                $this->mysqli->begin_transaction();
                try {
                    $callable();
                    $this->mysqli->commit();
                }
                catch (\Throwable $e) {
                    $this->mysqli->rollback();
                    throw $e;
                }   
                finally {
                    $this->isInAtomicExecution = false;
                } 
            }        
        }

        private function addOpenLineageDatasets(string $sql) : void {
            $parser = new PHPSQLParser($sql);
            $parsed = $parser->parsed;

            $inputTables = array();
            $outputTables = array();

            $collectTables = function($parsedPart) use (&$inputTables, &$collectTables) {
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
                $name = sprintf(self::OPENLINEAGE_DATASET_NAME_FORMAT, $this->database, $table);
                $columns = $this->fetchTableColumns($table);
                $this->openLineageEventManager?->getCurrentEvent()?->addInput($namespace, $name, array_fill_keys($columns, null));
            }

            foreach ($outputTables as $table) {
                $name = sprintf(self::OPENLINEAGE_DATASET_NAME_FORMAT, $this->database, $table);
                $columns = $this->fetchTableColumns($table);
                $this->openLineageEventManager?->getCurrentEvent()?->addOutput($namespace, $name, array_fill_keys($columns, null));
            }
        }

        private function fetchTableColumns(string $table) : array {
            $sql = <<<SQL
                SELECT COLUMN_NAME
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = ?
                    AND TABLE_NAME = ?
                ORDER BY ORDINAL_POSITION
            SQL;

            return (new MySQLStatementBuilder($this->mysqli, $sql, $this->logger))
                ->withParameters($this->database, $table)
                ->getResultSetForColumn("COLUMN_NAME");
        }
    }
?>