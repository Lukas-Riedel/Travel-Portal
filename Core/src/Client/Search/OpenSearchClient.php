<?php
    namespace Core\Client\Search;

    use Common\Client\HealthCheckable;
    use Core\OpenLineage\OpenLineageEventManager;
    use OpenSearch\Client;
    use OpenSearch\ClientBuilder;

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

        public function createIndex(string $index) : void {
            $this->init();

            // TODO: Externalize the settings of the index to not explicitly mention Czech here.
            $params = array(
                "index" => $index,
                "body"  => array(
                    "settings" => array(
                        "index" => array(
                            "number_of_shards" => 1,
                            "number_of_replicas" => 0,
                            "similarity" => array(
                                "no_length_norm" => array(
                                    "type" => "BM25",
                                    "b" => 0
                                )
                            )
                        ),
                        "analysis" => array(
                            "filter" => array(
                                "czech_stop" => array("type" => "stop", "stopwords" => "_czech_"),
                                "czech_stemmer" => array("type" => "stemmer", "language" => "czech"),
                                "my_ngram_filter" => array(
                                    "type" => "edge_ngram",
                                    "min_gram" => 3,
                                    "max_gram" => 10
                                )
                            ),
                            "analyzer" => array(
                                "custom_analyzer" => array(
                                    "tokenizer" => "standard",
                                    "filter" => array("lowercase", "asciifolding", "czech_stop", "czech_stemmer")
                                ),
                                "ngram_analyzer" => array(
                                    "tokenizer" => "standard",
                                    "filter" => array("lowercase", "asciifolding", "my_ngram_filter")
                                )
                            )
                        )
                    ),
                    "mappings" => array(
                        "properties" => array(
                            "id" => array("type" => "keyword"),
                            "entity_type" => array("type" => "keyword"),
                            "entity_name" => array(
                                "type" => "text",
                                "analyzer" => "custom_analyzer",
                                "similarity" => "no_length_norm"
                            ),
                            "search_text" => array(
                                "type" => "text", 
                                "analyzer" => "custom_analyzer",
                                "similarity" => "no_length_norm",
                                "fields" => array(
                                    "ngram" => array(
                                        "type" => "text",
                                        "analyzer" => "ngram_analyzer"
                                    )
                                )
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
                foreach ($this->getUniqueArrays(array_map(fn($document) => array_keys($document), $documents)) as &$columns) {
                    $this->addOpenLineageOutputDataset($index, $columns);
                }
            }
        }

        public function search(string $index, string $query, array $weights, array $filters, int $limit) : array {
            $this->init();

            $functions = array();
            foreach ($weights as $field => $terms) {
                foreach ($terms as $term => $priority) {
                    $functions[] = array(
                        "filter" => array("term" => array($field => $term)),
                        "weight" => $priority
                    );
                }
            }

            $params = array(
                "index" => $index,
                "body"  => array(
                    "size" => $limit,
                    "query" => array(
                        "function_score" => array(
                            "query" => array(
                                "bool" => array(
                                    "should" => array(
                                        array(
                                            "multi_match" => array(
                                                "query" => $query,
                                                "fields" => array(
                                                    "entity_name^10",
                                                    "search_text^1"
                                                ),
                                                "type" => "best_fields",
                                                "operator" => "and",
                                                "fuzziness" => "AUTO",
                                                "prefix_length" => 2
                                            )
                                        ),
                                        array(
                                            "match" => array(
                                                "search_text.ngram" => array(
                                                    "query" => $query,
                                                    "boost" => 0.2
                                                )
                                            )
                                        ),
                                        array(
                                            "match" => array(
                                                "search_text" => array(
                                                    "query" => $query,
                                                    "fuzziness" => "AUTO",
                                                    "prefix_length" => 2,
                                                    "boost" => 0.5
                                                )
                                            )
                                        )
                                    ),
                                    "minimum_should_match" => 1
                                )
                            ),
                            "functions" => $functions,
                            "score_mode" => "multiply",
                            "boost_mode" => "sum"
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
            
            $result = array_map(fn($hit) => $hit["_source"], $response["hits"]["hits"] ?? array());


            if (!empty($result)) {
                foreach ($this->getUniqueArrays(array_map(fn($item) => array_keys($item), $result)) as &$columns) {
                    $this->addOpenLineageInputDataset($index, $columns);
                }
            }

            return $result;
        }

        public function delete(string $index, string $id) : void {
            $this->init();

            $this->client->delete(array(
                "index" => $index,
                "id" => $id
            ));
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