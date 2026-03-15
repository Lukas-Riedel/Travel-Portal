import { endOfDay, format, fromUnixTime, startOfDay } from "date-fns"
import { toZonedTime } from "date-fns-tz"
import { optimize } from "svgo"
import { v4 as uuidv4 } from "uuid"

// TODO: Replace by formatTimestamp in timeUtils.ts
export function getDateString(timestamp) {
    return timestamp && format(fromUnixTime(timestamp), "d.M.yyyy")
}

// TODO: Replace by formatTimestamp in timeUtils.ts
export function getDateTimeString(timestamp, includeYear = true) {
    return timestamp && format(fromUnixTime(timestamp), includeYear ? "d.M.yyyy H:mm" : "d.M. H:mm")
}

// TODO: Replace by formatTimestamp in timeUtils.ts
export function getTimeString(timestamp) {
    return timestamp && format(fromUnixTime(timestamp), "H:mm")
}

export function getDateRangeString(start, end, includeYear = true) {
    if (!start || !end) {
        return null
    }

    const startDate = fromUnixTime(start)
    const endDate = fromUnixTime(end)

    if (startDate.getTime() === endDate.getTime()) {
        return format(startDate, "d.M.yyyy")
    }

    const startFormat = startDate.getFullYear() !== endDate.getFullYear() ? (includeYear ? "d.M.yyyy" : "d.M.") : startDate.getMonth() !== endDate.getMonth() ? "d.M." : "d."
    return `${format(startDate, startFormat)} - ${format(endDate, (includeYear ? "d.M.yyyy" : "d.M."))}`
}

export function decapitalize(str) {
    return str && (str[0].toLowerCase() + str.slice(1))
}

export function isInTrip(trips, date) {
    return trips.some(({ start, end }) => start * 1000 <= endOfDay(date).getTime() && end * 1000 > startOfDay(date).getTime())
}

export function getEvents(day, events, hoursFilter, timezone) {
    const targetDay = startOfDay(day).getTime()
    return (events ?? []).filter(event => hoursFilter(event.hours) && startOfDay(toZonedTime(fromUnixTime(event.timestamp), timezone)).getTime() === targetDay)
}

export function sumEventHours(events) {
    return events.map(e => e.hours).reduce((a, b) => a + b, 0)
}

export function isDaylightSavingTime(timestamp, timezone) {
    const date = toZonedTime(fromUnixTime(timestamp), timezone)
    return date.getTimezoneOffset() < Math.max(toZonedTime(new Date(date.getFullYear(), 0, 1), timezone).getTimezoneOffset(), toZonedTime(new Date(date.getFullYear(), 6, 1), timezone).getTimezoneOffset())
}

export function getOnlyElement(arr) {
    return arr?.length === 1 ? arr[0] : undefined
}

export function getGeoFeatures(geoJson) {
    if (geoJson.type === "FeatureCollection") {
        return geoJson.features
    }
    if (geoJson.type === "Feature") {
        return geoJson
    }
    if (geoJson.type === "GeometryCollection" && geoJson.geometries.length === 1) {
        return {
            type: "Feature",
            properties: {},
            geometry: geoJson.geometries[0]
        }
    }
    return []
}

export function getGeoJson(geometry) {
    return {
        type: "Feature",
        geometry
    }
}

// TODO: What to do with this?
export async function getCachedCoordinates(address, getCoordinates) {
    const cachedCoordinates = localStorage.getItem(address)

    if (cachedCoordinates) {
        return Promise.resolve(JSON.parse(cachedCoordinates))
    }

    return getCoordinates(address).then(coordinates => {
        localStorage.setItem(address, JSON.stringify(coordinates))
        return coordinates
    }).catch(() => undefined)
}