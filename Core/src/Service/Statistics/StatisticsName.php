<?php
    namespace Core\Service\Statistics;

    use Common\Service\Authentication\UserRole;
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "StatisticsName",
        type: "string",
        description: "The name of the statistics record"
    )]
    enum StatisticsName : string {
        case TotalVisitedCountriesCount = "TOTAL_VISITED_COUNTRIES_COUNT";
        case TotalVisitedPlacesCount = "TOTAL_VISITED_PLACES_COUNT";
        case FurthestPlaces = "FURTHEST_PLACES";
        case FurthestCountries = "FURTHEST_COUNTRIES";
        case LowestPlaces = "LOWEST_PLACES";
        case HighestPlaces = "HIGHEST_PLACES";
        case VisitedPlacesPerCountry = "VISITED_PLACES_PER_COUNTRY";
        case VisitedPlacesPerContinent = "VISITED_PLACES_PER_CONTINENT";
        case VisitedPlacesPerCategory = "VISITED_PLACES_PER_CATEGORY";
        case WesternmostPlaces = "WESTERNMOST_PLACES";
        case EasternmostPlaces = "EASTERNMOST_PLACES";
        case NorthernmostPlaces = "NORTHERNMOST_PLACES";
        case SouthernmostPlaces = "SOUTHERNMOST_PLACES";
        case LeastRecentlyVisitedPlaces = "LEAST_RECENTLY_VISITED_PLACES";
        case TotalTravelDaysCount = "TOTAL_TRAVEL_DAYS_COUNT";
        case TotalTravelDaysPerCountry = "TOTAL_TRAVEL_DAYS_PER_COUNTRY";
        case TotalTravelDaysPerContinent = "TOTAL_TRAVEL_DAYS_PER_CONTINENT";
        case LastVisit = "LAST_VISIT";
        case MostVisitedPlaces = "MOST_VISITED_PLACES";
        
        case TotalExpenses = "TOTAL_EXPENSES";
        case AverageExpensesPerDay = "AVERAGE_EXPENSES_PER_DAY";
        case MostExpensiveTrips = "MOST_EXPENSIVE_TRIPS";
        case MostExpensiveTripsPerDay = "MOST_EXPENSIVE_TRIPS_PER_DAY";
        case LeastExpensiveTripsPerDay = "LEAST_EXPENSIVE_TRIPS_PER_DAY";
        case MostExpensiveHotelStaysPerNight = "MOST_EXPENSIVE_HOTEL_STAYS_PER_NIGHT";
        case LeastExpensiveHotelStaysPerNight = "LEAST_EXPENSIVE_HOTEL_STAYS_PER_NIGHT";

        case TotalStepsCount = "TOTAL_STEPS_COUNT";
        case AverageStepsPerDay = "AVERAGE_STEPS_PER_DAY";
        case TotalTimeInMotion = "TOTAL_TIME_IN_MOTION";
        case AverageTimeInMotionPerDay = "AVERAGE_TIME_IN_MOTION_PER_DAY";
        case MostStepsPerDay = "MOST_STEPS_PER_DAY";
        case LeastStepsPerDay = "LEAST_STEPS_PER_DAY";
        case MostTimeInMotionPerDay = "MOST_TIME_IN_MOTION_PER_DAY";
        case LeastTimeInMotionPerDay = "LEAST_TIME_IN_MOTION_PER_DAY";
        case MostAverageStepsPerDayTrips = "MOST_AVERAGE_STEPS_PER_DAY_TRIPS";
        case LeastAverageStepsPerDayTrips = "LEAST_AVERAGE_STEPS_PER_DAY_TRIPS";
        case MostAverageTimeInMotionPerDayTrips = "MOST_AVERAGE_TIME_IN_MOTION_PER_DAY_TRIPS";
        case LeastAverageTimeInMotionPerDayTrips = "LEAST_AVERAGE_TIME_IN_MOTION_PER_DAY_TRIPS";

        case TotalFlightsCount = "TOTAL_FLIGHTS_COUNT";
        case TotalAirborneDistance = "TOTAL_AIRBORNE_DISTANCE";
        case TotalAirborneTime = "TOTAL_AIRBORNE_TIME";
        case AverageFlightDuration = "AVERAGE_FLIGHT_DURATION";
        case TotalVisitedAirportsCount = "TOTAL_VISITED_AIRPORTS_COUNT";
        case MostUsedAircrafts = "MOST_USED_AIRCRAFTS";
        case MostUsedAirlines = "MOST_USED_AIRLINES";
        case ShortestFlights = "SHORTEST_FLIGHTS";
        case LongestFlights = "LONGEST_FLIGHTS";
        case MostUsedAirports = "MOST_USED_AIRPORTS";
        case MostUsedFlights = "MOST_USED_FLIGHTS";
        case MostUsedAircraftRegistrations = "MOST_USED_AIRCRAFT_REGISTRATIONS";
        case MostDelayedFlights = "MOST_DELAYED_FLIGHTS";

        case TotalPhotosCount = "TOTAL_PHOTOS_COUNT";
        case AveragePhotosPerAlbum = "AVERAGE_PHOTOS_PER_ALBUM";
        case MostPhotosPerPlace = "MOST_PHOTOS_PER_PLACE";
        case MostPhotosPerDay = "MOST_PHOTOS_PER_DAY";
        case MostPhotosPerCountry = "MOST_PHOTOS_PER_COUNTRY";
        case MostPhotosPerCategory = "MOST_PHOTOS_PER_CATEGORY";
        case MostPhotosPerTrip = "MOST_PHOTOS_PER_TRIP";

        case TotalHotelNightsCount = "TOTAL_HOTEL_NIGHTS_COUNT";
        case AverageNightsPerHotel = "AVERAGE_NIGHTS_PER_HOTEL";
        case LongestHotelStays = "LONGEST_HOTEL_STAYS";

        case AverageTripLength = "AVERAGE_TRIP_LENGTH";
        case LongestTrips = "LONGEST_TRIPS";

        public function getRequiredRole() : UserRole {
            return match($this) {
                self::TotalVisitedCountriesCount,
                self::TotalVisitedPlacesCount,
                self::FurthestPlaces,
                self::FurthestCountries,
                self::LowestPlaces,
                self::HighestPlaces,
                self::VisitedPlacesPerCountry,
                self::VisitedPlacesPerContinent,
                self::VisitedPlacesPerCategory,
                self::WesternmostPlaces,
                self::EasternmostPlaces,
                self::NorthernmostPlaces,
                self::SouthernmostPlaces,
                self::LeastRecentlyVisitedPlaces,
                self::TotalTravelDaysCount,
                self::TotalTravelDaysPerCountry,
                self::TotalTravelDaysPerContinent,
                self::LastVisit,
                self::MostVisitedPlaces => UserRole::PlaceRead,

                self::TotalExpenses,
                self::AverageExpensesPerDay,
                self::MostExpensiveTrips,
                self::MostExpensiveTripsPerDay,
                self::LeastExpensiveTripsPerDay,
                self::MostExpensiveHotelStaysPerNight,
                self::LeastExpensiveHotelStaysPerNight => UserRole::TripExpenseRead,

                self::TotalStepsCount,
                self::AverageStepsPerDay,
                self::TotalTimeInMotion,
                self::AverageTimeInMotionPerDay,
                self::MostStepsPerDay,
                self::LeastStepsPerDay,
                self::MostTimeInMotionPerDay,
                self::LeastTimeInMotionPerDay,
                self::MostAverageStepsPerDayTrips,
                self::LeastAverageStepsPerDayTrips,
                self::MostAverageTimeInMotionPerDayTrips,
                self::LeastAverageTimeInMotionPerDayTrips => UserRole::TripFitnessRead,

                self::TotalFlightsCount,
                self::TotalAirborneDistance,
                self::TotalAirborneTime,
                self::AverageFlightDuration,
                self::TotalVisitedAirportsCount,
                self::MostUsedAircrafts,
                self::MostUsedAirlines,
                self::ShortestFlights,
                self::LongestFlights,
                self::MostUsedAirports,
                self::MostUsedFlights,
                self::MostUsedAircraftRegistrations,
                self::MostDelayedFlights => UserRole::TripFlightRead,

                self::TotalPhotosCount,
                self::AveragePhotosPerAlbum,
                self::MostPhotosPerPlace,
                self::MostPhotosPerDay,
                self::MostPhotosPerCountry,
                self::MostPhotosPerCategory,
                self::MostPhotosPerTrip => UserRole::PlaceAlbumRead,

                self::TotalHotelNightsCount,
                self::AverageNightsPerHotel,
                self::LongestHotelStays => UserRole::TripStayRead,

                self::AverageTripLength,
                self::LongestTrips => UserRole::TripRead,
            };
        }
    }
?>