<?php
    require_once(__DIR__ . "/../../vendor/autoload.php");
    require_once(__DIR__ . "/../../config/secrets.php");
    
    require_once(__DIR__ . "/Model/TargetError.php");
    require_once(__DIR__ . "/Exception/EntityNotFoundException.php");
    require_once(__DIR__ . "/Service/PlatformService.php");
    require_once(__DIR__ . "/Client/GoogleApiClient.php");
    require_once(__DIR__ . "/Client/ChatClient.php");
    require_once(__DIR__ . "/Client/HttpClient.php");
    require_once(__DIR__ . "/Client/CalendarClient.php");
    require_once(__DIR__ . "/Event/Scheduler.php");

    use Itspire\MonologLoki\Handler\LokiHandler;
    use Monolog\Handler\WhatFailureGroupHandler;
    use Monolog\Logger;
    use Core\Client\CacheClient;
    use Core\Client\CloudMessagingClient;
    use Core\Client\Database\MySQLDatabaseClient;
    use Core\Client\Messaging\RabbitMQMessagingClient;
    use Core\Event\EventManager;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;
    use Core\OpenLineage\GoogleDriveOpenLineageEventPublisher;
    use Core\OpenLineage\IbmCloudOpenLineageEventPublisher;
    use Core\OpenLineage\OpenLineageEventManager;
    use Core\OpenLineage\OpenLineageEventManagerListener;
    use Core\Service\Authentication\AuthenticationService;
    use Core\Service\Category\CategoryDataConsistencyMonitor;
    use Core\Service\Category\CategoryService;
    use Core\Service\Category\CategoryServiceListener;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Service\Device\DeviceService;
    use Core\Service\Device\DeviceServiceListener;
    use Core\Service\Expense\ExpenseService;
    use Core\Service\Expense\ExpenseStatisticsProvider;
    use Core\Service\Fitness\FitnessDataConsistencyMonitor;
    use Core\Service\Fitness\FitnessService;
    use Core\Service\Fitness\FitnessServiceListener;
    use Core\Service\Fitness\FitnessStatisticsProvider;
    use Core\Service\Flight\FlightDataConsistencyMonitor;
    use Core\Service\Flight\FlightService;
    use Core\Service\Flight\FlightServiceListener;
    use Core\Service\Flight\FlightStatisticsProvider;
    use Core\Service\Forecast\ForecastService;
    use Core\Service\Forecast\ForecastServiceListener;
    use Core\Service\Geocoding\GeocodingService;
    use Core\Service\Highlight\HighlightDataConsistencyMonitor;
    use Core\Service\Highlight\HighlightService;
    use Core\Service\Highlight\HighlightServiceListener;
    use Core\Service\Label\LabelService;
    use Core\Service\Label\LabelServiceListener;
    use Core\Service\Monitoring\MonitoringService;
    use Core\Service\Monitoring\MonitoringServiceListener;
    use Core\Service\Note\NoteService;
    use Core\Service\Photo\PhotoService;
    use Core\Service\Photo\PhotoServiceListener;
    use Core\Service\Photo\PhotoStatisticsProvider;
    use Core\Service\Place\PlaceDataConsistencyMonitor;
    use Core\Service\Place\PlaceService;
    use Core\Service\Place\PlaceServiceListener;
    use Core\Service\Place\PlaceStatisticsProvider;
    use Core\Service\Photo\PhotoDataConsistencyMonitor;
    use Core\Service\Statistics\StatisticsService;
    use Core\Service\Statistics\StatisticsServiceListener;
    use Core\Service\Stay\StayService;
    use Core\Service\Stay\StayServiceListener;
    use Core\Service\Stay\StayStatisticsProvider;
    use Core\Service\TimeTracking\TimeTrackingService;
    use Core\Service\TimeTracking\TimeTrackingServiceListener;
    use Core\Service\Trip\TripDataConsistencyMonitor;
    use Core\Service\Trip\TripService;
    use Core\Service\Trip\TripServiceListener;
    use Core\Service\Trip\TripStatisticsProvider;
    use Core\Service\Year\YearService;
    use Core\Service\Year\YearServiceListener;

    $transactionId = uniqid();

    // Logger.
    $logger = new Logger("core");
    $handler = new WhatFailureGroupHandler(array(
        new LokiHandler(array(
            "entrypoint" => GRAFANA_LOKI_ENTRYPOINT,
            "context" => array(
                "transactionId" => $transactionId
            ),
            "labels" => array(
                "service" => "core",
                "transactionId" => $transactionId
            ),
            "client_name" => GRAFANA_LOKI_CLIENT_NAME,
            "auth" => array(
                "basic" => array(
                    GRAFANA_LOKI_USER,
                    GRAFANA_LOKI_PASSWORD
                )
            )
        ))
    ));
    $logger->pushHandler($handler);

    // Clients.
    $databaseClient = new MySQLDatabaseClient(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, $logger);
    $httpClient = new HttpClient();
    $googleApiClient = new GoogleApiClient();
    $chatClient = new ChatClient();
    $calendarClient = new CalendarClient();
    $messagingClient = new RabbitMQMessagingClient(RMQ_HOST, RMQ_PORT, RMQ_VHOST, RMQ_USER, RMQ_PASSWORD, $logger);
    $cloudMessagingClient = new CloudMessagingClient($logger);
    $cacheClient = new CacheClient();

    // Event producers.
    $eventPublisher = new EventPublisher($messagingClient, $cloudMessagingClient);
    $scheduler = new Scheduler($databaseClient, $eventPublisher);

    // Services.
    $configurationService = new ConfigurationService($databaseClient, $eventPublisher);
    $platformService = new PlatformService();
    $authenticationService = new AuthenticationService($databaseClient, $configurationService, $httpClient, $cacheClient);
    $deviceService = new DeviceService($databaseClient, $authenticationService);
    $timeTrackingService = new TimeTrackingService($databaseClient, $configurationService);
    $statisticsService = new StatisticsService($cacheClient, $eventPublisher, $logger);
    $noteService = new NoteService($databaseClient);
    $stayService = new StayService($databaseClient, $calendarClient, $eventPublisher);
    $geocodingService = new GeocodingService($configurationService, $cacheClient, $httpClient);
    $photoService = new PhotoService($databaseClient, $googleApiClient, $eventPublisher, $cacheClient);
    $highlightService = new HighlightService($databaseClient, $photoService, $eventPublisher);
    $categoryService = new CategoryService($databaseClient, $configurationService, $highlightService, $statisticsService, $eventPublisher);
    $expenseService = new ExpenseService($databaseClient, $httpClient, $configurationService, $eventPublisher, $cacheClient);
    $fitnessService = new FitnessService($databaseClient, $eventPublisher, $configurationService, $logger);
    $flightService = new FlightService($databaseClient, $geocodingService, $categoryService, $httpClient, $calendarClient, $googleApiClient, $eventPublisher);
    $forecastService = new ForecastService($databaseClient, $httpClient, $configurationService);
    $labelService = new LabelService($databaseClient, $configurationService);
    $yearService = new YearService($databaseClient, $highlightService, $statisticsService);
    $placeService = new PlaceService($databaseClient, $chatClient, $calendarClient, $googleApiClient, $configurationService, $categoryService, $labelService, $forecastService, $photoService, $highlightService, $noteService, $geocodingService, $eventPublisher);
    $tripService = new TripService($databaseClient, $calendarClient, $googleApiClient, $configurationService, $placeService, $stayService, $flightService, $expenseService, $fitnessService, $noteService, $highlightService, $statisticsService, $yearService, $eventPublisher);
    $monitoringService = new MonitoringService($cacheClient, $eventPublisher, $logger);

    // Statistics providers.
    $statisticsProviders = array(
        new TripStatisticsProvider($tripService),
        new FlightStatisticsProvider($flightService),
        new StayStatisticsProvider($stayService),
        new FitnessStatisticsProvider($fitnessService, $placeService, $tripService),
        new PhotoStatisticsProvider($placeService),
        new ExpenseStatisticsProvider($expenseService, $tripService),
        new PlaceStatisticsProvider($placeService, $configurationService, $geocodingService),
    );
    $statisticsService->setStatisticsProviders($statisticsProviders);

    // Data consistency monitors.
    $dataConsistencyMonitors = array(
        new FitnessDataConsistencyMonitor($fitnessService),
        new PhotoDataConsistencyMonitor($photoService, $placeService),
        new FlightDataConsistencyMonitor($flightService),
        new CategoryDataConsistencyMonitor($categoryService, $placeService),
        new PlaceDataConsistencyMonitor($placeService),
        new TripDataConsistencyMonitor($tripService, $configurationService),
        new HighlightDataConsistencyMonitor($placeService, $tripService)
    );
    $monitoringService->setDataConsistencyMonitors($dataConsistencyMonitors);

    // OpenLineage manager.
    $openLineageEventPublishers = array(
        new IbmCloudOpenLineageEventPublisher($authenticationService, $configurationService, $httpClient, $logger),
        new GoogleDriveOpenLineageEventPublisher($configurationService, $googleApiClient)
    );
    $openLineageEventManager = new OpenLineageEventManager($openLineageEventPublishers, $eventPublisher);
    $messagingClient->setOpenLineageEventManager($openLineageEventManager);
    $cloudMessagingClient->setOpenLineageEventManager($openLineageEventManager);
    $cacheClient->setOpenLineageEventManager($openLineageEventManager);
    $databaseClient->setOpenLineageEventManager($openLineageEventManager);
    $httpClient->setOpenLineageEventManager($openLineageEventManager);
    
    // Event listeners.
    $listeners = array(
        new CategoryServiceListener($categoryService, $placeService, $eventPublisher, $scheduler),
        new FitnessServiceListener($fitnessService, $tripService, $placeService, $eventPublisher, $scheduler, $logger),
        new FlightServiceListener($flightService, $tripService, $calendarClient, $eventPublisher, $scheduler, $logger),
        new ForecastServiceListener($forecastService, $placeService, $eventPublisher, $scheduler),
        new HighlightServiceListener($highlightService, $eventPublisher, $scheduler),
        new PhotoServiceListener($photoService, $eventPublisher, $scheduler),
        new PlaceServiceListener($placeService, $tripService, $calendarClient, $eventPublisher),
        new StatisticsServiceListener($statisticsService, $placeService, $tripService, $categoryService, $flightService, $eventPublisher, $scheduler),
        new StayServiceListener($stayService, $tripService, $calendarClient),
        new TimeTrackingServiceListener($timeTrackingService, $eventPublisher, $scheduler),
        new TripServiceListener($databaseClient, $tripService, $placeService, $stayService, $flightService, $configurationService, $calendarClient, $eventPublisher, $scheduler),
        new YearServiceListener($yearService, $eventPublisher, $scheduler),
        new DeviceServiceListener($deviceService, $tripService, $eventPublisher, $scheduler),
        new MonitoringServiceListener($monitoringService, $eventPublisher, $scheduler),
        new LabelServiceListener($labelService, $placeService, $configurationService, $eventPublisher, $scheduler),
        new OpenLineageEventManagerListener($openLineageEventManager),
        $platformService
    );
    $eventManager = new EventManager($messagingClient, $databaseClient, $logger, $openLineageEventManager, $listeners);
    $eventPublisher->setDeviceService($deviceService);    
?>