<?php
    namespace Core\Service\Label;

    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\WhereClauseBuilder;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Service\Highlight\HighlightService;

    class LabelMapper {
        
        private readonly DatabaseClient $databaseClient;
        private readonly ConfigurationService $configurationService;
        private readonly HighlightService $highlightService;

        public function __construct(DatabaseClient $databaseClient, ConfigurationService $configurationService, HighlightService $highlightService) {
            $this->databaseClient = $databaseClient;
            $this->configurationService = $configurationService;
            $this->highlightService = $highlightService;
        }

        public function selectLabels(?string $labelId, ?string $placeId, array $includedEntities) : array {
            $sql = <<<'SQL'
                SELECT li.*
                FROM label_identifier li
                WHERE :CONDITIONS
                ORDER BY li.name
            SQL;

            $whereClauseBuilder = new WhereClauseBuilder();
            if ($labelId !== null) {
                $whereClauseBuilder->withClause("li.id = ?", $labelId);
            }
            if ($placeId !== null) {
                $whereClauseBuilder->withClause("EXISTS (SELECT 1 FROM label l WHERE l.label_id = li.id AND l.place_id = ?)", $placeId);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            $labelRows = $this->databaseClient
                ->statementBuilder($sql, $whereClause)
                ->getResultSet();

            $mainHighlightIds = array_filter(array_map(fn($labelRow) => $labelRow["main_highlight_id"], $labelRows), fn($highlightId) => $highlightId !== null);

            $mainHighlights = array();
            foreach ($this->highlightService->getHighlights($mainHighlightIds) as &$mainHighlight) {
                $mainHighlights[$mainHighlight->getId()] = $mainHighlight;
            }

            $labels = array();
            foreach ($labelRows as &$labelRow) {
                $highlights = array();
                if (in_array(LabelIncludedEntity::Highlights->value, $includedEntities)) {
                    $highlights = $this->highlightService->getLabelHighlights($labelRow["id"]);
                }

                $metadata = $labelRow["unicode"] === null ? null : new LabelMetadata($labelRow["unicode"]);
                $labels[] = new Label($labelRow["id"], $labelRow["name"], $metadata,
                    $mainHighlights[$labelRow["main_highlight_id"]] ?? null, $highlights);
            }

            return $labels;
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

        public function insertLabelId(string $labelName) : ?string {
            $sql = <<<'SQL'
                INSERT INTO label_identifier (
                    name
                )
                VALUES (
                    ?
                )
                ON CONFLICT DO NOTHING
                RETURNING id
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($labelName)
                ->getSingleColumn("id");
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

        public function updateLabelUnicode(string $labelId, string $unicode) : bool {
            $sql = <<<'SQL'
                UPDATE label_identifier
                SET unicode = ?
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($unicode, $labelId)
                ->execute() === 1;
        }


        public function updateLabelMainHighlight(string $labelId, ?string $highlightIdentifier) : bool {
            $sql = <<<'SQL'
                UPDATE label_identifier
                SET main_highlight_id = ?
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($highlightIdentifier, $labelId)
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

            $whereClauseBuilder = (new WhereClauseBuilder())
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