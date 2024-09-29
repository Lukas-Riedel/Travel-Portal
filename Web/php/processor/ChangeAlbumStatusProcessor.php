<?php
    require_once(dirname(__FILE__) . "/../model/Album.php");

    class ChangeAlbumStatusProcessor extends Processor {    
        public function process($input) {
            global $databaseProvider;

            if ($input["type"] == "MAIN_FOR_PLACE") {
                $databaseProvider
                    ->statementBuilder("UPDATE album_metadata SET is_main_for_place = 0 WHERE id IN (SELECT album_id FROM place_summary WHERE place_id = ?)")
                    ->withParameters($input["placeId"])
                    ->execute();
                    
                $databaseProvider
                    ->statementBuilder("UPDATE album_metadata SET is_main_for_place = 1 WHERE id = ?")
                    ->withParameters($input["albumId"])
                    ->execute();
            }
            else if ($input["type"] == "MAIN_FOR_COUNTRY") {
                $databaseProvider
                    ->statementBuilder("UPDATE album_metadata SET is_main_for_country = 0 WHERE id IN (SELECT album_id FROM place_summary WHERE country IN (SELECT country FROM place_summary WHERE place_id = ?))")
                    ->withParameters($input["placeId"])
                    ->execute();
                    
                $databaseProvider
                    ->statementBuilder("UPDATE album_metadata SET is_main_for_country = 1 WHERE id = ?")
                    ->withParameters($input["albumId"])
                    ->execute();
            }
            else if ($input["type"] == "MAIN_FOR_TRIP") {
                $databaseProvider
                    ->statementBuilder("UPDATE album_metadata SET is_main_for_trip = 0 WHERE id IN (SELECT album_id FROM place_summary WHERE trip_id IN (SELECT trip_id FROM place_summary WHERE place_id = ?))")
                    ->withParameters($input["placeId"])
                    ->execute();
                    
                $databaseProvider
                    ->statementBuilder("UPDATE album_metadata SET is_main_for_trip = 1 WHERE id = ?")
                    ->withParameters($input["albumId"])
                    ->execute();
            }
            else if ($input["type"] == "LOW_QUALITY") {                    
                $databaseProvider
                    ->statementBuilder("UPDATE album_metadata SET is_low_quality = ABS(is_low_quality - 1) WHERE id = ?")
                    ->withParameters($input["albumId"])
                    ->execute();
            }
            else if ($input["type"] == "BAD_WEATHER") {                    
                $databaseProvider
                    ->statementBuilder("UPDATE album_metadata SET is_bad_weather = ABS(is_bad_weather - 1) WHERE id = ?")
                    ->withParameters($input["albumId"])
                    ->execute();
            }

            $albumRow = $databaseProvider
                ->statementBuilder("SELECT a.*, am.is_main_for_place, am.is_main_for_country, am.is_main_for_trip, am.is_low_quality, am.is_bad_weather FROM album a INNER JOIN album_metadata am ON a.id = am.id WHERE a.id = ?")
                ->withParameters($input["albumId"])
                ->getSingleRow();

            return new Album($albumRow["id"], $albumRow["name"], $album["main_photo_id"], $albumRow["main_image_url"], $albumRow["permalink"], $albumRow["images_count"], $albumRow["indoor_images_count"], $albumRow["images_count"] == 0, 
                $albumRow["is_main_for_place"] == 1, $albumRow["is_main_for_country"] == 1, $albumRow["is_main_for_trip"] == 1, $albumRow["is_low_quality"] == 1, $albumRow["is_bad_weather"] == 1);
        }

        public function getRequiredArguments() {
            return array("type", "albumId", "placeId");
        }
        
        public function requiresAuthentication() {
            return TRUE;
        }
    }
?>