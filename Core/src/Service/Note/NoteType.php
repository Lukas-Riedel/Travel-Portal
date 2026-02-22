<?php
    namespace Core\Service\Note;
    
    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "NoteType",
        type: "string",
        description: "An enum representing a note type"
    )]
    enum NoteType : string {
        case Place = "place";
        case Trip = "trip";

        public function getTableName() : string {
            return match ($this) {
                self::Place => "note_place",
                self::Trip => "note_trip"
            };
        }
    }
?>