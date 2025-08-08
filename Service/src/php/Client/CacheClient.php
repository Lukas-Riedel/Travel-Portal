<?php
    namespace Service\Client;
    
    use Predis\Client;
    
    class CacheClient {

        private ?Client $redisClient = NULL;
        
        // Decrease Redis calls as much as possible by caching in memory.
        private array $cache = array();

        public function get(string $key) : mixed {
            $this->init();
            
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
            $this->init();

            $this->redisClient->set($key, json_encode($value), "EX", $ttl);
            $this->cache[$key] = json_encode($value);
        }

        public function trySet(string $key, mixed $value, int $ttl) : bool {
            $this->init();

            $wasSet = $this->redisClient->set($key, json_encode($value), "NX", "EX", $ttl) !== NULL;
            if ($wasSet) {                
                $this->cache[$key] = json_encode($value);
            }
            return $wasSet;
        }

        public function delete(string $key) : void {
            $this->init();
            
            $this->redisClient->del($key);
            unset($this->cache[$key]);
        }

        private function init() {
            if ($this->redisClient === NULL) {
                $this->redisClient = new Client(REDIS_URL);
            }
        }
    }
?>