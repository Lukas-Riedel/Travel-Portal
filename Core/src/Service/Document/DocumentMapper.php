<?php
    namespace Core\Service\Document;

    use Common\Client\Encryption\EncryptionClient;
    use Core\Client\Database\DatabaseClient;

    class DocumentMapper {

        private readonly DatabaseClient $databaseClient;
        private readonly EncryptionClient $encryptionClient;

        public function __construct(DatabaseClient $databaseClient, EncryptionClient $encryptionClient) {
            $this->databaseClient = $databaseClient;
            $this->encryptionClient = $encryptionClient;
        }

        public function selectAllDocuments() : array {
            $sql = <<<'SQL'
                SELECT *
                FROM document
                ORDER BY name
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->getMappedResultSet(function($documentRow) {
                    return new Document($documentRow["id"], $documentRow["name"], $this->encryptionClient->decrypt($documentRow["code"]),
                        $documentRow["issuer"], $documentRow["expiration"]);
                });
        }

        public function selectDocument(string $documentId) : ?Document {
            $sql = <<<'SQL'
                SELECT *
                FROM document
                WHERE id = ?
            SQL;

            $documentRow = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($documentId)
                ->getSingleRow();

            if ($documentRow === null) {
                return null;
            }

            return new Document($documentRow["id"], $documentRow["name"], $this->encryptionClient->decrypt($documentRow["code"]),
                $documentRow["issuer"], $documentRow["expiration"]);
        }

        public function insertDocument(Document $document) : bool {
            $sql = <<<'SQL'
                INSERT INTO document (
                    name,
                    code,
                    issuer,
                    expiration
                )
                VALUES (
                    ?,
                    ?,
                    ?,
                    ?
                )
                RETURNING id
            SQL;

            $id = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($document->getName(), $this->encryptionClient->encrypt($document->getCode()),
                    $document->getIssuer(), $document->getExpiration())
                ->getSingleColumn("id");

            if ($id === null) {
                return false;
            }

            $document->setId($id);
            return true;           
        }

        public function deleteDocument(string $documentId) : int {
            $sql = <<<'SQL'
                DELETE
                FROM document
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($documentId)
                ->execute();
        }

        public function deleteExpiredDocuments() : int {
            $sql = <<<'SQL'
                DELETE
                FROM document
                WHERE expiration IS NOT NULL
                    AND expiration < ROUND(EXTRACT(EPOCH FROM NOW()))
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->execute();
        }
    }