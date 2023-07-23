<?php
    require_once(dirname(__FILE__) . "/GetPlaceHandler.php");

    class UpdatePlaceHandler extends Handler {
        public function handle($input) {
            global $processorProvider;

            $response = (new GetPlaceHandler())
                ->handle(array(
                    "placeId" => $input["placeId"]));                    
            if ($response["code"] != 200) {
                return $response;
            }     

            $response = $processorProvider->run("ChangePlaceIdentifier", $input);
            if ($response instanceof TargetError) {
                return $this->createResponse(NULL, $response);
            }
    
            return (new GetPlaceHandler())
                ->handle(array(
                    "placeId" => $input["placeId"]));
        }

        public function getTag() {
            return "Places";
        }

        public function getPath() {
            return "/places/{placeId}";
        }

        public function getParameters() {
            return array(
                $this->createPathParameter("placeId", "integer", 2507));
        }

        public function getMethod() {
            return "PATCH";
        }

        public function getOperationId() {
            return "update_place";
        }
        
        public function getShortDescription() {
            return "Update a place with the specified identifier";
        }
        
        public function getLongDescription() {
            return "Updates a place with the specified identifier.";
        }
        
        public function getRequestExamples() {
            return array(
                $this->createRequestExample("Update place name", '{"name":"Praha"}'),
                $this->createRequestExample("Update place location", '{"latitude":50.0755381,"longitude":14.4378005}'));
        }

        public function getResponseExamples() {
            return array(
                $this->createResponseExample("Updated place", 200, '{"id":2507,"name":"Praha","country":"Česko","latitude":50.0755381,"longitude":14.4378005,"timezone":"Europe/Prague","categories":[{"id":1,"name":"Evropa","category":"CONTINENT"},{"id":9,"name":"Evropská unie","category":"REGION"},{"id":30,"name":"Střední Evropa","category":"REGION"},{"id":81,"name":"Česko","category":"COUNTRY"},{"id":441,"name":"Praha","category":"ADMINISTRATIVE"},{"id":2,"name":"Poslední rok","category":"VARIABLE"},{"id":3,"name":"Poslední měsíc","category":"VARIABLE"}],"dates":[{"start":1589580000,"end":1589666400,"weather":null,"album":{"id":1052,"name":"Praha 16.5.2020","mainImageUrl":"https://lriedel.cz/cache/album/AGhjs2kIrpOCCT54MdTt-oA1VE9oWRZp4V_uDZnaSZUvp8aM5tOtO_kWZDW9goBSC0nriO05LuKLRaPKcaAkQauVv7EsoxckQQ.jpg","permalink":"https://photos.google.com/lr/album/AGhjs2lxV1nXHgWw_G6CAL9Sm-jnZX7wU5qM_ai2Ro6OzEv2olcSAwgaB_gmXUrep2CPl2ygyjxq","imagesCount":112,"indoorImagesCount":4,"isEmpty":false,"isMainForPlace":false,"isMainForCountry":false,"isMainForTrip":false,"isLowQuality":true,"isBadWeather":false},"trip":null},{"start":1597183200,"end":1597269600,"weather":null,"album":{"id":1045,"name":"Praha 12.8.2020","mainImageUrl":"https://lriedel.cz/cache/album/AGhjs2mg-bQzUiTPFNLDlpuL1NTpCdVR5FJNZ0DAYc07mXdIXS9qUZ6fSwq64zJI-b8r0B3_3ad8mJ1RBnuOcTHULWzyAkryng.jpg","permalink":"https://photos.google.com/lr/album/AGhjs2kWvPW6xzetOZ7Cwcwlcgm8vrOAzOMIjGjlhWFhx7mmGjeR3_e4ucQNV4hEmKhj63r2kn69","imagesCount":40,"indoorImagesCount":0,"isEmpty":false,"isMainForPlace":false,"isMainForCountry":false,"isMainForTrip":false,"isLowQuality":true,"isBadWeather":false},"trip":null},{"start":1600293600,"end":1600380000,"weather":null,"album":{"id":1022,"name":"Praha 17.9.2020","mainImageUrl":"https://lriedel.cz/cache/album/AGhjs2lJqwTcGw6YPjmLpHqbCSAtfMuwqxLSj7aB6Idu1jnetE_lltrtlxmx-Ok67RTA3PlWnW0fB9CsvqNrtRY4W8a9XR3oNg.jpg","permalink":"https://photos.google.com/lr/album/AGhjs2ku_esxTpMeLXWKfh8KcZsGx6LW1mgtSHnSx6ZJWp-Q-vM8jb5oYbw27b0INQyiOaS3ZtjQ","imagesCount":37,"indoorImagesCount":0,"isEmpty":false,"isMainForPlace":false,"isMainForCountry":false,"isMainForTrip":false,"isLowQuality":true,"isBadWeather":false},"trip":null},{"start":1619733600,"end":1619820000,"weather":null,"album":{"id":1015,"name":"Praha 30.4.2021","mainImageUrl":"https://lriedel.cz/cache/album/AGhjs2nCTMmsrlMdsySSpWA9FH1BVnWGeEQasu1sRQwfmU6G63AQpHQyYnwT5Q9pDb_M12IDssh1ekM-uJGIFQ3KCmfcLl1BqA.jpg","permalink":"https://photos.google.com/lr/album/AGhjs2mmxBLE2QJW-Buozbn-UXpoUzrjCEFz3r3tNmkYYo1LIojTB3GKM_KmBvAzZK6Ci3g-FYE6","imagesCount":22,"indoorImagesCount":0,"isEmpty":false,"isMainForPlace":false,"isMainForCountry":false,"isMainForTrip":false,"isLowQuality":true,"isBadWeather":false},"trip":null},{"start":1619992800,"end":1620079200,"weather":null,"album":{"id":1014,"name":"Praha 3.5.2021","mainImageUrl":"https://lriedel.cz/cache/album/AGhjs2nTDj3hKLsOBvQ2lTv-Ih07aB9Z9eMMBCVxgktMAWsLM94ihr86uClhYXtYIIdmFh6rFud7d4-Q53vl05eVbhoA2XU4Aw.jpg","permalink":"https://photos.google.com/lr/album/AGhjs2k7TM1nTLu1UyQ2ZcishV0kz-cH-G908zJH8NhJ561PO2ldK5ohi2wrsvTMQwUm4KJOqOfd","imagesCount":15,"indoorImagesCount":0,"isEmpty":false,"isMainForPlace":false,"isMainForCountry":false,"isMainForTrip":false,"isLowQuality":true,"isBadWeather":false},"trip":null},{"start":1620165600,"end":1620252000,"weather":null,"album":{"id":1013,"name":"Praha 5.5.2021","mainImageUrl":"https://lriedel.cz/cache/album/AGhjs2n_umlT19ZLxKLORi47ssCipF4h51JgqWh7kRmQQitWkJWLjaP37LKL82zkD_jCFqXu-u0glceRKFUuRz7XM8xB5v9S4Q.jpg","permalink":"https://photos.google.com/lr/album/AGhjs2nTEScLN3hO1pvYe4mngtsDbarRNJ-RNNXiHR9NIw1wYmzOoQq81j9QbCyYnTUe44iF2QkE","imagesCount":94,"indoorImagesCount":1,"isEmpty":false,"isMainForPlace":false,"isMainForCountry":false,"isMainForTrip":false,"isLowQuality":true,"isBadWeather":false},"trip":null},{"start":1620597600,"end":1620684000,"weather":null,"album":{"id":1011,"name":"Praha 10.5.2021","mainImageUrl":"https://lriedel.cz/cache/album/AGhjs2lN9t5qTadr7ntuZBUckNbdo5sTxTmgB98ItJA9H7Iyj3_ygk5s5ruq2xGIRlqfUO-LbvHxSpm52WkRdgyg-IzSf8EXaw.jpg","permalink":"https://photos.google.com/lr/album/AGhjs2ln5sUPDa_4x_XF7meTjD6B2FT_R_lMpYuobhVumycqtEE-fD2Naocqc9d0JAWp3AfdxNGQ","imagesCount":74,"indoorImagesCount":0,"isEmpty":false,"isMainForPlace":false,"isMainForCountry":false,"isMainForTrip":false,"isLowQuality":true,"isBadWeather":false},"trip":null},{"start":1625004000,"end":1625090400,"weather":null,"album":{"id":993,"name":"Praha 30.6.2021","mainImageUrl":"https://lriedel.cz/cache/album/AGhjs2nzAssLZVrz--TBs2VyPAvCk_57oLB50gZrOlZ3_0ZoWNqtnKH4v7olNWj3VUV_euSGOL9sqaBb_qoy4hnA_zhGVLTUQw.jpg","permalink":"https://photos.google.com/lr/album/AGhjs2n55neh5x18ar9qxs4S8gOH_usXgV8-ZctcmSlfWQ6WPWv5SdQqWKMeAGqLhoPt_fmJSLBh","imagesCount":32,"indoorImagesCount":0,"isEmpty":false,"isMainForPlace":false,"isMainForCountry":false,"isMainForTrip":false,"isLowQuality":true,"isBadWeather":false},"trip":null},{"start":1683842400,"end":1683928800,"weather":null,"album":{"id":518,"name":"Praha 12.5.2023","mainImageUrl":"https://lriedel.cz/cache/album/AGhjs2mfenG2ziocmnjPXfyZz0K9XZs9SwzyTHm9ma3fxgsTShx0X7Sa3lNQSAieBVfgGkPYaFacBrQlY0rknL3R0-dTTZTIUw.jpg","permalink":"https://photos.google.com/lr/album/AGhjs2kb8-pVTQjFkAmujz1aEBOovnZ6OAxZztK2pMN8bhRW9QEBm1OA6tY3sOywcjiDb-B9libS","imagesCount":77,"indoorImagesCount":0,"isEmpty":false,"isMainForPlace":false,"isMainForCountry":false,"isMainForTrip":false,"isLowQuality":false,"isBadWeather":false},"trip":null},{"start":1684706400,"end":1684792800,"weather":null,"album":{"id":517,"name":"Praha 22.5.2023","mainImageUrl":"https://lriedel.cz/cache/album/AGhjs2mamJ0FwSdq_MK-CHQS2gRt9CqLz_Xw8cEdW1bI8qhDZjOzbc-4WmAYlawqOlHrAX7LHmHCvZLoyb_Z4xY7D6WNG8ctxg.jpg","permalink":"https://photos.google.com/lr/album/AGhjs2mBfXVUOCq9mTxGmOczInoQfjzT9duwOghrBN9usfZR3X1HTpYyED9bFHkA8sQYiC1wy0Yq","imagesCount":23,"indoorImagesCount":0,"isEmpty":false,"isMainForPlace":false,"isMainForCountry":false,"isMainForTrip":false,"isLowQuality":false,"isBadWeather":false},"trip":null},{"start":1685397600,"end":1685484000,"weather":null,"album":{"id":500,"name":"Praha 30.5.2023","mainImageUrl":"https://lriedel.cz/cache/album/AGhjs2mYWhPE88f_OsVL8zAH0gorkTarGzL0W6AkVEZ82hHDDzs-F2OI2SSYbR-VUx-gaj10xdj5CMiGYSh2eQAwdoSQrIUOjw.jpg","permalink":"https://photos.google.com/lr/album/AGhjs2lMFlBK9319veyH5GZgvfeDdMBEgzKXAdIQJHXM4aHbr9kV7u7xXPuQewDsr9x_XFG2KwW4","imagesCount":76,"indoorImagesCount":0,"isEmpty":false,"isMainForPlace":false,"isMainForCountry":false,"isMainForTrip":false,"isLowQuality":false,"isBadWeather":false},"trip":null},{"start":1692741600,"end":1692828000,"weather":null,"album":{"id":401,"name":"Praha 23.8.2023","mainImageUrl":"https://lriedel.cz/cache/album/AGhjs2nqM7MqQXG1vjmMZVWfs9ghZ0o8_eZTQIEPYFynJ_HFc5IB54-2zK4FROxRVoDAq5_s2Exq0YX0QWqhezCLKd3JJumerg.jpg","permalink":"https://photos.google.com/lr/album/AGhjs2khivVqks5cWTvgxL4X4mC93rew7KgyVdV7g19wZ4an0u_3Y7afQhJgVLqwQiwEOLKoNNiW","imagesCount":206,"indoorImagesCount":0,"isEmpty":false,"isMainForPlace":true,"isMainForCountry":true,"isMainForTrip":false,"isLowQuality":false,"isBadWeather":false},"trip":null},{"start":1710889200,"end":1710975600,"weather":null,"album":{"id":199,"name":"Praha 20.3.2024","mainImageUrl":"https://lriedel.cz/cache/album/AGhjs2nOV-y9fEdoIFbAdy7rQ4TvkPzrI8qI67mCVCoOBvJp4OQkGRZYTmxXmXgDqnXkmydx1IlakeKGXx9uERGmu8LEp7i5PA.jpg","permalink":"https://photos.google.com/lr/album/AGhjs2mOnjGRlFafK-lxtew5xAuQzN3NjDP5e1ETbOdydT4ZTUa4c3d3vhshJJAHJ4mAAXfaZPE-","imagesCount":45,"indoorImagesCount":0,"isEmpty":false,"isMainForPlace":false,"isMainForCountry":false,"isMainForTrip":false,"isLowQuality":false,"isBadWeather":false},"trip":null},{"start":1717970400,"end":1718056800,"weather":null,"album":{"id":72,"name":"Praha 10.6.2024","mainImageUrl":"https://lriedel.cz/cache/album/AGhjs2moZP4-AczLYm5GMk24p-gXNmuvZA548TrLPNmaK-gWpNdERJXaj9DYwUuENEEnkZ9uIX-s2Zkq7_nvkUedSXZgkOknSw.jpg","permalink":"https://photos.google.com/lr/album/AGhjs2khXUUOC8bnr0zZxVtZGmxxnm1RaM_eHQOBrBNHksO44QDu50p7I3_bjwO1-ZT-FwxdhHrl","imagesCount":161,"indoorImagesCount":19,"isEmpty":false,"isMainForPlace":false,"isMainForCountry":false,"isMainForTrip":false,"isLowQuality":false,"isBadWeather":false},"trip":null}],"imagesCount":1014,"imagesScore":382}'),
                $this->create400ResponseExample(),
                $this->create401ResponseExample(),
                $this->create404ResponseExample());
        }
    }
?>