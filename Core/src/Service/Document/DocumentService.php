<?php
    namespace Core\Service\Document;

    use Common\Client\Cache\CacheClient;
    use Common\Client\Encryption\EncryptionClient;
    use Core\Client\Database\DatabaseClient;

    class DocumentService {

        private const DOCUMENT_NOTIFICATION_CACHE_KEY_FORMAT = "DocumentService:DocumentNotification:%s";

        private readonly DocumentMapper $documentMapper;
        private readonly CacheClient $distributedCacheClient;
        
        public function __construct(DatabaseClient $databaseClient, EncryptionClient $encryptionClient, CacheClient $distributedCacheClient) {
            $this->documentMapper = new DocumentMapper($databaseClient, $encryptionClient);
            $this->distributedCacheClient = $distributedCacheClient;
        }

        public function getAllDocuments() : array {
            $this->documentMapper->deleteExpiredDocuments();
            return $this->documentMapper->selectAllDocuments();
        }

        public function getDocument(string $documentId) : ?Document {
            $this->documentMapper->deleteExpiredDocuments();
            return $this->documentMapper->selectDocument($documentId);
        }

        public function createDocument(string $name, string $code, string $issuer, ?int $expiration) : Document {
            $document = new Document(null, $name, $code, $issuer, $expiration);
            $this->documentMapper->insertDocument($document);

            return $document;
        }

        public function removeDocument(string $documentId) : bool {
            return $this->documentMapper->deleteDocument($documentId) > 0;
        }

        public function getDocumentsForNotifications(int $threshold) : array {
            $documents = array();
            foreach ($this->documentMapper->selectDocumentsForNotifications($threshold) as &$document) {
                $cacheKey = sprintf(self::DOCUMENT_NOTIFICATION_CACHE_KEY_FORMAT, $document->getId());
                if ($this->distributedCacheClient->trySet($cacheKey, true, $threshold)) {
                    $documents[] = $document;
                }
            }
            return $documents;
        }
    }
?>
