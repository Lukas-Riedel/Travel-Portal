// TODO: Eventually define in PHP and include in the Swagger schema (similarly to StatisticsName).
export enum DataConsistencyIssueName {
    ConflictingFitnessRecords = "CONFLICTING_FITNESS_RECORDS",
    CountryWithIncompleteMetadata = "COUNTRY_WITH_INCOMPLETE_METADATA",
    AlbumWithoutPlace = "ALBUM_WITHOUT_PLACE",
    EmptyAlbum = "EMPTY_ALBUM",
    ReplacedPhoto = "REPLACED_PHOTO",
    AirlineCodeWithoutAirline = "AIRLINE_CODE_WITHOUT_AIRLINE",
    PlaceWithoutAdministrativeCategory = "PLACE_WITHOUT_ADMINISTRATIVE_CATEGORY",
    PlaceWithoutCountry = "PLACE_WITHOUT_COUNTRY",
    NonReviewedPlace = "NON_REVIEWED_PLACE",
    CountryWithoutAdministrativeDivision = "COUNTRY_WITHOUT_ADMINISTRATIVE_DIVISION",
    DateWithoutTime = "DATE_WITHOUT_TIME",
    TripWithoutTime = "TRIP_WITHOUT_TIME",
    LoggedFlightWithoutFlightEvent = "LOGGED_FLIGHT_WITHOUT_FLIGHT_EVENT",
    DateWithIncorrectTime = "DATE_WITH_INCORRECT_TIME",
    DateWithIncorrectDuration = "DATE_WITH_INCORRECT_DURATION",
    DuplicatedPlace = "DUPLICATED_PLACE",
    AirportWithoutLongName = "AIRPORT_WITHOUT_LONG_NAME",
    AirportWithoutCountry = "AIRPORT_WITHOUT_COUNTRY",
    AirlineWithoutLogo = "AIRLINE_WITHOUT_LOGO",
    NonLoggedFlight = "NON_LOGGED_FLIGHT",
    GeographicalRegionsWithSameName = "GEOGRAPHICAL_REGIONS_WITH_SAME_NAME"
}