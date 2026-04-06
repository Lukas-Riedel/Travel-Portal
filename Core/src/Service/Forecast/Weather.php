<?php
    namespace Core\Service\Forecast;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Weather",
        type: "object",
        description: "A class representing a weather forecast",
        required: ["temperature", "wind", "precipitation", "lastUpdate", "validity"],
        properties: [
            new OA\Property(
                property: "temperature",
                description: "The forecasted temperature in degrees Celsius",
                type: "number",
                format: "float",
                example: 25.6
            ),
            new OA\Property(
                property: "clouds",
                description: "The forecasted clouds coverage",
                ref: "#/components/schemas/Clouds"
            ),
            new OA\Property(
                property: "wind",
                description: "The forecasted wind speed in meters per second",
                type: "number",
                format: "float",
                example: 13.2
            ),
            new OA\Property(
                property: "precipitation",
                description: "The forecasted precipitation",
                ref: "#/components/schemas/Precipitation"
            ),
            new OA\Property(
                property: "humidity",
                description: "The forecasted humidity percentage",
                type: "number",
                format: "float",
                example: 65
            ),
            new OA\Property(
                property: "lastUpdate",
                description: "The last update time of the weather forecast in epoch seconds",
                type: "integer",
                format: "int64",
                example: 1689786000
            ),
            new OA\Property(
                property: "validity",
                description: "The validity time of the weather forecast in epoch seconds",
                type: "integer",
                format: "int64",
                example: 1689786000
            )
        ]
    )]
    class Weather implements \JsonSerializable {
                
        private readonly float $temperature;
        private readonly ?Clouds $clouds;
        private readonly float $wind;
        private readonly Precipitation $precipitation;
        private readonly ?float $humidity;
        private readonly int $lastUpdate;
        private readonly int $validity;

        public function __construct(float $temperature, ?Clouds $clouds, float $wind,
            Precipitation $precipitation, ?float $humidity, int $lastUpdate, int $validity) {
            $this->temperature = $temperature;
            $this->clouds = $clouds;
            $this->wind = $wind;
            $this->precipitation = $precipitation;
            $this->humidity = $humidity;
            $this->lastUpdate = $lastUpdate;
            $this->validity = $validity;
        }

        public function getTemperature() : float {
            return $this->temperature;
        }

        public function getClouds() : ?Clouds {
            return $this->clouds;
        }

        public function getWind() : float {
            return $this->wind;
        }

        public function getPrecipitation() : Precipitation {
            return $this->precipitation;
        }

        public function getHumidity() : ?float {
            return $this->humidity;
        }

        public function getLastUpdate() : int {
            return $this->lastUpdate;
        }

        public function getValidity() : int {
            return $this->validity;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>