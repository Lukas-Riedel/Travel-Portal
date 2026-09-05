import { endOfDay, format, fromUnixTime, startOfDay } from "date-fns"
import { toZonedTime } from "date-fns-tz"
import { optimize } from "svgo"
import { v4 as uuidv4 } from "uuid"

// TODO: Replace by formatTimestamp in timeUtils.ts
export function getDateString(timestamp) {
    return timestamp && format(fromUnixTime(timestamp), "d.M.yyyy")
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

export function isInTrip(trips, date) {
    return trips.some(trip => trip.isDayInTrip(date))
}

export function getOnlyElement(arr) {
    return arr?.length === 1 ? arr[0] : undefined
}

// TODO: What to do with this? Use useCache?
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