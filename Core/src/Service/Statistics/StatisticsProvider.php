<?php
    namespace Core\Service\Statistics;

    interface StatisticsProvider {
        public function fetchStatistics(StatisticsType $statisticsType, StatisticsKind $statisticsKind,
            int $start, int $end, ?string $categoryId, ?string $entityId) : array;
    }
?>