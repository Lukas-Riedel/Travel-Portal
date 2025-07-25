<?php
    namespace Service\Service\Note;

    class NoteMapper {
        
        private readonly \DatabaseProvider $databaseProvider;

        public function __construct(\DatabaseProvider $databaseProvider) {
            $this->databaseProvider = $databaseProvider;
        }

        public function selectNotes(NoteType $noteType, string $entityId) : array {
            $sql = <<<SQL
                SELECT ni.*
                FROM note_identifier ni
                INNER JOIN {$noteType->getTableName()} n
                    ON ni.id = n.note_id
                WHERE n.id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($entityId)
                ->getMappedResultSet(function($noteRow) {
                    return new Note($noteRow["id"], $noteRow["content"], intval($noteRow["timestamp"]));
                });
        }

        public function insertNoteIdentifier(Note $note) : bool {
            $sql = <<<'SQL'
                INSERT INTO note_identifier (
                    content,
                    timestamp
                )
                VALUES (
                    ?,
                    ?
                )
            SQL;

            $wasInserted = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($note->getContent(), $note->getTimestamp())
                ->execute();
                 

            if ($wasInserted) {
                $note->setId($this->databaseProvider->getLastInsertedId());
            }

            return $wasInserted;
        }

        public function insertNote(NoteType $noteType, string $entityId, string $noteId) : bool {
            $sql = <<<SQL
                INSERT INTO {$noteType->getTableName()} (
                    id, 
                    note_id
                ) 
                VALUES (
                    ?, 
                    ?
                )
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($entityId, $noteId)
                ->execute() === 1;            
        }

        public function updateNoteOwner(NoteType $noteType, string $noteId, string $entityId) : bool {
            $sql = <<<SQL
                UPDATE {$noteType->getTableName()}
                SET id = ?
                WHERE note_id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($entityId, $noteId)
                ->execute() === 1;
        }

        public function deleteNoteIdentifier(NoteType $noteType, string $noteId, string $entityId) : int {
            $sql = <<<SQL
                DELETE ni
                FROM note_identifier ni
                WHERE ni.id = ?
                    AND EXISTS (
                        SELECT 1
                        FROM {$noteType->getTableName()} n
                        WHERE n.note_id = ni.id
                            AND n.id = ?
                    )
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($noteId, $entityId)
                ->execute();
        }
    }
?>