<?php
    session_start();

    require_once(dirname(__FILE__) . "/provider/DatabaseProvider.php");
    require_once(dirname(__FILE__) . "/provider/LoggingProvider.php");
    require_once(dirname(__FILE__) . "/provider/ConfigurationProvider.php");
    require_once(dirname(__FILE__) . "/provider/SchedulingProvider.php");
    require_once(dirname(__FILE__) . "/provider/ProcessorProvider.php");
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
    require_once(dirname(__FILE__) . "/service/FlightService.php");
    require_once(dirname(__FILE__) . "/service/ConfigurationService.php");
    require_once(dirname(__FILE__) . "/service/TimeTrackingService.php");
    require_once(dirname(__FILE__) . "/service/FitnessService.php");
    require_once(dirname(__FILE__) . "/client/GoogleApiClient.php");
    require_once(dirname(__FILE__) . "/client/ChatClient.php");
    require_once(dirname(__FILE__) . "/client/HttpClient.php");

    $databaseProvider = new DatabaseProvider(FALSE);
    $configurationProvider = new ConfigurationProvider($databaseProvider);
    $configuration = $configurationProvider->get(PUBLIC_CONFIGURATION, PRIVATE_CONFIGURATION);
    $loggingProvider = new LoggingProvider($databaseProvider);
    $schedulingProvider = new SchedulingProvider($databaseProvider, $configuration);
    $processorProvider = new ProcessorProvider($databaseProvider, $schedulingProvider, $loggingProvider, FALSE);
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
    
    $supportedActions = array_filter(array_map(function ($file) {
        $tokens = explode("/", $file);
        return str_replace("Processor.php", "", $tokens[count($tokens) - 1]);
    }, glob(dirname(__FILE__) . "/processor/*")), function ($action) {
        return $action !== "";
    });

    $key = ftok(__FILE__, 1);
    $semaphore = sem_get($key);

    if (sem_acquire($semaphore, TRUE)) {
        while (($jobExecution = getNextScheduledJobExecution()) != NULL) {
            $processorProvider->run($jobExecution["processor"], $jobExecution["args"]);
            terminateJobExecution($jobExecution["id"]);
        }

        sem_release($semaphore);
    }

    function getNextScheduledJobExecution() {
        global $databaseProvider, $supportedActions;

        $nextJob = $databaseProvider
            ->statementBuilder("SELECT * FROM queue_job WHERE FIND_IN_SET(processor, ?) ORDER BY priority ASC LIMIT 1")
            ->withParameters(implode(",", $supportedActions))
            ->getSingleRow();

        if ($nextJob != NULL) {    
            $nextJob = array(
                "id" => $nextJob["id"],
                "processor" => $nextJob["processor"],
                "args" => json_decode($nextJob["args"], TRUE)
            );
        }

        return $nextJob;
    }

    function terminateJobExecution($id) {
        global $databaseProvider;

        $databaseProvider
	        ->materializeViews();

        $databaseProvider
            ->statementBuilder("DELETE FROM queue_job WHERE id = ?")
            ->withParameters($id)
            ->execute();
    }
?>