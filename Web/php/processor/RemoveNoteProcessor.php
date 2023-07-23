<?php
    class RemoveNoteProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider;

            $deletedRowsCount = $databaseProvider
                ->statementBuilder("DELETE FROM note WHERE id = ? AND trip_id = ?")
                ->withParameters($input["noteId"], $input["tripId"])
                ->execute();

            return $deletedRowsCount == 1;
        }

        public function getRequiredArguments() {
            return array("noteId", "tripId");
        }
        
        public function requiresAuthentication() {
            return TRUE;
        }
    }
?>