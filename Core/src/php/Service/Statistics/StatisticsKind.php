<?php
    namespace Core\Service\Statistics;

    enum StatisticsKind : string {
        case Fact = "FACT";
        case Standings = "STANDINGS";
    }
?>