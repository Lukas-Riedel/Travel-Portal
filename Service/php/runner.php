<?php
    session_start();

    require_once(dirname(__FILE__) . "/provider/DatabaseProvider.php");
    require_once(dirname(__FILE__) . "/provider/LoggingProvider.php");
    require_once(dirname(__FILE__) . "/provider/ConfigurationProvider.php");
    require_once(dirname(__FILE__) . "/service/PlaceService.php");
    require_once(dirname(__FILE__) . "/service/HighlightService.php");
    require_once(dirname(__FILE__) . "/service/PhotoService.php");
    require_once(dirname(__FILE__) . "/service/TripService.php");
    require_once(dirname(__FILE__) . "/service/AlbumService.php");
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
    require_once(dirname(__FILE__) . "/event/Scheduler.php");
    require_once(dirname(__FILE__) . "/event/EventManager.php");
    require_once(dirname(__FILE__) . "/event/EventPublisher.php");

    $databaseProvider = new DatabaseProvider(FALSE);
    $configurationProvider = new ConfigurationProvider($databaseProvider);
    $configuration = $configurationProvider->get(PUBLIC_CONFIGURATION, PRIVATE_CONFIGURATION);
    $loggingProvider = new LoggingProvider($databaseProvider);
    $placeService = new PlaceService();
    $highlightService = new HighlightService();
    $photoService = new PhotoService();
    $tripService = new TripService();
    $albumService = new AlbumService();
    $categoryService = new CategoryService();
    $expenseService = new ExpenseService();
    $yearService = new YearService();
    $noteService = new NoteService();
    $configurationService = new ConfigurationService();
    $flightService = new FlightService();
    $timeTrackingService = new TimeTrackingService();
    $fitnessService = new FitnessService();
    $googleApiClient = new GoogleApiClient();
    $chatClient = new ChatClient();
    $httpClient = new HttpClient();
    $statisticsService = new StatisticsService();
    $platformService = new PlatformService();
    $geocodingService = new GeocodingService();
    $calendarClient = new CalendarClient();
    $stayService = new StayService();
    $forecastService = new ForecastService();
    $authenticationService = new AuthenticationService();

    $services = array($placeService, $highlightService, $photoService, $tripService, $albumService, $categoryService,
        $expenseService, $yearService, $noteService, $configurationService, $flightService, $timeTrackingService,
        $fitnessService, $statisticsService, $geocodingService, $stayService, $forecastService, $authenticationService, $platformService);
        
    $eventManager = new EventManager($services);
    $eventPublisher = new EventPublisher();
    $scheduler = new Scheduler($databaseProvider, $eventPublisher);
    
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