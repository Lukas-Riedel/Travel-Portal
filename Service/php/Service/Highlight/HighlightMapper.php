<?php
    namespace Service\Service\Highlight;
    
    use Service\Service\Photo\PhotoService;

    class HighlightMapper {

        private readonly \DatabaseProvider $databaseProvider;

        private readonly PhotoService $photoService;

        public function __construct(\DatabaseProvider $databaseProvider, PhotoService $photoService) {
            $this->databaseProvider = $databaseProvider;
            $this->photoService = $photoService;
        }

        public function selectHighlight(string $highlightId) : ?Highlight {
            $sql = <<<'SQL'
                SELECT *
                FROM highlight_identifier
                WHERE id = ?
            SQL;
    
            $highlightRow = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($highlightId)
                ->getSingleRow();

            if ($highlightRow === NULL) {
                return NULL;
            }

            return $this->getHighlight($highlightRow);
        }

        public function selectHighlights(HighlightType $highlightType, string $entityId) : array {
            $sql = <<<SQL
                SELECT hi.*
                FROM {$highlightType->getTableName()} ht
                INNER JOIN highlight_identifier hi
                    ON ht.highlight_id = hi.id
                WHERE ht.id = ?
            SQL;
            
            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($entityId)
                ->getMappedResultSet(function($highlightRow) { 
                    return $this->getHighlight($highlightRow);
                });
        }

        public function selectEntityIdsForHighlightId(HighlightType $highlightType, string $entityId) : array {
            $sql = <<<SQL
                SELECT ht.id
                FROM {$highlightType->getTableName()} ht
                INNER JOIN highlight_identifier hi
                    ON ht.highlight_id = hi.id
                WHERE ht.highlight_id = ?
            SQL;
            
            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($entityId)
                ->getResultSetForColumn("id");
        }

        public function selectAllHighlights(?string $highlightId, ?string $photoId) : array {
            $sql = <<<'SQL'
                SELECT * 
                FROM highlight_identifier
                WHERE :CONDITIONS
            SQL;
            
            $whereClauseBuilder = $this->databaseProvider->whereClauseBuilder();
            if ($highlightId !== NULL) {
                $whereClauseBuilder->withClause("id = ?", $highlightId);
            }
            if ($photoId !== NULL) {
                $whereClauseBuilder->withClause("photo_id = ?", $photoId);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();
            
            return $this->databaseProvider
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

            return $this->databaseProvider
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

            return $this->databaseProvider
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

            return $this->databaseProvider
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

            return $this->databaseProvider
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

            return $this->databaseProvider
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

            return $this->databaseProvider
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

            return $this->databaseProvider
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

            return $this->databaseProvider
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

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($circumstances, $highlightId)
                ->execute() === 1;
        }

        public function deleteHighlight(HighlightType $highlightType, string $entityId, string $highlightId) : int {
            $sql = <<<SQL
                DELETE
                FROM {$highlightType->getTableName()}
                WHERE id = ?
                    AND highlight_id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($entityId, $highlightId)
                ->execute();
        }

        private function getHighlight(mixed $highlightRow) : Highlight {
            $photo = $this->photoService->getPhoto($highlightRow["photo_id"]);
            if ($photo === NULL) {
                return new Highlight($highlightRow["id"], $highlightRow["thumbnail_url"], $highlightRow["full_url"], $highlightRow["photo_id"],
                    NULL, NULL, NULL, NULL, NULL, $highlightRow["composition"], $highlightRow["sky"],
                    $highlightRow["shadows"], $highlightRow["circumstances"], NULL);
            }
            else {
                return new Highlight($highlightRow["id"], $highlightRow["thumbnail_url"], $highlightRow["full_url"], $highlightRow["photo_id"],
                    $photo->getPermalink(), $photo->getFocalLength(), $photo->getAperture(), $photo->getShutterSpeed(), $photo->getIso(),
                    $highlightRow["composition"], $highlightRow["sky"], $highlightRow["shadows"], $highlightRow["circumstances"],
                    $photo->getTimestamp());
            }
        }
    }
?>