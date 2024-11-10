<?php
    require_once(dirname(__FILE__) . "/GetMediaItemsProcessor.php");

    class FinishReuploadPhotosProcessor extends Processor {
        public function process($input) {
            global $databaseProvider;

            $getMediaItemsProcessor = new GetMediaItemsProcessor();
            $oldPhotos = $getMediaItemsProcessor
                ->process(array(
                    "albumId" => $input["oldAlbumId"]));
            $newPhotos = $getMediaItemsProcessor
                ->process(array(
                    "albumId" => $input["newAlbumId"]));

            if (count($oldPhotos) != count($newPhotos)) {
                throw new RuntimeException("Photos count mismatch. The old album contains " . count($oldPhotos) . " photos, but the new album contains " . count($newPhotos) . " photos.");
            }

            for ($i = 0; $i < count($oldPhotos); ++$i) {
                if ($newPhotos[$i]->getTimestamp() == $oldPhotos[$i]->getTimestamp() && $newPhotos[$i]->getId() != $oldPhotos[$i]->getId()) {
                    $databaseProvider
                        ->statementBuilder("UPDATE photo_identifier SET external_id = (SELECT external_id FROM photo_identifier WHERE id = ?) WHERE id = ?")
                        ->withParameters($newPhotos[$i]->getId(), $oldPhotos[$i]->getId())
                        ->execute();
    
                    $databaseProvider
                        ->statementBuilder("UPDATE album SET main_photo_id = ? WHERE main_photo_id = ?")
                        ->withParameters($oldPhotos[$i]->getId(), $newPhotos[$i]->getId())
                        ->execute();
    
                    $databaseProvider
                        ->statementBuilder("DELETE FROM photo WHERE id = ?")
                        ->withParameters($newPhotos[$i]->getId())
                        ->execute();
    
                    $databaseProvider
                        ->statementBuilder("DELETE FROM photo_identifier WHERE id = ?")
                        ->withParameters($newPhotos[$i]->getId())
                        ->execute();
                }
            }

            $databaseProvider
                ->statementBuilder("UPDATE album_identifier SET replacement_album_id = ? WHERE id = ?")
                ->withParameters($input["newAlbumId"], $input["oldAlbumId"])
                ->execute();
        }

        public function getRequiredArguments() {
            return array("oldAlbumId", "newAlbumId");
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }
    }
?>