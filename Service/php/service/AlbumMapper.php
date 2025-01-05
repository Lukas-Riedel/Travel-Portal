<?php
    require_once(dirname(__FILE__) . "/../model/Album.php");

    class AlbumMapper {

        private DatabaseProvider $databaseProvider;

        public function __construct(DatabaseProvider $databaseProvider) {
            $this->databaseProvider = $databaseProvider;
        }

        public function selectAlbum(string $albumId) : ?Album {
            $sql = <<<'SQL'
                SELECT *
                FROM album
                WHERE id = ?
            SQL;

            $albumRow = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($albumId)
                ->getSingleRow();
            
            if ($albumRow === NULL) {                
                return NULL;
            }

            return new Album($albumRow["id"], $albumRow["name"], $albumRow["main_photo_id"], $albumRow["thumbnail_url"],
                $albumRow["permalink"], intval($albumRow["images_count"]), intval($albumRow["indoor_images_count"]));
        }

        public function selectAlbumId(string $externalId) : ?string {
            $sql = <<<'SQL'
                SELECT id
                FROM album_identifier
                WHERE external_id = ?
            SQL;
            
            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($externalId)
                ->getFirstColumn("id");
        }

        public function selectAlbumIdsWithOutdatedPhotos() : array {
            $sql = <<<'SQL'
                SELECT a.id
                FROM album a
                WHERE a.images_count <> (
                    SELECT COUNT(*)
                    FROM photo p
                    WHERE p.album_id = a.id
                )
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->getResultSetForColumn("id");
        }

        public function selectExternalId(string $albumId) : ?string {            
            $sql = <<<'SQL'
                SELECT external_id
                FROM album_identifier
                WHERE id = ?
            SQL;
            
            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($albumId)
                ->getFirstColumn("external_id");
        }

        public function insertAlbum(Album $album) : bool {    
            $sql = <<<'SQL'
                INSERT INTO album (
                    name,
                    id,
                    main_photo_id,
                    thumbnail_url,
                    images_count,
                    indoor_images_count,
                    permalink
                ) 
                VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    GET_INDOOR_IMAGES_COUNT(?),
                    ?
                )
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($album->getName(), $album->getId(), $album->getMainPhotoId(), $album->getMainImageUrl(),
                    $album->getImagesCount(), $album->getId(), $album->getPermalink())
                ->execute() === 1;
        }

        public function insertAlbumId(string $externalId) : bool {    
            $sql = <<<'SQL'
                INSERT INTO album_identifier (
                    external_id
                )
                VALUES (
                    ?
                )
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($externalId)
                ->execute() === 1;
        }

        public function deleteAlbums(?string $albumId) : bool {  
            $sql = <<<'SQL'
                DELETE
                FROM album
                WHERE :CONDITIONS
            SQL;

            $whereClauseBuilder = $this->databaseProvider->whereClauseBuilder();
            if ($albumId !== NULL) {
                $whereClauseBuilder->withClause("id = ?", $albumId);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            return $this->databaseProvider
                ->statementBuilder($sql, $whereClause)
                ->execute() > 0;
        }
    }
?>