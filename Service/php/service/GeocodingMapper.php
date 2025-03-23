<?php
    class GeocodingMapper {

        private readonly DatabaseProvider $databaseProvider;

        public function __construct(DatabaseProvider $databaseProvider) {
            $this->databaseProvider = $databaseProvider;
        }

        public function selectLocation(string $address) : ?Location {
            $sql = <<<'SQL'
                SELECT country,
                    latitude,
                    longitude,
                    timezone
                FROM cache_location
                WHERE address = ?
                ORDER BY last_access DESC
            SQL;

            $locationRow = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($address)
                ->getFirstRow();

            if ($locationRow === NULL) {
                return NULL;
            }

            $sql = <<<'SQL'
                UPDATE cache_location
                SET last_access = UNIX_TIMESTAMP()
                WHERE address = ?
            SQL;
            
            $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($address)
                ->execute();

            return new Location($locationRow["country"], $locationRow["latitude"], $locationRow["longitude"], $locationRow["timezone"]);
        }

        public function insertLocation(Location $location, string $address) : bool {
            $sql = <<<'SQL'
                INSERT INTO cache_location (
                    address, 
                    country, 
                    timezone, 
                    latitude, 
                    longitude, 
                    last_access
                )
                VALUES (
                    ?, 
                    ?, 
                    ?, 
                    ?, 
                    ?, 
                    UNIX_TIMESTAMP()
                )
            SQL;
            
            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($address, $location->getCountry(), $location->getTimezone(), $location->getLatitude(), $location->getLongitude())
                ->execute() === 1;
        }
    }
?>