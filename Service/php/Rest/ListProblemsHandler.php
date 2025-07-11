<?php
    require_once(dirname(__FILE__) . "/../Processor/GetProblemsReportProcessor.php");

    class ListProblemsHandler extends Handler {
        public function handle($input, $roles) {
            $response = (new GetProblemsReportProcessor())->getReport();
            return $this->createResponse(200, $response);
        }

        public function getRequiredRole() {
            return "USER";
        }
        
        public function isProtected() {
            return TRUE;
        }

        public function getTag() {
            return "Problems";
        }

        public function getPath() {
            return "/problems";
        }

        public function getParameters() {
            return array();
        }

        public function getMethod() {
            return "GET";
        }
        
        public function getShortDescription() {
            return "Retrieve a collection of problems";
        }
        
        public function getLongDescription() {
            global $databaseProvider;
            $problemNames = $databaseProvider
                ->statementBuilder("SELECT CONCAT('``', name, '``') AS name FROM definition_problem")
                ->getResultSetForColumn("name");

            return "Retrieves a collection of reported problems that cannot be resolved automatically and require some attention from the user. The allowed problem names are: " . implode(", ", $problemNames);
        }
        
        public function getRequestExamples() {
            return array();
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Reported problems", 200, '[{"name":"NON_LOGGED_FLIGHTS","values":[{"name":"Benátky - Praha (FR1531) @ 04.09.2024","context":{"scheduledDeparture":1725473100,"flight":"FR1531","from":"Benátky","to":"Praha","tripId":125}}]},{"name":"LOW_QUALITY_PHOTOS_WITHOUT_REPLACEMENT","values":[{"name":"Demänovská dolina","context":null},{"name":"Skalnatá dolina","context":null},{"name":"Mengusovská dolina","context":null},{"name":"Štrbské pleso","context":null},{"name":"Königssee","context":null},{"name":"Hallstatt","context":null},{"name":"Krippenstein","context":null},{"name":"Salcburk","context":null},{"name":"Hohnstein","context":null},{"name":"Hřensko","context":null},{"name":"Houska","context":null},{"name":"Jetřichovice","context":null},{"name":"Sychrov","context":null},{"name":"Bad Schandau","context":null},{"name":"Norimberk","context":null},{"name":"Most","context":null},{"name":"Bílina","context":null},{"name":"Duchcov","context":null},{"name":"Litomyšl","context":null},{"name":"Svojkov","context":null},{"name":"Měděnec","context":null},{"name":"Hluboká nad Vltavou","context":null},{"name":"Georgenfeldské vrchoviště","context":null},{"name":"Tetín","context":null}]},{"name":"FUTURE_COUNTRIES_WITHOUT_PUBLIC_HOLIDAYS_CALENDAR","values":[{"name":"Kosovo","context":{"country":"Kosovo"}}]},{"name":"LOGGED_ERRORS","values":[{"name":"{\"code\":400,\"error\":\"The configuration was not updated. Either it does not exist, or no changes were made.\",\"details\":{\"action\":\"ChangeConfiguration\",\"arguments\":{\"key\":\"FR\",\"value\":\"Ryanair\",\"type\":\"AIRLINES\"}}} @ 21:33:38<br><br>","context":null},{"name":"{\"code\":400,\"error\":\"Duplicate entry \'41061\' for key \'PRIMARY\'\",\"details\":{\"action\":\"GetMediaItems\",\"arguments\":{\"placeId\":4865,\"albumId\":\"813\"},\"trace\":\"#0 \\/data\\/web\\/virtuals\\/254146\\/virtual\\/www\\/domains\\/lriedel.cz\\/php\\/provider\\/DatabaseProvider.php(168): mysqli_stmt-\\u003Eexecute(Array)\\n#1 \\/data\\/web\\/virtuals\\/254146\\/virtual\\/www\\/domains\\/lriedel.cz\\/php\\/processor\\/GetMediaItemsProcessor.php(52): StatementBuilder-\\u003Eexecute()\\n#2 \\/data\\/web\\/virtuals\\/254146\\/virtual\\/www\\/domains\\/lriedel.cz\\/php\\/provider\\/ProcessorProvider.php(41): GetMediaItemsProcessor-\\u003Eprocess(Array)\\n#3 \\/data\\/web\\/virtuals\\/254146\\/virtual\\/www\\/domains\\/lriedel.cz\\/php\\/runner.php(23): ProcessorProvider-\\u003Erun(\'GetMediaItems\', Array)\\n#4 \\/data\\/web\\/virtuals\\/254146\\/virtual\\/www\\/domains\\/lriedel.cz\\/php\\/scheduler.php(47): require_once(\'\\/data\\/web\\/virtu...\')\\n#5 {main}\"}} @ 03:41:01<br><br>","context":null},{"name":"{\"code\":400,\"error\":\"Duplicate entry \'34375\' for key \'PRIMARY\'\",\"details\":{\"action\":\"GetMediaItems\",\"arguments\":{\"placeId\":4699,\"albumId\":\"866\"},\"trace\":\"#0 \\/data\\/web\\/virtuals\\/254146\\/virtual\\/www\\/domains\\/lriedel.cz\\/php\\/provider\\/DatabaseProvider.php(168): mysqli_stmt-\\u003Eexecute(Array)\\n#1 \\/data\\/web\\/virtuals\\/254146\\/virtual\\/www\\/domains\\/lriedel.cz\\/php\\/processor\\/GetMediaItemsProcessor.php(52): StatementBuilder-\\u003Eexecute()\\n#2 \\/data\\/web\\/virtuals\\/254146\\/virtual\\/www\\/domains\\/lriedel.cz\\/php\\/provider\\/ProcessorProvider.php(41): GetMediaItemsProcessor-\\u003Eprocess(Array)\\n#3 \\/data\\/web\\/virtuals\\/254146\\/virtual\\/www\\/domains\\/lriedel.cz\\/php\\/runner.php(23): ProcessorProvider-\\u003Erun(\'GetMediaItems\', Array)\\n#4 \\/data\\/web\\/virtuals\\/254146\\/virtual\\/www\\/domains\\/lriedel.cz\\/php\\/scheduler.php(47): require_once(\'\\/data\\/web\\/virtu...\')\\n#5 {main}\"}} @ 03:40:57<br><br>","context":null},{"name":"{\"code\":400,\"error\":\"The argument \'scheduledDeparture\' is required.\",\"details\":{\"action\":\"LogFlight\",\"arguments\":{\"flight\":\"FR1531\",\"from\":\"Benátky\",\"to\":\"Praha\",\"start\":\"1725473100\",\"tripId\":\"125\"},\"trace\":\"#0 \\/data\\/web\\/virtuals\\/254146\\/virtual\\/www\\/domains\\/lriedel.cz\\/php\\/controller.php(28): ProcessorProvider-\\u003Erun(\'LogFlight\', Array)\\n#1 {main}\"}} @ 23:02:14<br><br>","context":null},{"name":"{\"code\":400,\"error\":\"json_decode(): Passing null to parameter #1 ($json) of type string is deprecated\",\"details\":{\"action\":\"GetProblemsReport\",\"arguments\":[]}} @ 22:56:00<br><br>","context":null}]}]'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create403ResponseExample());
        }
    }
?>