<?php
    require_once(dirname(__FILE__) . "/../config/db.php");

    class DatabaseProvider {

        private $connection;
        private $viewsToMaterialize;
        private $delayMaterializationIfNeeded;

        public function __construct($delayMaterializationIfNeeded) {
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            $this->connection->set_charset("utf8mb4");
            $this->viewsToMaterialize = array();
            $this->delayMaterializationIfNeeded = $delayMaterializationIfNeeded;
        }

        public function __destruct() {
            $this->materializeViews();
        }

        private function materializeView($viewToMaterialize) {
            $this->connection->begin_transaction();
            $this->connection->query("DELETE FROM view_materialization WHERE view_name = '" . $viewToMaterialize . "'");
            $this->connection->query("DELETE FROM " . substr($viewToMaterialize, 1));
            $start = microtime(TRUE);
            $this->connection->query("INSERT INTO " . substr($viewToMaterialize, 1) . " SELECT * FROM " . $viewToMaterialize);
            $_SESSION["materializationDuration"][$viewToMaterialize] = ceil(1000 * (microtime(TRUE) - $start));
            $this->connection->query("INSERT INTO view_materialization (view_name, last_materialization_duration, is_materialization_delayed) VALUES ('" 
                . $viewToMaterialize . "', " . $_SESSION["materializationDuration"][$viewToMaterialize] . ", 0)");
            $this->connection->commit();
        }

        public function materializeViews() {            
            foreach ($this->viewsToMaterialize as &$viewToMaterialize) {
                if ($this->delayMaterializationIfNeeded && isset($_SESSION["materializationDuration"][$viewToMaterialize])
                    && $_SESSION["materializationDuration"][$viewToMaterialize] > 3000) {
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

        public function statementBuilder($sql, $whereClause = NULL) {
            if ($whereClause != NULL) {
                $sql = str_replace("{{WHERE CLAUSE}}", $whereClause["clause"], $sql);
            }
            $builder = new StatementBuilder($this->connection->prepare($sql));
            if ($whereClause != NULL) {
                $builder->withDeferredParameters(...$whereClause["parameters"]);
            }
            $this->updateViewsToMaterialize($sql);
            return $builder;
        }

        public function whereClauseBuilder() {
            return new WhereClauseBuilder();
        }

        public function beginTransaction() {
            $this->connection->begin_transaction();
        }

        public function commit() {
            $this->connection->commit();
        }

        public function rollback() {
            $this->connection->rollback();
        }

        public function escape($str) {
            return $this->connection->real_escape_string($str);
        }

        public function getIsNullOrEqualTo($var) {
            return $var == NULL ? "IS NULL" : ("= '" . $this->escape($var) . "'");
        }

        private function updateViewsToMaterialize($sql) {            
            if (!isset($_SESSION["materializationDuration"])) {
                $views = $this->connection->query("SELECT * FROM view_materialization");
                while ($view = $views->fetch_assoc()) {
                    $_SESSION["materializationDuration"][$view["view_name"]] = intval($view["last_materialization_duration"]);
                }
            }

            if (!isset($_SESSION["viewDependencies"])) {  
                $_SESSION["viewDependencies"] = array();

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
                            if (!array_key_exists($table, $_SESSION["viewDependencies"])) {
                                $_SESSION["viewDependencies"][$table] = array();
                            }

                            if (!in_array($dependency, $_SESSION["viewDependencies"][$table])) {                                    
                                $_SESSION["viewDependencies"][$table][] = $dependency;
                            }
                        }
                    }
                }
            }
            
            foreach ($_SESSION["viewDependencies"] as $table => $dependentViews) {
                if (str_contains($sql, "DELETE FROM " . $table) || str_contains($sql, "INSERT INTO " . $table) || str_contains($sql, "UPDATE " . $table)) {
                    foreach ($dependentViews as &$dependentView) {   
                        if (!in_array($dependentView, $this->viewsToMaterialize)) {
                            $this->viewsToMaterialize[] = $dependentView;
                        }
                    }
                }
            }

            $delayedViews = $this->connection->query("SELECT view_name FROM view_materialization WHERE is_materialization_delayed = 1");
            while ($delayedView = $delayedViews->fetch_assoc()) {
                if (!in_array($delayedView["view_name"], $this->viewsToMaterialize)) {
                    $this->viewsToMaterialize[] = $delayedView["view_name"];
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
    }

    class StatementBuilder {

        private $statement;
        private $params;
        private $deferredParams;

        function __construct($statement) {
            $this->statement = $statement;
            $this->params = array();
            $this->deferredParams = array();
        }

        public function withParameters(...$params) {
            foreach ($params as &$param) {
                $this->params[] = $param;
            } 
            return $this;
        }

        function withDeferredParameters(...$params) {
            foreach ($params as &$param) {
                $this->deferredParams[] = $param;
            } 
            return $this;
        }

        public function execute() {
            $params = array_merge($this->params, $this->deferredParams);
            if (empty($params)) {
                $this->statement->execute();
            }
            else {
                $this->statement->execute($params);
            }
            return $this->statement->affected_rows;
        }

        public function getResultSet() {
            $this->execute();
            $result = $this->statement->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        }

        public function getMappedResultSet($fn) {
            return array_map($fn, $this->getResultSet());
        }

        public function getResultSetForColumn($column) {
            $resultSet = $this->getResultSet();
            $result = array();
            foreach ($resultSet as &$row) {
                $result[] = $row[$column];
            }
            return $result;
        }

        public function getSingleRow() {
            $resultSet = $this->getResultSet();
            return count($resultSet) === 1 ? $resultSet[0] : NULL;
        }

        public function getFirstRow() {
            $resultSet = $this->getResultSet();
            return count($resultSet) > 0 ? $resultSet[0] : NULL;
        }

        public function getSingleColumn($column) {
            $row = $this->getSingleRow();
            return $row == NULL ? NULL : $row[$column];
        }

        public function getFirstColumn($column) {
            $row = $this->getFirstRow();
            return $row == NULL ? NULL : $row[$column];
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