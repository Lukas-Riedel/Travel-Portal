<?php
    namespace Core\Client\Flight;

    interface FlightClient {
        public function fetchFlight(string $flight, int $scheduledDeparture) : FetchedFlight;
    }
?>