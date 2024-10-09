<?php
    class RemoveJobProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider;

            $deletedRowsCount = $databaseProvider
                ->statementBuilder("DELETE FROM queue_job WHERE id = ?")
                ->withParameters($input["jobId"])
                ->execute();

            return $deletedRowsCount == 1;
        }

        public function getRequiredArguments() {
            return array("jobId");
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }
    }
?>