<?php
    class RemoveCandidateTripProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider;

            $deletedRowsCount = $databaseProvider
                ->statementBuilder("DELETE FROM place_candidate_event WHERE trip_id = ?")
                ->withParameters($input["tripId"])
                ->execute();

            return $deletedRowsCount == 1;
        }

        public function getRequiredArguments() {
            return array("tripId");
        }
        
        public function requiresAuthentication() {
            return TRUE;
        }
    }
?>