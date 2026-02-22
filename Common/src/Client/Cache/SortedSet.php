<?php
    namespace Common\Client\Cache;

    class SortedSet {

        private readonly CacheClient $cacheClient;

        private readonly string $key;

        public function __construct(CacheClient $cacheClient, string $key) {
            $this->cacheClient = $cacheClient;
            $this->key = $key;
        }
        
        public function add(mixed $value, int $score) : void {
            $this->cacheClient->addToSortedSet($this->key, $value, $score);
        }

        public function remove(int $minScore, int $maxScore) : array {
            return $this->cacheClient->removeFromSortedSet($this->key, $minScore, $maxScore);
        }
    }
?>