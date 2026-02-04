<?php
    namespace Common\Client\Cache;

    class Lock {

        private readonly CacheClient $cacheClient;

        private readonly string $lockKey;
        private readonly string $lockValue;

        public function __construct(CacheClient $cacheClient, string $lockKey, string $lockValue) {
            $this->cacheClient = $cacheClient;
            $this->lockKey = $lockKey;
            $this->lockValue = $lockValue;
        }

        public function unlock() {
            return $this->cacheClient->unlock($this->lockKey, $this->lockValue);
        }
    }
?>