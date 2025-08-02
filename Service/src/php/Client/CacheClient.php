<?php
    namespace Service\Client;
    
    class CacheClient {

        private readonly \DatabaseProvider $databaseProvider;

        public function __construct(\DatabaseProvider $databaseProvider) {
            $this->databaseProvider = $databaseProvider;
        }

        public function get(string $key) : mixed {
            $this->prune();

            $value = $this->databaseProvider
                ->statementBuilder("SELECT value FROM cache WHERE `key` = ? AND expiration > UNIX_TIMESTAMP()")
                ->withParameters($key)
                ->getSingleColumn("value");

            if ($value === NULL) {
                return NULL;
            }

            return json_decode($value, TRUE);
        }

        public function set(string $key, mixed $value, int $ttl) : void {
            $this->prune();

            $this->databaseProvider
                ->statementBuilder("DELETE FROM cache WHERE `key` = ?")
                ->withParameters($key)
                ->execute();

            $this->databaseProvider
                ->statementBuilder("INSERT INTO cache (`key`, value, expiration) VALUES (?, ?, ?)")
                ->withParameters($key, json_encode($value), time() + $ttl)
                ->execute();
        }

        private function prune() : void {            
            $this->databaseProvider
                ->statementBuilder("DELETE FROM cache WHERE expiration < UNIX_TIMESTAMP()")
                ->execute();
        }
    }
?>