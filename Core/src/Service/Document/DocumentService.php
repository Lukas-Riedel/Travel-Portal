<?php
    namespace Core\Service\Document;

    use Common\Client\Encryption\EncryptionClient;
    use Core\Client\Database\DatabaseClient;

    class DocumentService {

        private readonly DocumentMapper $documentMapper;
        
        public function __construct(DatabaseClient $databaseClient, EncryptionClient $encryptionClient) {
            $this->documentMapper = new DocumentMapper($databaseClient, $encryptionClient);
        }

        public function getAllDocuments() : array {
            $this->documentMapper->deleteExpiredDocuments();
            return $this->documentMapper->selectAllDocuments();
        }

        public function getDocument(string $documentId) : ?Document {
            $this->documentMapper->deleteExpiredDocuments();
            return $this->documentMapper->selectDocument($documentId);
        }

        public function createDocument(string $name, string $documentId, string $issuer, ?int $expiration) : Document {
            $document = new Document(null, $name, $documentId, $issuer, $expiration);
            $this->documentMapper->insertDocument($document);

            return $document;
        }

        public function removeDocument(string $documentId) : bool {
            return $this->documentMapper->deleteDocument($documentId) > 0;
        }
    }
?>