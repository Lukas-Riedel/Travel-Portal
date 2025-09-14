<?php
    namespace Core\Service\Note;
    
    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\TransactionManager;

    class NoteService {

        private readonly NoteMapper $noteMapper;

        private readonly TransactionManager $transactionManager;

        public function __construct(DatabaseClient $databaseClient) {
            $this->noteMapper = new NoteMapper($databaseClient);
            $this->transactionManager = $databaseClient;
        }

        public function createTripNote(string $tripId, string $content) : Note {
            return $this->createNote(NoteType::Trip, $tripId, $content);
        }

        public function createPlaceNote(string $placeId, string $content) : Note {
            return $this->createNote(NoteType::Place, $placeId, $content);
        }

        public function getTripNotes(string $tripId) : array {
            return $this->noteMapper->selectNotes(NoteType::Trip, $tripId);
        }

        public function getPlaceNotes(string $placeId) : array {
            return $this->noteMapper->selectNotes(NoteType::Place, $placeId);
        }

        public function removeTripNote(string $tripId, string $noteId) : bool {
            return $this->noteMapper->deleteNoteIdentifier(NoteType::Trip, $noteId, $tripId) > 0;
        }

        public function removePlaceNote(string $placeId, string $noteId) : bool {
            return $this->noteMapper->deleteNoteIdentifier(NoteType::Place, $noteId, $placeId) > 0;
        }

        public function updateTripNoteOwner(string $oldTripId, string $newTripId) : bool {   
            return $this->noteMapper->updateNoteOwner(NoteType::Trip, $oldTripId, $newTripId);    
        }

        private function createNote(NoteType $noteType, string $entityId, string $content) : Note {
            $note = new Note(null, $content, time());
            $this->transactionManager->executeAtomically(function() use (&$noteType, &$entityId, &$note) {
                $this->noteMapper->insertNoteIdentifier($note);
                $this->noteMapper->insertNote($noteType, $entityId, $note->getId());                
            });
            return $note;
        }
    }
?>