<?php
    require_once(dirname(__FILE__) . "/../Model/Problem.php");
    require_once(dirname(__FILE__) . "/../Model/ProblemValue.php");

    class GetProblemsReportProcessor {        
        public function getReport() {
            global $databaseProvider;
            
            $result = array();

            $problemDefinitionRows = $databaseProvider
                ->statementBuilder("SELECT * FROM definition_problem")
                ->getResultSet();

            foreach ($problemDefinitionRows as &$problemDefinitionRow) {
                if ($problemDefinitionRow["helper_statements"] != NULL) {
                    foreach (explode(";", $problemDefinitionRow["helper_statements"]) as &$helperStatement) {
                        $databaseProvider
                            ->statementBuilder($helperStatement)
                            ->execute();
                    }
                }
        
                $problemRows = $databaseProvider 
                    ->statementBuilder($problemDefinitionRow["query"])
                    ->getResultSet();
                
                if (count($problemRows) > 0) {
                    $result[] = new Problem($problemDefinitionRow["name"], array_map(function($problemRow) {
                        $context = $problemRow[array_key_last($problemRow)];
                        return new Problemvalue($problemRow[array_key_first($problemRow)], $context === NULL ? NULL : json_decode($context, TRUE));
                    }, $problemRows));
                }
            }

            return $result;
        }
    }
?>