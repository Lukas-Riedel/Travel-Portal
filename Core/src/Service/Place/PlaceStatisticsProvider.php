<?php
    namespace Core\Service\Place;

    use Core\Common\CommonConstants;
    use Core\Service\Category\CategoryCategory;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Service\Geocoding\GeocodingService;
    use Core\Service\Statistics\KeyValuePair;
    use Core\Service\Statistics\Statistics;
    use Core\Service\Statistics\StatisticsKind;
    use Core\Service\Statistics\StatisticsProvider;
    use Core\Service\Statistics\StatisticsType;
    use Core\Service\Statistics\StatisticsUnit;

    class PlaceStatisticsProvider implements StatisticsProvider {

        private const TOTAL_VISITED_COUNTRIES_COUNT_STATISTICS_NAME = "TOTAL_VISITED_COUNTRIES_COUNT";
        private const TOTAL_VISITED_PLACES_COUNT_STATISTICS_NAME = "TOTAL_VISITED_PLACES_COUNT";
        private const FURTHEST_PLACES_STATISTICS_NAME = "FURTHEST_PLACES";
        private const FURTHEST_COUNTRIES_STATISTICS_NAME = "FURTHEST_COUNTRIES";
        private const VISITED_PLACES_PER_COUNTRY_STATISTICS_NAME = "VISITED_PLACES_PER_COUNTRY";
        private const VISITED_PLACES_PER_CONTINENT_STATISTICS_NAME = "VISITED_PLACES_PER_CONTINENT";
        private const VISITED_PLACES_PER_CATEGORY_STATISTICS_NAME = "VISITED_PLACES_PER_CATEGORY";
        private const WESTERNMOST_PLACES_STATISTICS_NAME = "WESTERNMOST_PLACES";
        private const EASTERNMOST_PLACES_STATISTICS_NAME = "EASTERNMOST_PLACES";
        private const NORTHERNMOST_PLACES_STATISTICS_NAME = "NORTHERNMOST_PLACES";
        private const SOUTHERNMOST_PLACES_STATISTICS_NAME = "SOUTHERNMOST_PLACES";
        private const LEAST_RECENTLY_VISITED_PLACES_STATISTICS_NAME = "LEAST_RECENTLY_VISITED_PLACES";
        private const TOTAL_TRAVEL_DAYS_COUNT_STATISTICS_NAME = "TOTAL_TRAVEL_DAYS_COUNT";
        private const TOTAL_TRAVEL_DAYS_PER_COUNTRY_STATISTICS_NAME = "TOTAL_TRAVEL_DAYS_PER_COUNTRY";
        private const TOTAL_TRAVEL_DAYS_PER_CONTINENT_STATISTICS_NAME = "TOTAL_TRAVEL_DAYS_PER_CONTINENT";
        private const LAST_VISIT_STATISTICS_NAME = "LAST_VISIT";
        private const MOST_VISITED_PLACES_STATISTICS_NAME = "MOST_VISITED_PLACES";

        private readonly PlaceService $placeService;

        private readonly ConfigurationService $configurationService;

        private readonly GeocodingService $geocodingService;

        public function __construct(PlaceService $placeService, ConfigurationService $configurationService, GeocodingService $geocodingService) {
            $this->placeService = $placeService;
            $this->configurationService = $configurationService;
            $this->geocodingService = $geocodingService;
        }

        public function fetchStatistics(StatisticsType $statisticsType, StatisticsKind $statisticsKind,
            int $start, int $end, ?string $categoryId, ?string $entityId) : array {
            $statistics = array();

            if ($statisticsKind === StatisticsKind::Fact) {        
                $relevantPlaces = $this->placeService->getRegularPlaces($categoryId, null, null, null, null, null, 
                    null, $start, $end, null, array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::OldestAscending);

                $visitedPlacesCount = count($relevantPlaces);
                if ($statisticsType === StatisticsType::Trip) {
                    $visitedPlacesCount = count(array_filter($relevantPlaces, fn($place) => count($place->getDates()) > 0));
                }

                if ($visitedPlacesCount > 0) {
                    $statistics[] = new Statistics(self::TOTAL_VISITED_PLACES_COUNT_STATISTICS_NAME, $visitedPlacesCount, StatisticsUnit::Places);
                }

                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year
                    || $statisticsType === StatisticsType::Category) {
                    if ($visitedPlacesCount > 0) {
                        $totalTravelDaysCount = count(array_unique(array_map(fn($date) => $date->getStart() - ($date->getStart() % CommonConstants::ONE_DAY_SECONDS),
                            array_merge(...array_map(fn($place) => $place->getDates(), $relevantPlaces)))));
                        $statistics[] = new Statistics(self::TOTAL_TRAVEL_DAYS_COUNT_STATISTICS_NAME, $totalTravelDaysCount, StatisticsUnit::Days);
                    }
                }

                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year) {
                    if ($visitedPlacesCount > 0) {
                        $visitedCountriesCount = count(array_unique(array_map(fn($place) => $place->getCountry(), $relevantPlaces)));
                        $statistics[] = new Statistics(self::TOTAL_VISITED_COUNTRIES_COUNT_STATISTICS_NAME, $visitedCountriesCount, StatisticsUnit::Countries);
                    }
                }

                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Category) {
                    if ($visitedPlacesCount > 0) {
                        $dates = array_merge(...array_map(fn($place) => $place->getDates(), $relevantPlaces));
                        if (count($dates) > 0) {
                            $lastVisit = max(array_map(fn($place) => $place->getStart(), $dates));
                            $statistics[] = new Statistics(self::LAST_VISIT_STATISTICS_NAME, $lastVisit, StatisticsUnit::BeforeDaysTimestamp);
                        }
                    }
                }
            }
            
            if ($statisticsKind === StatisticsKind::Standings) {                      
                $homeLocation = $this->configurationService->getConfigurationEntry("homeLocation");              
                $relevantPlaces = $this->placeService->getRegularPlaces($categoryId, null, null, null, null, null,
                    null, $start, $end, null, array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::OldestAscending);

                $distances = array();
                foreach ($relevantPlaces as &$relevantPlace) {
                    $distances[$relevantPlace->getId()] = intval($this->geocodingService->getDistance($relevantPlace->getLatitude(), $relevantPlace->getLongitude(), $homeLocation["latitude"], $homeLocation["longitude"]));
                }
                usort($relevantPlaces, fn($a, $b) => $distances[$b->getId()] <=> $distances[$a->getId()]);

                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year) {
                    $travelDaysCountByCountry = array_map(fn($visitedCategory) => new KeyValuePair($visitedCategory->getCategory()->getName(),
                        count(array_unique(array_map(fn($date) => $date->getStart() - ($date->getStart() % CommonConstants::ONE_DAY_SECONDS),
                            array_merge(...array_map(fn($place) => $place->getDates(), $visitedCategory->getPlaces())))))),
                        $this->placeService->getVisitedCategoriesForInterval($start, $end, CategoryCategory::Country, VisitedCategoriesSortingStrategy::TravelDaysCountDescending));
                    if (count($travelDaysCountByCountry) > 0) {
                        $statistics[] = new Statistics(self::TOTAL_TRAVEL_DAYS_PER_COUNTRY_STATISTICS_NAME, $travelDaysCountByCountry, StatisticsUnit::Days);
                    }
                    
                    $travelDaysCountByContinent = array_map(fn($visitedCategory) => new KeyValuePair($visitedCategory->getCategory()->getName(),
                        count(array_unique(array_map(fn($date) => $date->getStart() - ($date->getStart() % CommonConstants::ONE_DAY_SECONDS),
                            array_merge(...array_map(fn($place) => $place->getDates(), $visitedCategory->getPlaces())))))),
                        $this->placeService->getVisitedCategoriesForInterval($start, $end, CategoryCategory::Continent, VisitedCategoriesSortingStrategy::TravelDaysCountDescending));
                    if (count($travelDaysCountByContinent) > 0) {
                        $statistics[] = new Statistics(self::TOTAL_TRAVEL_DAYS_PER_CONTINENT_STATISTICS_NAME, $travelDaysCountByContinent, StatisticsUnit::Days);
                    }

                    $visitedPlacesCountByCountry = array_map(fn($visitedCategory) => new KeyValuePair($visitedCategory->getCategory()->getName(), count($visitedCategory->getPlaces())),
                        $this->placeService->getVisitedCategoriesForInterval($start, $end, CategoryCategory::Country, VisitedCategoriesSortingStrategy::VisitedPlacesCountDescending));
                    if (count($visitedPlacesCountByCountry) > 0) {
                        $statistics[] = new Statistics(self::VISITED_PLACES_PER_COUNTRY_STATISTICS_NAME, $visitedPlacesCountByCountry, StatisticsUnit::Places);
                    }

                    $visitedPlacesCountByContinent = array_map(fn($visitedCategory) => new KeyValuePair($visitedCategory->getCategory()->getName(), count($visitedCategory->getPlaces())),
                        $this->placeService->getVisitedCategoriesForInterval($start, $end, CategoryCategory::Continent, VisitedCategoriesSortingStrategy::VisitedPlacesCountDescending));
                    if (count($visitedPlacesCountByContinent) > 0) {
                        $statistics[] = new Statistics(self::VISITED_PLACES_PER_CONTINENT_STATISTICS_NAME, $visitedPlacesCountByContinent, StatisticsUnit::Places);
                    }
                    
                    $visitedPlacesCountByCategory = array_map(fn($visitedCategory) => new KeyValuePair($visitedCategory->getCategory()->getName(), count($visitedCategory->getPlaces())),
                        $this->placeService->getVisitedCategoriesForInterval($start, $end, null, VisitedCategoriesSortingStrategy::VisitedPlacesCountDescending));
                    if (count($visitedPlacesCountByCategory) > 0) {
                        $statistics[] = new Statistics(self::VISITED_PLACES_PER_CATEGORY_STATISTICS_NAME, $visitedPlacesCountByCategory, StatisticsUnit::Places);
                    }
                    
                    if (count($relevantPlaces) > 0) {
                        $furthestPlaces = array_map(fn($place) => new KeyValuePair($place->getName(), $distances[$place->getId()]), $relevantPlaces);
                        $statistics[] = new Statistics(self::FURTHEST_PLACES_STATISTICS_NAME, $furthestPlaces, StatisticsUnit::Kilometers);

                        $furthestCountries = array_map(fn($place) => new KeyValuePair($place->getCountry(),
                            $distances[$place->getId()]), array_values(array_reduce($relevantPlaces, fn($carry, $place) => $carry + [$place->getCountry() => $place], array())));
                        $statistics[] = new Statistics(self::FURTHEST_COUNTRIES_STATISTICS_NAME, $furthestCountries, StatisticsUnit::Kilometers);
                    }
                                        
                    $northernmostPlaces = array_map(fn($place) => new KeyValuePair($place->getName(), $distances[$place->getId()]),
                        $this->placeService->getRegularPlaces($categoryId, null, null, null, null, null, null, $start, $end, null, array(), PlaceSortingStrategy::LatitudeDescending));
                    if (count($northernmostPlaces) > 0) {
                        $statistics[] = new Statistics(self::NORTHERNMOST_PLACES_STATISTICS_NAME, $northernmostPlaces, StatisticsUnit::Kilometers);
                    }

                    $southernmostPlaces = array_map(fn($place) => new KeyValuePair($place->getName(), $distances[$place->getId()]),
                        $this->placeService->getRegularPlaces($categoryId, null, null, null, null, null, null, $start, $end, null, array(), PlaceSortingStrategy::LatitudeAscending));
                    if (count($southernmostPlaces) > 0) {
                        $statistics[] = new Statistics(self::SOUTHERNMOST_PLACES_STATISTICS_NAME, $southernmostPlaces, StatisticsUnit::Kilometers);
                    }

                    $easternmostPlaces = array_map(fn($place) => new KeyValuePair($place->getName(), $distances[$place->getId()]),
                        $this->placeService->getRegularPlaces($categoryId, null, null, null, null, null, null, $start, $end, null, array(), PlaceSortingStrategy::LongitudeDescending));
                    if (count($easternmostPlaces) > 0) {
                        $statistics[] = new Statistics(self::EASTERNMOST_PLACES_STATISTICS_NAME, $easternmostPlaces, StatisticsUnit::Kilometers);
                    }

                    $westernmostPlaces = array_map(fn($place) => new KeyValuePair($place->getName(), $distances[$place->getId()]),
                        $this->placeService->getRegularPlaces($categoryId, null, null, null, null, null, null, $start, $end, null, array(), PlaceSortingStrategy::LongitudeAscending));
                    if (count($westernmostPlaces) > 0) {
                        $statistics[] = new Statistics(self::WESTERNMOST_PLACES_STATISTICS_NAME, $westernmostPlaces, StatisticsUnit::Kilometers);
                    }
                }
                
                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Category) {
                    $leastRecentlyVisitedPlaces = array_filter($relevantPlaces, fn($place) => !empty($place->getDates()));
                    usort($leastRecentlyVisitedPlaces, fn($a, $b) => $a->getDates()[count($a->getDates()) - 1]->getStart() <=> $b->getDates()[count($b->getDates()) - 1]->getStart());
                    $leastRecentlyVisitedPlaces = array_map(fn($place) => new KeyValuePair($place->getName(), $place->getDates()[count($place->getDates()) - 1]->getStart()), $leastRecentlyVisitedPlaces);

                    if (count($leastRecentlyVisitedPlaces) > 0) {
                        $statistics[] = new Statistics(self::LEAST_RECENTLY_VISITED_PLACES_STATISTICS_NAME, $leastRecentlyVisitedPlaces, StatisticsUnit::BeforeDaysTimestamp);
                    }
                }
                
                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year
                    || $statisticsType === StatisticsType::Category) {
                    $mostVisitedPlaces = array_filter($relevantPlaces, fn($place) => count(array_unique(array_map(fn($date) => $date->getTrip()?->getId(), $place->getDates()))) > 1);
                    usort($mostVisitedPlaces, fn($a, $b) => count(array_unique(array_map(fn($date) => $date->getTrip()?->getId(), $b->getDates()))) <=> count(array_unique(array_map(fn($date) => $date->getTrip()?->getId(), $a->getDates()))));
                    $mostVisitedPlaces = array_map(fn($place) => new KeyValuePair($place->getName(), count(array_unique(array_map(fn($date) => $date->getTrip()?->getId(), $place->getDates())))), $mostVisitedPlaces);

                    if (count($mostVisitedPlaces) > 0) {
                        $statistics[] = new Statistics(self::MOST_VISITED_PLACES_STATISTICS_NAME, $mostVisitedPlaces, StatisticsUnit::Visits);
                    }
                }
            }

            return $statistics;
        }
    }
?>