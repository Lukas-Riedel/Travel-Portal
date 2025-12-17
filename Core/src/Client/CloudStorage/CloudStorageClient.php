<?php
    namespace Core\Client\CloudStorage;

    interface CloudStorageClient {
        public function exists(string $bucket, string $key) : bool;
        public function list(string $bucket) : array;
        public function put(string $bucket, string $key, string $body) : void;
        public function delete(string $bucket, string $key) : void;
        public function getPath(string $bucket, string $key) : string;
    }
?>