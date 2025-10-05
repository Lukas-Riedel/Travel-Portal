<?php
    namespace Core\Service\Place;

    use Core\Service\Forecast\Weather;
    use Core\Service\Forecast\Sun;
    use Core\Service\Photo\Album;
    use Core\Service\Trip\TripIdentifier;    
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Date",
        type: "object",
        description: "A class representing a date",
        required: ["start", "end", "layover"],
        properties: [
            new OA\Property(
                property: "start",
                description: "The start time of the date in epoch seconds",
                type: "integer",
                format: "int64",
                example: 1688563200
            ),
            new OA\Property(
                property: "end",
                description: "The end time of the date in epoch seconds",
                type: "integer",
                format: "int64",
                example: 1689786000
            ),
            new OA\Property(
                property: "weather",
                description: "The weather forecast for the date",
                ref: "#/components/schemas/Weather"
            ),
            new OA\Property(
                property: "sun",
                description: "The sun position for the date",
                ref: "#/components/schemas/Sun"
            ),
            new OA\Property(
                property: "album",
                description: "The album for the date",
                ref: "#/components/schemas/Album"
            ),
            new OA\Property(
                property: "trip",
                description: "The trip of the date",
                ref: "#/components/schemas/TripIdentifier"
            )
        ]
    )]
    class Date implements \JsonSerializable {        
        private readonly int $start;
        private readonly int $end;
        private readonly bool $layover;
        private readonly ?Weather $weather;
        private readonly ?Sun $sun;
        private readonly ?Album $album;
        private readonly ?TripIdentifier $trip;

        public function __construct(int $start, int $end, bool $layover, ?Weather $weather,
            ?Sun $sun, ?Album $album, ?TripIdentifier $trip) {
            $this->start = $start;
            $this->end = $end;
            $this->layover = $layover;
            $this->weather = $weather;
            $this->sun = $sun;
            $this->album = $album;
            $this->trip = $trip;
        }

        public function getStart() : int {
            return $this->start;
        }

        public function getEnd() : int {
            return $this->end;
        }

        public function isLayover() : bool {
            return $this->layover;
        }

        public function getWeather() : ?Weather {
            return $this->weather;
        }

        public function getSun() : ?Sun {
            return $this->sun;
        }

        public function getAlbum() : ?Album {
            return $this->album;
        }

        public function getTrip() : ?TripIdentifier {
            return $this->trip;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>