<?php
    namespace Core\Client\Search;

    use Common\Client\HealthCheckable;
    use Core\OpenLineage\OpenLineageEventManager;
    use OpenSearch\Client;
    use OpenSearch\GuzzleClientFactory;

    class OpenSearchClient implements SearchClient, HealthCheckable {
        
        private const OPEN_SEARCH_SCHEME = "opensearch";        
        private const OPENLINEAGE_DATASET_NAMESPACE_FORMAT = self::OPEN_SEARCH_SCHEME . "://%s:%s";

        private readonly string $host;
        private readonly int $port;

        private ?Client $client = null;
        
        private ?OpenLineageEventManager $openLineageEventManager;

        public function __construct(string $host, int $port) {
            $this->host = $host;
            $this->port = $port;
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

        public function createIndex(string $index) : void {
            $this->init();

            $params = array(
                "index" => $index,
                "body"  => array(
                    "settings" => array(
                        "index" => array(
                            "number_of_shards" => 1,
                            "number_of_replicas" => 0
                        )
                    ),
                    "mappings" => array(
                        "properties" => array(
                            "id" => array("type" => "keyword"),
                            "entity_type" => array("type" => "keyword"),
                            "search_text" => array(
                                "type" => "text", 
                                "analyzer" => "standard"
                            )
                        )
                    )
                )
            );

            $this->client->indices()->create($params);
            $this->addOpenLineageOutputDataset($index, null);
        }

        public function deleteIndex(string $index) : void {
            $this->init();

            if ($this->client->indices()->exists(array("index" => $index))) {
                $this->client->indices()->delete(array("index" => $index));
                $this->addOpenLineageOutputDataset($index, null);
            }
        }

        public function index(string $index, array $documents) : void {
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

            if (!empty($documents)) {
                $this->addOpenLineageOutputDataset($index, array_values(array_unique(array_map(fn($document) => array_keys($document), $documents))));
            }
        }

        public function search(string $index, string $query, array $filters, int $limit) : array {
            $this->init();

            $params = array(
                "index" => $index,
                "body"  => array(
                    "size" => $limit,
                    "query" => array(
                        "bool" => array(
                            "must" => array(
                                "match" => array(
                                    "search_text" => array(
                                        "query" => $query,
                                        "fuzziness" => "AUTO",
                                        "operator" => "and"
                                    )
                                )
                            ),
                            "filter" => array()
                        )
                    )
                )
            );

            foreach ($filters as $key => $value) {
                $params["body"]["query"]["bool"]["filter"][] = array(
                    "term" => array($key => $value)
                );
            }

            $response = $this->client->search($params);
            
            $result = array_map(fn($hit) => array(
                "id" => $hit["_id"],
                "score" => $hit["_score"],
                "source" => $hit["_source"]
            ), $response["hits"]["hits"] ?? array());


            if (!empty($result)) {
                $this->addOpenLineageInputDataset($index, array_values(array_unique(array_map(fn($item) => $item["source"], $result))));
            }

            return $result;
        }

        public function delete(string $index, string $id) : void {
            $this->init();

            $this->client->delete(array(
                "index" => $index,
                "id"    => $id
            ));
            $this->addOpenLineageOutputDataset($index, null);
        }

        private function init() : void {
            if ($this->client === null) {
                $this->client = (new GuzzleClientFactory())->create(array("base_uri" => sprintf("http://%s:%s", $this->host, $this->port)));
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
    }
?>