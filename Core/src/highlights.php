<?php
    use Core\Common\CommonConstants;
    use Core\Service\Place\PlaceIncludedEntity;
    use Core\Service\Place\PlaceSortingStrategy;
    
    require_once(__DIR__ . "/bootstrap.php");
    
    $logger->pushProcessor(function($record) use(&$loggingContext) {
        $record["context"]["transaction_id"] = $loggingContext->getTransactionId();
        $record["extra"]["transaction_id"] = $loggingContext->getTransactionId();
        return $record;
    });

    $places = $placeService->getRegularPlaces(null, null, null, null, null, null, null, null, time(), null, null, array(PlaceIncludedEntity::Highlights->value, PlaceIncludedEntity::Categories->value, PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::OldestDescending);
    
    $i = 0;
    $counter = 0;

    foreach ($places as &$place) {
        $i++;
        if (count($place->getHighlights()) !== 1) {
            continue;
        }
        
        if ($distributedCacheClient->get("Temporary:HighlightsSelecting:" . $place->getId()) === null) {
            $distributedCacheClient->set("Temporary:HighlightsSelecting:" . $place->getId(), $place->getId(), CommonConstants::ONE_YEAR_SECONDS);
            ++$counter;
        }

        foreach ($place->getCategories() as &$category) {
            if ($distributedCacheClient->get("Temporary:HighlightsSelecting:" . $category->getId()) === null) {
                $distributedCacheClient->set("Temporary:HighlightsSelecting:" . $category->getId(), $category->getId(), CommonConstants::ONE_YEAR_SECONDS);
                ++$counter;
            }
        }
        
        if ($counter > 60) {
            break;
        }

        $logger->info("[HIGHLIGHTS] Refreshing highlights for " . $place->getName() . " (" . $i . "/" . count($places) . ")");
        $placeService->refreshPlaceHighlights($place->getId(), getSuggestedHighlightsCount($place->getId()));
    }
    
    function getSuggestedHighlightsCount(string $placeId) : int {
        global $placeService;

        $v = max(0.0, min(1.0, $placeService->getPlaceSignificance($placeId) / 100.0));
        $range = getenv("MAX_HIGHLIGHTS_PER_PLACE_COUNT") - getenv("MIN_HIGHLIGHTS_PER_PLACE_COUNT");
        return (int) round(getenv("MIN_HIGHLIGHTS_PER_PLACE_COUNT") + $range * pow($v, 1.4));
    }
?>