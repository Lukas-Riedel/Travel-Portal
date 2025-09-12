<?php
    namespace Core\Client;

    use Core\OpenLineage\OpenLineageEventManager;
    use Predis\Client;
    
    class CacheClient {
        
        private const OPENLINEAGE_DATASET_NAMESPACE_FORMAT = "%s://%s:%s";

        private ?Client $redisClient = null;
        
        // Decrease Redis calls as much as possible by caching in memory.
        // TODO: Remove after switching to VPS.
        private array $cache = array();

        private ?OpenLineageEventManager $openLineageEventManager = null;

        public function setOpenLineageEventManager(OpenLineageEventManager $openLineageEventManager) : void {
            $this->openLineageEventManager = $openLineageEventManager;
        }

        public function get(string $key, ?int $newTtl = null) : mixed {
            $this->init();
            
            $value = isset($this->cache[$key]) ? $this->cache[$key] : null;
            if ($value !== null) {
                if ($newTtl !== null) {
                    $this->redisClient->expire($key, $newTtl);
                }

                $convertedValue = json_decode($value, true);
                $this->addOpenLineageInputDataset($key, $value);
                return $convertedValue;
            }

            $value = $this->redisClient->get($key);
            if ($value !== null) {
                if ($newTtl !== null) {
                    $this->redisClient->expire($key, $newTtl);                    
                }

                $convertedValue = json_decode($value, true);
                $this->addOpenLineageInputDataset($key, $value);
                return $convertedValue;
            }

            return null;
        }

        public function set(string $key, mixed $value, int $ttl) : void {
            $this->init();

            $this->redisClient->set($key, json_encode($value), "EX", $ttl);
            $this->cache[$key] = json_encode($value);

            $this->addOpenLineageOutputDataset($key, $value);
        }

        public function trySet(string $key, mixed $value, int $ttl) : bool {
            $this->init();

            $wasSet = $this->redisClient->set($key, json_encode($value), "NX", "EX", $ttl) !== null;
            if ($wasSet) {                
                $this->cache[$key] = json_encode($value);
                $this->addOpenLineageOutputDataset($key, $value);
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

        private function addOpenLineageInputDataset(string $key, mixed $value) : void {
            $this->addOpenLineageDataset(fn($namespace, $name, $columns) => $this->openLineageEventManager?->getCurrentEvent()?->addInput($namespace, $name, $columns), $key, $value);
        }

        private function addOpenLineageOutputDataset(string $key, mixed $value) : void {
            $this->addOpenLineageDataset(fn($namespace, $name, $columns) => $this->openLineageEventManager?->getCurrentEvent()?->addOutput($namespace, $name, $columns), $key, $value);
        }

        private function addOpenLineageDataset(callable $callable, string $key, mixed $value) : void {
            $parsedUrl = parse_url(REDIS_URL);
            $namespace = sprintf(self::OPENLINEAGE_DATASET_NAMESPACE_FORMAT, $parsedUrl["scheme"], $parsedUrl["host"], $parsedUrl["port"] ?? 6379);
            $name = str_replace(":", "/", str_replace(".", "", str_replace("/", "-", $key)));
            $callable($namespace, $name, $value);
        }
    }
?>