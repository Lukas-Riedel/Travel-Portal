<?php
    namespace Core\Service\Task;

    class EphemeralTaskCandidate {

        private readonly int $validity;
        private readonly array $placeholders;
        private readonly string $tripId;
        private readonly ?string $url;

        public function __construct(int $validity, array $placeholders, string $tripId, ?string $url) {
            $this->validity = $validity;
            $this->placeholders = $placeholders;
            $this->tripId = $tripId;
            $this->url = $url;
        }

        public function getValidity() : int {
            return $this->validity;
        }

        public function getPlaceholders() : array {
            return $this->placeholders;
        }

        public function getTripId() : string {
            return $this->tripId;
        }

        public function getUrl() : ?string {
            return $this->url;
        }
    }
?>
