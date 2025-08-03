<?php
    namespace Service\Service\Flight;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Flight",
        type: "object",
        description: "A class representing a flight",
        required: ["flight", "from", "to", "start", "end"],
        properties: [
            new OA\Property(
                property: "flight",
                description: "The number of the flight",
                type: "string",
                example: "EK139"
            ),
            new OA\Property(
                property: "registration",
                description: "The registration of the aircraft",
                type: "string",
                example: "A6-EOQ"
            ),
            new OA\Property(
                property: "aircraft",
                description: "The type of the aircraft",
                type: "string",
                example: "A388"
            ),
            new OA\Property(
                property: "airline",
                description: "The identifier of the airline",
                ref: "#/components/schemas/AirlineIdentifier"
            ),
            new OA\Property(
                property: "distance",
                description: "The distance of the flight in kilometers",
                type: "number",
                format: "float",
                example: 1732.5
            ),
            new OA\Property(
                property: "from",
                description: "The departure airport of the flight",
                ref: "#/components/schemas/Airport"
            ),
            new OA\Property(
                property: "to",
                description: "The arrival airport of the flight",
                ref: "#/components/schemas/Airport"
            ),
            new OA\Property(
                property: "start",
                description: "The departure time of the flight in epoch seconds",
                type: "integer",
                format: "int64",
                example: 1688563200
            ),
            new OA\Property(
                property: "end",
                description: "The arrival time of the flight in epoch seconds",
                type: "integer",
                format: "int64",
                example: 1688570400
            ),
            new OA\Property(
                property: "delay",
                description: "The delay of the flight in seconds",
                type: "integer",
                example: 153
            )
        ]
    )]
    class Flight implements \JsonSerializable {        
        private readonly string $flight;
        private readonly ?string $registration;
        private readonly ?string $aircraft;
        private readonly ?AirlineIdentifier $airline;
        private readonly ?float $distance;
        private readonly Airport $from;
        private readonly Airport $to;
        private readonly int $start;
        private readonly int $end;
        private readonly ?int $delay;

        public function __construct(string $flight, ?string $registration, ?string $aircraft, ?AirlineIdentifier $airline,
            ?float $distance, Airport $from, Airport $to, int $start, int $end, ?int $delay) {
            $this->flight = $flight;
            $this->registration = $registration;
            $this->aircraft = $aircraft;
            $this->airline = $airline;
            $this->distance = $distance;
            $this->from = $from;
            $this->to = $to;
            $this->start = $start;
            $this->end = $end;
            $this->delay = $delay;
        }

        public function getFlight() : string {
            return $this->flight;
        }

        public function getRegistration() : string {
            return $this->registration;
        }

        public function getAircraft() : string {
            return $this->aircraft;
        }

        public function getAirline() : ?AirlineIdentifier {
            return $this->airline;
        }

        public function getDistance() : float {
            return $this->distance;
        }

        public function getFrom() : Airport {
            return $this->from;
        }

        public function getTo() : Airport {
            return $this->to;
        }

        public function getStart() : int {
            return $this->start;
        }

        public function getEnd() : int {
            return $this->end;
        }

        public function getDelay() : ?int {
            return $this->delay;
        }

        public function getDuration() : int {
            return $this->end - $this->start;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>