<?php
    namespace Core\Service\Place;

    use Core\Common\CommonConstants;
    use Core\Service\Category\CategoryCategory;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Service\Geocoding\GeocodingService;
    use Core\Service\Statistics\KeyValuePair;
    use Core\Service\Statistics\Statistics;
    use Core\Service\Statistics\StatisticsKind;
    use Core\Service\Statistics\StatisticsName;
    use Core\Service\Statistics\StatisticsProvider;
    use Core\Service\Statistics\StatisticsType;
    use Core\Service\Statistics\StatisticsUnit;

    class PlaceStatisticsProvider implements StatisticsProvider {

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
                    null, $start, $end, null, null, array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::OldestAscending);

                $visitedPlacesCount = count($relevantPlaces);
                if ($statisticsType === StatisticsType::Trip) {
                    $visitedPlacesCount = count(array_filter($relevantPlaces, fn($place) => count($place->getDates()) > 0));
                }

                if ($visitedPlacesCount > 0) {
                    $statistics[] = new Statistics(StatisticsName::TotalVisitedPlacesCount, $visitedPlacesCount, StatisticsUnit::Places);
                }

                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year
                    || $statisticsType === StatisticsType::Category) {
                    if ($visitedPlacesCount > 0) {
                        $totalTravelDaysCount = count(array_unique(array_map(fn($date) => $date->getStart() - ($date->getStart() % CommonConstants::ONE_DAY_SECONDS),
                            array_merge(...array_map(fn($place) => $place->getDates(), $relevantPlaces)))));
                        $statistics[] = new Statistics(StatisticsName::TotalTravelDaysCount, $totalTravelDaysCount, StatisticsUnit::Days);
                    }
                }

                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year) {
                    if ($visitedPlacesCount > 0) {
                        $visitedCountriesCount = count(array_unique(array_filter(array_map(fn($place) => $place->getCountry(), $relevantPlaces), fn($country) => $country !== null)));
                        $statistics[] = new Statistics(StatisticsName::TotalVisitedCountriesCount, $visitedCountriesCount, StatisticsUnit::Countries);
                    }
                }

                if ($statisticsType === StatisticsType::Category) {
                    if ($visitedPlacesCount > 0) {
                        $dates = array_merge(...array_map(fn($place) => $place->getDates(), $relevantPlaces));
                        if (count($dates) > 0) {
                            $lastVisit = max(array_map(fn($place) => $place->getStart(), $dates));
                            $statistics[] = new Statistics(StatisticsName::LastVisit, $lastVisit, StatisticsUnit::BeforeDaysTimestamp);
                        }
                    }
                }
            }
            
            if ($statisticsKind === StatisticsKind::Standings) {                      
                $homeLocation = $this->configurationService->getConfigurationEntry("homeLocation");              
                $relevantPlaces = $this->placeService->getRegularPlaces($categoryId, null, null, null, null, null,
                    null, $start, $end, null, null, array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::OldestAscending);

                $distances = array();
                foreach ($relevantPlaces as &$relevantPlace) {
                    $distances[$relevantPlace->getId()] = intval($this->geocodingService->getDistance($relevantPlace->getLatitude(), $relevantPlace->getLongitude(), $homeLocation["latitude"], $homeLocation["longitude"]));
                }
                usort($relevantPlaces, fn($a, $b) => $distances[$b->getId()] <=> $distances[$a->getId()]);

                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year) {
                    $travelDaysCountByCountry = array_map(fn($visitedCategory) => new KeyValuePair($visitedCategory->getCategory()->getName(),
                        count(array_unique(array_map(fn($date) => $date->getStart() - ($date->getStart() % CommonConstants::ONE_DAY_SECONDS),
                            array_merge(...array_map(fn($place) => $place->getDates(), $visitedCategory->getPlaces())))))),
                        array_filter($this->placeService->getVisitedCategoriesForInterval($start, $end, CategoryCategory::Country, VisitedCategoriesSortingStrategy::TravelDaysCountDescending),
                            fn($visitedCategory) => $visitedCategory->getCategory()->getName() !== $homeLocation["country"]));
                    if (count($travelDaysCountByCountry) > 0) {
                        $statistics[] = new Statistics(StatisticsName::TotalTravelDaysPerCountry, $travelDaysCountByCountry, StatisticsUnit::Days);
                    }
                    
                    // TODO: Exclude days spent by traveling in the home country.
                    $travelDaysCountByContinent = array_map(fn($visitedCategory) => new KeyValuePair($visitedCategory->getCategory()->getName(),
                        count(array_unique(array_map(fn($date) => $date->getStart() - ($date->getStart() % CommonConstants::ONE_DAY_SECONDS),
                            array_merge(...array_map(fn($place) => $place->getDates(), $visitedCategory->getPlaces())))))),
                        $this->placeService->getVisitedCategoriesForInterval($start, $end, CategoryCategory::Continent, VisitedCategoriesSortingStrategy::TravelDaysCountDescending));
                    if (count($travelDaysCountByContinent) > 0) {
                        $statistics[] = new Statistics(StatisticsName::TotalTravelDaysPerContinent, $travelDaysCountByContinent, StatisticsUnit::Days);
                    }

                    $visitedPlacesCountByCountry = array_map(fn($visitedCategory) => new KeyValuePair($visitedCategory->getCategory()->getName(), count($visitedCategory->getPlaces())),
                        $this->placeService->getVisitedCategoriesForInterval($start, $end, CategoryCategory::Country, VisitedCategoriesSortingStrategy::VisitedPlacesCountDescending));
                    if (count($visitedPlacesCountByCountry) > 0) {
                        $statistics[] = new Statistics(StatisticsName::VisitedPlacesPerCountry, $visitedPlacesCountByCountry, StatisticsUnit::Places);
                    }

                    $visitedPlacesCountByContinent = array_map(fn($visitedCategory) => new KeyValuePair($visitedCategory->getCategory()->getName(), count($visitedCategory->getPlaces())),
                        $this->placeService->getVisitedCategoriesForInterval($start, $end, CategoryCategory::Continent, VisitedCategoriesSortingStrategy::VisitedPlacesCountDescending));
                    if (count($visitedPlacesCountByContinent) > 0) {
                        $statistics[] = new Statistics(StatisticsName::VisitedPlacesPerContinent, $visitedPlacesCountByContinent, StatisticsUnit::Places);
                    }
                    
                    $visitedPlacesCountByCategory = array_map(fn($visitedCategory) => new KeyValuePair($visitedCategory->getCategory()->getName(), count($visitedCategory->getPlaces())),
                        $this->placeService->getVisitedCategoriesForInterval($start, $end, null, VisitedCategoriesSortingStrategy::VisitedPlacesCountDescending));
                    if (count($visitedPlacesCountByCategory) > 0) {
                        $statistics[] = new Statistics(StatisticsName::VisitedPlacesPerCategory, $visitedPlacesCountByCategory, StatisticsUnit::Places);
                    }
                    
                    if (count($relevantPlaces) > 0) {
                        $furthestPlaces = array_map(fn($place) => new KeyValuePair($place->getName(), $distances[$place->getId()]), $relevantPlaces);
                        $statistics[] = new Statistics(StatisticsName::FurthestPlaces, $furthestPlaces, StatisticsUnit::Kilometers);

                        $rawFurthestCountries = array_values(array_reduce($relevantPlaces, fn($carry, $place) => array_merge($carry, [$place->getCountry() => $place]), array()));
                        usort($rawFurthestCountries, fn($a, $b) => $distances[$b->getId()] <=> $distances[$a->getId()]);
                        $furthestCountries = array_map(fn($place) => new KeyValuePair($place->getCountry(), $distances[$place->getId()]), $rawFurthestCountries);
                        $statistics[] = new Statistics(StatisticsName::FurthestCountries, $furthestCountries, StatisticsUnit::Kilometers);
                    }
                                        
                    $northernmostPlaces = array_map(fn($place) => new KeyValuePair($place->getName(), $place->getLatitude()),
                        $this->placeService->getRegularPlaces($categoryId, null, null, null, null, null, null, $start, $end, null, null, array(), PlaceSortingStrategy::LatitudeDescending));
                    if (count($northernmostPlaces) > 0) {
                        $statistics[] = new Statistics(StatisticsName::NorthernmostPlaces, $northernmostPlaces, StatisticsUnit::Latitude);
                    }

                    $southernmostPlaces = array_map(fn($place) => new KeyValuePair($place->getName(), $place->getLatitude()),
                        $this->placeService->getRegularPlaces($categoryId, null, null, null, null, null, null, $start, $end, null, null, array(), PlaceSortingStrategy::LatitudeAscending));
                    if (count($southernmostPlaces) > 0) {
                        $statistics[] = new Statistics(StatisticsName::SouthernmostPlaces, $southernmostPlaces, StatisticsUnit::Latitude);
                    }

                    $easternmostPlaces = array_map(fn($place) => new KeyValuePair($place->getName(), $place->getLongitude()),
                        $this->placeService->getRegularPlaces($categoryId, null, null, null, null, null, null, $start, $end, null, null, array(), PlaceSortingStrategy::LongitudeDescending));
                    if (count($easternmostPlaces) > 0) {
                        $statistics[] = new Statistics(StatisticsName::EasternmostPlaces, $easternmostPlaces, StatisticsUnit::Longitude);
                    }

                    $westernmostPlaces = array_map(fn($place) => new KeyValuePair($place->getName(), $place->getLongitude()),
                        $this->placeService->getRegularPlaces($categoryId, null, null, null, null, null, null, $start, $end, null, null, array(), PlaceSortingStrategy::LongitudeAscending));
                    if (count($westernmostPlaces) > 0) {
                        $statistics[] = new Statistics(StatisticsName::WesternmostPlaces, $westernmostPlaces, StatisticsUnit::Longitude);
                    }
                }
                
                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Category) {
                    $leastRecentlyVisitedPlaces = array_filter($relevantPlaces, fn($place) => !empty($place->getDates()));
                    usort($leastRecentlyVisitedPlaces, fn($a, $b) => $a->getDates()[count($a->getDates()) - 1]->getStart() <=> $b->getDates()[count($b->getDates()) - 1]->getStart());
                    $leastRecentlyVisitedPlaces = array_map(fn($place) => new KeyValuePair($place->getName(), $place->getDates()[count($place->getDates()) - 1]->getStart()), $leastRecentlyVisitedPlaces);

                    if (count($leastRecentlyVisitedPlaces) > 0) {
                        $statistics[] = new Statistics(StatisticsName::LeastRecentlyVisitedPlaces, $leastRecentlyVisitedPlaces, StatisticsUnit::BeforeDaysTimestamp);
                    }
                }
                
                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year
                    || $statisticsType === StatisticsType::Category) {
                    $mostVisitedPlaces = array_filter($relevantPlaces, fn($place) => count(array_unique(array_map(fn($date) => $date->getTrip()?->getId(), $place->getDates()))) > 1);
                    usort($mostVisitedPlaces, fn($a, $b) => count(array_unique(array_map(fn($date) => $date->getTrip()?->getId(), $b->getDates()))) <=> count(array_unique(array_map(fn($date) => $date->getTrip()?->getId(), $a->getDates()))));
                    $mostVisitedPlaces = array_map(fn($place) => new KeyValuePair($place->getName(), count(array_unique(array_map(fn($date) => $date->getTrip()?->getId(), $place->getDates())))), $mostVisitedPlaces);

                    if (count($mostVisitedPlaces) > 0) {
                        $statistics[] = new Statistics(StatisticsName::MostVisitedPlaces, $mostVisitedPlaces, StatisticsUnit::Visits);
                    }
                }

                if ($statisticsType === StatisticsType::Overall) {
                    $lowestPlaces = array_map(fn($place) => new KeyValuePair($place->getName(), $place->getElevation()),
                        array_filter($this->placeService->getRegularPlaces($categoryId, null, null, null, null, null, null, $start, $end, null, null, array(), PlaceSortingStrategy::ElevationAscending),
                            fn($place) => $place->getElevation() < 0));
                    if (count($lowestPlaces) > 0) {
                        $statistics[] = new Statistics(StatisticsName::LowestPlaces, $lowestPlaces, StatisticsUnit::ElevationMeters);
                    }

                    $highestPlaces = array_map(fn($place) => new KeyValuePair($place->getName(), $place->getElevation()),
                        // Use Trip ID to filter out permanent places.
                        $this->placeService->getRegularPlaces($categoryId, null, $statisticsType === StatisticsType::Trip ? $entityId : null, null,
                            null, null, null, $start, $end, null, null, array(), PlaceSortingStrategy::ElevationDescending));
                    if (count($highestPlaces) > 0) {
                        $statistics[] = new Statistics(StatisticsName::HighestPlaces, $highestPlaces, StatisticsUnit::ElevationMeters);
                    }
                }
            }

            return $statistics;
        }
    }
?>