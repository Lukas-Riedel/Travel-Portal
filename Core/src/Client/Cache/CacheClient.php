<?php
    namespace Core\Client\Cache;
    
    interface CacheClient {
        public function get(string $key, ?int $newTtl = null) : mixed;
        public function set(string $key, mixed $value, int $ttl) : void;
        public function trySet(string $key, mixed $value, int $ttl) : bool;
        public function tryLock(string $key, int $ttl) : ?Lock;
        public function unlock(string $key, string $value) : void;
        public function lock(string $key, int $ttl, callable $callable) : void;
        public function delete(string $key) : void;
        public function getSortedSet(string $key) : SortedSet;
        public function addToSortedSet(string $key, mixed $value, int $score) : void;
        public function removeFromSortedSet(string $key, int $minScore, int $maxScore) : array;
    }
?>