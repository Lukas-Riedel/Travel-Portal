import type { Coordinate } from "recharts"
import type { AdminNavigationTarget } from "../classes/AdminNavigationTarget.ts"
import type { Airline, Airport, Category, Flight, Place, Trip } from "../types/CoreSwaggerTypes.ts"
import type { Navigable } from "../types/Navigable.ts"
import type { Coordinates } from "../types/Coordinates.ts"
import { formatTimestamp } from "./timeUtils.ts"
import type { PlaceAlbum } from "../types/PlaceAlbum.ts"

const PLAN_PAGE_PREFIX = "/plan"
const AIRLINE_PAGE_PREFIX = "/airline"
const AIRPORT_PAGE_PREFIX = "/airport"
const CATEGORY_PAGE_PREFIX = "/category"
const PLACE_PAGE_PREFIX = "/place"
const TRIP_PAGE_PREFIX = "/trip"
const ALBUM_PAGE_PREFIX = "/album"
const ADMIN_PAGE_PREFIX = "/admin"

const isAirline = (to: Navigable): to is Airline => (to as Airline).codes !== undefined
const isAirport = (to: Navigable): to is Airport => (to as Airport).shortName !== undefined
const isCategory = (to: Navigable): to is Category => (to as Category).category !== undefined
const isPlace = (to: Navigable): to is Place => (to as Place).score !== undefined
// TODO: Countries are optional, there is no way how to decide whether the entity is a trip at this point.
const isTrip = (to: Navigable): to is Trip => (to as Trip).countries !== undefined
const isPlaceAlbum = (to: Navigable): to is PlaceAlbum => (to as PlaceAlbum).place !== undefined && (to as PlaceAlbum).album !== undefined
const isAdminNavigationTarget = (to: Navigable): to is AdminNavigationTarget => (to as AdminNavigationTarget).tab !== undefined

export function getPath(to: Navigable, currentPath?: string): string {
    let path = ""
    if (currentPath?.startsWith(PLAN_PAGE_PREFIX)) {
        path += PLAN_PAGE_PREFIX
    }

    if (isAirline(to)) {
        path += AIRLINE_PAGE_PREFIX + "/" + to.id
    }
    else if (isAirport(to)) {
        path += AIRPORT_PAGE_PREFIX + "/" + to.id
    }
    else if (isCategory(to)) {
        path += CATEGORY_PAGE_PREFIX + "/" + to.id
    }
    else if (isPlace(to)) {
        path += PLACE_PAGE_PREFIX + "/" + to.id
    }
    else if (isTrip(to)) {
        path += TRIP_PAGE_PREFIX + "/" + to.id
    }
    else if (isPlaceAlbum(to)) {
        path += PLACE_PAGE_PREFIX + "/" + to.place.id + ALBUM_PAGE_PREFIX + "/" + to.album.id
    }
    else if (isAdminNavigationTarget(to)) {
        path += ADMIN_PAGE_PREFIX + "?" + to.getURLSearchParams()
    }

    return path;
}

export function getMapLink(address: string): string {
    return `https://www.google.com/maps/search/${address}`
}

export function getSatelliteLink(coordinates: Coordinates): string {
    return `https://www.windy.com/${coordinates.latitude}/${coordinates.longitude}?satellite`
}

export function getFlightLink(flight: string): string {
    return `https://www.flightradar24.com/data/flights/${flight}`
}

export function getAircraftLink(registration: string): string {
    return `https://www.flightradar24.com/data/aircraft/${registration}`
}

export function getFlightPriceLink(flight: Flight): string {
    return `https://www.google.com/travel/flights?q=One way flight from ${flight.from.shortName} to ${flight.to.shortName} on ${formatTimestamp(flight.start, "d.M.yyyy", flight.from.timezone)}`
}