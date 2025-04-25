<?php
    session_start();

    require_once(dirname(__FILE__) . "/../vendor/autoload.php");
    require_once(dirname(__FILE__) . "/config/secrets.php");
    
    require_once(dirname(__FILE__) . "/Provider/DatabaseProvider.php");
    require_once(dirname(__FILE__) . "/Provider/LoggingProvider.php");
    require_once(dirname(__FILE__) . "/Provider/ConfigurationProvider.php");
    require_once(dirname(__FILE__) . "/Rest/Handler.php");
    require_once(dirname(__FILE__) . "/Model/TargetError.php");
    require_once(dirname(__FILE__) . "/Exception/AuthorizationException.php");
    require_once(dirname(__FILE__) . "/Exception/EntityNotFoundException.php");
    require_once(dirname(__FILE__) . "/Service/ConfigurationService.php");
    require_once(dirname(__FILE__) . "/Service/PlatformService.php");
    require_once(dirname(__FILE__) . "/Client/GoogleApiClient.php");
    require_once(dirname(__FILE__) . "/Client/ChatClient.php");
    require_once(dirname(__FILE__) . "/Client/HttpClient.php");
    require_once(dirname(__FILE__) . "/Client/CalendarClient.php");
    require_once(dirname(__FILE__) . "/Event/Scheduler.php");
    require_once(dirname(__FILE__) . "/Event/EventManager.php");
    require_once(dirname(__FILE__) . "/Event/EventPublisher.php");

    use Service\Service\Authentication\AuthenticationService;
    use Service\Service\Category\CategoryService;
    use Service\Service\Expense\ExpenseService;
    use Service\Service\Fitness\FitnessService;
    use Service\Service\Flight\FlightService;
    use Service\Service\Forecast\ForecastService;
    use Service\Service\Geocoding\GeocodingService;
    use Service\Service\Highlight\HighlightService;
    use Service\Service\Label\LabelService;
    use Service\Service\Note\NoteService;
    use Service\Service\Photo\PhotoService;
    use Service\Service\Place\PlaceService;
    use Service\Service\Statistics\StatisticsService;
    use Service\Service\Stay\StayService;
    use Service\Service\TimeTracking\TimeTrackingService;
    use Service\Service\Trip\TripService;
    use Service\Service\Year\YearService;

    $databaseProvider = new DatabaseProvider($delayViewMaterializationIfNeeded);
    $configurationProvider = new ConfigurationProvider($databaseProvider);
    $configuration = $configurationProvider->get(PUBLIC_CONFIGURATION, PRIVATE_CONFIGURATION);
    $configurationService = new ConfigurationService();
    $authenticationService = new AuthenticationService($databaseProvider, $configurationService);
    $timeTrackingService = new TimeTrackingService($databaseProvider, $configurationService);
    $googleApiClient = new GoogleApiClient();
    $chatClient = new ChatClient();
    $httpClient = new HttpClient();
    $calendarClient = new CalendarClient();
    $platformService = new PlatformService();
    $eventPublisher = new EventPublisher();
    $scheduler = new Scheduler($databaseProvider, $eventPublisher);
    $statisticsService = new StatisticsService($databaseProvider, $configurationService, $eventPublisher, $scheduler);
    $noteService = new NoteService($databaseProvider);
    $stayService = new StayService($databaseProvider, $calendarClient, $eventPublisher);
    $geocodingService = new GeocodingService($databaseProvider, $configurationService, $httpClient);
    $photoService = new PhotoService($databaseProvider, $googleApiClient, $configurationService, $eventPublisher, $scheduler);
    $highlightService = new HighlightService($databaseProvider, $photoService, $configurationService, $eventPublisher, $scheduler);
    $categoryService = new CategoryService($databaseProvider, $configurationService, $highlightService, $statisticsService, $eventPublisher, $scheduler);
    $expenseService = new ExpenseService($databaseProvider, $httpClient, $configurationService, $eventPublisher);
    $fitnessService = new FitnessService($databaseProvider, $configurationService, $eventPublisher, $scheduler);
    $flightService = new FlightService($databaseProvider, $geocodingService, $categoryService, $httpClient, $calendarClient, $googleApiClient, $eventPublisher, $scheduler);
    $forecastService = new ForecastService($databaseProvider, $httpClient, $configurationService, $eventPublisher, $scheduler);
    $labelService = new LabelService($databaseProvider, $configurationService);
    $yearService = new YearService($databaseProvider, $highlightService, $statisticsService, $eventPublisher, $scheduler);
    $placeService = new PlaceService($databaseProvider, $chatClient, $calendarClient, $googleApiClient, $configurationService, $categoryService, $labelService, $forecastService, $photoService, $highlightService, $geocodingService, $eventPublisher);
    $tripService = new TripService($databaseProvider, $calendarClient, $googleApiClient, $configurationService, $placeService, $stayService, $flightService, $expenseService, $fitnessService, $noteService, $highlightService, $statisticsService, $yearService, $eventPublisher, $scheduler);

    $statisticsService->setStatisticsProviders(array($placeService, $tripService, $yearService, $stayService, $photoService, $categoryService, $expenseService, $fitnessService, $flightService));
    
    $services = array($placeService, $highlightService, $photoService, $tripService, $photoService, $categoryService, $labelService,
        $expenseService, $yearService, $noteService, $configurationService, $flightService, $timeTrackingService,
        $fitnessService, $statisticsService, $geocodingService, $stayService, $forecastService, $authenticationService, $platformService);
    $eventManager = new EventManager($services);
?>