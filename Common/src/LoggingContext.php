<?php
    namespace Common;

    use Ramsey\Uuid\Uuid;

    class LoggingContext {

        private string $transactionId;
        private ?string $requestOrigin;

        public function __construct() {
            $this->transactionId = Uuid::uuid4()->toString();
            $this->requestOrigin = null;
        }

        public function getTransactionId() : string {
            return $this->transactionId;
        }

        public function resetTransactionId() : void {
            $this->transactionId = Uuid::uuid4()->toString();
        }

        public function setTransactionId(string $transactionId) {
            $this->transactionId = $transactionId;
        }

        public function getRequestOrigin() : ?string {
            return $this->requestOrigin;
        }
        
        public function setRequestOrigin(string $requestOrigin) : void {
            $this->requestOrigin = $requestOrigin;
        }

        public function resetRequestOrigin() : void {
            $this->requestOrigin = null;
        }
    }
?>
