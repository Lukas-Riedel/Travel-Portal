<?php
    require_once(__DIR__ . "/vendor/autoload.php");
    require_once(__DIR__ . "/config/secrets.php");
    
    require_once(__DIR__ . "/src/php/Provider/DatabaseProvider.php");
    require_once(__DIR__ . "/src/php/Provider/LoggingProvider.php");
    require_once(__DIR__ . "/src/php/Rest/Handler.php");
    require_once(__DIR__ . "/src/php/Model/TargetError.php");
    require_once(__DIR__ . "/src/php/Exception/AuthorizationException.php");
    require_once(__DIR__ . "/src/php/Exception/EntityNotFoundException.php");
    require_once(__DIR__ . "/src/php/Service/PlatformService.php");
    require_once(__DIR__ . "/src/php/Client/GoogleApiClient.php");
    require_once(__DIR__ . "/src/php/Client/ChatClient.php");
    require_once(__DIR__ . "/src/php/Client/HttpClient.php");
    require_once(__DIR__ . "/src/php/Client/CalendarClient.php");
    require_once(__DIR__ . "/src/php/Event/Scheduler.php");
    require_once(__DIR__ . "/src/php/Event/EventManager.php");
    require_once(__DIR__ . "/src/php/Event/EventPublisher.php");

    use Service\Client\CacheClient;
    use Service\Client\CloudMessagingClient;
    use Service\Service\Authentication\AuthenticationService;
    use Service\Service\Category\CategoryDataConsistencyMonitor;
    use Service\Service\Category\CategoryService;
    use Service\Service\Category\CategoryServiceListener;
    use Service\Service\Configuration\ConfigurationService;
    use Service\Service\Device\DeviceService;
    use Service\Service\Device\DeviceServiceListener;
    use Service\Service\Expense\ExpenseService;
    use Service\Service\Expense\ExpenseStatisticsProvider;
    use Service\Service\Fitness\FitnessService;
    use Service\Service\Fitness\FitnessServiceListener;
    use Service\Service\Fitness\FitnessStatisticsProvider;
    use Service\Service\Flight\FlightDataConsistencyMonitor;
    use Service\Service\Flight\FlightService;
    use Service\Service\Flight\FlightServiceListener;
    use Service\Service\Flight\FlightStatisticsProvider;
    use Service\Service\Forecast\ForecastService;
    use Service\Service\Forecast\ForecastServiceListener;
    use Service\Service\Geocoding\GeocodingService;
    use Service\Service\Highlight\HighlightDataConsistencyMonitor;
    use Service\Service\Highlight\HighlightService;
    use Service\Service\Highlight\HighlightServiceListener;
    use Service\Service\Label\LabelService;
    use Service\Service\Label\LabelServiceListener;
    use Service\Service\Monitoring\MonitoringService;
    use Service\Service\Monitoring\MonitoringServiceListener;
    use Service\Service\Note\NoteService;
    use Service\Service\Photo\PhotoService;
    use Service\Service\Photo\PhotoServiceListener;
    use Service\Service\Photo\PhotoStatisticsProvider;
    use Service\Service\Place\PlaceDataConsistencyMonitor;
    use Service\Service\Place\PlaceService;
    use Service\Service\Place\PlaceServiceListener;
    use Service\Service\Place\PlaceStatisticsProvider;
    use Service\Service\Photo\PhotoDataConsistencyMonitor;
    use Service\Service\Statistics\StatisticsService;
    use Service\Service\Statistics\StatisticsServiceListener;
    use Service\Service\Stay\StayService;
    use Service\Service\Stay\StayServiceListener;
    use Service\Service\Stay\StayStatisticsProvider;
    use Service\Service\TimeTracking\TimeTrackingService;
    use Service\Service\TimeTracking\TimeTrackingServiceListener;
    use Service\Service\Trip\TripDataConsistencyMonitor;
    use Service\Service\Trip\TripService;
    use Service\Service\Trip\TripServiceListener;
    use Service\Service\Trip\TripStatisticsProvider;
    use Service\Service\Year\YearService;
    use Service\Service\Year\YearServiceListener;

    // Clients.
    $databaseProvider = new DatabaseProvider($delayViewMaterializationIfNeeded);
    $googleApiClient = new GoogleApiClient();
    $chatClient = new ChatClient();
    $httpClient = new HttpClient();
    $calendarClient = new CalendarClient();
    $cloudMessagingClient = new CloudMessagingClient();
    $cacheClient = new CacheClient($databaseProvider);
    
    $loggingProvider = new LoggingProvider($databaseProvider);

    // Event producers.
    $eventPublisher = new EventPublisher();
    $scheduler = new Scheduler($databaseProvider, $eventPublisher);

    // Services.
    $configurationService = new ConfigurationService($databaseProvider, $eventPublisher);
    $platformService = new PlatformService();
    $authenticationService = new AuthenticationService($databaseProvider, $configurationService, $httpClient);
    $timeTrackingService = new TimeTrackingService($databaseProvider, $configurationService);
    $statisticsService = new StatisticsService($databaseProvider, $eventPublisher);
    $noteService = new NoteService($databaseProvider);
    $stayService = new StayService($databaseProvider, $calendarClient, $eventPublisher);
    $geocodingService = new GeocodingService($databaseProvider, $configurationService, $httpClient);
    $photoService = new PhotoService($databaseProvider, $googleApiClient, $eventPublisher);
    $highlightService = new HighlightService($databaseProvider, $photoService, $eventPublisher);
    $categoryService = new CategoryService($databaseProvider, $configurationService, $highlightService, $statisticsService, $eventPublisher);
    $expenseService = new ExpenseService($databaseProvider, $httpClient, $configurationService, $eventPublisher);
    $fitnessService = new FitnessService($databaseProvider, $eventPublisher, $configurationService);
    $flightService = new FlightService($databaseProvider, $geocodingService, $categoryService, $httpClient, $calendarClient, $googleApiClient, $eventPublisher);
    $forecastService = new ForecastService($databaseProvider, $httpClient, $configurationService);
    $labelService = new LabelService($databaseProvider, $configurationService);
    $yearService = new YearService($databaseProvider, $highlightService, $statisticsService);
    $placeService = new PlaceService($databaseProvider, $chatClient, $calendarClient, $googleApiClient, $configurationService, $categoryService, $labelService, $forecastService, $photoService, $highlightService, $noteService, $geocodingService, $eventPublisher);
    $tripService = new TripService($databaseProvider, $calendarClient, $googleApiClient, $configurationService, $placeService, $stayService, $flightService, $expenseService, $fitnessService, $noteService, $highlightService, $statisticsService, $yearService, $eventPublisher);
    $deviceService = new DeviceService($databaseProvider, $authenticationService);
    $monitoringService = new MonitoringService($cacheClient);

    // Statistics providers.
    $statisticsProviders = array(
        new PlaceStatisticsProvider($placeService, $configurationService, $geocodingService),
        new TripStatisticsProvider($tripService),
        new FlightStatisticsProvider($flightService),
        new StayStatisticsProvider($stayService),
        new FitnessStatisticsProvider($fitnessService, $placeService, $tripService),
        new PhotoStatisticsProvider($placeService),
        new ExpenseStatisticsProvider($expenseService, $tripService)
    );
    $statisticsService->setStatisticsProviders($statisticsProviders);

    // Data consistency monitors.
    $dataConsistencyMonitors = array(
        new PhotoDataConsistencyMonitor($photoService, $placeService),
        new FlightDataConsistencyMonitor($flightService),
        new CategoryDataConsistencyMonitor($categoryService, $placeService),
        new PlaceDataConsistencyMonitor($placeService),
        new TripDataConsistencyMonitor($tripService, $configurationService),
        new HighlightDataConsistencyMonitor($placeService, $tripService)
    );
    $monitoringService->setDataConsistencyMonitors($dataConsistencyMonitors);
    
    // Event listeners.
    $listeners = array(
        new CategoryServiceListener($categoryService, $placeService, $eventPublisher, $scheduler),
        new FitnessServiceListener($fitnessService, $eventPublisher, $scheduler),
        new FlightServiceListener($flightService, $tripService, $calendarClient, $eventPublisher, $scheduler),
        new ForecastServiceListener($forecastService, $placeService, $eventPublisher, $scheduler),
        new HighlightServiceListener($highlightService, $eventPublisher, $scheduler),
        new PhotoServiceListener($photoService, $eventPublisher, $scheduler),
        new PlaceServiceListener($placeService, $tripService, $calendarClient, $eventPublisher),
        new StatisticsServiceListener($statisticsService, $placeService, $tripService, $categoryService, $flightService, $eventPublisher, $scheduler),
        new StayServiceListener($stayService, $tripService, $calendarClient),
        new TimeTrackingServiceListener($timeTrackingService, $eventPublisher, $scheduler),
        new TripServiceListener($tripService, $placeService, $stayService, $flightService, $configurationService, $calendarClient, $eventPublisher, $scheduler),
        new YearServiceListener($yearService, $eventPublisher, $scheduler),
        new DeviceServiceListener($deviceService, $eventPublisher, $scheduler),
        new MonitoringServiceListener($monitoringService, $eventPublisher, $scheduler),
        new LabelServiceListener($labelService, $placeService, $configurationService, $eventPublisher, $scheduler),
        $platformService
    );
    $eventManager = new EventManager($listeners);
?>