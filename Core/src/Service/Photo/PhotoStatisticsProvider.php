<?php
    namespace Core\Service\Photo;

    use Core\Common\CommonConstants;
    use Core\Service\Place\PlaceIncludedEntity;
    use Core\Service\Place\PlaceService;
    use Core\Service\Place\PlaceSortingStrategy;
    use Core\Service\Statistics\KeyValuePair;
    use Core\Service\Statistics\Statistics;
    use Core\Service\Statistics\StatisticsKind;
    use Core\Service\Statistics\StatisticsProvider;
    use Core\Service\Statistics\StatisticsType;
    use Core\Service\Statistics\StatisticsUnit;

    class PhotoStatisticsProvider implements StatisticsProvider {
        
        private const TOTAL_PHOTOS_COUNT_STATISTICS_NAME = "TOTAL_PHOTOS_COUNT";
        private const AVERAGE_PHOTOS_PER_ALBUM_STATISTICS_NAME = "AVERAGE_PHOTOS_PER_ALBUM";
        private const MOST_PHOTOS_PER_PLACE_STATISTICS_NAME = "MOST_PHOTOS_PER_PLACE";
        private const MOST_PHOTOS_PER_DAY_STATISTICS_NAME = "MOST_PHOTOS_PER_DAY";
        private const MOST_PHOTOS_PER_COUNTRY_STATISTICS_NAME = "MOST_PHOTOS_PER_COUNTRY";
        private const MOST_PHOTOS_PER_CATEGORY_STATISTICS_NAME = "MOST_PHOTOS_PER_CATEGORY";
        private const MOST_PHOTOS_PER_TRIP_STATISTICS_NAME = "MOST_PHOTOS_PER_TRIP";

        private const PHOTOS_DATE_STATISTICS_FORMAT = "%s @ %s";

        private readonly PlaceService $placeService;

        public function __construct(PlaceService $placeService) {
            $this->placeService = $placeService;
        }

        public function fetchStatistics(StatisticsType $statisticsType, StatisticsKind $statisticsKind,
            int $start, int $end, ?string $categoryId, ?string $entityId) : array {
            $statistics = array();

            $relevantPlaces = $this->placeService->getRegularPlaces($categoryId, null, null, null, null, null, null, $start, $end, null, null,
                array(PlaceIncludedEntity::Dates->value, PlaceIncludedEntity::Categories->value), PlaceSortingStrategy::OldestAscending);

            if ($statisticsKind === StatisticsKind::Fact) {
                $albums = array_filter(array_map(fn($date) => $date->getAlbum(),
                    array_merge(...array_map(fn($place) => $place->getDates(), $relevantPlaces))), fn($album) => $album !== null);

                if (count($albums) > 0) {
                    $totalPhotosCount = array_sum(array_map(fn($album) => $album->getImagesCount(), $albums));

                    $statistics[] = new Statistics(self::TOTAL_PHOTOS_COUNT_STATISTICS_NAME, $totalPhotosCount, StatisticsUnit::Photos);
                    $statistics[] = new Statistics(self::AVERAGE_PHOTOS_PER_ALBUM_STATISTICS_NAME, intval($totalPhotosCount / count($albums)), StatisticsUnit::Photos);
                }
            }
            
            if ($statisticsKind === StatisticsKind::Standings) {   
                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year
                    || $statisticsType === StatisticsType::Trip || $statisticsType === StatisticsType::Category) {
                    $mostPhotosPerPlace = array_map(fn($place) => new KeyValuePair($place->getName(), array_sum(array_map(
                        fn($date) => $date->getAlbum() !== null ? $date->getAlbum()->getImagesCount() : 0, $place->getDates()))),
                        array_filter($relevantPlaces, fn($place) => count($place->getDates()) > 0));
                    usort($mostPhotosPerPlace, fn($a, $b) => $b->getValue() <=> $a->getValue());

                    if (count($mostPhotosPerPlace) > 0) {
                        $statistics[] = new Statistics(self::MOST_PHOTOS_PER_PLACE_STATISTICS_NAME, $mostPhotosPerPlace, StatisticsUnit::Photos);
                    }
                }

                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year || $statisticsType === StatisticsType::Trip) {
                    $placesByDay = array();
                    foreach ($relevantPlaces as $place) {
                        foreach ($place->getDates() as $date) {
                            $dayKey = date(CommonConstants::DMY_DATE_FORMAT, $date->getStart());
                            $placesByDay[$dayKey] ??= array();
                            $placesByDay[$dayKey][] = $place;
                        }
                    }

                    $mostPhotosPerDay = $this->getStandingsStatistics(fn($place, $date) => array(sprintf(self::PHOTOS_DATE_STATISTICS_FORMAT, implode(", ",
                        array_unique(array_map(fn($place) => $place->getName(), $placesByDay[date(CommonConstants::DMY_DATE_FORMAT, $date->getStart())]))),
                        date(CommonConstants::DMY_DATE_FORMAT, $date->getStart()))), $relevantPlaces);
                    if (count($mostPhotosPerDay) > 0) {
                        $statistics[] = new Statistics(self::MOST_PHOTOS_PER_DAY_STATISTICS_NAME, $mostPhotosPerDay, StatisticsUnit::Photos);
                    }
                }

                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year) {
                    $mostPhotosPerCountry = $this->getStandingsStatistics(fn($place, $date) => array($place->getCountry()), $relevantPlaces);
                    if (count($mostPhotosPerCountry) > 0) {
                        $statistics[] = new Statistics(self::MOST_PHOTOS_PER_COUNTRY_STATISTICS_NAME, $mostPhotosPerCountry, StatisticsUnit::Photos);
                    }

                    $mostPhotosPerCategory = $this->getStandingsStatistics(fn($place, $date) => array_map(fn($category) => $category->getName(), $place->getCategories()), $relevantPlaces);
                    if (count($mostPhotosPerCategory) > 0) {
                        $statistics[] = new Statistics(self::MOST_PHOTOS_PER_CATEGORY_STATISTICS_NAME, $mostPhotosPerCategory, StatisticsUnit::Photos);
                    }

                    $mostPhotosPerTrip = $this->getStandingsStatistics(fn($place, $date) => array($date->getTrip()?->getFullName()), $relevantPlaces);
                    if (count($mostPhotosPerTrip) > 0) {
                        $statistics[] = new Statistics(self::MOST_PHOTOS_PER_TRIP_STATISTICS_NAME, $mostPhotosPerTrip, StatisticsUnit::Photos);
                    }
                }
            }

            return $statistics;
        }

        private function getStandingsStatistics(callable $keysSelector, array $relevantPlaces) : array {
            $statistics = array_reduce($relevantPlaces, fn($carry, $place) => array_reduce($place->getDates(), 
                function($innerCarry, $date) use(&$place, &$keysSelector) {
                    foreach ($keysSelector($place, $date) as &$key) {
                        if ($key !== null && $date->getAlbum() !== null) {
                            $innerCarry[$key] = isset($innerCarry[$key])
                                ? $innerCarry[$key]->withValue($innerCarry[$key]->getValue() + $date->getAlbum()->getImagesCount())
                                : new KeyValuePair($key, $date->getAlbum()->getImagesCount());
                        }
                    }
                    return $innerCarry;
            }, $carry), array());
            usort($statistics, fn($a, $b) => $b->getValue() <=> $a->getValue());
            return $statistics;
        }
    }
?>