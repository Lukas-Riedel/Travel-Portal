<?php

    namespace Core\Service\Stay;

    enum StaySortingStrategy : string {
        case DurationDescending = "ORDER BY (end - start) DESC";
    }
?>