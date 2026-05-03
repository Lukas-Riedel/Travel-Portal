<?php
    namespace Core\Client\Proxy;

    // TODO: Add support for more HTTP methods. Or, drop completely, and implement as a decorator for HttpClient?
    interface ProxyClient {
        public function get(string $url) : mixed;
    }
?>