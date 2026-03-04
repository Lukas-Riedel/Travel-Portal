<?php
    namespace Core\Service\Index;

    // TODO: Figure out what to do with this class. I don't like it's current form at all (the mixture of PHP and JSON).
    class IndexQueryDefinitionFactory {
        public function createAllPlaceMainHighlightsEmbeddingQuery() : array {
            // TODO: The hard limit is 10000, fix this limitation by making multiple requests.
            $json = <<<JSON
                {
                    "size": 10000,
                    "_source": [
                        "embedding"
                    ],
                    "query": {
                        "term": {
                            "is_place_main_highlight": {
                                "value": true
                            }
                        }
                    }
                }
            JSON;

            return json_decode($json, true);
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
            
            $vector = json_encode($embedding);
            $filter = json_encode($filterConditions);

            $json = <<<JSON
            {
                "size": $limit,
                "_source": [
                    "photo_id",
                    "highlight_id",
                    "embedding"
                ],
                "query": {
                    "function_score": {
                        "query": {
                            "script_score": {
                                "query": {
                                    "bool": {
                                    "filter": $filter
                                    }
                                },
                                "script": {
                                    "source": "knn_score",
                                    "lang": "knn",
                                    "params": {
                                        "field": "embedding",
                                        "query_value": $vector,
                                        "space_type": "cosinesimil"
                                    }
                                }
                            }
                        },
                        "functions": [
                            {
                                "gauss": {
                                    "iso": {
                                        "origin": "100",
                                        "scale": "400",
                                        "decay": 0.5
                                    }
                                },
                                "weight": 2
                            }
                        ],
                        "score_mode": "multiply",
                        "boost_mode": "multiply"
                    }
                }
            }
            JSON;

            return json_decode($json, true);
        }

        public function createPhotoNearestNeighbourQuery(array $embedding, int $limit, bool $placeHighlightsOnly, bool $placeMainHighlightsOnly, bool $distinctPlacesOnly) : array {
            $rawParams = array(
                "vector" => $embedding,
                "k" => $limit
            );

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

            if (count($filters) > 0) {
                if (count($filters) > 1) {
                    $rawParams["filter"] = array(
                        "bool" => array(
                            "must" => $filters
                        )
                    );
                }
                else {
                    $rawParams["filter"] = $filters[0];
                }
            }

            $params = json_encode($rawParams);

            $json = <<<JSON
                {
                    "size": $limit,
                    "collapse": {
                        "field": "place_id" 
                    },
                    "_source": [
                        "photo_id",
                        "highlight_id",
                        "place_id"
                    ],
                    "query": {
                        "knn": {
                            "embedding": $params
                        }
                    }
                }
            JSON;

            $query = json_decode($json, true);
            if ($distinctPlacesOnly) {
                $query["collapse"] = array("field" => "place_id");
            }
            return $query;
        }

        public function createCompositeIndexSearchQuery(string $query, int $limit, array $allowedEntityTypes) : array {
            $sanitizedQuery = json_encode($query);
            $functions = json_encode(array_values(array_map(fn($type) => array("filter" => array("term" => array("entity_type" => $type->value)), "weight" => $type->getPriority()), IndexableEntityType::cases())));
            $allowedValues = json_encode(array_values(array_map(fn($entityType) => $entityType->value, $allowedEntityTypes)));

            $json = <<<JSON
                {
                    "size": $limit,
                    "_source": [
                        "entity_type",
                        "entity_id"
                    ],
                    "query": {
                        "function_score": {
                            "query": {
                                "bool": {
                                    "should": [
                                        {
                                            "term": {
                                                "entity_name.raw": {
                                                    "value": $sanitizedQuery,
                                                    "boost": 1000
                                                }
                                            }
                                        },
                                        {
                                            "multi_match": {
                                                "query": $sanitizedQuery,
                                                "fields": [
                                                    "entity_name^10",
                                                    "search_text^1"
                                                ],
                                                "type": "best_fields",
                                                "operator": "and",
                                                "fuzziness": "AUTO",
                                                "prefix_length": 2
                                            }
                                        },
                                        {
                                            "match": {
                                                "search_text.ngram": {
                                                    "query": $sanitizedQuery,
                                                    "boost": 0.2
                                                }
                                            }
                                        },
                                        {
                                            "match": {
                                                "search_text": {
                                                    "query": $sanitizedQuery,
                                                    "fuzziness": "AUTO",
                                                    "prefix_length": 2,
                                                    "boost": 0.5
                                                }
                                            }
                                        }
                                    ],
                                    "filter": [
                                        {
                                            "terms": { 
                                                "entity_type": $allowedValues
                                            }
                                        }
                                    ],
                                    "minimum_should_match": 1
                                }
                            },
                            "functions": $functions,
                            "score_mode": "multiply",
                            "boost_mode": "sum"
                        }
                    }
                }
            JSON;

            return json_decode($json, true);
        }

        // TODO: Externalize the settings of the index to not explicitly mention Czech here.
        public function createCompositeIndexDefinition() : array {
            $json = <<<'JSON'
                {
                    "settings": {
                        "index": {
                            "number_of_shards": 1,
                            "number_of_replicas": 0,
                            "similarity": {
                                "no_length_norm": {
                                    "type": "BM25",
                                    "b": 0
                                }
                            }
                        },
                        "analysis": {
                            "filter": {
                                "czech_stop": {
                                    "type": "stop",
                                    "stopwords": "_czech_"
                                },
                                "czech_stemmer": {
                                    "type": "stemmer",
                                    "language": "czech"
                                },
                                "my_ngram_filter": {
                                    "type": "edge_ngram",
                                    "min_gram": 3,
                                    "max_gram": 10
                                }
                            },
                            "analyzer": {
                                "custom_analyzer": {
                                    "tokenizer": "standard",
                                    "filter": [
                                        "lowercase",
                                        "asciifolding",
                                        "czech_stop",
                                        "czech_stemmer"
                                    ]
                                },
                                "ngram_analyzer": {
                                    "tokenizer": "standard",
                                    "filter": [
                                        "lowercase",
                                        "asciifolding",
                                        "my_ngram_filter"
                                    ]
                                }
                            }
                        }
                    },
                    "mappings": {
                        "properties": {
                            "id": {
                                "type": "keyword"
                            },
                            "entity_id": {
                                "type": "keyword"
                            },
                            "entity_type": {
                                "type": "keyword"
                            },
                            "entity_name": {
                                "type": "text",
                                "analyzer": "custom_analyzer",
                                "similarity": "no_length_norm",
                                "fields": {
                                    "raw": { 
                                        "type": "keyword" 
                                    }
                                }
                            },
                            "search_text": {
                                "type": "text",
                                "analyzer": "custom_analyzer",
                                "similarity": "no_length_norm",
                                "fields": {
                                    "ngram": {
                                        "type": "text",
                                        "analyzer": "ngram_analyzer"
                                    }
                                }
                            }
                        }
                    }
                }        
            JSON;

            return json_decode($json, true);
        }

        public function createPhotoIndexDefinition() : array {
            $json = <<<'JSON'
                {
                    "settings": {
                        "index": {
                            "number_of_shards": 1,
                            "number_of_replicas": 0,
                            "knn": true,
                            "knn.algo_param.ef_search": "100"
                        }
                    },
                    "mappings": {
                        "properties": {
                            "id": {
                                "type": "keyword"
                            },
                            "photo_id": {
                                "type": "keyword"
                            },
                            "album_id": {
                                "type": "keyword"
                            },
                            "highlight_id": {
                                "type": "keyword"
                            },
                            "place_id": {
                                "type": "keyword"
                            },
                            "trip_id": {
                                "type": "keyword"
                            },
                            "iso": {
                                "type": "integer"
                            },
                            "is_place_highlight": {
                                "type": "boolean"
                            },
                            "is_trip_highlight": {
                                "type": "boolean"
                            },
                            "is_place_main_highlight": {
                                "type": "boolean"
                            },
                            "embedding": {
                                "type": "knn_vector",
                                "dimension": 768,
                                "method": {
                                    "name": "hnsw",
                                    "space_type": "cosinesimil",
                                    "engine": "lucene",
                                    "parameters": {
                                        "ef_construction": 128,
                                        "m": 16
                                    }
                                }
                            }
                        }
                    }
                }
            JSON;

            return json_decode($json, true);
        }
    }
?>