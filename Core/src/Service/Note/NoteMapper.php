<?php
    namespace Core\Service\Note;
    
    use Core\Client\Database\DatabaseClient;

    class NoteMapper {
        
        private readonly DatabaseClient $databaseClient;

        public function __construct(DatabaseClient $databaseClient) {
            $this->databaseClient = $databaseClient;
        }

        public function selectNotes(NoteType $noteType, string $entityId) : array {
            $sql = <<<SQL
                SELECT ni.*
                FROM note_identifier ni
                INNER JOIN {$noteType->getTableName()} n
                    ON ni.id = n.note_id
                WHERE n.id = ?
                ORDER BY timestamp DESC
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($entityId)
                ->getMappedResultSet(function($noteRow) {
                    return new Note($noteRow["id"], $noteRow["content"], intval($noteRow["timestamp"]));
                });
        }

        public function selectNote(NoteType $noteType, string $entityId, string $noteId) : ?Note {
            $sql = <<<SQL
                SELECT ni.*
                FROM note_identifier ni
                INNER JOIN {$noteType->getTableName()} n
                    ON ni.id = n.note_id
                WHERE n.id = ?
                    AND ni.id = ?
            SQL;

            $noteRow = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($entityId, $noteId)
                ->getSingleRow();

            if ($noteRow === null) {
                return null;
            }

            return new Note($noteRow["id"], $noteRow["content"], intval($noteRow["timestamp"]));
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

            $wasInserted = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($note->getContent(), $note->getTimestamp())
                ->execute();
                 

            if ($wasInserted) {
                $note->setId($this->databaseClient->getLastInsertedId());
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

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($entityId, $noteId)
                ->execute() === 1;            
        }

        public function updateNoteContent(string $noteId, string $content) : bool {
            $sql = <<<SQL
                UPDATE note_identifier
                SET content = ?,
                    timestamp = UNIX_TIMESTAMP()
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($content, $noteId)
                ->execute() === 1;
        }

        public function updateNoteOwner(NoteType $noteType, string $noteId, string $entityId) : bool {
            $sql = <<<SQL
                UPDATE {$noteType->getTableName()}
                SET id = ?
                WHERE note_id = ?
            SQL;

            return $this->databaseClient
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

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($noteId, $entityId)
                ->execute();
        }
    }
?>