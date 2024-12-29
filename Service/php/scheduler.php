<?php
    require_once(dirname(__FILE__) . "/provider/DatabaseProvider.php");
    require_once(dirname(__FILE__) . "/provider/LoggingProvider.php");
    require_once(dirname(__FILE__) . "/provider/ConfigurationProvider.php");
    require_once(dirname(__FILE__) . "/provider/SchedulingProvider.php");
    require_once(dirname(__FILE__) . "/processor/Processor.php");
    require_once(dirname(__FILE__) . "/service/PlaceService.php");
    require_once(dirname(__FILE__) . "/service/HighlightService.php");
    require_once(dirname(__FILE__) . "/service/PhotoService.php");
    require_once(dirname(__FILE__) . "/service/TripService.php");
    require_once(dirname(__FILE__) . "/service/AlbumService.php");
    require_once(dirname(__FILE__) . "/service/CategoryService.php");
    require_once(dirname(__FILE__) . "/service/ExpenseService.php");
    require_once(dirname(__FILE__) . "/service/YearService.php");
    require_once(dirname(__FILE__) . "/service/NoteService.php");
    require_once(dirname(__FILE__) . "/service/ConfigurationService.php");
    require_once(dirname(__FILE__) . "/service/FlightService.php");
    require_once(dirname(__FILE__) . "/service/FitnessService.php");
    require_once(dirname(__FILE__) . "/service/TimeTrackingService.php");
    require_once(dirname(__FILE__) . "/client/GoogleApiClient.php");
    require_once(dirname(__FILE__) . "/client/ChatClient.php");
    require_once(dirname(__FILE__) . "/client/HttpClient.php");
    require_once(dirname(__FILE__) . "/service/StatisticsService.php");
    require_once(dirname(__FILE__) . "/service/GeocodingService.php");
    require_once(dirname(__FILE__) . "/client/CalendarClient.php");
    require_once(dirname(__FILE__) . "/service/StayService.php");
    require_once(dirname(__FILE__) . "/service/ForecastService.php");

    $databaseProvider = new DatabaseProvider(FALSE);
    $configurationProvider = new ConfigurationProvider($databaseProvider);
    $configuration = $configurationProvider->get(PUBLIC_CONFIGURATION, PRIVATE_CONFIGURATION);
    $schedulingProvider = new SchedulingProvider($databaseProvider, $configuration);
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
    $fitnessService = new FitnessService();
    $timeTrackingService = new TimeTrackingService();
    $googleApiClient = new GoogleApiClient();
    $chatClient = new ChatClient();
    $httpClient = new HttpClient();
    $statisticsService = new StatisticsService();
    $geocodingService = new GeocodingService();
    $calendarClient = new CalendarClient();
    $stayService = new StayService();
    $forecastService = new ForecastService();

    $schedulers = $databaseProvider
        ->statementBuilder("SELECT * FROM scheduler")
        ->getResultSet();

    foreach ($schedulers as &$scheduler) {
        $interval = doubleval(array_values($databaseProvider
            ->statementBuilder(str_replace("{{name}}", $scheduler["name"], $scheduler["interval_query"]))
            ->getSingleRow())[0]);

        if ($scheduler["last_execution"] + $interval > time()) {
            continue;
        }

        if ($scheduler["args_query"] == NULL) {
            $schedulingProvider
                ->scheduleJobExecution($scheduler["processor"], NULL, NULL);
        }
        else {
            $argumentsList = $databaseProvider
                ->statementBuilder($scheduler["args_query"])
                ->getResultSet();

            foreach ($argumentsList as &$arguments) {
                $schedulingProvider
                    ->scheduleJobExecution($scheduler["processor"], $arguments, NULL);
            }
        }

        $databaseProvider
            ->statementBuilder("UPDATE scheduler SET last_execution = UNIX_TIMESTAMP() WHERE name = ?")
            ->withParameters($scheduler["name"])
            ->execute();
    }

    require_once(dirname(__FILE__) . "/runner.php");
?>