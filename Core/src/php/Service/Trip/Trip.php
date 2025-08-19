<?php    
    namespace Core\Service\Trip;

    use Core\Service\Highlight\Highlight;
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Trip",
        type: "object",
        description: "A class representing a trip",
        required: ["id", "name"],
        properties: [
            new OA\Property(
                property: "id",
                description: "The identifier of the trip",
                type: "string",
                example: "26135e57-fe89-4a38-82d4-5e0ad0485e28"
            ),
            new OA\Property(
                property: "name",
                description: "The name of the trip",
                type: "string",
                example: "One Thousand Scents of Sri Lanka"
            ),
            new OA\Property(
                property: "year",
                description: "The year of the trip",
                type: "integer",
                example: 2025
            ),
            new OA\Property(
                property: "mainHighlight",
                description: "The main highlight of the trip",
                ref: "#/components/schemas/Highlight"
            ),
            new OA\Property(
                property: "start",
                description: "The start time of the trip in epoch seconds",
                type: "integer",
                format: "int64",
                example: 1688563200
            ),
            new OA\Property(
                property: "end",
                description: "The end time of the trip in epoch seconds",
                type: "integer",
                format: "int64",
                example: 1689786000
            ),
            new OA\Property(
                property: "countries",
                description: "The country names visited in the trip",
                type: "array",
                items: new OA\Items(type: "string"),
                example: '["Sri Lanka"]'
            ),
            new OA\Property(
                property: "expenses",
                description: "The expenses of the trip",
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/Expense")
            ),
            new OA\Property(
                property: "stays",
                description: "The stays of the trip",
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/Stay")
            ),
            new OA\Property(
                property: "flights",
                description: "The flights of the trip",
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/Flight")
            ),
            new OA\Property(
                property: "watchedFlights",
                description: "The watched flights of the trip",
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/Flight")
            ),
            new OA\Property(
                property: "fitness",
                description: "The day fitness records of the trip",
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/Fitness")
            ),
            new OA\Property(
                property: "notes",
                description: "The notes of the trip",
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/Note")
            ),
            new OA\Property(
                property: "highlights",
                description: "The highlights of the trip",
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/Highlight")
            ),
            new OA\Property(
                property: "statistics",
                description: "The statistics of the trip",
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/Statistics")
            ),
            new OA\Property(
                property: "publicHolidays",
                description: "The public holidays of the trip",
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/PublicHoliday")
            )
        ]
    )]
    class Trip implements \JsonSerializable {          
        private const FULL_TRIP_NAME_FORMAT = "%s %d";

        private readonly string $id;
        private readonly string $name;
        private readonly ?int $year;
        private readonly ?Highlight $mainHighlight;
        private readonly ?int $start;
        private readonly ?int $end;
        private readonly array $countries;
        private readonly array $expenses;
        private readonly array $stays;
        private readonly array $flights;
        private readonly array $watchedFlights;
        private readonly array $fitness;
        private readonly array $notes;
        private readonly array $highlights;
        private readonly array $statistics;
        private readonly array $publicHolidays;

        public function __construct(string $id, string $name, ?int $year, ?Highlight $mainHighlight, ?int $start, ?int $end,
            array $countries, array $expenses, array $stays, array $flights, array $watchedFlights, array $fitness, array $notes,
            array $highlights, array $statistics, array $publicHolidays) {
            $this->id = $id;
            $this->name = $name;
            $this->year = $year;
            $this->mainHighlight = $mainHighlight;
            $this->start = $start;
            $this->end = $end;
            $this->countries = $countries;
            $this->expenses = $expenses;
            $this->stays = $stays;
            $this->flights = $flights;
            $this->watchedFlights = $watchedFlights;
            $this->fitness = $fitness;
            $this->notes = $notes;
            $this->highlights = $highlights;
            $this->statistics = $statistics;
            $this->publicHolidays = $publicHolidays;
        }

        public function getId() : string {
            return $this->id;
        }

        public function getName() : string {
            return $this->name;
        }

        public function getFullName() : string {
            return sprintf(self::FULL_TRIP_NAME_FORMAT, $this->name, $this->year);
        }

        public function getYear() : ?int {
            return $this->year;
        }

        public function getMainHighlight() : ?Highlight {
            return $this->mainHighlight;
        }

        public function getStart() : int {
            return $this->start;
        }

        public function getEnd() : int {
            return $this->end;
        }

        public function getCountries() : array {
            return $this->countries;
        }

        public function getDaysCount() : int {
            $startDate = (new \DateTime())->setTimestamp($this->start)->setTime(0, 0);
            $endDate = (new \DateTime())->setTimestamp($this->end)->setTime(0, 0);
            return $startDate->diff($endDate)->days + 1;
        }

        public function getExpenses() : array {
            return $this->expenses;
        }

        public function getStays() : array {
            return $this->stays;
        }

        public function getFlights() : array {
            return $this->flights;
        }

        public function getWatchedFlights() : array {
            return $this->watchedFlights;
        }

        public function getFitness() : array {
            return $this->fitness;
        }

        public function getNotes() : array {
            return $this->notes;
        }

        public function getHighlights() : array {
            return $this->highlights;
        }

        public function getStats() : array {
            return $this->statistics;
        }

        public function getPublicHolidays() : array {
            return $this->publicHolidays;
        }

        public function withOffset(int $offset) : Trip {
            return new Trip($this->id, $this->name, $this->year, $this->mainHighlight, $this->start + $offset, $this->end + $offset, $this->countries,
                $this->expenses, $this->stays, $this->flights, $this->watchedFlights, $this->fitness, $this->notes, $this->highlights, $this->statistics,
                $this->publicHolidays);
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>