<?php
    class Trip implements JsonSerializable {        
        private $id;
        private $name;
        private $year;
        private $start;
        private $end;
        private $countries;
        private $imageUrl;
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
        private $stats;
        private $publicHolidays;

        public function __construct($id, $name, $year, $start, $end, $countries, $imageUrl, $cost, $totalDays, $workingDays,
            $expectedVacation, $maximumVacation, $expenses, $stays, $flights, $watchedFlights, $layovers, $fitness, $notes, $stats, $publicHolidays) {
            $this->id = $id;
            $this->name = $name;
            $this->year = $year;
            $this->start = $start;
            $this->end = $end;
            $this->countries = $countries;
            $this->imageUrl = $imageUrl;
            $this->cost = $cost;
            $this->days = array("total" => $totalDays, "working" => $workingDays);
            $this->vacation = ($expectedVacation === NULL && $maximumVacation === NULL) ? NULL : array("expected" => $expectedVacation, "maximum" => $maximumVacation);
            $this->expenses = $expenses;
            $this->stays = $stays;
            $this->flights = $flights;
            $this->watchedFlights = $watchedFlights;
            $this->layovers = $layovers;
            $this->fitness = $fitness;
            $this->notes = $notes;
            $this->stats = $stats;
            $this->publicHolidays = $publicHolidays;
        }

        public function getId() {
            return $this->id;
        }

        public function getName() {
            return $this->name;
        }

        public function getYear() {
            return $this->year;
        }

        public function getStart() {
            return $this->start;
        }

        public function getEnd() {
            return $this->end;
        }

        public function getCountries() {
            return $this->countries;
        }

        public function getImageUrl() {
            return $this->imageUrl;
        }

        public function getCost() {
            return $this->cost;
        }

        public function getDays() {
            return $this->days;
        }

        public function getVacation() {
            return $this->vacation;
        }

        public function getExpenses() {
            return $this->expenses;
        }

        public function getStays() {
            return $this->stays;
        }

        public function getFlights() {
            return $this->flights;
        }

        public function getWatchedFlights() {
            return $this->watchedFlights;
        }

        public function getLayovers() {
            return $this->layovers;
        }

        public function getFitness() {
            return $this->fitness;
        }

        public function getNotes() {
            return $this->notes;
        }

        public function getStats() {
            return $this->stats;
        }

        public function getPublicHolidays() {
            return $this->publicHolidays;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() {
            return get_object_vars($this);
        }
    }
?>