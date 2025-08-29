<?php
    namespace Core\Client;
    
    use Predis\Client;
    
    class CacheClient {

        private ?Client $redisClient = null;
        
        // Decrease Redis calls as much as possible by caching in memory.
        // TODO: Remove after moving from shared webhosting
        private array $cache = array();

        public function get(string $key, ?int $newTtl = null) : mixed {
            $this->init();
            
            $value = isset($this->cache[$key]) ? $this->cache[$key] : null;
            if ($value !== null) {
                return json_decode($value, true);
            }

            $value = $this->doGet($key, $newTtl);
            if ($value !== null) {
                return json_decode($value, true);
            }

            return null;
        }

        public function set(string $key, mixed $value, int $ttl) : void {
            $this->init();

            $this->redisClient->set($key, json_encode($value), "EX", $ttl);
            $this->cache[$key] = json_encode($value);
        }

        public function trySet(string $key, mixed $value, int $ttl) : bool {
            $this->init();

            $wasSet = $this->redisClient->set($key, json_encode($value), "NX", "EX", $ttl) !== null;
            if ($wasSet) {                
                $this->cache[$key] = json_encode($value);
            }
            return $wasSet;
        }

        public function tryLock(string $key, int $ttl) : ?DistributedLock {
            $lockValue = uniqid("", true);
            return $this->trySet($key, $lockValue, $ttl) ? new DistributedLock($this, $key, $lockValue) : null;
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

            $this->redisClient->eval($lua, 1, $key, json_encode($value));
        }

        public function delete(string $key) : void {
            $this->init();
            
            $this->redisClient->del($key);
            unset($this->cache[$key]);
        }

        private function init() {
            if ($this->redisClient === null) {
                $this->redisClient = new Client(REDIS_URL);
            }
        }

        private function doGet(string $key, ?int $newTtl = null) : mixed {
            if ($newTtl !== null) {
                $lua = <<<'LUA'
                    local val = redis.call("GET", KEYS[1])
                    if val then
                        redis.call("EXPIRE", KEYS[1], ARGV[1])
                    end
                    return val
                LUA;

                return $this->redisClient->eval($lua, 1, $key, $newTtl);
            }

            return $this->redisClient->get($key);
        }
    }
?>