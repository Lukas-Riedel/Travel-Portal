<?php
    namespace Core\Service\Highlight;
    
    use Core\Service\Photo\PhotoService;
    use Core\Client\Database\DatabaseClient;

    class HighlightMapper {

        private readonly DatabaseClient $databaseClient;

        private readonly PhotoService $photoService;

        public function __construct(DatabaseClient $databaseClient, PhotoService $photoService) {
            $this->databaseClient = $databaseClient;
            $this->photoService = $photoService;
        }

        public function selectHighlight(string $highlightId) : ?Highlight {
            $sql = <<<'SQL'
                SELECT *
                FROM highlight_identifier
                WHERE id = ?
            SQL;
    
            $highlightRow = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($highlightId)
                ->getSingleRow();

            if ($highlightRow === null) {
                return null;
            }

            return $this->getHighlight($highlightRow);
        }

        public function selectHighlightsByIds(array $highlightIds) : array {
            $sql = <<<SQL
                SELECT *
                FROM highlight_identifier
                WHERE id IN ({$this->databaseClient->getPlaceholdersSequence(count($highlightIds))})
            SQL;

            $highlightRows = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters(...$highlightIds)
                ->getResultSet();            
            
            $photoIds = array_filter(array_map(fn($placeRow) => $placeRow["photo_id"], $highlightRows), fn($photoId) => $photoId !== null);

            $photos = array();
            foreach ($this->photoService->getPhotosByIds($photoIds) as &$photo) {
                $photos[$photo->getId()] = $photo;
            }

            $highlights = array();
            foreach ($highlightRows as &$highlightRow) {                
                if (!isset($photos[$highlightRow["photo_id"]])) {
                    $highlights[] = new Highlight($highlightRow["id"], $highlightRow["thumbnail_url"], $highlightRow["full_url"], $highlightRow["photo_id"],
                        null, null, null, null, null, null, $highlightRow["composition"], $highlightRow["sky"],
                        $highlightRow["shadows"], $highlightRow["circumstances"], $highlightRow["atmosphere"], null, null, null);
                }
                else {
                    $photo = $photos[$highlightRow["photo_id"]];
                    $highlights[] = new Highlight($highlightRow["id"], $highlightRow["thumbnail_url"], $highlightRow["full_url"], $highlightRow["photo_id"],
                        $photo->getPermalink(), $photo->getCamera(), $photo->getFocalLength(), $photo->getAperture(), $photo->getShutterSpeed(), $photo->getIso(),
                        $highlightRow["composition"], $highlightRow["sky"], $highlightRow["shadows"], $highlightRow["circumstances"],
                        $highlightRow["atmosphere"], $photo->getTimestamp(), $photo->getSunAltitude(), $photo->getSunAzimuth());
                }
            }
            
            return $highlights;
        }

        public function selectHighlightsForEntity(HighlightType $highlightType, string $entityId) : array {
            $sql = <<<SQL
                SELECT hi.*
                FROM {$highlightType->getTableName()} ht
                INNER JOIN highlight_identifier hi
                    ON ht.highlight_id = hi.id
                WHERE ht.id = ?
                ORDER BY photo_id ASC
            SQL;
            
            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($entityId)
                ->getMappedResultSet(function($highlightRow) { 
                    return $this->getHighlight($highlightRow);
                });
        }

        public function selectEntityIdsForHighlightId(HighlightType $highlightType, string $highlightId) : array {
            $sql = <<<SQL
                SELECT ht.id
                FROM {$highlightType->getTableName()} ht
                INNER JOIN highlight_identifier hi
                    ON ht.highlight_id = hi.id
                WHERE ht.highlight_id = ?
            SQL;
            
            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($highlightId)
                ->getResultSetForColumn("id");
        }

        public function selectAllHighlights(?string $highlightId, ?string $photoId) : array {
            $sql = <<<'SQL'
                SELECT * 
                FROM highlight_identifier
                WHERE :CONDITIONS
            SQL;
            
            $whereClauseBuilder = $this->databaseClient->whereClauseBuilder();
            if ($highlightId !== null) {
                $whereClauseBuilder->withClause("id = ?", $highlightId);
            }
            if ($photoId !== null) {
                $whereClauseBuilder->withClause("photo_id = ?", $photoId);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();
            
            return $this->databaseClient
                ->statementBuilder($sql, $whereClause)
                ->getMappedResultSet(function($highlightRow) { 
                    return $this->getHighlight($highlightRow);
                });
        }

        public function selectHighlightId(string $photoId) : ?string {
            $sql = <<<'SQL'
                SELECT id
                FROM highlight_identifier
                WHERE photo_id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($photoId)
                ->getFirstColumn("id");
        }

        public function selectPhotoId(string $highlightId) : ?string {
            $sql = <<<'SQL'
                SELECT photo_id
                FROM highlight_identifier
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($highlightId)
                ->getFirstColumn("photo_id");
        }

        public function insertHighlightId(string $photoId) : bool {    
            $sql = <<<'SQL'
                INSERT INTO highlight_identifier (
                    photo_id
                )
                VALUES (
                    ?
                )
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($photoId)
                ->execute() === 1;
        }

        public function insertHighlight(HighlightType $highlightType, string $entityId, string $highlightId) : bool {    
            $sql = <<<SQL
                INSERT INTO {$highlightType->getTableName()} (
                    id, 
                    highlight_id
                ) 
                VALUES (
                    ?, 
                    ?
                )
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($entityId, $highlightId)
                ->execute() === 1;
        }

        public function updateHighlightImageUrl(HighlightSize $highlightSize, string $highlightId, string $imageUrl) : bool {
            $sql = <<<SQL
                UPDATE highlight_identifier
                SET {$highlightSize->getUrlColumnName()} = ?
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($imageUrl, $highlightId)
                ->execute() === 1;
        }

        public function updateHighlightComposition(string $highlightId, int $composition) : bool {
            $sql = <<<'SQL'
                UPDATE highlight_identifier
                SET composition = ?
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($composition, $highlightId)
                ->execute() === 1;
        }

        public function updateHighlightSky(string $highlightId, int $sky) : bool {
            $sql = <<<'SQL'
                UPDATE highlight_identifier
                SET sky = ?
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($sky, $highlightId)
                ->execute() === 1;
        }

        public function updateHighlightShadows(string $highlightId, int $shadows) : bool {
            $sql = <<<'SQL'
                UPDATE highlight_identifier
                SET shadows = ?
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($shadows, $highlightId)
                ->execute() === 1;
        }

        public function updateHighlightCircumstances(string $highlightId, int $circumstances) : bool {
            $sql = <<<'SQL'
                UPDATE highlight_identifier
                SET circumstances = ?
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($circumstances, $highlightId)
                ->execute() === 1;
        }

        public function updateHighlightAtmosphere(string $highlightId, int $atmosphere) : bool {
            $sql = <<<'SQL'
                UPDATE highlight_identifier
                SET atmosphere = ?
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($atmosphere, $highlightId)
                ->execute() === 1;
        }

        public function deleteHighlight(HighlightType $highlightType, string $entityId, string $highlightId) : int {
            $sql = <<<SQL
                DELETE
                FROM {$highlightType->getTableName()}
                WHERE id = ?
                    AND highlight_id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($entityId, $highlightId)
                ->execute();
        }

        public function deleteStaleHighlightIdentifiers() : int {
            $sql = <<<'SQL'
                DELETE
                FROM highlight_identifier hi
                WHERE NOT EXISTS (
                        SELECT 1 
                        FROM highlight_place hp
                        WHERE hp.highlight_id = hi.id
                    ) AND NOT EXISTS (
                        SELECT 1 
                        FROM highlight_trip ht
                        WHERE ht.highlight_id = hi.id
                    )
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->execute();
        }

        private function getHighlight(mixed $highlightRow) : Highlight {
            $photo = $this->photoService->getPhoto($highlightRow["photo_id"]);
            if ($photo === null) {
                return new Highlight($highlightRow["id"], $highlightRow["thumbnail_url"], $highlightRow["full_url"], $highlightRow["photo_id"],
                    null, null, null, null, null, null, $highlightRow["composition"], $highlightRow["sky"],
                    $highlightRow["shadows"], $highlightRow["circumstances"], $highlightRow["atmosphere"], null, null, null);
            }
            else {
                return new Highlight($highlightRow["id"], $highlightRow["thumbnail_url"], $highlightRow["full_url"], $highlightRow["photo_id"],
                    $photo->getPermalink(), $photo->getCamera(), $photo->getFocalLength(), $photo->getAperture(), $photo->getShutterSpeed(), $photo->getIso(),
                    $highlightRow["composition"], $highlightRow["sky"], $highlightRow["shadows"], $highlightRow["circumstances"],
                    $highlightRow["atmosphere"], $photo->getTimestamp(), $photo->getSunAltitude(), $photo->getSunAzimuth());
            }
        }
    }
?>