import { endOfDay, format, fromUnixTime, startOfDay } from "date-fns"
import { toZonedTime } from "date-fns-tz"
import { optimize } from "svgo"

export function getDateString(timestamp) {
    return timestamp && format(new Date(timestamp * 1000), "d.M.yyyy")
}

export function getDateRangeString(start, end, includeYear = true) {
    if (!start || !end) {
        return null
    }

    const startDate = new Date(start * 1000)
    const endDate = new Date(end * 1000)

    if (startDate.getTime() === endDate.getTime()) {
        return format(startDate, "d.M.yyyy")
    }

    const startFormat = startDate.getFullYear() !== endDate.getFullYear() ? (includeYear ? "d.M.yyyy" : "d.M.") : startDate.getMonth() !== endDate.getMonth() ? "d.M." : "d."
    return `${format(startDate, startFormat)} - ${format(endDate, (includeYear ? "d.M.yyyy" : "d.M."))}`
}

export function getMaxEndTimestamp(isAdmin) {
    return isAdmin ? Number.MAX_SAFE_INTEGER : Math.floor(Date.now() / 1000)
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

export function getAirlineCodeForFlight(flight) {
    return flight?.substring(0, 2)
}

export function prefixSvgIds(svgString, prefix) {
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