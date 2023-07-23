<?php
    require_once(dirname(__FILE__) . "/../model/TimeTrackingEvent.php");

    class GetTimeTrackingEventsProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider;
            
            $result = array();

            $whereClauseBuilder = $databaseProvider->whereClauseBuilder();
            if (isset($input["type"])) {
                $whereClauseBuilder->withClause("type = ?", $input["type"]);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            $trackingEventRows = $databaseProvider
                ->statementBuilder("SELECT * FROM tracking {{WHERE CLAUSE}} ORDER BY timestamp DESC, id DESC", $whereClause)
                ->getResultSet();

            foreach ($trackingEventRows as &$trackingEventRow) {
                $whereClauseBuilder = $databaseProvider->whereClauseBuilder()->withClause("timestamp <= ?", $trackingEventRow["timestamp"]);
                if (isset($input["type"])) {
                    $whereClauseBuilder->withClause("type = ?", $input["type"]);
                }
                $whereClause = $whereClauseBuilder->buildForAnd();

                $balance = $databaseProvider
                    ->statementBuilder("SELECT ROUND(SUM(hours), 2) AS balance FROM tracking {{WHERE CLAUSE}}", $whereClause)
                    ->getSingleColumn("balance");

                $result[] = new TimeTrackingEvent($trackingEventRow["id"], $trackingEventRow["description"], $trackingEventRow["hours"],
                    $trackingEventRow["timestamp"], $trackingEventRow["type"], $balance);
            }

            return $result;
        }

        public function getRequiredArguments() {
            return array();
        }
        
        public function requiresAuthentication() {
            return FALSE;
        }
    }
?>