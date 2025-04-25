<?php
    class NoteMapper {
        
        private readonly DatabaseProvider $databaseProvider;

        public function __construct(DatabaseProvider $databaseProvider) {
            $this->databaseProvider = $databaseProvider;
        }

        public function selectNotesForTrip(string $tripId) : array {
            $sql = <<<'SQL'
                SELECT *
                FROM note
                WHERE trip_id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($tripId)
                ->getMappedResultSet(function($noteRow) {
                    return new Note($noteRow["id"], $noteRow["content"]);
                });
        }

        public function insertNote(Note $note, string $tripId) : bool {
            $sql = <<<'SQL'
                INSERT INTO note(
                    trip_id,
                    content
                )
                VALUES (
                    ?,
                    ?
                )
            SQL;

            $wasInserted = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($tripId, $note->getContent())
                ->execute();
                 

            if ($wasInserted) {
                $note->setId($this->databaseProvider->getLastInsertedId());
            }

            return $wasInserted;
        }

        public function updateNoteTripId(string $noteId, string $tripId) : bool {
            $sql = <<<'SQL'
                UPDATE note
                SET trip_id = ?
                WHERE id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($tripId, $noteId)
                ->execute() === 1;
        }

        public function deleteNote(string $noteId) : int {
            $sql = <<<'SQL'
                DELETE
                FROM note
                WHERE id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($noteId)
                ->execute();
        }
    }
?>