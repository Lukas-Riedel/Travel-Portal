<?php
    namespace Core\Client\Http;

    use Core\OpenLineage\OpenLineageEventManager;
    use Monolog\Logger;
    use Common\Client\Http\HttpClient as CommonHttpClient;
    use Common\Client\Http\HttpMethod;
    use Common\LoggingContext;
    use Core\Client\Messaging\ProgressReporter;

    class HttpClient extends CommonHttpClient {
        
        private const OPENLINEAGE_DATASET_NAMESPACE_FORMAT = "%s://%s";
        private const UNKNOWN_PATH = "UNKNOWN";

        private ?ProgressReporter $progressReporter;

        private ?OpenLineageEventManager $openLineageEventManager;

        public function __construct(string $appName, LoggingContext $loggingContext, Logger $logger) {
            parent::__construct($appName, $loggingContext, $logger);
            $this->progressReporter = null;
            $this->openLineageEventManager = null;
        }

        public function setProgressReporter(ProgressReporter $progressReporter) : void {
            $this->progressReporter = $progressReporter;
        }

        public function setOpenLineageEventManager(OpenLineageEventManager $openLineageEventManager) : void {
            $this->openLineageEventManager = $openLineageEventManager;
        }

        public function executeRequest(HttpMethod $method, string $url, array $headers = array(), mixed $payload = null, bool $includeResponseHeaders = false) : mixed {            
            $this->progressReporter?->heartbeat();
            $result = parent::executeRequest($method, $url, $headers, $payload, $includeResponseHeaders);            
            $this->progressReporter?->heartbeat();

            if ($this->openLineageEventManager !== null) {
                $parsedUrl = parse_url($url);
                $namespace = sprintf(self::OPENLINEAGE_DATASET_NAMESPACE_FORMAT, $parsedUrl["scheme"], $parsedUrl["host"]);
                $name = str_replace(".", "", isset($parsedUrl["path"]) ? ltrim($parsedUrl["path"], "/") : self::UNKNOWN_PATH);
                if ($method === HttpMethod::GET) {
                    $this->openLineageEventManager->getCurrentEvent()?->addInput($namespace, $name, $result);
                }
                else {
                    $this->openLineageEventManager->getCurrentEvent()?->addOutput($namespace, $name, $payload);
                }
            }

            return $result;
        }
    }
?>