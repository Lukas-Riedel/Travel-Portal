import { AppLinkTarget } from "../types/AppLinkTarget.ts"
import type { AdminNavigationTarget } from "../types/AdminNavigationTarget.ts"
import { AdminMenuTabName } from "../types/AdminMenuTabName.ts"
import type { Coordinates } from "../types/Coordinates.ts"
import type { Airline, Airport, AirportIdentifier, Category, CategoryIdentifier, Flight, Label, Place, PlaceIdentifier, Trip, TripIdentifier, Year, YearIdentifier } from "../types/CoreSwaggerTypes.ts"
import type { Navigable } from "../types/Navigable.ts"
import type { PlaceAlbum } from "../types/PlaceAlbum.ts"
import type { PlansNavigationTarget } from "../types/PlansNavigationTarget.ts"
import { PlansMenuTabName } from "../types/PlansMenuTabName.ts"
import { StaticNavigationTarget } from "../types/StaticNavigationTarget.ts"
import { formatTimestamp } from "./timeUtils.ts"

const PLAN_PAGE_PREFIX = "/plan"
const YEAR_PAGE_PREFIX = "/year"
const AIRLINE_PAGE_PREFIX = "/airline"
const AIRPORT_PAGE_PREFIX = "/airport"
const CATEGORY_PAGE_PREFIX = "/category"
const PLACE_PAGE_PREFIX = "/place"
const TRIP_PAGE_PREFIX = "/trip"
const ALBUM_PAGE_PREFIX = "/album"
const LABEL_PAGE_PREFIX = "/label"
const ADMIN_PAGE_PREFIX = "/admin"
const HIGHLIGHT_PAGE_PREFIX = "/highlight"
const TRACKER_PAGE_PREFIX = "/tracker"
const FLIGHT_PAGE_PREFIX = "/flight"
const STATISTICS_PAGE_PREFIX = "/statistics"
const FEED_PAGE_PREFIX = "/feed"

const isYearNumber = (to: Navigable): to is number => typeof to === "number" && (to as number) >= 1900
const isYearObject = (to: Navigable): to is Year | YearIdentifier => typeof to === "object" && typeof (to as Year).id === "number"
const isAirline = (to: Navigable): to is Airline => (to as Airline).codes !== undefined
const isAirport = (to: Navigable): to is Airport | AirportIdentifier => (to as Airport).shortName !== undefined || ((to as AirportIdentifier).code !== undefined && (to as AirportIdentifier).latitude !== undefined)
const isCategory = (to: Navigable): to is Category | CategoryIdentifier => (to as Category).category !== undefined
const isPlace = (to: Navigable): to is Place | PlaceIdentifier => (to as PlaceIdentifier).score !== undefined
// TODO: TripIdentifier without year is structurally identical to Label ({ id, name }) and cannot be distinguished at runtime. A TripIdentifier without year will fall through to isLabel.
const isTrip = (to: Navigable): to is Trip | TripIdentifier => (to as Trip).countries !== undefined || (to as TripIdentifier).year !== undefined
// TODO: AirlineIdentifier and Label are structurally identical ({ id, name }) and cannot be distinguished at runtime. AirlineIdentifier will fall through to isLabel and incorrectly route to /label/:id.
const isLabel = (to: Navigable): to is Label => (to as Label).name !== undefined && !isAirline(to) && !isCategory(to) && !isPlace(to) && !isTrip(to)
const isPlaceAlbum = (to: Navigable): to is PlaceAlbum => (to as PlaceAlbum).place !== undefined && (to as PlaceAlbum).album !== undefined
const isAdminNavigationTarget = (to: Navigable): to is AdminNavigationTarget => Object.values(AdminMenuTabName).includes((to as AdminNavigationTarget).tab as AdminMenuTabName)
const isPlansNavigationTarget = (to: Navigable): to is PlansNavigationTarget => Object.values(PlansMenuTabName).includes((to as PlansNavigationTarget).tab as PlansMenuTabName)
const isStaticNavigationTarget = (to: Navigable): to is StaticNavigationTarget => StaticNavigationTarget[to as number] !== undefined

export function getPath(to: Navigable, target: AppLinkTarget = AppLinkTarget.Default, currentPath?: string): string {
    let path = ""
    if (target === AppLinkTarget.Plans || currentPath?.startsWith(PLAN_PAGE_PREFIX)) {
        path += PLAN_PAGE_PREFIX
    }

    if (isYearNumber(to)) {
        path += YEAR_PAGE_PREFIX + "/" + to
    }
    else if (isYearObject(to)) {
        path += YEAR_PAGE_PREFIX + "/" + to.id
    }
    else if (isAirline(to)) {
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
    else if (isLabel(to)) {
        path += LABEL_PAGE_PREFIX + "/" + to.id
    }
    else if (isPlaceAlbum(to)) {
        path += PLACE_PAGE_PREFIX + "/" + to.place.id + ALBUM_PAGE_PREFIX + "/" + to.album.id
    }
    else if (isAdminNavigationTarget(to)) {
        path += ADMIN_PAGE_PREFIX + "?" + getAdminURLSearchParams(to)
    }
    else if (isPlansNavigationTarget(to)) {
        path += PLAN_PAGE_PREFIX + "?" + getPlansURLSearchParams(to)
    }
    else if (isStaticNavigationTarget(to)) {
        if (to === StaticNavigationTarget.Highlights) {
            path = currentPath + "/" + HIGHLIGHT_PAGE_PREFIX
        }
        else if (to === StaticNavigationTarget.Home) {
            path = "/"
        }
        else if (to === StaticNavigationTarget.Feed) {
            path = FEED_PAGE_PREFIX
        }
        else if (to === StaticNavigationTarget.Trips) {
            path = TRIP_PAGE_PREFIX
        }
        else if (to === StaticNavigationTarget.Places) {
            path = PLACE_PAGE_PREFIX
        }
        else if (to === StaticNavigationTarget.Flights) {
            path = FLIGHT_PAGE_PREFIX
        }
        else if (to === StaticNavigationTarget.Statistics) {
            path = STATISTICS_PAGE_PREFIX
        }
        else if (to === StaticNavigationTarget.Plan) {
            path = PLAN_PAGE_PREFIX
        }
        else if (to === StaticNavigationTarget.Tracker) {
            path = TRACKER_PAGE_PREFIX
        }
        else if (to === StaticNavigationTarget.Admin) {
            path = ADMIN_PAGE_PREFIX
        }
    }

    return path;
}

export function getMapLink(address: string): string {
    return `https://www.google.com/maps/search/${encodeURIComponent(address)}`
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

export function getGoogleCalendarLink(date: Date): string {
    return `https://calendar.google.com/calendar/u/0/r/week/${date.getFullYear()}/${date.getMonth() + 1}/${date.getDate()}`
}

export function getGoogleCloudAuthenticationLink(): string {
    return (window.env?.VITE_IAM_BASE_URL || import.meta.env.VITE_IAM_BASE_URL) + "/google/auth"
}

export function getAdminNavigationTarget(tab: AdminMenuTabName, key?: string): AdminNavigationTarget {
    return { tab, key }
}

export function getAdminURLSearchParams(target: AdminNavigationTarget): URLSearchParams {
    const params: Record<string, string> = { tab: target.tab }
    if (target.key) params.key = target.key
    return new URLSearchParams(params)
}

export function getPlansNavigationTarget(tab: PlansMenuTabName): PlansNavigationTarget {
    return { tab }
}

export function getPlansURLSearchParams(target: PlansNavigationTarget): URLSearchParams {
    return new URLSearchParams({ tab: target.tab })
}