import { format } from "date-fns";

export function getDateString(timestamp) {
    return timestamp && format(new Date(timestamp * 1000), "d.M.yyyy")
}

export function getMaxEndTimestamp(isAdmin) {
    return isAdmin ? Number.MAX_SAFE_INTEGER : Math.floor(Date.now () / 1000)
}

export function decapitalize(str) {
    return str && (str[0].toLowerCase() + str.slice(1))
}

export function getPrettyName(name) {
    return name?.split("(")[0].trim()
}