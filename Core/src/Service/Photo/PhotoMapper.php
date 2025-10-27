<?php
    namespace Core\Service\Photo;
    
    use Core\Client\Database\DatabaseClient;
    use Core\Client\Google\GoogleClient;

    class PhotoMapper {

        private const INDOOR_PHOTO_ISO_THRESHOLD = 640;

        private readonly DatabaseClient $databaseClient;

        private readonly GoogleClient $googleClient;

        public function __construct(DatabaseClient $databaseClient, GoogleClient $googleClient) {
            $this->databaseClient = $databaseClient;
            $this->googleClient = $googleClient;
        }

        public function selectReplacedPhotos() : array {
            $sql = <<<'SQL'
                SELECT id
                FROM photo_identifier
                WHERE replaced = 1
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->getMappedResultSetForColumn("id", function($photoId) {
                    return $this->doSelectPhoto($photoId, fn() => null);
                });
        }

        public function selectAllAlbums() : array {
            $sql = <<<SQL
                WITH target_album AS (
                    SELECT *
                    FROM album
                )
                {$this->getSelectAlbumsQuery("target_album")}
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->getMappedResultSet(function($albumRow) {
                    return new Album($albumRow["id"], $albumRow["name"], $albumRow["main_photo_id"] === null 
                        ? null : $this->doSelectPhoto($albumRow["main_photo_id"], fn() => $albumRow["thumbnail_url"]),
                        $albumRow["thumbnail_url"], $albumRow["permalink"], intval($albumRow["images_count"]), intval($albumRow["indoor_images_count"]),
                        boolval($albumRow["reviewed"]), $albumRow["uploading_start"] === null ? null : intval($albumRow["uploading_start"]), 
                        $albumRow["uploading_progress"] === null ? null : floatval($albumRow["uploading_progress"]));
                });
        }

        public function selectAlbumsForPlaceName(string $placeName) : array {
            $sql = <<<SQL
                WITH target_album AS (
                    SELECT *
                    FROM album
                    WHERE name LIKE CONCAT(?, ' _._.____')
                        OR name LIKE CONCAT(?, ' __._.____')
                        OR name LIKE CONCAT(?, ' _.__.____')
                        OR name LIKE CONCAT(?, ' __.__.____')
                )
                {$this->getSelectAlbumsQuery("target_album")}
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($placeName, $placeName, $placeName, $placeName)
                ->getMappedResultSet(function($albumRow) {
                    return new Album($albumRow["id"], $albumRow["name"], $albumRow["main_photo_id"] === null 
                        ? null : $this->doSelectPhoto($albumRow["main_photo_id"], fn() => $albumRow["thumbnail_url"]),
                        $albumRow["thumbnail_url"], $albumRow["permalink"], intval($albumRow["images_count"]), intval($albumRow["indoor_images_count"]),
                        boolval($albumRow["reviewed"]), $albumRow["uploading_start"] === null ? null : intval($albumRow["uploading_start"]), 
                        $albumRow["uploading_progress"] === null ? null : floatval($albumRow["uploading_progress"]));
                });
        }

        public function selectAlbum(string $albumId) : ?Album {
            $sql = <<<SQL
                WITH target_album AS (
                    SELECT *
                    FROM album
                    WHERE id = ?
                )
                {$this->getSelectAlbumsQuery("target_album")}
            SQL;

            $albumRow = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($albumId)
                ->getSingleRow();
            
            if ($albumRow === null) {                
                return null;
            }

            return new Album($albumRow["id"], $albumRow["name"], $albumRow["main_photo_id"] === null 
                ? null : $this->doSelectPhoto($albumRow["main_photo_id"], fn() => $albumRow["thumbnail_url"]),
                $albumRow["thumbnail_url"], $albumRow["permalink"], intval($albumRow["images_count"]), intval($albumRow["indoor_images_count"]),
                boolval($albumRow["reviewed"]), $albumRow["uploading_start"] === null ? null : intval($albumRow["uploading_start"]), 
                $albumRow["uploading_progress"] === null ? null : floatval($albumRow["uploading_progress"]));
        }

        public function selectAlbumByName(string $albumName) : ?Album {
            $sql = <<<SQL
                WITH target_album AS (
                    SELECT *
                    FROM album
                    WHERE name = ?
                )
                {$this->getSelectAlbumsQuery("target_album")}
            SQL;

            $albumRow = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($albumName)
                ->getSingleRow();
            
            if ($albumRow === null) {                
                return null;
            }

            return new Album($albumRow["id"], $albumRow["name"], $albumRow["main_photo_id"] === null 
                ? null : $this->doSelectPhoto($albumRow["main_photo_id"], fn() => $albumRow["thumbnail_url"]),
                $albumRow["thumbnail_url"], $albumRow["permalink"], intval($albumRow["images_count"]), intval($albumRow["indoor_images_count"]),
                boolval($albumRow["reviewed"]), $albumRow["uploading_start"] === null ? null : intval($albumRow["uploading_start"]), 
                $albumRow["uploading_progress"] === null ? null : floatval($albumRow["uploading_progress"]));
        }

        public function selectAlbumForPhotoId(string $photoId) : ?Album {
            $sql = <<<'SQL'
                SELECT album_id
                FROM photo
                WHERE id = ?
            SQL;

            $albumId = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($photoId)
                ->getSingleColumn(("album_id"));

            if ($albumId === null) {
                return null;
            }

            return $this->selectAlbum($albumId);
        }

        public function selectPhoto(string $photoId) : ?Photo {
            $urlProvider = function() use(&$photoId) { 
                return $this->googleClient->getPhoto($this->selectPhotoExternalId($photoId))["baseUrl"];
            };
            $urlProvider->bindTo($this);
            return $this->doSelectPhoto($photoId, $urlProvider);
        }

        public function selectPendingPhotosWithFixedPosition(string $albumId) : array {
            $sql = <<<'SQL'
                SELECT *
                FROM photo_pending
                WHERE album_id = ?
                    AND replaced_photo_id IS NULL
                    AND expiration > UNIX_TIMESTAMP()
                ORDER BY batch_position
                LIMIT 50
            SQL;
            
            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($albumId)
                ->getMappedResultSet(function($photoRow) {
                    return new PendingPhoto($photoRow["id"], $photoRow["album_id"], $photoRow["file_name"],
                        $photoRow["batch_id"], $photoRow["expected_batch_size"], $photoRow["batch_position"],
                        $photoRow["replaced_photo_id"], $photoRow["upload_token"]);
                });
        }

        public function selectPendingPhotosWithRelativePosition(string $albumId) : array {
            $sql = <<<'SQL'
                SELECT *
                FROM photo_pending
                WHERE album_id = ?
                    AND replaced_photo_id IS NOT NULL
                    AND expiration > UNIX_TIMESTAMP()
            SQL;
            
            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($albumId)
                ->getMappedResultSet(function($photoRow) {
                    return new PendingPhoto($photoRow["id"], $photoRow["album_id"], $photoRow["file_name"],
                        $photoRow["batch_id"], $photoRow["expected_batch_size"], $photoRow["batch_position"],
                        $photoRow["replaced_photo_id"], $photoRow["upload_token"]);
                });
        }

        public function selectAlbumId(string $externalId) : ?string {
            $sql = <<<'SQL'
                SELECT id
                FROM album_identifier
                WHERE external_id = ?
            SQL;
            
            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($externalId)
                ->getFirstColumn("id");
        }

        public function selectPhotoId(string $externalId) : ?string {
            $sql = <<<'SQL'
                SELECT id
                FROM photo_identifier
                WHERE external_id = ?
            SQL;
            
            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($externalId)
                ->getFirstColumn("id");
        }

        public function selectAlbumExternalId(string $albumId) : ?string {            
            $sql = <<<'SQL'
                SELECT external_id
                FROM album_identifier
                WHERE id = ?
            SQL;
            
            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($albumId)
                ->getFirstColumn("external_id");
        }

        public function selectPhotoExternalId(string $photoId) : ?string {            
            $sql = <<<'SQL'
                SELECT external_id
                FROM photo_identifier
                WHERE id = ?
            SQL;
            
            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($photoId)
                ->getFirstColumn("external_id");
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

            return $this->databaseClient
                ->statementBuilder($sql)
                ->getResultSetForColumn("id");
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
                    ?,
                    ?
                )
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($album->getName(), $album->getId(), $album->getMainPhoto()?->getId(), $album->getMainImageUrl(),
                    $album->getImagesCount(), $this->selectIndoorPhotosCount($album->getId()), $album->getPermalink())
                ->execute() === 1;
        }

        public function insertPhoto(Photo $photo, string $albumId) : bool {
            $sql = <<<'SQL'
                INSERT INTO photo (
                    id,
                    album_id,
                    focal_length,
                    aperture,
                    shutter_speed,
                    iso,
                    timestamp,
                    permalink,
                    sun_altitude,
                    sun_azimuth
                )
                VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            SQL;
            
            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($photo->getId(), $albumId, $photo->getFocalLength(), $photo->getAperture(),
                    $photo->getShutterSpeed(), $photo->getIso(), $photo->getTimestamp(), $photo->getPermalink(),
                    $photo->getSunAltitude(), $photo->getSunAzimuth())
                ->execute() === 1;
        }

        public function insertPendingPhoto(PendingPhoto $pendingPhoto, int $expirationInterval) : bool {
            $sql = <<<'SQL'
                INSERT INTO photo_pending (
                    album_id,
                    file_name,
                    batch_id,
                    expected_batch_size,
                    batch_position,
                    replaced_photo_id,
                    upload_token,
                    created,
                    expiration
                )
                VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    UNIX_TIMESTAMP(),
                    UNIX_TIMESTAMP() + ?
                )
            SQL;

            $wasInserted = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($pendingPhoto->getAlbumId(), $pendingPhoto->getFileName(), $pendingPhoto->getBatchId(),
                    $pendingPhoto->getExpectedBatchSize(), $pendingPhoto->getBatchPosition(),  $pendingPhoto->getReplacedPhotoId(),
                    $pendingPhoto->getUploadToken(), $expirationInterval)
                ->execute() === 1;

            if ($wasInserted) {
                $pendingPhoto->setId($this->databaseClient->getLastInsertedId());
            }

            return $wasInserted;
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

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($externalId)
                ->execute() === 1;
        }

        public function updateAlbumReviewed(string $albumId) : bool {
            $sql = <<<'SQL'
                UPDATE photo_identifier pi
                INNER JOIN photo p
                    ON pi.id = p.id
                SET pi.reviewed = UNIX_TIMESTAMP()
                WHERE p.album_id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($albumId)
                ->execute() > 0;
        }

        public function insertPhotoId(string $externalId, bool $replaced) : bool {    
            $sql = <<<'SQL'
                INSERT INTO photo_identifier (
                    external_id,
                    replaced,
                    reviewed
                )
                VALUES (
                    ?,
                    ?,
                    NULL
                )
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($externalId, $replaced ? 1 : 0)
                ->execute() === 1;
        }

        public function updatePhotoExternalId(string $photoId, string $newExternalId) : bool {
            $sql = <<<'SQL'
                UPDATE photo_identifier
                SET external_id = ?
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($newExternalId, $photoId)
                ->execute() === 1;
        }

        public function deleteAlbums(?string $albumId) : int {  
            $sql = <<<'SQL'
                DELETE
                FROM album
                WHERE :CONDITIONS
            SQL;

            $whereClauseBuilder = $this->databaseClient->whereClauseBuilder();
            if ($albumId !== null) {
                $whereClauseBuilder->withClause("id = ?", $albumId);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            return $this->databaseClient
                ->statementBuilder($sql, $whereClause)
                ->execute();
        }

        public function deletePhotos(string $albumId) : int {
            $sql = <<<'SQL'
                DELETE
                FROM photo
                WHERE album_id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($albumId)
                ->execute();
        }

        public function deletePendingPhoto(string $id) : int {
            $sql = <<<'SQL'
                DELETE
                FROM photo_pending
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($id)
                ->execute();
        }

        public function deleteStaleAlbumIdentifiers() : int {
            $sql = <<<'SQL'
                DELETE
                FROM album_identifier
                WHERE id NOT IN (
                    SELECT id
                    FROM album
                )
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->execute();
        }

        public function deleteStalePhotoIdentifiers() : int {
            $sql = <<<'SQL'
                DELETE
                FROM photo_identifier
                WHERE id NOT IN (
                    SELECT id
                    FROM photo
                )
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->execute();
        }

        public function deleteStalePendingPhotos() : int {
            $sql = <<<'SQL'
                DELETE
                FROM photo_pending
                WHERE expiration <= UNIX_TIMESTAMP()
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->execute();
        }

        public function deletePendingPhotosForAlbum(string $albumId) : int {
            $sql = <<<'SQL'
                DELETE
                FROM photo_pending
                WHERE album_id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($albumId)
                ->execute();
        }

        private function selectIndoorPhotosCount(string $albumId) : int {
            $sql = <<<'SQL'
                SELECT COUNT(*) AS count
                FROM photo
                WHERE album_id = ?
                    AND iso >= ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($albumId, self::INDOOR_PHOTO_ISO_THRESHOLD)
                ->getSingleColumn("count");
        }

        private function doSelectPhoto(string $photoId, callable $urlProvider) : ?Photo {
            $sql = <<<'SQL'
                SELECT *
                FROM photo
                WHERE id = ?
            SQL;            
                
            $photoRow = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($photoId)
                ->getSingleRow();

            if ($photoRow === null) {
                return null;
            }

            return new Photo($photoId, $urlProvider, $photoRow["permalink"], $photoRow["focal_length"], $photoRow["aperture"],
                $photoRow["shutter_speed"], $photoRow["iso"], $photoRow["timestamp"], $photoRow["sun_altitude"], $photoRow["sun_azimuth"]);
        }

        private function getSelectAlbumsQuery(string $cteName) : string {
            return <<<SQL
                SELECT
                    a.*, 
                    pp.uploading_start, 
                    ROUND(100 * pp.uploaded_photos / pp.batch_size) AS uploading_progress,
                    ps.non_reviewed_count = 0 AS reviewed
                FROM {$cteName} a
                LEFT JOIN (
                    SELECT 
                        album_id, 
                        MIN(created) AS uploading_start, 
                        SUM(uploaded_photos) AS uploaded_photos, 
                        SUM(batch_size) AS batch_size
                    FROM (
                        SELECT 
                        album_id, 
                        MIN(created) AS created, 
                        COUNT(*) AS uploaded_photos, 
                        MAX(expected_batch_size) AS batch_size
                        FROM photo_pending
                        WHERE album_id IN (SELECT id FROM {$cteName})
                        GROUP BY album_id, batch_id
                    ) x
                    GROUP BY album_id
                ) pp 
                    ON a.id = pp.album_id
                LEFT JOIN (
                    SELECT p.album_id,
                        SUM(pi.reviewed IS NULL) AS non_reviewed_count
                    FROM photo p
                    INNER JOIN photo_identifier pi
                        ON p.id = pi.id
                    WHERE album_id IN (SELECT id FROM {$cteName})
                    GROUP BY p.album_id
                ) ps
                    ON a.id = ps.album_id
            SQL;
        }
    }
?>