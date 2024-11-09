<?php
    require_once(dirname(__FILE__) . "/GetHttpResponseProcessor.php");
    require_once(dirname(__FILE__) . "/../lib/suncalc.php");

    class UpdateDaylightForecastProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider;
            
            $placeIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM place_identifier WHERE id = ?")
                ->withParameters($input["placeId"])
                ->getSingleRow();

            $dateTime = new DateTime();
            $dateTime->setTimestamp(intval($input["start"]));
            $suncalc = new AurorasLive\SunCalc($dateTime, $placeIdentifierRow["latitude"], $placeIdentifierRow["longitude"]);
            $sunTimes = $suncalc->getSunTimes();
            $startSunPosition = $suncalc->getSunPosition($dateTime);
            $dateTime->setTimestamp(intval($input["end"]));
            $endSunPosition = $suncalc->getSunPosition($dateTime);
            
            $databaseProvider
                ->statementBuilder("DELETE FROM forecast_daylight WHERE place_id = ? AND timestamp = ?")
                ->withParameters($placeIdentifierRow["id"], $input["start"])
                ->execute();

            $databaseProvider
                ->statementBuilder("INSERT INTO forecast_daylight (place_id, timestamp, sunrise, sunset, start_sun_altitude, end_sun_altitude) VALUES (?, ?, ?, ?, ?, ?)")
                ->withParameters($placeIdentifierRow["id"], $input["start"], $sunTimes["sunrise"]->getTimestamp(), $sunTimes["sunset"]->getTimestamp(), $startSunPosition->altitude * 180 / M_PI, $endSunPosition->altitude * 180 / M_PI)
                ->execute();

            return TRUE;
        }

        public function getRequiredArguments() {
            return array("placeId", "start", "end");
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }
    }
?>