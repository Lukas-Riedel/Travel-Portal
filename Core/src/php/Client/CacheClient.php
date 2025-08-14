<?php
    namespace Core\Client;
    
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

        public function tryLock(string $key, int $ttl) : ?DistributedLock {
            $lockValue = uniqid("", TRUE);
            return $this->trySet($key, $lockValue, $ttl) ? new DistributedLock($this, $key, $lockValue) : NULL;
        }

        public function unlock(string $key, string $value) : void {
            $this->init();

            $lua = <<<'LUA'
                if redis.call('get', KEYS[1]) == ARGV[1] then
                    return redis.call('del', KEYS[1])
                else
                    return 0
                end
            LUA;

            $this->redisClient->eval($lua, 1, $key, $value);
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