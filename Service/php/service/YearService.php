<?php
    require_once(dirname(__FILE__) . "/../model/YearIdentifier.php");
    require_once(dirname(__FILE__) . "/../model/Year.php");

    class YearService {
        public function getYear($year) : ?Year {
            global $statisticsService, $highlightService;

            $yearIdentifier = $this->getYearIdentifier($year);

            if ($yearIdentifier === NULL) {
                return NULL;
            }
                
            $statistics = $statisticsService->getYearStatistics($year);   
            $highlights = $highlightService->getYearHighlights($year);   

            return new Year($year, $yearIdentifier->getMainHighlight(), $highlights, $statistics);
        }

        public function getYears($includedEntities) : array {
            global $databaseProvider, $statisticsService, $highlightService;

            $years = array();

            $yearRows = $databaseProvider
                ->statementBuilder("SELECT * FROM year_identifier")
                ->getResultSet();

            foreach ($yearRows as &$yearRow) {
                $statistics = array();
                if (in_array(YearIncludedEntity::Statistics->value, $includedEntities)) {
                    $statistics = $statisticsService->getYearStatistics($yearRow["id"]);               
                }

                $highlights = array();
                if (in_array(YearIncludedEntity::Highlights->value, $includedEntities)) {
                    $highlights = $highlightService->getYearHighlights($yearRow["id"]);                      
                }

                $years[] = new Year($yearRow["id"], $highlightService->getHighlight($yearRow["main_highlight_id"]), $highlights, $statistics);
            }

            return $years;
        }

        public function getYearIdentifier($year) : ?YearIdentifier {
            global $databaseProvider, $highlightService;
            
            $yearIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM year_identifier WHERE id = ?")
                ->withParameters($year)
                ->getSingleRow();

            if ($yearIdentifierRow === NULL) {
                return NULL;
            }
            
            return new YearIdentifier($yearIdentifierRow["id"], $highlightService->getHighlight($yearIdentifierRow["main_highlight_id"]));
        }

        public function getOrCreateYearIdentifier($year) : YearIdentifier {            
            global $databaseProvider;

            $yearIdentifier = $this->getYearIdentifier($year);
            if ($yearIdentifier !== NULL) {
                return $yearIdentifier;
            }

            $databaseProvider
                ->statementBuilder("INSERT INTO year_identifier (id) VALUES (?)")
                ->withParameters($year)
                ->execute();
                
            return $this->getYearIdentifier($year);
        }

        public function updateYearMainHighlight($year, $highlightIdentifier) : bool {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("UPDATE year_identifier SET main_highlight_id = ? WHERE id = ?")
                ->withParameters($highlightIdentifier, $year)
                ->execute() === 1;
        }

        public function onHighlightCreated(mixed $message) : void {
            if ($message["highlightType"] === HighlightType::Year->name) {
                $yearIdentifier = $this->getYearIdentifier($message["entityId"]);
                if ($yearIdentifier !== NULL && $yearIdentifier->getMainHighlight() === NULL) {
                    $this->updateYearMainHighlight($message["entityId"], $message["highlightId"]);
                }
            }
        }

        public function onSchedulerTriggered(mixed $message) : void {
            global $databaseProvider, $eventPublisher, $scheduler;
            
            if ($message["action"] === "UPDATE_YEAR_STATISTICS" && $message["timeSinceLastExecution"] > 604800) {
                $argsList = $databaseProvider
                    ->statementBuilder("SELECT DISTINCT year AS id FROM trip_summary WHERE start < UNIX_TIMESTAMP() AND name <> GET_CONFIGURATION_FOR_KEY('SPECIAL_TRIP_NAMES', 'dayTrips')")
                    ->getResultSet();

                foreach ($argsList as &$args) {
                    $eventPublisher->publishYearStatisticsInvalidatedEvent($args["id"]);
                }
                        
                $scheduler->recordEventsTriggered($message["action"]);
            }
        }
    }

    enum YearIncludedEntity : string {
        case Statistics = "STATISTICS";
        case Highlights = "HIGHLIGHTS";
    }
?>