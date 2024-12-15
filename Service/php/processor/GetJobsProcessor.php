<?php
    require_once(dirname(__FILE__) . "/GetGoogleResponseProcessor.php");

    class GetJobsProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("SELECT * FROM queue_job WHERE processor = ?")
                ->withParameters($input["name"])
                ->getMappedResultSet(function ($job) {
                    return array(
                        "id" => $job["id"],
                        "args" => json_decode($job["args"], TRUE)
                    );
                });
        }

        public function getRequiredArguments() {
            return array("name");
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }
    }
?>