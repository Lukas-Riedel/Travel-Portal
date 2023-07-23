<?php
    require_once(dirname(__FILE__) . "/../model/Note.php");

    class AddNoteProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider;

            $databaseProvider
                ->statementBuilder("INSERT INTO note (trip_id, content) VALUES (?, ?)")
                ->withParameters($input["tripId"], $input["content"])
                ->execute();
            
            $noteRow = $databaseProvider
                ->statementBuilder("SELECT * FROM note ORDER BY id DESC LIMIT 1")
                ->getSingleRow();
                
            return new Note($noteRow["id"], $noteRow["content"]);
        }

        public function getRequiredArguments() {
            return array("tripId", "content");
        }
        
        public function requiresAuthentication() {
            return TRUE;
        }
    }
?>