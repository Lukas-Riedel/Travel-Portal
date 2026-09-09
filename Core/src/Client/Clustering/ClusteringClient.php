<?php
    namespace Core\Client\Clustering;

    interface ClusteringClient {
        public function getEmbeddingsClusters(array $embeddings, int $clustersCount) : array;
    }
?>
