<?php
    namespace Core\Client\Cache;

    use Ramsey\Uuid\Uuid;

    class MemoryCacheClient implements CacheClient {

        private array $expirations = array();
        private array $values = array();

        public function get(string $key, ?int $newTtl = null) : mixed {
            $this->prune();

            if ($newTtl !== null && isset($this->expirations[$key])) {
                $this->expirations[$key] = time() + $newTtl;
            }
            
            return $this->values[$key] ?? null;
        }

        public function set(string $key, mixed $value, int $ttl) : void {
            $this->prune();

            $this->expirations[$key] = time() + $ttl;
            $this->values[$key] = $value;
        }

        public function trySet(string $key, mixed $value, int $ttl) : bool {
            $this->prune();

            if (isset($this->values[$key])) {
                return false;
            }
            
            $this->set($key, $value, $ttl);
            return true;
        }

        public function tryLock(string $key, int $ttl) : ?Lock {
            $lockValue = Uuid::uuid4()->toString();
            return $this->trySet($key, $lockValue, $ttl) ? new Lock($this, $key, $lockValue) : null;
        }

        public function unlock(string $key, string $value) : void {
            $this->prune();

            if (!isset($this->values[$key]) || $this->values[$key] !== $value) {
                return;
            }

            $this->delete($key);
        }

        public function lock(string $key, int $ttl, callable $callable) : void {
            // PHP is single-threaded, so there is no need to lock.
            $callable();
        }

        public function delete(string $key) : void {
            $this->prune();
            
            unset($this->values[$key]);
            unset($this->expirations[$key]);
        }

        public function getSortedSet(string $key) : SortedSet {
            return new SortedSet($this, $key);
        }

        public function addToSortedSet(string $key, mixed $value, int $score) : void {
            $this->prune();

            if (!isset($this->values[$key])) {
                $this->values[$key] = array();
                $this->expirations[$key] = PHP_INT_MAX;
            }

            $this->values[$key][] = array("value" => $value, "score" => $score);
            usort($this->values[$key], fn($a, $b) => $a["score"] <=> $b["score"]);
        }

        public function removeFromSortedSet(string $key, int $minScore, int $maxScore) : array {
            $this->prune();

            if (!isset($this->values[$key])) {
                return array();
            }

            $removed = array();
            foreach ($this->values[$key] as $i => $item) {
                if ($item["score"] >= $minScore && $item["score"] <= $maxScore) {
                    $removed[] = $item["value"];
                    unset($this->values[$key][$i]);
                }
            }

            $this->values[$key] = array_values($this->values[$key]);

            return $removed;
        }

        private function prune() {
            foreach ($this->expirations as $key => $expiration) {
                if (time() > $expiration) {
                    unset($this->values[$key]);
                    unset($this->expirations[$key]);
                }
            }
        }
    }
?>
