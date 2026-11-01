<?php
    namespace Core\Client\Calendar;    

    enum Calendar : string {
        case Trips = "trips";
        case Places = "places";
        case Stays = "stays";
        case WatchedStays = "watchedStays";
        case CarRentals = "carRentals";
        case Flights = "flights";
        case WatchedFlights = "watchedFlights";
    }
?>