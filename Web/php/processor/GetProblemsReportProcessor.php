<?php
    require_once(dirname(__FILE__) . "/../model/Problem.php");
    require_once(dirname(__FILE__) . "/../model/ProblemValue.php");

    class GetProblemsReportProcessor extends Processor {        
        public function process($input) {
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
                        if ($context != NULL) {
                            $context = json_decode($context, TRUE);
                        }
                        return new Problemvalue($problemRow[array_key_first($problemRow)], $context); 
                    }, $problemRows));
                }
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