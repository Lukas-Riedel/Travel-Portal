<?php
    session_start();

    require_once(dirname(__FILE__) . "/../vendor/autoload.php");
    
    require_once(dirname(__FILE__) . "/provider/DatabaseProvider.php");
    require_once(dirname(__FILE__) . "/provider/LoggingProvider.php");
    require_once(dirname(__FILE__) . "/provider/ConfigurationProvider.php");
    require_once(dirname(__FILE__) . "/service/PlaceService.php");
    require_once(dirname(__FILE__) . "/service/HighlightService.php");
    require_once(dirname(__FILE__) . "/service/PhotoService.php");
    require_once(dirname(__FILE__) . "/service/TripService.php");
    require_once(dirname(__FILE__) . "/service/CategoryService.php");
    require_once(dirname(__FILE__) . "/service/ExpenseService.php");
    require_once(dirname(__FILE__) . "/service/YearService.php");
    require_once(dirname(__FILE__) . "/service/NoteService.php");
    require_once(dirname(__FILE__) . "/service/FlightService.php");
    require_once(dirname(__FILE__) . "/service/ConfigurationService.php");
    require_once(dirname(__FILE__) . "/service/TimeTrackingService.php");
    require_once(dirname(__FILE__) . "/service/FitnessService.php");
    require_once(dirname(__FILE__) . "/client/GoogleApiClient.php");
    require_once(dirname(__FILE__) . "/client/ChatClient.php");
    require_once(dirname(__FILE__) . "/client/HttpClient.php");
    require_once(dirname(__FILE__) . "/service/StatisticsService.php");
    require_once(dirname(__FILE__) . "/service/GeocodingService.php");
    require_once(dirname(__FILE__) . "/client/CalendarClient.php");
    require_once(dirname(__FILE__) . "/service/StayService.php");
    require_once(dirname(__FILE__) . "/service/ForecastService.php");
    require_once(dirname(__FILE__) . "/service/AuthenticationService.php");
    require_once(dirname(__FILE__) . "/service/PlatformService.php");
    require_once(dirname(__FILE__) . "/service/LabelService.php");
    require_once(dirname(__FILE__) . "/event/Scheduler.php");
    require_once(dirname(__FILE__) . "/event/EventManager.php");
    require_once(dirname(__FILE__) . "/event/EventPublisher.php");

    $databaseProvider = new DatabaseProvider(FALSE);
    $configurationProvider = new ConfigurationProvider($databaseProvider);
    $configuration = $configurationProvider->get(PUBLIC_CONFIGURATION, PRIVATE_CONFIGURATION);
    $loggingProvider = new LoggingProvider($databaseProvider);
    $placeService = new PlaceService();
    $tripService = new TripService();
    $yearService = new YearService();
    $configurationService = new ConfigurationService();
    $timeTrackingService = new TimeTrackingService($databaseProvider, $configurationService);
    $googleApiClient = new GoogleApiClient();
    $chatClient = new ChatClient();
    $httpClient = new HttpClient();
    $platformService = new PlatformService();
    $calendarClient = new CalendarClient();
    $authenticationService = new AuthenticationService();
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
    $labelService = new LabelService($databaseProvider);

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