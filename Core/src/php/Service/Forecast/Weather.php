<?php
    namespace Core\Service\Forecast;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Weather",
        type: "object",
        description: "A class representing a weather forecast",
        required: ["temperature", "wind", "precipitation", "lastUpdate"],
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
                description: "The forecasted clouds coverage percentage",
                type: "number",
                format: "float",
                example: 13
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
                description: "The forecasted precipitation in millimeters",
                type: "number",
                format: "float",
                example: 0.2
            ),
            new OA\Property(
                property: "symbol",
                description: "The symbol of the weather forecast",
                type: "string",
                example: "clearsky"
            ),
            new OA\Property(
                property: "lastUpdate",
                description: "The last update time of the weather forecast in epoch seconds",
                type: "integer",
                format: "int64",
                example: 1689786000
            )
        ]
    )]
    class Weather implements \JsonSerializable {        
        private readonly float $temperature;
        private readonly ?float $clouds;
        private readonly float $wind;
        private readonly float $precipitation;
        private readonly ?string $symbol;
        private readonly int $lastUpdate;

        public function __construct(float $temperature, ?float $clouds, float $wind,
            float $precipitation, ?string $symbol, int $lastUpdate) {
            $this->temperature = $temperature;
            $this->clouds = $clouds;
            $this->wind = $wind;
            $this->precipitation = $precipitation;
            $this->symbol = $symbol;
            $this->lastUpdate = $lastUpdate;
        }

        public function getTemperature() : float {
            return $this->temperature;
        }

        public function getClouds() : ?float {
            return $this->clouds;
        }

        public function getWind() : float {
            return $this->wind;
        }

        public function getPrecipitation() : float {
            return $this->precipitation;
        }

        public function getSymbol() : ?string {
            return $this->symbol;
        }

        public function getLastUpdate() : int {
            return $this->lastUpdate;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>