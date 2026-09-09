<?php
    namespace Core\Client\Cache;

    use Common\Client\Cache\RedisCacheClient;
    use Core\OpenLineage\OpenLineageEventManager;

    // TODO: Rename to ExtendedRedisCacheClient (similar pattern to ExtendedHttpClient).
    class OpenLineageRedisCacheClient extends RedisCacheClient {

        private const REDIS_SCHEME = "redis";
        private const OPENLINEAGE_DATASET_NAMESPACE_FORMAT = self::REDIS_SCHEME . "://%s:%s";

        private OpenLineageEventManager $openLineageEventManager;

        public function __construct(string $host, int $port, string $password) {
            parent::__construct($host, $port, $password);
        }

        public function setOpenLineageEventManager(OpenLineageEventManager $openLineageEventManager) : void {
            $this->openLineageEventManager = $openLineageEventManager;
        }

        public function get(string $key, ?int $newTtl = null) : mixed {
            $value = parent::get($key, $newTtl);
            if ($value !== null) {
                $this->addOpenLineageInputDataset($key, $value);
            }
            return $value;
        }

        public function set(string $key, mixed $value, int $ttl) : void {
            parent::set($key, $value, $ttl);
            $this->addOpenLineageOutputDataset($key, $value);
        }

        public function trySet(string $key, mixed $value, int $ttl) : bool {
            $wasSet = parent::trySet($key, $value, $ttl);
            if ($wasSet) {
                $this->addOpenLineageOutputDataset($key, $value);
            }
            return $wasSet;
        }

        public function delete(string $key) : void {
            parent::delete($key);
            $this->addOpenLineageOutputDataset($key, null);
        }

        public function addToSortedSet(string $key, mixed $value, int $score) : void {
            parent::addToSortedSet($key, $value, $score);
            $this->addOpenLineageOutputDataset($key, $value);
        }

        public function removeFromSortedSet(string $key, int $minScore, int $maxScore) : array {
            $result = parent::removeFromSortedSet($key, $minScore, $maxScore);
            $this->addOpenLineageOutputDataset($key, null);
            return $result;
        }

        private function addOpenLineageInputDataset(string $key, mixed $value) : void {
            $this->addOpenLineageDataset(fn($namespace, $name, $hierarchy, $columns) => $this->openLineageEventManager->getCurrentEvent()?->addInput($namespace, $name, $hierarchy, $columns), $key, $value);
        }

        private function addOpenLineageOutputDataset(string $key, mixed $value) : void {
            $this->addOpenLineageDataset(fn($namespace, $name, $hierarchy, $columns) => $this->openLineageEventManager->getCurrentEvent()?->addOutput($namespace, $name, $hierarchy, $columns), $key, $value);
        }

        private function addOpenLineageDataset(callable $callable, string $key, mixed $value) : void {
            if (isset($this->openLineageEventManager)) {
                $namespace = sprintf(self::OPENLINEAGE_DATASET_NAMESPACE_FORMAT, $this->host, $this->port);
                $callable($namespace, $key, $this->getHierarchy($key), $value);
            }
        }

        private function getHierarchy(string $key) : array {
            $hierarchy = array();
            foreach (explode(":", $key) as &$keyToken) {
                $hierarchy[] = array("type" => "Namespace", "name" => $keyToken);
            }
            if ($hierarchy) {
                $hierarchy[array_key_last($hierarchy)]["type"] = "Key";
            }
            return $hierarchy;
        }
    }
?>
