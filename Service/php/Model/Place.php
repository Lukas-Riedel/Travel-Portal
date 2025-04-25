<?php
    require_once(dirname(__FILE__) . "/Highlight.php");

    class Place implements JsonSerializable {        
        private $id;
        private $name;
        private $country;
        private $latitude;
        private $longitude;
        private $timezone;
        private $mainHighlight;
        private $excerpt;
        private $categories;
        private $highlights;
        private $labels;
        private $dates;

        public function __construct($id, $name, $country, $latitude, $longitude, $timezone, $mainHighlight, $excerpt, $categories, $highlights, $labels, $dates) {
            $this->id = $id;
            $this->name = $name;
            $this->country = $country;
            $this->latitude = $latitude;
            $this->longitude = $longitude;
            $this->timezone = $timezone;
            $this->mainHighlight = $mainHighlight;
            $this->excerpt = $excerpt;
            $this->categories = $categories;
            $this->highlights = $highlights;
            $this->labels = $labels;
            $this->dates = $dates;
        }

        public function getId() : int {
            return $this->id;
        }

        public function getName() : string {
            return $this->name;
        }

        public function getCountry() : string {
            return $this->country;
        }

        public function getLatitude() : float {
            return $this->latitude;
        }

        public function getLongitude() : float {
            return $this->longitude;
        }

        public function getTimezone() : string {
            return $this->timezone;
        }

        public function getMainHighlight() : ?Highlight {
            return $this->mainHighlight;
        }

        public function getExcerpt() : ?string {
            return $this->excerpt;
        }

        public function getCategories() : array {
            return $this->categories;
        }

        public function getHighlights() : array {
            return $this->highlights;
        }

        public function getLabels() : array {
            return $this->labels;
        }

        public function getDates() : array {
            return $this->dates;
        }

        public function addDate($date) : void {
            $this->dates[] = $date;
        }

        public function getPlaceIdentifier() : PlaceIdentifier {
            return new PlaceIdentifier($this->id, $this->name, $this->country, $this->latitude, $this->longitude, $this->timezone, $this->mainHighlight, $this->excerpt);
        }

        public function withUpdatedDates($dates) : Place {
            $newPlace = clone $this;
            $newPlace->dates = $dates;
            return $newPlace;
        }

        public function findAlbum($albumId) : ?Album {
            foreach ($this->getDates() as &$date) {
                $album = $date->getAlbum();
                if ($album != NULL && $album->getId() == $albumId) {
                    return $album;
                }
            }

            return NULL;
        }

        public function getImagesCount() : int {
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
    
        public function getImagesScore() : float {            
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

        private function getRelevantImagesCountForScore($album) : int {
            return $album->getImagesCount() == 0 || $album->getIndoorImagesCount() / $album->getImagesCount() > 0.6
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