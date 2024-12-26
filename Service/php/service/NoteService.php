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

        public function getNotes($tripId) {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("SELECT id, content FROM note WHERE trip_id = ?")
                ->withParameters($tripId)
                ->getMappedResultSet(function ($noteRow) {
                    return new Note($noteRow["id"], $noteRow["content"]);
                });
        }

        public function removeNote($noteId) : bool {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("DELETE FROM note WHERE id = ?")
                ->withParameters($noteId)
                ->execute() === 1;
        }

        public function updateNotesOwner($oldTripId, $newTripId) : bool {       
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("UPDATE note SET trip_id = ? WHERE trip_id = ?")
                ->withParameters($newTripId, $oldTripId)
                ->execute() > 0;
        }
    }
?>