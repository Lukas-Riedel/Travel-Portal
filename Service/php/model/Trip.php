<?php
    require_once(dirname(__FILE__) . "/Highlight.php");
    require_once(dirname(__FILE__) . "/TripDays.php");
    require_once(dirname(__FILE__) . "/TripVacation.php");

    class Trip implements JsonSerializable {        
        private $id;
        private $name;
        private $year;
        private $mainHighlight;
        private $start;
        private $end;
        private $countries;
        private $cost;
        private $days;
        private $vacation;
        private $expenses;
        private $stays;
        private $flights;
        private $watchedFlights;
        private $layovers;
        private $fitness;
        private $notes;
        private $highlights;
        private $statistics;
        private $publicHolidays;

        public function __construct($id, $name, $year, $mainHighlight, $start, $end, $countries, $cost, $totalDays, $workingDays,
            $expectedVacation, $maximumVacation, $expenses, $stays, $flights, $watchedFlights, $layovers, $fitness, $notes, $highlights, $statistics, $publicHolidays) {
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
            $this->layovers = $layovers;
            $this->fitness = $fitness;
            $this->notes = $notes;
            $this->highlights = $highlights;
            $this->statistics = $statistics;
            $this->publicHolidays = $publicHolidays;
        }

        public function getId() : int {
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

        public function getLayovers() : array {
            return $this->layovers;
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
                $this->watchedFlights, $this->layovers, $this->fitness, $this->notes, $this->highlights, $this->statistics, $this->publicHolidays);
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>