<?php
    namespace Core\Service\Fitness;

    use Core\Service\Trip\Trip;
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "TripFitness",
        type: "object",
        description: "A class representing fitness data associated with a trip",
        required: ["trip", "fitness"],
        properties: [
            new OA\Property(
                property: "trip",
                ref: "#/components/schemas/Trip",
                description: "The trip associated with the fitness data"
            ),
            new OA\Property(
                property: "fitness",
                ref: "#/components/schemas/Fitness",
                description: "The fitness record associated with the trip"
            )
        ]
    )]
    class TripFitness implements \JsonSerializable {
        private readonly Trip $trip;
        private readonly Fitness $fitness;

        public function __construct(Trip $trip, Fitness $fitness) {
            $this->trip = $trip;
            $this->fitness = $fitness;
        }

        public function getTrip() : Trip {
            return $this->trip;
        }

        public function getFitness() : Fitness {
            return $this->fitness;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>