<?php
    namespace Core\Service\Index;

    class IndexQueryDefinitionFactory {
        public function createPhotoNearestNeighbourQuery(array $embedding, int $limit, bool $mainHighlightsOnly) : array {
            $rawParams = array(
                "vector" => $embedding,
                "k" => $limit
            );

            $filteredTerms = array();
            if ($mainHighlightsOnly) {
                $filteredTerms["is_place_main_highlight"] = true;
            }


            if (count($filteredTerms) > 0) {
                $rawParams["filter"] = array("term" => $filteredTerms);
            }

            $params = json_encode($rawParams);

            $json = <<<JSON
                {
                    "size": $limit,
                    "_source": ["photo_id"],
                    "query": {
                        "knn": {
                            "embedding": $params
                        }
                    }
                }
            JSON;

            return json_decode($json, true);
        }

        public function createCompositeIndexSearchQuery(string $query, int $limit, array $allowedEntityTypes) : array {
            $sanitizedQuery = json_encode($query);
            $functions = json_encode(array_map(fn($type) => array("filter" => array("term" => array("entity_type" => $type->value)), "weight" => $type->getPriority()), IndexableEntityType::cases()));
            $allowedValues = json_encode(array_map(fn($entityType) => $entityType->value, $allowedEntityTypes));

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
                            "place_id": {
                                "type": "keyword"
                            },
                            "trip_id": {
                                "type": "keyword"
                            },
                            "is_place_highlight": {
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