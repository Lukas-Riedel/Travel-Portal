<?php
    namespace Service\Client;
    
    use Predis\Client;
    
    class CacheClient {

        private readonly Client $redisClient;
        
        // Decrease Redis calls as much as possible by caching in memory.
        private readonly array $cache;

        public function __construct() {
            $this->redisClient = new Client(REDIS_URL);
            $this->cache = array();
        }

        public function get(string $key) : mixed {
            $value = isset($this->cache[$key]) ? $this->cache[$key] : NULL;
            if ($value !== NULL) {
                return json_decode($value, TRUE);
            }

            $value = $this->redisClient->get($key);
            if ($value !== NULL) {
                return json_decode($value, TRUE);
            }

            return NULL;
        }

        public function set(string $key, mixed $value, int $ttl) : void {
            $this->redisClient->set($key, json_encode($value), "EX", $ttl);
            $this->cache[$key] = json_encode($value);
        }
    }
?>