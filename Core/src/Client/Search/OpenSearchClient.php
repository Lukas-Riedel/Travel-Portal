<?php
    namespace Core\Client\Search;

    use Common\Client\HealthCheckable;
    use Core\OpenLineage\OpenLineageEventManager;
    use Monolog\Logger;
    use OpenSearch\Client;
    use OpenSearch\ClientBuilder;

    class OpenSearchClient implements SearchClient, HealthCheckable {
        
        private const OPEN_SEARCH_SCHEME = "opensearch";        
        private const OPENLINEAGE_DATASET_NAMESPACE_FORMAT = self::OPEN_SEARCH_SCHEME . "://%s:%s";

        private readonly Logger $logger;

        private readonly string $host;
        private readonly int $port;

        private ?Client $client = null;        
        private ?OpenLineageEventManager $openLineageEventManager;

        public function __construct(string $host, int $port, Logger $logger) {
            $this->host = $host;
            $this->port = $port;
            $this->logger = $logger;
            $this->openLineageEventManager = null;
        }

        public function setOpenLineageEventManager(OpenLineageEventManager $openLineageEventManager) : void {
            $this->openLineageEventManager = $openLineageEventManager;
        }

        public function getServiceName() : string {
            return "opensearch";
        }

        public function isHealthy() : bool {
            try {
                $this->init();
                return $this->client->ping();
            }
            catch (\Throwable $e) {
                return false;
            }
        }

        public function createIndex(string $index, array $definition) : void {
            $this->init();

            $this->client->indices()->create(array("index" => $index, "body"  => $definition));
            $this->addOpenLineageOutputDataset($index, null);
        }

        public function deleteIndex(string $index) : void {
            $this->init();

            if ($this->client->indices()->exists(array("index" => $index))) {
                $this->client->indices()->delete(array("index" => $index));
                $this->addOpenLineageOutputDataset($index, null);
            }
        }        

        public function deleteUnusedIndexes(array $usedIndexes) : void {
            $allSettings = $this->client->indices()->getSettings();
            $indices = array_keys($allSettings);

            foreach ($indices as $index) {
                if (str_starts_with($index, ".")) {
                    continue;
                }

                if (in_array($index, $usedIndexes)) {
                    continue;
                }

                try {
                    $this->deleteIndex($index);
                }
                catch (\Throwable $e) {
                    // Do nothing. The index has already been deleted.
                }
            }
        }

        public function reassignAlias(string $alias, string $index) : void {
            $this->init();

            $oldIndices = array();
            try {
                $oldIndices = array_keys($this->client->indices()->getAlias(["name" => $alias]));
            }
            catch (\Throwable $e) {
                // The alias does not exist -> the array will remain empty.
            }

            $actions = array(array("add" => array("index" => $index, "alias" => $alias)));
            foreach ($oldIndices as &$oldIndex) {
                $actions[] = array("remove" => array("index" => $oldIndex, "alias" => $alias));
            }

            $this->client->indices()->updateAliases(array("body" => array("actions" => $actions)));

            foreach ($oldIndices as &$oldIndex) {
                $remainingAliases = $this->client->indices()->getAlias(array("index" => $oldIndex));
        
                if (empty($remainingAliases[$oldIndex]["aliases"])) {
                    try {
                        $this->deleteIndex($oldIndex);
                    }
                    catch (\Throwable $e) {
                        // Do nothing. The alias has already been deleted.
                    }
                }
            }
            
            $this->addOpenLineageInputDataset($index, null);
            $this->addOpenLineageOutputDataset($alias, null);
        }

        public function index(string $index, array $documents) : void {
            if (empty($documents)) {
                return;
            }

            $this->init();

            $params = array("body" => array());
            foreach ($documents as &$document) {
                $params["body"][] = array(
                    "index" => array(
                        "_index" => $index,
                        "_id" => $document["id"]
                    )
                );
                $params["body"][] = $document;
            }

            $this->client->bulk($params);

            foreach ($this->getUniqueArrays(array_map(fn($document) => array_keys($document), $documents)) as &$columns) {
                $this->addOpenLineageOutputDataset($index, $columns);
            }
        }

        public function search(string $index, array $query) : array {
            $this->init();

            $response = $this->client->search(array("index" => $index, "body"  => $query));            
            $result = array_map(fn($hit) => new SearchEntry($hit["_score"], $hit["_source"]), $response["hits"]["hits"] ?? array());

            if (!empty($result)) {
                foreach ($this->getUniqueArrays(array_map(fn($item) => array_keys($item->getData()), $result)) as &$columns) {
                    $this->addOpenLineageInputDataset($index, $columns);
                }
            }

            $this->logger->debug("Totally " . count($result) . " results were found in the '$index' index.", array("query" => $query));

            return $result;
        }

        public function delete(string $index, string $id) : void {
            $this->init();

            $this->client->delete(array("index" => $index, "id" => $id));

            $this->addOpenLineageOutputDataset($index, null);
        }

        private function init() : void {
            if ($this->client === null) {
                $this->client = ClientBuilder::create()->setHosts(array(sprintf("http://%s:%s", $this->host, $this->port)))->build();
            }
        }

        private function addOpenLineageInputDataset(string $key, mixed $value) : void {
            $this->addOpenLineageDataset(fn($namespace, $name, $columns) => $this->openLineageEventManager->getCurrentEvent()?->addInput($namespace, $name, $columns), $key, $value);
        }

        private function addOpenLineageOutputDataset(string $key, mixed $value) : void {
            $this->addOpenLineageDataset(fn($namespace, $name, $columns) => $this->openLineageEventManager->getCurrentEvent()?->addOutput($namespace, $name, $columns), $key, $value);
        }

        private function addOpenLineageDataset(callable $callable, string $key, mixed $value) : void {
            if ($this->openLineageEventManager !== null) {
                $namespace = sprintf(self::OPENLINEAGE_DATASET_NAMESPACE_FORMAT, $this->host, $this->port);
                $name = str_replace(":", "/", str_replace(".", "", str_replace("/", "-", $key)));
                $callable($namespace, $name, $value);
            }
        }

        private function getUniqueArrays(array $arrays) : array {
            $serializedArrays = array_map(fn($array) => json_encode($array), $arrays);
            $uniqueSerializedArrays = array_unique($serializedArrays);
            return array_map(fn($serializedArray) => json_decode($serializedArray, true), $uniqueSerializedArrays);
        }
    }
?>