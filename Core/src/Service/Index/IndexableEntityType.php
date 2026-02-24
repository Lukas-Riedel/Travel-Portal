<?php
    namespace Core\Service\Index;

    enum IndexableEntityType : string {
        case Category = "category";
        case Place = "place";
        case Flight = "flight";
        case Airport = "airport";
        case Airline = "airline";
        case Label = "label";
        case Trip = "trip";
        case Year = "year";
    }
?>