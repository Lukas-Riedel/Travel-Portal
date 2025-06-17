import { format } from "date-fns";

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