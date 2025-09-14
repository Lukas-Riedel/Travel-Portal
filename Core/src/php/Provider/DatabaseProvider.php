<?php

    use Core\OpenLineage\OpenLineageEventManager;
    use PHPSQLParser\PHPSQLParser;

    class DatabaseProvider {
        
        private const OPENLINEAGE_DATASET_NAMESPACE_FORMAT = "mysql://%s";
        private const OPENLINEAGE_DATASET_NAME_FORMAT = "%s.%s";

        private $connection;
        private $viewsToMaterialize;
        private $delayMaterializationIfNeeded;
        private $isDatabaseInitialized;
        private $isInTransaction;
        private $shouldBeginTransaction;
        private $openLineageEventManager;

        private $isInAtomicExecution;
        private $cache = array();

        public function __construct($delayMaterializationIfNeeded) {
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            $this->connection->set_charset("utf8mb4");
            $this->viewsToMaterialize = array();
            $this->delayMaterializationIfNeeded = $delayMaterializationIfNeeded;
            $this->isDatabaseInitialized = $this
                ->query("SELECT COUNT(*) AS count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'configuration'")->fetch_assoc()["count"] > 0;
            $this->isInTransaction = false;
            $this->shouldBeginTransaction = false;
            $this->isInAtomicExecution = false;
        }

        public function __destruct() {
            if ($this->isDatabaseInitialized) {
                $this->materializeViews();
            }
        }

        public function setOpenLineageEventManager(OpenLineageEventManager $openLineageEventManager) : void {
            $this->openLineageEventManager = $openLineageEventManager;
        }

        public function isDatabaseInitialized() {
            return $this->isDatabaseInitialized;
        }

        private function materializeView($viewToMaterialize) {
            $this->connection->begin_transaction();
            $this->connection->query("DELETE FROM view_materialization WHERE view_name = '" . $viewToMaterialize . "'");
            $this->connection->query("DROP TEMPORARY TABLE IF EXISTS materialized_view");
            $this->connection->query("CREATE TEMPORARY TABLE materialized_view AS SELECT * FROM " . $viewToMaterialize);
            $this->connection->query("DELETE FROM " . substr($viewToMaterialize, 1));
            $start = microtime(true);
            $this->connection->query("INSERT INTO " . substr($viewToMaterialize, 1) . " SELECT * FROM materialized_view");
            $this->cache["materializationDuration"][$viewToMaterialize] = ceil(1000 * (microtime(true) - $start));
            $this->connection->query("INSERT INTO view_materialization (view_name, last_materialization_duration, is_materialization_delayed) VALUES ('" 
                . $viewToMaterialize . "', " . $this->cache["materializationDuration"][$viewToMaterialize] . ", 0)");
            $this->connection->commit();
        }

        public function materializeViews() {            
            foreach ($this->viewsToMaterialize as &$viewToMaterialize) {
                if ($this->delayMaterializationIfNeeded && isset($this->cache["materializationDuration"][$viewToMaterialize])
                    && $this->cache["materializationDuration"][$viewToMaterialize] > 3000) {
                    $this->connection->query("UPDATE view_materialization SET is_materialization_delayed = 1 WHERE view_name = '" . $viewToMaterialize . "'");
                }
                else {                    
                    $this->materializeView($viewToMaterialize);
                }
            }
            $this->viewsToMaterialize = array();
        }

        public function query($sql) {
            return $this->connection->query($sql);
        }

        public function executeAtomically(callable $callable) : void {
            if ($this->isInAtomicExecution) {
                $callable();
            }

            else {
                $this->isInAtomicExecution = true;
                $this->connection->begin_transaction();
                try {
                    $callable();
                    $this->connection->commit();
                }
                catch (\Throwable $e) {
                    $this->connection->rollback();
                    throw $e;
                }   
                finally {
                    $this->isInAtomicExecution = false;
                } 
            }        
        }

        public function statementBuilder($sql, $whereClause = null) {
            global $logger;
            if ($whereClause != null) {
                $sql = str_replace("WHERE :CONDITIONS", $whereClause["clause"], $sql);
            }
            if ($this->shouldBeginTransaction && preg_match('/^\s*(INSERT|UPDATE|DELETE|REPLACE)\b/i', $sql)) {        
                $this->connection->begin_transaction();
                $this->isInTransaction = true;
                $this->shouldBeginTransaction = false;
            }
            $builder = new StatementBuilder($this->connection->prepare($sql), $sql);
            if ($whereClause != null) {
                $builder->withDeferredParameters(...$whereClause["parameters"]);
            }
            try {
                $this->addOpenLineageDatasets($sql); 
            }
            catch (\Exception $e) {
                $logger->warning("Unable to add OpenLineage datasets. Reason: " . $e->getMessage(), array("exception" => $e));
            }
            $this->updateViewsToMaterialize($sql);
            return $builder;
        }

        public function whereClauseBuilder() {
            return new WhereClauseBuilder();
        }

        public function beginTransaction() {
            $this->shouldBeginTransaction = true;
        }

        public function commit() {
            if ($this->isInTransaction) {
                $this->connection->commit();
            }
        }

        public function rollback() {
            if ($this->isInTransaction) {
                $this->connection->rollback();
            }
        }

        public function escape($str) {
            return $this->connection->real_escape_string($str);
        }

        public function getIsNullOrEqualTo($var) {
            return $var == null ? "IS NULL" : ("= '" . $this->escape($var) . "'");
        }

        public function getLastInsertedId() {
            return $this->connection->insert_id;
        }

        private function updateViewsToMaterialize($sql) {            
            if (!isset($this->cache["materializationDuration"])) {
                $views = $this->connection->query("SELECT * FROM view_materialization");
                if ($views) {
                    while ($view = $views->fetch_assoc()) {
                        $this->cache["materializationDuration"][$view["view_name"]] = intval($view["last_materialization_duration"]);
                    }
                }
            }

            if (!isset($this->cache["viewDependencies"])) {  
                $this->cache["viewDependencies"] = array();

                $directDependencies = array();
                $materializableViews = array();

                $views = $this->connection->query("SELECT TABLE_NAME, VIEW_DEFINITION, EXISTS(SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE <> 'VIEW' AND SUBSTRING(v.TABLE_NAME, 2) = TABLE_NAME) AS MATERIALIZABLE FROM INFORMATION_SCHEMA.VIEWS v");
                while ($view = $views->fetch_assoc()) {
                    $matches = array();
                    preg_match_all('/\`' . DB_NAME . '\`\.\`([^\`]+)\`/', $view["VIEW_DEFINITION"], $matches);

                    foreach ($matches[1] as &$usedTable) {
                        if (!array_key_exists($usedTable, $directDependencies)) {
                            $directDependencies[$usedTable] = array();
                        }

                        if (!in_array($view["TABLE_NAME"], $directDependencies[$usedTable])) {
                            $directDependencies[$usedTable][] = $view["TABLE_NAME"];
                        }
                    }

                    if ($view["MATERIALIZABLE"]) {
                        $materializableViews[] = $view["TABLE_NAME"];
                    }
                }

                foreach (array_keys($directDependencies) as &$table) {
                    $dependencies = array();      
                    $this->computeTransitiveDependencies($table, $directDependencies, $dependencies);
                    
                    foreach ($dependencies as &$dependency) {
                        if (in_array($dependency, $materializableViews)) {
                            if (!array_key_exists($table, $this->cache["viewDependencies"])) {
                                $this->cache["viewDependencies"][$table] = array();
                            }

                            if (!in_array($dependency, $this->cache["viewDependencies"][$table])) {                                    
                                $this->cache["viewDependencies"][$table][] = $dependency;
                            }
                        }
                    }
                }
            }
            
            foreach ($this->cache["viewDependencies"] as $table => $dependentViews) {
                $normalizedSql = preg_replace("/\s+/", " ", $sql);
                if (str_contains($normalizedSql, "DELETE FROM " . $table) || str_contains($normalizedSql, "INSERT INTO " . $table) || str_contains($normalizedSql, "UPDATE " . $table)) {
                    foreach ($dependentViews as &$dependentView) {   
                        if (!in_array($dependentView, $this->viewsToMaterialize)) {
                            $this->viewsToMaterialize[] = $dependentView;
                        }
                    }
                }
            }

            $delayedViews = $this->connection->query("SELECT view_name FROM view_materialization WHERE is_materialization_delayed = 1");
            if ($delayedViews) {
                while ($delayedView = $delayedViews->fetch_assoc()) {
                    if (!in_array($delayedView["view_name"], $this->viewsToMaterialize)) {
                        $this->viewsToMaterialize[] = $delayedView["view_name"];
                    }
                }
            }
        }

        private function computeTransitiveDependencies($view, &$directDependencies, &$dependencies) {
            if (array_key_exists($view, $directDependencies)) {
                foreach ($directDependencies[$view] as &$dependency) {
                    $this->computeTransitiveDependencies($dependency, $directDependencies, $dependencies);
                }
            }
            if (!in_array($view, $dependencies)) {
                $dependencies[] = $view;
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

            $namespace = sprintf(self::OPENLINEAGE_DATASET_NAMESPACE_FORMAT, DB_HOST);

            foreach ($inputTables as $table) {
                $name = sprintf(self::OPENLINEAGE_DATASET_NAME_FORMAT, DB_NAME, $table);
                $columns = $this->fetchTableColumns($table);
                $this->openLineageEventManager?->getCurrentEvent()?->addInput($namespace, $name, array_fill_keys($columns, null));
            }

            foreach ($outputTables as $table) {
                $name = sprintf(self::OPENLINEAGE_DATASET_NAME_FORMAT, DB_NAME, $table);
                $columns = $this->fetchTableColumns($table);
                $this->openLineageEventManager?->getCurrentEvent()?->addOutput($namespace, $name, array_fill_keys($columns, null));
            }
        }

        private function fetchTableColumns(string $table) : array {
            $escapedDbName = $this->connection->real_escape_string(DB_NAME);
            $escapedTableName = $this->connection->real_escape_string($table);

            $sql = <<<SQL
                SELECT COLUMN_NAME
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = '{$escapedDbName}'
                    AND TABLE_NAME = '{$escapedTableName}'
                ORDER BY ORDINAL_POSITION
            SQL;

            $result = $this->connection->query($sql);

            $columns = array();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $columns[] = $row["COLUMN_NAME"];
                }
            }
            return $columns;
        }
    }

    class StatementBuilder {

        private $statement;
        private $params;
        private $deferredParams;

        private $sql;

        function __construct($statement, $sql) {
            $this->statement = $statement;
            $this->params = array();
            $this->deferredParams = array();
            $this->sql = $sql;
        }

        public function withParameters(...$params) {
            foreach ($params as &$param) {
                $this->params[] = $param;
            } 
            return $this;
        }

        public function withDeferredParameters(...$params) {
            foreach ($params as &$param) {
                $this->deferredParams[] = $param;
            } 
            return $this;
        }

        public function execute() {
            return $this->doExecute(true);
        }

        private function doExecute($logStatement) {
            global $logger;

            if (!$this->statement) {
                return 0;
            }

            $params = array_merge($this->params, $this->deferredParams);
            
            $start = microtime(true);
            try {                
                if (empty($params)) {
                    $this->statement->execute();
                }
                else {
                    $this->statement->execute($params);
                }
            }
            catch (\Exception $e) {
                $logger->warning("Unable to execute query: " . trim(preg_replace('/\s+/', ' ', $this->sql)) . "", array("parameters" => $params));
                throw $e;
            }
            $duration = round((microtime(true) - $start) * 1000);
            if ($duration > 100) {
                $logger->debug("Took " . $duration . " milliseconds: " . trim(preg_replace('/\s+/', ' ', $this->sql)) . "", array("parameters" => $params));
            }

            if ($logStatement && !str_contains($this->sql, "INSERT INTO") && $this->statement->affected_rows > 0) {
                $logger->debug("Affected " . $this->statement->affected_rows . " rows: " . trim(preg_replace('/\s+/', ' ', $this->sql)) . "", array("parameters" => $params));
            }

            return $this->statement->affected_rows;
        }

        public function getResultSet() {
            if (!$this->statement) {
                return array();
            }

            $this->doExecute(false);
            $result = $this->statement->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        }

        public function getMappedResultSet($fn) {
            return array_filter(array_map($fn, $this->getResultSet()), fn($v) => !is_null($v));
        }

        public function getResultSetForColumn($column) {
            $resultSet = $this->getResultSet();
            $result = array();
            foreach ($resultSet as &$row) {
                $result[] = $row[$column];
            }
            return $result;
        }

        public function getMappedResultSetForColumn($column, $fn) {
            return array_filter(array_map($fn, $this->getResultSetForColumn($column)), fn($v) => !is_null($v));
        }

        public function getSingleRow() {
            $resultSet = $this->getResultSet();
            return count($resultSet) === 1 ? $resultSet[0] : null;
        }

        public function getFirstRow() {
            $resultSet = $this->getResultSet();
            return count($resultSet) > 0 ? $resultSet[0] : null;
        }

        public function getSingleColumn($column) {
            $row = $this->getSingleRow();
            return $row == null ? null : $row[$column];
        }

        public function getFirstColumn($column) {
            $row = $this->getFirstRow();
            return $row == null ? null : $row[$column];
        }
    }

    class WhereClauseBuilder {
         
        private $clauses;
        private $params;

        function __construct() {
            $this->clauses = array();
            $this->params = array();
        }

        public function copy() {
            $copy = new WhereClauseBuilder();
            $copy->clauses = $this->clauses;
            $copy->params = $this->params;
            return $copy;
        }

        public function withClause($clause, ...$params) {
            $this->clauses[] = $clause;
            foreach ($params as &$param) {
                $this->params[] = $param;
            }
            return $this;
        }

        public function buildForAnd() {
            return $this->build("AND");
        }

        public function buildForOr() {
            return $this->build("OR");
        }

        private function build($glue) {
            return empty($this->clauses) ? array("clause" => "", "parameters" => array()) : array("clause" => "WHERE " . implode(" " . $glue . " ", $this->clauses), "parameters" => $this->params);
        }
    }
?>