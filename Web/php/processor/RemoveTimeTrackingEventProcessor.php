<?php
    class RemoveTimeTrackingEventProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider;

            $deletedRowsCount = $databaseProvider
                ->statementBuilder("DELETE FROM tracking WHERE id = ?")
                ->withParameters($input["eventId"])
                ->execute();

            // A little hack to force the trip_summary view materialization before there's a support for propagating dependencies over functions.
            $databaseProvider
                ->statementBuilder("UPDATE view_materialization SET is_materialization_delayed = 1 WHERE view_name = '_trip_summary'")
                ->execute();

            return $deletedRowsCount == 1;
        }

        public function getRequiredArguments() {
            return array("eventId");
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }
    }
?>