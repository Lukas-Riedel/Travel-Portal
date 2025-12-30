<?php
    namespace Common;

    use Ramsey\Uuid\Uuid;

    class LoggingContext {

        private ?string $transactionId = null;

        public function getTransactionId() : ?string {
            return $this->transactionId;
        }

        public function resetTransactionId() : void {
            $this->transactionId = Uuid::uuid4()->toString();
        }

        public function setTransactionId(string $transactionId) {
            $this->transactionId = $transactionId;
        }
    }
?>
