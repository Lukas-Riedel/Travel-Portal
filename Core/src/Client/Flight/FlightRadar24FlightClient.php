<?php
    namespace Core\Client\Flight;

    use Common\Client\Http\HttpClient;
    use Common\Client\Http\HttpMethod;
    use Core\Common\CommonConstants;

    class FlightRadar24FlightClient implements FlightClient {
        
        private const UTC_TIMEZONE = "UTC";
        private const UNKNOWN_FLIGHT_STATUS = "Unknown";

        private const GET_FLIGHT_API_ENDPOINT_FORMAT = "https://api.flightradar24.com/common/v1/flight/list.json?&fetchBy=flight&page=1&limit=20&query=%s";

        private readonly HttpClient $httpClient;

        public function __construct(HttpClient $httpClient) {
            $this->httpClient = $httpClient;
        }

        public function fetchFlight(string $flight, int $scheduledDeparture) : FetchedFlight {
            date_default_timezone_set(self::UTC_TIMEZONE);
            $rawApiResponse = $this->httpClient->executeRequest(HttpMethod::GET, sprintf(self::GET_FLIGHT_API_ENDPOINT_FORMAT, $flight));
            $apiResponse = is_array($rawApiResponse) ? $rawApiResponse : json_decode($rawApiResponse, true);
            if ($apiResponse === null || !isset($apiResponse["result"]["response"]["data"])) {
                throw new \RuntimeException("Unable to read data. Response: " . $rawApiResponse);
            }

            $selectedFlight = null;
            foreach ($apiResponse["result"]["response"]["data"] as &$fetchedFlight) {
                if (($fetchedFlight["time"]["scheduled"]["departure"] - CommonConstants::ONE_HOUR_SECONDS <= $scheduledDeparture)
                    && ($fetchedFlight["time"]["scheduled"]["departure"] + CommonConstants::ONE_HOUR_SECONDS >= $scheduledDeparture)
                    && ($fetchedFlight["status"]["text"]) !== self::UNKNOWN_FLIGHT_STATUS) {
                    $selectedFlight = $fetchedFlight;
                    break;
                }
            }

            if ($selectedFlight === null) {
                throw new \RuntimeException("Cannot fetch the flight $flight departing at $scheduledDeparture. Is the departure time correct?");
            }

            return new FetchedFlight($flight, $selectedFlight["aircraft"]["registration"], $selectedFlight["aircraft"]["model"]["code"],
                $selectedFlight["airport"]["origin"]["code"]["iata"], $selectedFlight["airport"]["destination"]["code"]["iata"],
                $selectedFlight["time"]["scheduled"]["departure"], $selectedFlight["time"]["estimated"]["departure"], $selectedFlight["time"]["real"]["departure"],
                $selectedFlight["time"]["scheduled"]["arrival"], $selectedFlight["time"]["estimated"]["arrival"], $selectedFlight["time"]["real"]["arrival"]);
        }
    }
?>