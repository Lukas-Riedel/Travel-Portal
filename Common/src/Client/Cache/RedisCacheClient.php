<?php
    namespace Common\Client\Cache;

    use Common\Client\HealthCheckable;
    use Predis\Client;
    use Ramsey\Uuid\Uuid;

    class RedisCacheClient implements CacheClient, HealthCheckable {

        private const REDIS_SCHEME = "redis";

        protected readonly string $host;
        protected readonly int $port;
        private readonly string $password;

        private ?Client $redisClient = null;

        public function __construct(string $host, int $port, string $password) {
            $this->host = $host;
            $this->port = $port;
            $this->password = $password;
        }

        public function getServiceName() : string {
            return "redis";
        }

        public function isHealthy() : bool {
            try {
                $this->init();
                $ping = Uuid::uuid4()->toString();
                return $this->redisClient->ping($ping) === $ping;
            }
            catch (\Throwable $e) {
                return false;
            }
        }

        public function get(string $key, ?int $newTtl = null) : mixed {
            $this->init();

            $value = $this->redisClient->get($key);
            if ($value !== null) {
                if ($newTtl !== null) {
                    $this->redisClient->expire($key, $newTtl);
                }

                return json_decode($value, true);
            }

            return null;
        }

        public function set(string $key, mixed $value, int $ttl) : void {
            $this->init();

            $this->redisClient->set($key, json_encode($value), "EX", $ttl);
        }

        public function trySet(string $key, mixed $value, int $ttl) : bool {
            $this->init();

            return $this->redisClient->set($key, json_encode($value), "NX", "EX", $ttl) !== null;
        }

        public function tryLock(string $key, int $ttl) : ?Lock {
            $lockValue = Uuid::uuid4()->toString();
            return $this->trySet($key, $lockValue, $ttl) ? new Lock($this, $key, $lockValue) : null;
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

        public function lock(string $key, int $ttl, callable $callable) : void {
            $lock = null;

            $interval = 1;
            while (($lock = $this->tryLock($key, $ttl)) === null) {
                sleep(min($interval, $ttl - 1));
                $interval *= 2;
            }

            try {
                $callable();
            }
            finally {
                if ($lock !== null) {
                    $lock->unlock();
                }
            }
        }

        public function delete(string $key) : void {
            $this->init();

            $this->redisClient->del($key);
        }

        public function getSortedSet(string $key) : SortedSet {
            return new SortedSet($this, $key);
        }

        public function addToSortedSet(string $key, mixed $value, int $score) : void {
            $this->init();

            $this->redisClient->zadd($key, "GT", $score, json_encode($value));
        }

        public function removeFromSortedSet(string $key, int $minScore, int $maxScore) : array {
            $this->init();

            $lua = <<<'LUA'
                local members = redis.call('ZRANGEBYSCORE', KEYS[1], ARGV[1], ARGV[2])
                
                if #members > 0 then
                    redis.call('ZREM', KEYS[1], unpack(members))
                end
                
                return members
            LUA;

            $result = $this->redisClient->eval($lua, 1, $key, $minScore, $maxScore);
            return is_array($result) ? array_map(fn($value) => json_decode($value, true), $result) : array();
        }

        private function init() {
            if ($this->redisClient === null) {
                $this->redisClient = new Client(array(
                    "scheme" => self::REDIS_SCHEME,
                    "host" => $this->host,
                    "port" => $this->port,
                    "password" => $this->password,
                    "database" => 0
                ));
            }
        }

    }
?>
