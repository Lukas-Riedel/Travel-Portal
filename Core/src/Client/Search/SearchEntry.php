<?php
    namespace Core\Client\Search;

    class SearchEntry {
        private readonly float $score;
        private readonly array $data;

        public function __construct(float $score, array $data) {
            $this->score = $score;
            $this->data = $data;
        }

        public function getScore() : float {
            return $this->score;
        }

        public function getData() : array {
            return $this->data;
        }
    }
?>