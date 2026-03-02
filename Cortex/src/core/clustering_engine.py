from typing import List

import numpy as np
from sklearn.cluster import AgglomerativeClustering


class ClusteringEngine:
    def get_embeddings_clusters(self, embeddings: List[List[float]], clusters_count: int) -> List[List[int]]:
        k = min(clusters_count, len(embeddings))
        data = np.array(embeddings)

        model = AgglomerativeClustering(
            n_clusters=k,
            distance_threshold=None,
            metric="cosine",
            linkage="average"
        )

        labels = model.fit_predict(data)

        clusters = {}
        for idx, label in enumerate(labels):
            l = int(label)
            if l not in clusters:
                clusters[l] = []
            clusters[l].append(idx)

        return list(clusters.values())
