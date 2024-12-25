<?php
    class UpdateHighlightProcessor extends Processor {        
        public function process($input) {
            global $configuration;
        
            $this->updateHighlights($input, $configuration["cachePath"]["highlightThumbnail"], $configuration["highlightThumbnailImageSize"], "thumbnail_url");
        }

        public function getRequiredArguments() {
            return array();
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }

        private function updateHighlights($input, $cachePath, $imageSize, $urlColumnName) {
            global $databaseProvider, $googleApiClient;

            $highlightCachePath = dirname(__FILE__) . "/../../" . $cachePath;        
            $actuallyUsedImages = array();

            $whereClauseBuilder = $databaseProvider->whereClauseBuilder();
            if (isset($input["highlightId"])) {
                $whereClauseBuilder->withClause("hi.id = ?", $input["highlightId"]);
            }
            if (isset($input["photoId"])) {
                $whereClauseBuilder->withClause("pi.id = ?", $input["photoId"]);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();
            
            $highlightRows = $databaseProvider
                ->statementBuilder("SELECT hi.*, pi.external_id FROM highlight_identifier hi INNER JOIN photo_identifier pi ON hi.photo_id = pi.id {{WHERE CLAUSE}}", $whereClause)
                ->getResultSet();

            foreach ($highlightRows as &$highlightRow) {
                $fileName = $highlightRow["external_id"] . ".jpg";
                $filePath = $highlightCachePath . "/" . $fileName;
    
                if ((isset($input["forceOverwrite"]) && $input["forceOverwrite"] == "true") || !file_exists($filePath)) {
                    $baseUrl = $googleApiClient->getMediaItem($highlightRow["external_id"])["baseUrl"];
                    file_put_contents($filePath, file_get_contents($baseUrl . "=w" . $imageSize["width"] . "-h" . $imageSize["height"]));
                }
    
                $actuallyUsedImages[] = $filePath;
                $imageUrl = BASE_URL . "/" . $cachePath . "/" . $fileName;

                $databaseProvider
                    ->statementBuilder("UPDATE highlight_identifier SET " . $urlColumnName . " = ? WHERE id = ?")
                    ->withParameters($imageUrl, $highlightRow["id"])
                    ->execute();
            }
            
            if (!isset($input["highlightId"])) {
                $downloadedImages = array_filter((array) glob($highlightCachePath . "/*"));
                $unusedImages = array_diff($downloadedImages, $actuallyUsedImages);    
                array_map("unlink", $unusedImages);
            }

            return TRUE;
        }
    }
?>