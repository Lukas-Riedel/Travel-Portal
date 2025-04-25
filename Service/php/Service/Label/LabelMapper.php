<?php
    namespace Service\Service\Label;

    class LabelMapper {
        
        private readonly \DatabaseProvider $databaseProvider;

        public function __construct(\DatabaseProvider $databaseProvider) {
            $this->databaseProvider = $databaseProvider;
        }

        public function selectLabelsForPlace(string $placeId) : array {
            $sql = <<<'SQL'
                SELECT *
                FROM label
                WHERE place_id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($placeId)
                ->getMappedResultSet(function($labelRow) {
                    return new Label($labelRow["id"], $labelRow["name"]);
                });
        }

        public function insertLabel(Label $label, string $placeId) : bool {
            $sql = <<<'SQL'
                INSERT INTO label(
                    place_id,
                    name
                )
                VALUES (
                    ?,
                    ?
                )
            SQL;

            $wasInserted = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($placeId, $label->getname())
                ->execute();
                 

            if ($wasInserted) {
                $label->setId($this->databaseProvider->getLastInsertedId());
            }

            return $wasInserted;
        }

        public function deleteLabel(string $labelId) : int {
            $sql = <<<'SQL'
                DELETE
                FROM label
                WHERE id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($labelId)
                ->execute();
        }
    }
?>