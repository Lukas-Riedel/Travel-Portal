<?php
    namespace Core\Service\Label;

    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\WhereClauseBuilder;
    use Core\Service\Highlight\HighlightService;

    class LabelMapper {
        
        private readonly DatabaseClient $databaseClient;
        private readonly HighlightService $highlightService;

        private readonly float $minHighlightSimilarity;

        public function __construct(DatabaseClient $databaseClient, HighlightService $highlightService, float $minHighlightSimilarity) {
            $this->databaseClient = $databaseClient;
            $this->highlightService = $highlightService;
            $this->minHighlightSimilarity = $minHighlightSimilarity;
        }

        public function selectLabels(?string $labelId, array $includedEntities) : array {
            $sql = <<<'SQL'
                SELECT *
                FROM label_identifier
                WHERE :CONDITIONS
                ORDER BY name
            SQL;

            $whereClauseBuilder = new WhereClauseBuilder();
            if ($labelId !== null) {
                $whereClauseBuilder->withClause("id = ?", $labelId);
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

                $metadata = $labelRow["unicode"] === null && $labelRow["keywords"] === null ? null : new LabelMetadata($labelRow["unicode"], $labelRow["keywords"] === null ? null : json_decode($labelRow["keywords"], true));
                $labels[] = new Label($labelRow["id"], $labelRow["name"], $metadata,
                    $mainHighlights[$labelRow["main_highlight_id"]] ?? null, $highlights);
            }

            return $labels;
        }
        
        public function selectLabelIdentifiersForHighlights(array $highlightIds) : array {
            $sql = <<<SQL
                SELECT li.*
                FROM label_identifier li
                WHERE li.id IN (
                    SELECT DISTINCT lh.label_id
                    FROM label_highlight lh
                    WHERE similarity > ?
                        AND lh.highlight_id IN ({$this->databaseClient->getPlaceholdersSequence(count($highlightIds))})
                )
                ORDER BY li.name
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($this->minHighlightSimilarity, ...$highlightIds)
                ->getMappedResultSet(function($labelRow) {
                    $metadata = $labelRow["unicode"] === null && $labelRow["keywords"] === null ? null : new LabelMetadata($labelRow["unicode"], $labelRow["keywords"] === null ? null : json_decode($labelRow["keywords"], true));
                    return new LabelIdentifier($labelRow["id"], $labelRow["name"], $metadata, $mainHighlights[$labelRow["main_highlight_id"]] ?? null);
                });
        }

        public function selectLabelIdentifier(string $labelName) : ?LabelIdentifier {       
            $sql = <<<'SQL'
                SELECT *
                FROM label_identifier
                WHERE name = ?
            SQL;

            $labelRow = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($labelName)
                ->getSingleRow();
            
            if ($labelRow === null) {
                return null;
            }
            
            $metadata = $labelRow["unicode"] === null && $labelRow["keywords"] === null ? null : new LabelMetadata($labelRow["unicode"], $labelRow["keywords"] === null ? null : json_decode($labelRow["keywords"], true));
            return new LabelIdentifier($labelRow["id"], $labelRow["name"], $metadata, $mainHighlights[$labelRow["main_highlight_id"]] ?? null);
        }

        public function selectHighlightIdsForLabelId(string $labelId) : array {            
            $sql = <<<'SQL'
                SELECT highlight_id
                FROM label_highlight
                WHERE similarity > ?
                    AND label_id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($this->minHighlightSimilarity, $labelId)
                ->getResultSetForColumn("highlight_id");
        }

        public function insertHighlightLabel(string $highlightId, string $labelId, float $similarity) : bool {
            $sql = <<<'SQL'
                INSERT INTO label_highlight (
                    highlight_id,
                    label_id,
                    similarity
                )
                VALUES (
                    ?,
                    ?,
                    ?
                )
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($highlightId, $labelId, $similarity)
                ->execute() === 1;
        }

        public function insertLabelIdentifier(LabelIdentifier $labelIdentifier) : ?string {
            $sql = <<<'SQL'
                INSERT INTO label_identifier (
                    name,
                    unicode,
                    keywords
                )
                VALUES (
                    ?,
                    ?,
                    ?
                )
                ON CONFLICT DO NOTHING
                RETURNING id
            SQL;

            $keywords = $labelIdentifier->getMetadata()?->getKeywords();
            $id = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($labelIdentifier->getName(), $labelIdentifier->getMetadata()?->getUnicode(), $keywords === null ? null : json_encode($keywords))
                ->getSingleColumn("id");

            if ($id === null) {
                return false;
            }

            $labelIdentifier->setId($id);
            return true;
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

        public function updateLabelKeywords(string $labelId, array $keywords) : bool {
            $sql = <<<'SQL'
                UPDATE label_identifier
                SET keywords = ?
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters(json_encode($keywords), $labelId)
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
        
        public function deleteLabelForHighlight(string $highlightId, string $labelId) : int {
            $sql = <<<'SQL'
                DELETE
                FROM label_highlight
                WHERE highlight_id = ?
                    AND label_id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($highlightId, $labelId)
                ->execute();
        }

        public function deleteLabelForAllHighlights(string $labelId) : int {
            $sql = <<<'SQL'
                DELETE
                FROM label_highlight
                WHERE label_id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($labelId)
                ->execute();
        }

        public function deleteLabel(string $labelId) : int {
            $sql = <<<'SQL'
                DELETE
                FROM label_identifier
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($labelId)
                ->execute();
        }
    }
?>