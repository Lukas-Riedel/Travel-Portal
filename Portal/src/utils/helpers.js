import { endOfDay, format, fromUnixTime, startOfDay } from "date-fns"
import { toZonedTime } from "date-fns-tz"
import { optimize } from "svgo"
import { v4 as uuidv4 } from "uuid"

// TODO: Make sure all code that needs to format date uses this function.
export function getDateString(timestamp) {
    return timestamp && format(fromUnixTime(timestamp), "d.M.yyyy")
}

// TODO: Make sure all code that needs to format datetime uses this function.
export function getDateTimeString(timestamp, includeYear = true) {
    return timestamp && format(fromUnixTime(timestamp), includeYear ? "d.M.yyyy H:mm" : "d.M. H:mm")
}

// TODO: Make sure all code that needs to format time uses this function.
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

export function getMaxEndTimestamp(isAdmin) {
    return isAdmin ? Number.MAX_SAFE_INTEGER : Math.round(Date.now() / 1000)
}

export function decapitalize(str) {
    return str && (str[0].toLowerCase() + str.slice(1))
}

export function getPrettyName(name) {
    return name?.split("(")[0].trim()
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

export function getSafeSvgString(svgString, prefix) {
    if (!svgString) {
        return svgString
    }

    const result = optimize(svgString, {
        plugins: [
            {
                name: "prefixIds",
                params: {
                    prefix,
                    delim: "-",
                    prefixIds: true,
                    prefixClassNames: true
                }
            }
        ]
    })

    if ("data" in result) {
        return result.data
    }

    return svgString
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