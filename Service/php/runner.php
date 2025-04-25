<?php
    session_start();

    require_once(dirname(__FILE__) . "/../vendor/autoload.php");
    require_once(dirname(__FILE__) . "/config/secrets.php");
    
    require_once(dirname(__FILE__) . "/Provider/DatabaseProvider.php");
    require_once(dirname(__FILE__) . "/Provider/LoggingProvider.php");
    require_once(dirname(__FILE__) . "/Provider/ConfigurationProvider.php");
    require_once(dirname(__FILE__) . "/Service/PlaceService.php");
    require_once(dirname(__FILE__) . "/Service/HighlightService.php");
    require_once(dirname(__FILE__) . "/Service/PhotoService.php");
    require_once(dirname(__FILE__) . "/Service/TripService.php");
    require_once(dirname(__FILE__) . "/Service/CategoryService.php");
    require_once(dirname(__FILE__) . "/Service/ExpenseService.php");
    require_once(dirname(__FILE__) . "/Service/YearService.php");
    require_once(dirname(__FILE__) . "/Service/NoteService.php");
    require_once(dirname(__FILE__) . "/Service/FlightService.php");
    require_once(dirname(__FILE__) . "/Service/ConfigurationService.php");
    require_once(dirname(__FILE__) . "/Service/TimeTrackingService.php");
    require_once(dirname(__FILE__) . "/Service/FitnessService.php");
    require_once(dirname(__FILE__) . "/Client/GoogleApiClient.php");
    require_once(dirname(__FILE__) . "/Client/ChatClient.php");
    require_once(dirname(__FILE__) . "/Client/HttpClient.php");
    require_once(dirname(__FILE__) . "/Service/StatisticsService.php");
    require_once(dirname(__FILE__) . "/Service/GeocodingService.php");
    require_once(dirname(__FILE__) . "/Client/CalendarClient.php");
    require_once(dirname(__FILE__) . "/Service/StayService.php");
    require_once(dirname(__FILE__) . "/Service/ForecastService.php");
    require_once(dirname(__FILE__) . "/Service/AuthenticationService.php");
    require_once(dirname(__FILE__) . "/Service/PlatformService.php");
    require_once(dirname(__FILE__) . "/Service/LabelService.php");
    require_once(dirname(__FILE__) . "/Event/Scheduler.php");
    require_once(dirname(__FILE__) . "/Event/EventManager.php");
    require_once(dirname(__FILE__) . "/Event/EventPublisher.php");

    $databaseProvider = new DatabaseProvider(FALSE);
    $configurationProvider = new ConfigurationProvider($databaseProvider);
    $configuration = $configurationProvider->get(PUBLIC_CONFIGURATION, PRIVATE_CONFIGURATION);
    $loggingProvider = new LoggingProvider($databaseProvider);
    $configurationService = new ConfigurationService();
    $timeTrackingService = new TimeTrackingService($databaseProvider, $configurationService);
    $googleApiClient = new GoogleApiClient();
    $chatClient = new ChatClient();
    $httpClient = new HttpClient();
    $platformService = new PlatformService();
    $calendarClient = new CalendarClient();
    $authenticationService = new AuthenticationService($databaseProvider, $configurationService);
    $eventPublisher = new EventPublisher();
    $scheduler = new Scheduler($databaseProvider, $eventPublisher);
    $statisticsService = new StatisticsService($databaseProvider, $configurationService, $eventPublisher, $scheduler);
    $noteService = new NoteService($databaseProvider);
    $stayService = new StayService($databaseProvider, $calendarClient, $googleApiClient, $eventPublisher);
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
    $placeService = new PlaceService($databaseProvider, $chatClient, $calendarClient, $googleApiClient, $configurationService, $categoryService, $labelService, $forecastService, $photoService, $highlightService, $geocodingService, $eventPublisher, $scheduler);
    $tripService = new TripService($databaseProvider, $calendarClient, $googleApiClient, $configurationService, $placeService, $stayService, $flightService,
        $expenseService, $fitnessService, $noteService, $highlightService, $statisticsService, $yearService, $eventPublisher, $scheduler);

    $services = array($placeService, $highlightService, $photoService, $tripService, $photoService, $categoryService, $labelService,
        $expenseService, $yearService, $noteService, $configurationService, $flightService, $timeTrackingService,
        $fitnessService, $statisticsService, $geocodingService, $stayService, $forecastService, $authenticationService, $platformService);
    $statisticsService->setStatisticsProviders(array($placeService, $tripService, $yearService, $stayService, $photoService, $categoryService, $expenseService, $fitnessService, $flightService));
        
    $eventManager = new EventManager($services);
    
    $onError = function($level, $message, $file, $line) {
        throw new RuntimeException($message);
    };
    set_error_handler($onError);
    
    $key = ftok(__FILE__, 1);
    $semaphore = sem_get($key);

    if (sem_acquire($semaphore, TRUE)) {
        $eventManager->handleEvents();
        sem_release($semaphore);
    }
    
    restore_error_handler();
?>