<?php    
    namespace Service\Service\Trip;

    use Service\Service\Highlight\Highlight;

    class Trip implements \JsonSerializable {        
        private readonly string $id;
        private readonly string $name;
        private readonly ?int $year;
        private readonly ?Highlight $mainHighlight;
        private readonly ?int $start;
        private readonly ?int $end;
        private readonly array $countries;
        private readonly ?float $cost;
        private readonly TripDays $days;
        private readonly ?TripVacation $vacation;
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
            array $countries, ?float $cost, int $totalDays, ?int $workingDays, ?float $expectedVacation, ?float $maximumVacation,
            array $expenses, array $stays, array $flights, array $watchedFlights, array $fitness, array $notes, array $highlights,
            array $statistics, array $publicHolidays) {
            $this->id = $id;
            $this->name = $name;
            $this->year = $year;
            $this->mainHighlight = $mainHighlight;
            $this->start = $start;
            $this->end = $end;
            $this->countries = $countries;
            $this->cost = $cost;
            $this->days = new TripDays($totalDays, $workingDays);
            $this->vacation = ($expectedVacation === NULL && $maximumVacation === NULL) ? NULL : new TripVacation($expectedVacation, $maximumVacation); 
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

        public function getCost() : float {
            return $this->cost;
        }

        public function getDays() : TripDays {
            return $this->days;
        }

        public function getVacation() : ?TripVacation {
            return $this->vacation;
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
            return new Trip($this->id, $this->name, $this->year, $this->mainHighlight, $this->start + $offset, $this->end + $offset, $this->countries, $this->cost,
                $this->days->getTotal(), $this->days->getWorking(), $this->vacation === NULL ? NULL : $this->vacation->getExpected(), 
                $this->vacation === NULL ? NULL : $this->vacation->getMaximum(), $this->expenses, $this->stays, $this->flights, 
                $this->watchedFlights, $this->fitness, $this->notes, $this->highlights, $this->statistics, $this->publicHolidays);
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>