<?php
    namespace Core\Service\Label;

    use Core\Service\Configuration\ConfigurationService;
    use Core\Client\Database\DatabaseClient;

    class LabelMapper {
        
        private readonly DatabaseClient $databaseClient;
        private readonly ConfigurationService $configurationService;

        public function __construct(DatabaseClient $databaseClient, ConfigurationService $configurationService) {
            $this->databaseClient = $databaseClient;
            $this->configurationService = $configurationService;
        }

        public function selectLabelsForPlace(string $placeId) : array {
            $sql = <<<'SQL'
                SELECT li.id, li.name
                FROM label l
                INNER JOIN label_identifier li
                    ON l.label_id = li.id
                WHERE l.place_id = ?
                ORDER BY li.name ASC
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($placeId)
                ->getMappedResultSet(function($labelRow) {
                    return new Label($labelRow["id"], $labelRow["name"]);
                });
        }

        public function selectAllLabels() : array {
            $sql = <<<'SQL'
                SELECT *
                FROM label_identifier
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->getMappedResultSet(function($labelRow) {
                    return new Label($labelRow["id"], $labelRow["name"]);
                });
        }

        public function selectLabel(string $labelId) : ?Label {
            $sql = <<<'SQL'
                SELECT *
                FROM label_identifier
                WHERE id = ?
            SQL;

            $labelRow = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($labelId)
                ->getSingleRow();

            if ($labelRow === null) {
                return null;
            }
            
            return new Label($labelRow["id"], $labelRow["name"]);
        }

        public function selectPlaceIdsForLabelId(string $labelId) : array {            
            $sql = <<<'SQL'
                SELECT place_id
                FROM label
                WHERE label_id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($labelId)
                ->getResultSetForColumn("place_id");
        }

        public function selectLabelId(string $labelName) : ?string {
            $sql = <<<'SQL'
                SELECT id
                FROM label_identifier
                WHERE name = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($labelName)
                ->getSingleColumn("id");
        }

        public function insertLabel(string $placeId, string $labelId) : bool {
            $sql = <<<'SQL'
                INSERT INTO label (
                    place_id,
                    label_id
                )
                VALUES (
                    ?,
                    ?
                )
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($placeId, $labelId)
                ->execute() === 1;
        }

        public function insertLabelId(string $labelName) : bool {    
            $sql = <<<'SQL'
                INSERT INTO label_identifier (
                    name
                )
                VALUES (
                    ?
                )
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($labelName)
                ->execute() === 1;
        }
        

        public function updateLabelName(string $labelId, string $name) : bool {
            $sql = <<<'SQL'
                UPDATE label_identifier
                SET name = ?
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($name, $labelId)
                ->execute() === 1;
        }

        public function deleteLabelForPlace(string $placeId, string $labelId) : int {
            $sql = <<<'SQL'
                DELETE
                FROM label
                WHERE place_id = ?
                    AND label_id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($placeId, $labelId)
                ->execute();
        }

        public function deleteLabelForAllPlaces(string $labelId) : int {
            $sql = <<<'SQL'
                DELETE
                FROM label
                WHERE label_id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($labelId)
                ->execute();
        }

        public function deleteStaleLabelIdentifiers() : int {
            $sql = <<<'SQL'
                DELETE
                FROM label_identifier li
                WHERE :CONDITIONS
            SQL;

            $whereClauseBuilder = $this->databaseClient->whereClauseBuilder()
                ->withClause("NOT EXISTS (SELECT 1 FROM label l WHERE l.label_id = li.id)");

            $dynamicLabelNames = array_map(fn($dynamicLabel) => $dynamicLabel["name"], 
                $this->configurationService->getConfigurationEntry("dynamicLabels"));
            if (count($dynamicLabelNames) > 0) {
                $whereClauseBuilder->withClause("name NOT IN (" . $this->databaseClient->getPlaceholdersSequence(count($dynamicLabelNames)) . ")", ...$dynamicLabelNames);
            }

            $whereClause = $whereClauseBuilder->buildForAnd();
            
            return $this->databaseClient
                ->statementBuilder($sql, $whereClause)
                ->execute();
        }
    }
?>