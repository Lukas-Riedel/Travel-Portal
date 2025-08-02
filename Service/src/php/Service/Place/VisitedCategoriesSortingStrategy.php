<?php
    namespace Service\Service\Place;

    enum VisitedCategoriesSortingStrategy : string {
        case TravelDaysCountDescending = "ORDER BY COUNT(DISTINCT p.start - (p.start % 86400)) DESC";
        case VisitedPlacesCountDescending = "ORDER BY COUNT(DISTINCT p.place_id) DESC";
    }
?>