<?php
    require_once(dirname(__FILE__) . "/NoteMapper.php");
    require_once(dirname(__FILE__) . "/../model/Note.php");

    class NoteService {

        private readonly NoteMapper $noteMapper;

        public function __construct(DatabaseProvider $databaseProvider) {
            $this->noteMapper = new NoteMapper($databaseProvider);
        }

        public function createNote(string $tripId, string $content) : Note {
            $note = new Note(NULL, $content);
            $this->noteMapper->insertNote($note, $tripId);
            return $note;
        }

        public function getNotesForTrip(string $tripId) : array {
            return $this->noteMapper->selectNotesForTrip($tripId);
        }

        public function removeNote(string $noteId) : bool {
            return $this->noteMapper->deleteNote($noteId) > 0;
        }

        public function updateNoteTripId(string $oldTripId, string $newTripId) : bool {   
            return $this->noteMapper->updateNoteTripId($oldTripId, $newTripId);    
        }
    }
?>