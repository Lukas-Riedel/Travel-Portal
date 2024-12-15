<?php
    class PruneDatabaseProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider, $schedulingProvider;

            $pruneStatements = $databaseProvider
                ->statementBuilder("SELECT * FROM pruner")
                ->getResultSetForColumn("query");

            foreach ($pruneStatements as &$pruneStatement) {
                $databaseProvider 
                    ->statementBuilder($pruneStatement)
                    ->execute();
            }
        
            return TRUE;
        }

        public function getRequiredArguments() {
            return array();
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }
    }
?>