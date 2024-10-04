<?php
    class Place implements JsonSerializable {        
        private $id;
        private $name;
        private $country;
        private $latitude;
        private $longitude;
        private $timezone;
        private $mainHighlight;
        private $categories;
        private $highlights;
        private $dates;

        public function __construct($id, $name, $country, $latitude, $longitude, $timezone, $mainHighlight, $categories, $highlights, $dates) {
            $this->id = $id;
            $this->name = $name;
            $this->country = $country;
            $this->latitude = $latitude;
            $this->longitude = $longitude;
            $this->timezone = $timezone;
            $this->mainHighlight = $mainHighlight;
            $this->categories = $categories;
            $this->highlights = $highlights;
            $this->dates = $dates;
        }

        public function getId() {
            return $this->id;
        }

        public function getName() {
            return $this->name;
        }

        public function getCountry() {
            return $this->country;
        }

        public function getLatitude() {
            return $this->latitude;
        }

        public function getLongitude() {
            return $this->longitude;
        }

        public function getTimezone() {
            return $this->timezone;
        }

        public function getMainHighlight() {
            return $this->mainHighlight;
        }

        public function getCategories() {
            return $this->categories;
        }

        public function getHighlights() {
            return $this->highlights;
        }

        public function getDates() {
            return $this->dates;
        }

        public function addDate($date) {
            $this->dates[] = $date;
        }

        public function findAlbum($albumId) {
            foreach ($this->getDates() as &$date) {
                $album = $date->getAlbum();
                if ($album != NULL && $album->getId() == $albumId) {
                    return $album;
                }
            }

            return NULL;
        }

        public function getImagesCount() {
            $count = 0;
            $encounteredAlbums = array();
    
            foreach ($this->dates as &$date) {
                $album = $date->getAlbum();

                if ($album == NULL || in_array($album->getId(), $encounteredAlbums)) {
                    continue;
                }
                $encounteredAlbums[] = $album->getId();
    
                $count += $album->getImagesCount();
            }
    
            return $count;
        }
    
        public function getImagesScore() {
            global $configuration;
            
            $buckets = array();
            $encounteredAlbums = array();
    
            foreach ($this->dates as &$date) {
                $album = $date->getAlbum();

                if ($album == NULL || in_array($album->getId(), $encounteredAlbums)) {
                    continue;
                }
                $encounteredAlbums[] = $album->getId();
    
                $tripId = $date->getTrip() == NULL
                    ? intval($date->getStart() / (86400 * 365))
                    : $date->getTrip()->getId();
    
                if (!isset($buckets[$tripId])) {
                    $buckets[$tripId] = 0;
                }
    
                $buckets[$tripId] += $this->getRelevantImagesCountForScore($album);
            }
    
            return empty($buckets) ? 0 : max(array_values($buckets));
        }

        private function getRelevantImagesCountForScore($album) {
            return $album->getImagesCount() == 0 || $album->getIndoorImagesCount() / $album->getImagesCount() > 0.7
                ? $album->getImagesCount() // This is an indoor-only location.
                : $album->getImagesCount() - $album->getIndoorImagesCount(); // Exclude indoor photos from the score.
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this) + array(
                "imagesCount" => $this->getImagesCount(), 
                "imagesScore" => $this->getImagesScore());
        }
    }
?>