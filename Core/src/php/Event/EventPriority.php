<?php
    namespace Core\Event;

    enum EventPriority : int {
        case Lowest = 0;
        case Low = 1;
        case Medium = 2;
        case High = 3;
        case Highest = 4;
    }
?>