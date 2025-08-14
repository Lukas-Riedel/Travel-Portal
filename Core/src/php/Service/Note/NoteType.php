<?php
    namespace Core\Service\Note;

    enum NoteType {
        case Place;
        case Trip;

        public function getTableName() : string {
            return match ($this) {
                self::Place => "note_place",
                self::Trip => "note_trip"
            };
        }
    }
?>