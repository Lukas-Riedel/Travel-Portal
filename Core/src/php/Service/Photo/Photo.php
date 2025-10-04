<?php
    namespace Core\Service\Photo;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Photo",
        type: "object",
        description: "An object representing a photo",
        required: ["id", "url"],
        properties: [
            new OA\Property(
                property: "id",
                type: "string",
                description: "The unique identifier of the photo",
                example: "ff4574f4-cff7-42ef-b76e-c770d3e645a6"
            ),
            new OA\Property(
                property: "url",
                type: "string",
                format: "uri",
                description: "The URL of the photo",
                example: "https://lh3.googleusercontent.com/lr/AAJ1LKf2T7n3LdRucrjT-yQgqfv_o96vcOLy5Zs8sJow2zwvUOhoj_ujP9xBFNT1r-Z0SxQRfd12hvaUKOrrdszSHwJ-gx4rmTi1vyaeIEpIMTOFLxvItLCJBgx60wzaT3zs1xl3aAORPoU3fE41SryWUluZ4MIjDDUN3FUrVafn1qtwF8tpuZ-IKR7VjUgUOP7x-3O-Zm_7vsS4FAxlx32m3EEWSStSqXPkcMl9MtsBXzOC3hSckDbIzhu-2NBW4MILNmfTUTuMOSJGKuiIkRJdJr8FHtgDJhR8FIOB3J2tqvXJOqPssZ_x6AbXcFwEl-zg7xG-Lj29JODrcQW2-bH5_RlcACXWxR3_CKjSarwrhiV6cYmplgE6im3q3YOS5KpmDqxHdxXvwi0vDLR3zjnSgf4u0sWOMr5X2WP7yxudpjD4RRmvBytSczgej4D-0Ug9se65MXYMzA6ZJXjEFy8trX0E5siSiqc-rwd72CKyw0BZhSQdvVPaNrE2xNEqJ07PV-YTEFtSxCkpYhVqa5tIU_mQu24ERsfNvlzD2rBYr6bb9dvTmu5g-1lMcjYjPBO1tgGj5mtT6POrfowwb_8RKO46PSlBLDID9tuqNzPQ9FHH1AWLOpVAw00Cpipzl7xN0k2yI3yA-TQXaT3trP4Lkj_qeDFXhEmz8TpEwcyj-8f2Ix7Fy98yFSXRwpzsl0hzDiBFU27ja40oJgXkoo44x8E-dW2s1S_C9ypOjEi2TOGJ_eYSFkBdfvEPY0WC7WOvfZ_spUdtUM_NoF-lqmwT2yXOcXNdUlbtYA8-CeAVSMmd6Q3w3-49yfc2UeysLjh4ZpnB7dDOXPJ_izLG6WghBzsBoWZtl4VVn_qia0vjGvaeEC_O__cwy77YhhgLiTkzzdBQDjonC9Q7WIWtpR_qLUnPllG_1qt8CHspAsrtIPnqDokNOkqfsZcKIXD1XubIzoj4uwjAOAegPpKo1RlmnA5vs3aLEMf-z-pKTegEoYv7aF-ushESccG4NwI"
            ),
            new OA\Property(
                property: "permalink",
                type: "string",
                format: "uri",
                description: "The permalink to the photo",
                example: "https://photos.google.com/lr/album/ADpjswnfN09upo9IvJjWV5E7C_x_rHXBv6MX09Ys20De8FbeKkjYL9HLrDo-lPrLUz8KfnU-Nps7/photo/ADpjswm-BZXKwAifE3JYSadq8aARaZtkHXhr51mZBgrvT1IwozwAgSG8h-viZAnzSlw_fYSzU-APFfbZ8UYCMdK0C091hI64lA"
            ),
            new OA\Property(
                property: "focalLength",
                type: "number",
                format: "float",
                description: "The focal length of the photo",
                example: 18
            ),
            new OA\Property(
                property: "aperture",
                type: "number",
                format: "float",
                description: "The aperture of the photo",
                example: 9
            ),
            new OA\Property(
                property: "shutterSpeed",
                type: "number",
                format: "float",
                description: "The shutter speed of the photo",
                example: 0.01
            ),
            new OA\Property(
                property: "iso",
                type: "integer",
                description: "The ISO settings of the photo",
                example: 100
            ),
            new OA\Property(
                property: "timestamp",
                type: "integer",
                description: "The epoch timestamp of the photo",
                example: 1708540024
            ),
            new OA\Property(
                property: "sunAltitude",
                type: "number",
                format: "float",
                description: "The altitude of the sun in degress at the time the photo was taken",
                example: 34.3
            ),
            new OA\Property(
                property: "sunAzimuth",
                type: "number",
                format: "float",
                description: "The azimuth of the sun in degress at the time the photo was taken",
                example: 63.2
            )
        ]
    )]
    class Photo implements \JsonSerializable {        
        private readonly string $id;
        private readonly mixed $urlProvider;
        private readonly ?string $permalink;
        private readonly ?float $focalLength;
        private readonly ?float $aperture;
        private readonly ?float $shutterSpeed;
        private readonly ?int $iso;
        private readonly ?int $timestamp;
        private readonly ?float $sunAltitude;
        private readonly ?float $sunAzimuth;

        public function __construct(string $id, callable $urlProvider, ?string $permalink, ?float $focalLength,
            ?float $aperture, ?float $shutterSpeed, ?int $iso, ?int $timestamp, ?float $sunAltitude, ?float $sunAzimuth) {
            $this->id = $id;
            $this->urlProvider = $urlProvider;
            $this->permalink = $permalink;
            $this->focalLength = $focalLength;
            $this->aperture = $aperture;
            $this->shutterSpeed = $shutterSpeed;
            $this->iso = $iso;
            $this->timestamp = $timestamp;
            $this->sunAltitude = $sunAltitude;
            $this->sunAzimuth = $sunAzimuth;
        }

        public function getId() : string {
            return $this->id;
        }

        public function getUrl() : ?string {
            // Compute the URL only when it is needed to avoid unnecessary Google API calls.
            return ($this->urlProvider)();
        }

        public function getPermalink() : ?string {
            return $this->permalink;
        }

        public function getFocalLength() : ?float {
            return $this->focalLength;
        }

        public function getAperture() : ?float {
            return $this->aperture;
        }

        public function getShutterSpeed() : ?float {
            return $this->shutterSpeed;
        }

        public function getIso() : ?int {
            return $this->iso;
        }

        public function getTimestamp() : ?int {
            return $this->timestamp;
        }

        public function getSunAltitude() : ?float {
            return $this->sunAltitude;
        }

        public function getSunAzimuth() : ?float {
            return $this->sunAzimuth;
        }
    
        public function withReplacedId(string $newId) : Photo {
            return new Photo($newId, $this->urlProvider, $this->permalink, $this->focalLength,
                $this->aperture, $this->shutterSpeed, $this->iso, $this->timestamp, $this->sunAltitude, $this->sunAzimuth);
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return array_merge(array_diff_key(get_object_vars($this), ["urlProvider" => null]), ["url" => ($this->urlProvider)()]);
        }
    }
?>