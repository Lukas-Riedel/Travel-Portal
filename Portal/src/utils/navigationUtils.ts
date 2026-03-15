import type { AdminNavigationTarget } from "../classes/AdminNavigationTarget.ts"
import type { Place } from "../classes/Place.ts"
import type { Airline, Category } from "../types/CoreSwaggerTypes.ts"
import type { Navigable } from "../types/Navigable.ts"

const PLAN_PAGE_PREFIX = "/plan"
const AIRLINE_PAGE_PREFIX = "/airline"
const CATEGORY_PAGE_PREFIX = "/category"
const PLACE_PAGE_PREFIX = "/place"
const ADMIN_PAGE_PREFIX = "/admin"

const isAirline = (to: Navigable): to is Airline => (to as Airline).codes !== undefined
const isCategory = (to: Navigable): to is Category => (to as Category).category !== undefined
const isPlace = (to: Navigable): to is Place => (to as Place).score !== undefined
const isAdminNavigationTarget = (to: Navigable): to is AdminNavigationTarget => (to as AdminNavigationTarget).tab !== undefined

export function getPath(to: Navigable, currentPath?: string): string {
    let path = ""
    if (currentPath?.startsWith(PLAN_PAGE_PREFIX)) {
        path += PLAN_PAGE_PREFIX
    }

    if (isAirline(to)) {
        path += AIRLINE_PAGE_PREFIX + "/" + to.id
    }
    else if (isCategory(to)) {
        path += CATEGORY_PAGE_PREFIX + "/" + to.id
    }
    else if (isPlace(to)) {
        path += PLACE_PAGE_PREFIX + "/" + to.id
    }
    else if (isAdminNavigationTarget(to)) {
        path += ADMIN_PAGE_PREFIX + "?" + to.getURLSearchParams()
    }

    return path;
}