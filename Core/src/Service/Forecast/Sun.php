<?php
    namespace Core\Service\Forecast;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Sun",
        type: "object",
        description: "A class representing a sun position",
        required: ["temperature", "wind", "precipitation", "lastUpdate"],
        properties: [
            new OA\Property(
                property: "sunrise",
                description: "The sunrise time in epoch seconds",
                type: "integer",
                format: "int64",
                example: 1720358685
            ),
            new OA\Property(
                property: "sunset",
                description: "The sunset time in epoch seconds",
                type: "integer",
                format: "int64",
                example: 1720423994
            ),
            new OA\Property(
                property: "altitude",
                description: "The altitude of the sun for the measured interval",
                ref: "#/components/schemas/Interval"
            ),
            new OA\Property(
                property: "azimuth",
                description: "The azimuth of the sun for the measured interval",
                ref: "#/components/schemas/Interval"
            )
        ]
    )]
    class Sun implements \JsonSerializable {
        
        private readonly int $sunrise;
        private readonly int $sunset;
        private readonly Interval $altitude;
        private readonly Interval $azimuth;

        public function __construct(int $sunrise, int $sunset, float $startAltitude,
            float $endAltitude, float $startAzimuth, float $endAzimuth) {
            $this->sunrise = $sunrise;
            $this->sunset = $sunset;
            $this->altitude = new Interval($startAltitude, $endAltitude);
            $this->azimuth = new Interval($startAzimuth, $endAzimuth);
        }

        public function getSunrise() : int {
            return $this->sunrise;
        }

        public function getSunset() : int {
            return $this->sunset;
        }

        public function getAltitude() : Interval {
            return $this->altitude;
        }

        public function getAzimuth() : Interval {
            return $this->azimuth;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>