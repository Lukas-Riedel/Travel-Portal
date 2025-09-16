<?php
    namespace Core\Client\Cache;
    
    interface CacheClient {
        public function get(string $key, ?int $newTtl = null) : mixed;
        public function set(string $key, mixed $value, int $ttl) : void;
        public function trySet(string $key, mixed $value, int $ttl) : bool;
        public function tryLock(string $key, int $ttl) : ?DistributedLock;
        public function unlock(string $key, string $value);
    }
?>