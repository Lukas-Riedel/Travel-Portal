<?php
    require_once(__DIR__ . "/../vendor/autoload.php");

    use Common\Client\Encryption\EncryptionClient;
    use Common\LoggingContext;
    use Common\Service\Authentication\AuthenticationService as CommonAuthenticationService;
    use Common\Client\Cache\MemoryCacheClient;
    use Common\Client\Cache\RedisCacheClient;
    use Core\Client\Calendar\CalendarClient;
    use Core\Client\CloudMessaging\FirebaseCloudMessagingClient;
    use Core\Client\CloudStorage\S3CloudStorageClient;
    use Monolog\Logger;
    use Core\Client\Database\PostgreSQLDatabaseClient;
    use Core\Client\ExchangeRate\ExchangeRateApiExchangeRateClient;
    use Core\Client\Flight\FlightRadar24FlightClient;
    use Core\Client\Forecast\OpenMeteoActualForecastClient;
    use Core\Client\GenerativeContent\GeminiGenerativeContentClient;
    use Core\Client\Google\GoogleClient;
    use Core\Client\Forecast\OpenMeteoHistoricalForecastClient;
    use Core\Client\GenerativeContent\CachingGenerativeClient;
    use Core\Client\Http\FlareSolverrHttpClientDecorator;
    use Core\Client\Http\HttpClient;
    use Core\Client\Messaging\RabbitMQMessagingClient;
    use Core\Client\Search\OpenSearchClient;
    use Core\Client\Translation\CortexTranslationClient;
    use Core\Event\EventPublisher;
    use Core\Event\RabbitMQEventListener;
    use Core\Event\Scheduler;
    use Core\OpenLineage\GoogleDriveOpenLineageEventPublisher;
    use Core\OpenLineage\IbmCloudOpenLineageEventPublisher;
    use Core\OpenLineage\OpenLineageEventManager;
    use Core\OpenLineage\OpenLineageEventManagerListener;
    use Core\PlatformListener;
    use Core\Service\Authentication\AuthenticationService;
    use Core\Service\Category\CategoryDataConsistencyMonitor;
    use Core\Service\Category\CategoryIndexer;
    use Core\Service\Category\CategoryService;
    use Core\Service\Category\CategoryServiceListener;
    use Core\Service\Clustering\ClusteringService;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Service\Device\DeviceService;
    use Core\Service\Device\DeviceServiceListener;
    use Core\Service\Document\DocumentService;
    use Core\Service\Embedding\EmbeddingService;
    use Core\Service\Expense\ExpenseService;
    use Core\Service\Expense\ExpenseStatisticsProvider;
    use Core\Service\Fitness\FitnessDataConsistencyMonitor;
    use Core\Service\Fitness\FitnessService;
    use Core\Service\Fitness\FitnessServiceListener;
    use Core\Service\Fitness\FitnessStatisticsProvider;
    use Core\Service\Flight\FlightDataConsistencyMonitor;
    use Core\Service\Flight\FlightIndexer;
    use Core\Service\Flight\FlightService;
    use Core\Service\Flight\FlightServiceListener;
    use Core\Service\Flight\FlightStatisticsProvider;
    use Core\Service\Forecast\ForecastService;
    use Core\Service\Forecast\ForecastServiceListener;
    use Core\Service\Geocoding\GeocodingService;
    use Core\Service\Highlight\HighlightService;
    use Core\Service\Highlight\HighlightServiceListener;
    use Core\Service\Index\IndexService;
    use Core\Service\Index\IndexServiceListener;
    use Core\Service\Label\LabelIndexer;
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
    use Core\Service\Photo\PhotoIndexer;
    use Core\Service\Place\PlaceIndexer;
    use Core\Service\Statistics\StatisticsService;
    use Core\Service\Statistics\StatisticsServiceListener;
    use Core\Service\Stay\StayService;
    use Core\Service\Stay\StayServiceListener;
    use Core\Service\Stay\StayStatisticsProvider;
    use Core\Service\TimeTracking\TimeTrackingService;
    use Core\Service\TimeTracking\TimeTrackingServiceListener;
    use Core\Service\Trip\TripDataConsistencyMonitor;
    use Core\Service\Trip\TripIndexer;
    use Core\Service\Trip\TripService;
    use Core\Service\Trip\TripServiceListener;
    use Core\Service\Trip\TripStatisticsProvider;
    use Core\Service\Year\YearIndexer;
    use Core\Service\Year\YearService;
    use Core\Service\Year\YearServiceListener;
    use Monolog\Formatter\JsonFormatter;
    use Monolog\Handler\StreamHandler;
    use Monolog\Level;

    $onError = function($level, $message, $file, $line) {
        throw new \ErrorException($message);
    };
    set_error_handler($onError);

    // Logger.
    $loggingContext = new LoggingContext();
    $logger = new Logger(getenv("APP_NAME"));
    $handler = new StreamHandler("php://stdout", Level::Debug);
    $handler->setFormatter(new JsonFormatter());
    $logger->pushHandler($handler);

    // Clients.
    $distributedCacheClient = new RedisCacheClient(getenv("REDIS_HOST"), getenv("REDIS_PORT"), getenv("REDIS_PASSWORD"));
    $memoryCacheClient = new MemoryCacheClient();
    $databaseClient = new PostgreSQLDatabaseClient(getenv("DB_HOST"), getenv("DB_PORT"), getenv("DB_USER"), getenv("DB_PASSWORD"), getenv("DB_NAME"), $distributedCacheClient, $logger); 
    $httpClient = new HttpClient(getenv("APP_NAME"), $loggingContext, $logger);
    $flareSolverrHttpClientDecorator = new FlareSolverrHttpClientDecorator($httpClient, getenv("FLARESOLVERR_HOST"), getenv("FLARESOLVERR_PORT"), $logger);
    $googleClient = new GoogleClient($distributedCacheClient, $httpClient, $logger, getenv("BACKEND_GOOGLE_MAPS_API_KEY"));
    $generativeContentClient = new GeminiGenerativeContentClient($httpClient, $distributedCacheClient, $logger, getenv("GOOGLE_GEMINI_API_KEY"));
    $cachingGenerativeContentClient = new CachingGenerativeClient($generativeContentClient, $distributedCacheClient);
    $translationClient = new CortexTranslationClient($httpClient, $distributedCacheClient, getenv("CORTEX_HOST"), getenv("CORTEX_PORT"));
    $calendarClient = new CalendarClient($googleClient, $distributedCacheClient, $translationClient, $logger, getenv("CORE_BASE_URL")); 
    $cloudMessagingClient = new FirebaseCloudMessagingClient(getenv("FCM_PROJECT_ID"), $httpClient, $loggingContext, $logger);
    $exchangeRateClient = new ExchangeRateApiExchangeRateClient($httpClient, $logger, getenv("EXCHANGE_RATE_API_KEY"));
    $flightClient = new FlightRadar24FlightClient($flareSolverrHttpClientDecorator);
    $actualForecastClient = new OpenMeteoActualForecastClient($httpClient, $distributedCacheClient, explode(",", getenv("ACTUAL_WEATHER_FORECAST_MODELS")), explode(",", getenv("ACTUAL_WEATHER_FORECAST_REFRESH_HOURS")));
    $historicalForecastClient = new OpenMeteoHistoricalForecastClient($httpClient);
    $encryptionClient = new EncryptionClient(getenv("ENCRYPTION_PRIVATE_KEY"));
    $messagingClient = new RabbitMQMessagingClient(getenv("RMQ_INTERNAL_HOST"), getenv("RMQ_INTERNAL_PORT"), getenv("RMQ_VHOST"), getenv("RMQ_USER"), getenv("RMQ_PASSWORD"), getenv("RMQ_HEARTBEAT"), getenv("RMQ_PREFETCH_COUNT"), $databaseClient, $loggingContext, $logger);
    $cloudStorageClient = new S3CloudStorageClient(getenv("S3_REGION"), getenv("S3_HOST"), getenv("S3_PORT"), getenv("S3_ACCESS_KEY"), getenv("S3_SECRET_KEY"), getenv("S3_BASE_URL"));
    $searchClient = new OpenSearchClient(getenv("OPENSEARCH_HOST"), getenv("OPENSEARCH_PORT"), $logger);
    $databaseClient->setProgressReporter($messagingClient);
    $httpClient->setProgressReporter($messagingClient);
    $healthCheckables = array(
        $distributedCacheClient,
        $databaseClient,
        $messagingClient,
        $searchClient
    );

    // Event producers.
    $eventPublisher = new EventPublisher($messagingClient, $cloudMessagingClient, $distributedCacheClient, getenv("WORKER_QUEUE_NAME"));
    $calendarClient->setEventPublisher($eventPublisher);

    $scheduler = new Scheduler($databaseClient, $distributedCacheClient, $eventPublisher);

    // Configuration service.
    $configurationService = new ConfigurationService($databaseClient, $eventPublisher, getenv("RMQ_EXTERNAL_HOST"), getenv("RMQ_EXTERNAL_PORT"), getenv("RMQ_VHOST"), getenv("RMQ_USER"), getenv("RMQ_PASSWORD"));
    $googleClient->setConfigurationService($configurationService);

    // Authentication service.
    $commonAuthenticationService = new CommonAuthenticationService($distributedCacheClient, $httpClient, getenv("IAM_APP_CLIENT_ID"), getenv("IAM_HOST"), getenv("IAM_PORT"));
    $authenticationService = new AuthenticationService($httpClient, $distributedCacheClient, getenv("IAM_BACKEND_CLIENT_ID"), getenv("IAM_BACKEND_CLIENT_SECRET"), getenv("IAM_HOST"), getenv("IAM_PORT"));
    $cloudMessagingClient->setAuthenticationService($authenticationService);
    $googleClient->setAuthenticationService($authenticationService);
    $translationClient->setAuthenticationService($authenticationService);

    // Services.
    $embeddingService = new EmbeddingService($authenticationService, $httpClient, $distributedCacheClient, getenv("CORTEX_HOST"), getenv("CORTEX_PORT"));
    $clusteringService = new ClusteringService($authenticationService, $httpClient, getenv("CORTEX_HOST"), getenv("CORTEX_PORT"));
    $indexService = new IndexService($clusteringService, $embeddingService, $configurationService, $searchClient, $distributedCacheClient, $logger, getenv("COMPOSITE_INDEX_NAME"), getenv("PHOTO_INDEX_NAME"),
        getenv("SELECTED_PHOTO_CANDIDATES_LIMIT_COEFFICIENT"), getenv("CLUSTERS_COUNT_COEFFICIENT"), getenv("STYLE_EMBEDDING_COEFFICIENT"), getenv("NEGATIVE_EMBEDDING_COEFFICIENT"));
    $geocodingService = new GeocodingService($distributedCacheClient, $googleClient);
    $deviceService = new DeviceService($databaseClient, $authenticationService);
    $timeTrackingService = new TimeTrackingService($databaseClient, $configurationService);
    $statisticsService = new StatisticsService($distributedCacheClient, $eventPublisher, $logger, getenv("STATISTICS_VALUES_COUNT_LIMIT"));
    $noteService = new NoteService($databaseClient);
    $stayService = new StayService($databaseClient, $calendarClient, $googleClient, $eventPublisher);
    $photoService = new PhotoService($databaseClient, $embeddingService, $googleClient, $eventPublisher, $cloudStorageClient, $distributedCacheClient, $httpClient, getenv("CORE_BASE_URL"), getenv("ALBUM_THUMBNAIL_BUCKET"),
        getenv("PHOTO_THUMBNAIL_WIDTH"), getenv("PHOTO_THUMBNAIL_HEIGHT"), getenv("PHOTO_EMBEDDING_WIDTH"), getenv("PHOTO_EMBEDDING_HEIGHT"), getenv("INDOOR_PHOTO_ISO_THRESHOLD"));
    $highlightService = new HighlightService($databaseClient, $photoService, $embeddingService, $configurationService, $eventPublisher, $cloudStorageClient, $httpClient, $logger);
    $categoryService = new CategoryService($databaseClient, $configurationService, $highlightService, $indexService, $statisticsService, $memoryCacheClient, $cachingGenerativeContentClient, $eventPublisher, $logger);
    $expenseService = new ExpenseService($databaseClient, $configurationService, $eventPublisher, $exchangeRateClient, $distributedCacheClient, $encryptionClient);
    $fitnessService = new FitnessService($databaseClient, $eventPublisher, $logger, getenv("ALLOW_FITNESS_OVERWRITE_THRESHOLD_COEFFICIENT"),
        getenv("ALLOW_FITNESS_OVERWRITE_THRESHOLD_STEPS"), getenv("ALLOW_FITNESS_OVERWRITE_THRESHOLD_DISTANCE"), getenv("ALLOW_FITNESS_OVERWRITE_THRESHOLD_DURATION"), getenv("UPDATE_FITNESS_THRESHOLD_DAYS"));
    $flightService = new FlightService($databaseClient, $geocodingService, $categoryService, $flightClient, $calendarClient, $googleClient, $distributedCacheClient, $eventPublisher);
    $forecastService = new ForecastService($databaseClient, $actualForecastClient, $historicalForecastClient);
    $labelService = new LabelService($databaseClient, $configurationService);
    $placeService = new PlaceService($databaseClient, $generativeContentClient, $cachingGenerativeContentClient, $calendarClient, $googleClient, $distributedCacheClient, $memoryCacheClient, $configurationService, $categoryService,
        $labelService, $forecastService, $photoService, $highlightService, $noteService, $geocodingService, $indexService, $eventPublisher);
    $yearService = new YearService($databaseClient, $fitnessService, $placeService, $configurationService, $highlightService, $statisticsService, $indexService, $cachingGenerativeContentClient);
    $tripService = new TripService($databaseClient, $calendarClient, $googleClient, $cachingGenerativeContentClient, $configurationService, $placeService, $stayService, $flightService, $expenseService, $fitnessService,
        $noteService, $highlightService, $statisticsService, $yearService, $indexService, $eventPublisher);
    $monitoringService = new MonitoringService($distributedCacheClient, $eventPublisher, $logger);
    $documentService = new DocumentService($databaseClient, $encryptionClient);

    // Statistics providers.
    $statisticsProviders = array(
        new TripStatisticsProvider($tripService),
        new FlightStatisticsProvider($flightService),
        new StayStatisticsProvider($stayService),
        new FitnessStatisticsProvider($fitnessService, $placeService, $tripService),
        new PhotoStatisticsProvider($photoService, $placeService),
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
        new TripDataConsistencyMonitor($tripService, $configurationService),
        new PlaceDataConsistencyMonitor($placeService)
    );
    $monitoringService->setDataConsistencyMonitors($dataConsistencyMonitors);

    // Entity indexers.
    $entityIndexers = array(
        new CategoryIndexer($categoryService, $placeService),
        new PlaceIndexer($placeService, $geocodingService),
        new FlightIndexer($flightService),
        new LabelIndexer($labelService),
        new TripIndexer($tripService),
        new YearIndexer($yearService),
        new PhotoIndexer($photoService, $placeService, $tripService)
    );
    $indexService->setEntityIndexers($entityIndexers);

    // OpenLineage manager. Switching the producers on or off will require a script restart for changes to take effect.
    $openLineageEventManager = null;
    if (OpenLineageEventManager::isOpenLineageEnabled($configurationService)) {
        $openLineageEventPublishers = array(
            new IbmCloudOpenLineageEventPublisher($authenticationService, $configurationService, $httpClient, getenv("IBM_DATAPLATFORM_BASE_URL"), $logger), 
            new GoogleDriveOpenLineageEventPublisher($configurationService, $googleClient)
        );
        $openLineageEventManager = new OpenLineageEventManager($openLineageEventPublishers, $eventPublisher, getenv("CORE_BASE_URL"));
        $messagingClient->setOpenLineageEventManager($openLineageEventManager);
        $cloudMessagingClient->setOpenLineageEventManager($openLineageEventManager);
        $distributedCacheClient->setOpenLineageEventManager($openLineageEventManager);
        $databaseClient->setOpenLineageEventManager($openLineageEventManager);
        $httpClient->setOpenLineageEventManager($openLineageEventManager);      
        $searchClient->setOpenLineageEventManager($openLineageEventManager);  
    }
    
    // Event listeners.
    $listeners = array(
        new IndexServiceListener($indexService, $photoService, $highlightService, $placeService, $eventPublisher, $scheduler),
        new CategoryServiceListener($categoryService, $placeService, $eventPublisher, $scheduler, $logger, getenv("MAX_HIGHLIGHTS_PER_CATEGORY_COUNT")),
        new FitnessServiceListener($fitnessService, $tripService, $placeService, $eventPublisher, $scheduler, $logger),
        new FlightServiceListener($flightService, $tripService, $calendarClient, $eventPublisher, $scheduler, $logger),
        new ForecastServiceListener($forecastService, $placeService, $eventPublisher, $scheduler, getenv("ACTUAL_WEATHER_FORECAST_DAYS_TO_CACHE")),
        new HighlightServiceListener($highlightService, $eventPublisher, $scheduler),
        new PhotoServiceListener($photoService, $placeService, $distributedCacheClient, $eventPublisher, $scheduler),
        new PlaceServiceListener($placeService, $tripService, $categoryService, $photoService, $calendarClient, $eventPublisher, getenv("MIN_HIGHLIGHTS_PER_PLACE_COUNT"), getenv("MAX_HIGHLIGHTS_PER_PLACE_COUNT"), 
            getenv("HIGHLIGHT_SCORE_MULTIPLIER"), getenv("PHOTO_SCORE_MULTIPLIER"), getenv("MAIN_HIGHLIGHT_QUALITY_MULTIPLIER")),
        new StatisticsServiceListener($statisticsService, $placeService, $tripService, $categoryService, $flightService, $eventPublisher, $scheduler),
        new StayServiceListener($stayService, $tripService, $calendarClient),
        new TimeTrackingServiceListener($timeTrackingService, $eventPublisher, $scheduler),
        new TripServiceListener($databaseClient, $tripService, $placeService, $stayService, $flightService, $photoService, $highlightService, $calendarClient, $eventPublisher, $scheduler, getenv("MAX_HIGHLIGHTS_PER_TRIP_COUNT")),
        new YearServiceListener($yearService, $eventPublisher, $scheduler, $logger, getenv("MAX_HIGHLIGHTS_PER_YEAR_COUNT")),
        new DeviceServiceListener($deviceService, $tripService, $eventPublisher, $scheduler),
        new MonitoringServiceListener($monitoringService, $eventPublisher, $scheduler),
        new LabelServiceListener($labelService, $placeService, $configurationService, $eventPublisher, $scheduler),
        new OpenLineageEventManagerListener($openLineageEventManager, getenv("CORE_BASE_URL")),
        new PlatformListener($eventPublisher, $scheduler)
    );
    $eventListener = new RabbitMQEventListener($messagingClient, $loggingContext, $logger, $openLineageEventManager, $listeners, getenv("WORKER_QUEUE_NAME"));
    $eventPublisher->setDeviceService($deviceService);    
?>