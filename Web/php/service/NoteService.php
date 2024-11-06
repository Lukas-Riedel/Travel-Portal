<?php
    require_once(dirname(__FILE__) . "/../model/Note.php");

    class NoteService {
        public function createNote($tripId, $content) : Note {
            global $databaseProvider;

            $databaseProvider
                ->statementBuilder("INSERT INTO note (trip_id, content) VALUES (?, ?)")
                ->withParameters($tripId, $content)
                ->execute();
            
            $noteRow = $databaseProvider
                ->statementBuilder("SELECT * FROM note ORDER BY id DESC LIMIT 1")
                ->getSingleRow();
                
            return new Note($noteRow["id"], $noteRow["content"]);
        }
    }
?>