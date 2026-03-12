<?php
    namespace Core\Service\Index;

    class IndexQueryDefinitionFactory {
        // TODO: The hard limit in OpenSearch is 10000, fix this limitation by making multiple requests.
        public function createAllPlaceMainHighlightsEmbeddingQuery() : array {
            return array(
                "size" => 10000,
                "_source" => array("embedding"),
                "query" => array(
                    "term" => array(
                        "is_place_main_highlight" => array(
                            "value" => true
                        )
                    )
                )
            );
        }

        public function createPhotoSelectionQuery(array $embedding, int $limit, array $placeIds, array $tripIds, array $photoIds, ?bool $placeHighlightsOnly, ?bool $tripHighlightsOnly) : array {            
            $filterConditions = array();
            if (!empty($placeIds)) {
                $filterConditions[] = array("terms" => array("place_id" => $placeIds));
            }
            if (!empty($tripIds)) {
                $filterConditions[] = array("terms" => array("trip_id" => $tripIds));
            }
            if (!empty($photoIds)) {
                $filterConditions[] = array("terms" => array("photo_id" => $photoIds));
            }
            if ($placeHighlightsOnly !== null) {
                $filterConditions[] = array("term" => array("is_place_highlight" => $placeHighlightsOnly));
            }
            if ($tripHighlightsOnly !== null) {
                $filterConditions[] = array("term" => array("is_trip_highlight" => $tripHighlightsOnly));
            }
            
            return array(
                "size" => $limit,
                "_source" => array("photo_id", "highlight_id", "embedding"),
                "query" => array(
                    "function_score" => array(
                        "query" => array(
                            "script_score" => array(
                                "query" => array(
                                    "bool" => array(
                                        "filter" => $filterConditions
                                    )
                                ),
                                "script" => array(
                                    "source" => "knn_score",
                                    "lang" => "knn",
                                    "params" => array(
                                        "field" => "embedding",
                                        "query_value" => $embedding,
                                        "space_type" => "cosinesimil"
                                    )
                                )
                            )
                        ),
                        "functions" => array(
                            array(
                                "gauss" => array(
                                    "iso" => array(
                                        "origin" => "100",
                                        "scale" => "400",
                                        "decay" => 0.5
                                    )
                                ),
                                "weight" => 2
                            )
                        ),
                        "score_mode" => "multiply",
                        "boost_mode" => "multiply"
                    )
                )
            );
        }

        public function createPhotoNearestNeighbourQuery(array $embedding, int $limit, int $neighboursCount, bool $placeHighlightsOnly, bool $placeMainHighlightsOnly, bool $distinctPlacesOnly) : array {
            $filters = array();
            if ($placeMainHighlightsOnly) {
                $filters[] = array("term" => array("is_place_main_highlight" => true));
            }
            if ($placeHighlightsOnly) {
                $filters[] = array(
                    "bool" => array(
                        "should" => array(
                            array("term" => array("is_place_highlight" => true))
                        ),
                        "minimum_should_match" => 1
                    )
                );
            }

            $knnParams = array(
                "vector" => $embedding,
                "k" => $neighboursCount
            );

            if (count($filters) > 0) {
                if (count($filters) > 1) {
                    $knnParams["filter"] = array(
                        "bool" => array(
                            "must" => $filters
                        )
                    );
                } else {
                    $knnParams["filter"] = $filters[0];
                }
            }

            $query = array(
                "size" => $limit,
                "_source" => array("photo_id", "highlight_id", "place_id"),
                "query" => array(
                    "knn" => array(
                        "embedding" => $knnParams
                    )
                )
            );

            if ($distinctPlacesOnly) {
                $query["collapse"] = array("field" => "place_id");
            }

            return $query;
        }

        public function createCompositeIndexSearchQuery(string $query, int $limit, array $allowedEntityTypes) : array {
            $functions = array_values(array_map(function($type) {
                return array(
                    "filter" => array("term" => array("entity_type" => $type->value)),
                    "weight" => $type->getPriority()
                );
            }, IndexableEntityType::cases()));

            $allowedValues = array_values(array_map(function($entityType) {
                return $entityType->value;
            }, $allowedEntityTypes));

            return array(
                "size" => $limit,
                "_source" => array("entity_type", "entity_id"),
                "query" => array(
                    "function_score" => array(
                        "query" => array(
                            "bool" => array(
                                "should" => array(
                                    array(
                                        "term" => array(
                                            "entity_name.raw" => array(
                                                "value" => $query,
                                                "boost" => 1000
                                            )
                                        )
                                    ),
                                    array(
                                        "multi_match" => array(
                                            "query" => $query,
                                            "fields" => array("entity_name^10", "search_text^1"),
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
                                "filter" => array(
                                    array(
                                        "terms" => array(
                                            "entity_type" => $allowedValues
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
            );
        }

        // TODO: Externalize the settings of the index to not explicitly mention Czech here.
        public function createCompositeIndexDefinition() : array {
            return array(
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
                            "czech_stop" => array(
                                "type" => "stop",
                                "stopwords" => "_czech_"
                            ),
                            "czech_stemmer" => array(
                                "type" => "stemmer",
                                "language" => "czech"
                            ),
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
                        "entity_id" => array("type" => "keyword"),
                        "entity_type" => array("type" => "keyword"),
                        "entity_name" => array(
                            "type" => "text",
                            "analyzer" => "custom_analyzer",
                            "similarity" => "no_length_norm",
                            "fields" => array(
                                "raw" => array("type" => "keyword")
                            )
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
            );
        }

        public function createPhotoIndexDefinition() : array {
            return array(
                "settings" => array(
                    "index" => array(
                        "number_of_shards" => 1,
                        "number_of_replicas" => 0,
                        "knn" => true,
                        "knn.algo_param.ef_search" => "100"
                    )
                ),
                "mappings" => array(
                    "properties" => array(
                        "id" => array("type" => "keyword"),
                        "photo_id" => array("type" => "keyword"),
                        "album_id" => array("type" => "keyword"),
                        "highlight_id" => array("type" => "keyword"),
                        "place_id" => array("type" => "keyword"),
                        "trip_id" => array("type" => "keyword"),
                        "year" => array("type" => "keyword"),
                        "iso" => array("type" => "integer"),
                        "is_place_highlight" => array("type" => "boolean"),
                        "is_trip_highlight" => array("type" => "boolean"),
                        "is_place_main_highlight" => array("type" => "boolean"),
                        "embedding" => array(
                            "type" => "knn_vector",
                            "dimension" => 768,
                            "method" => array(
                                "name" => "hnsw",
                                "space_type" => "cosinesimil",
                                "engine" => "lucene",
                                "parameters" => array(
                                    "ef_construction" => 128,
                                    "m" => 16
                                )
                            )
                        )
                    )
                )
            );
        }
    }
?>