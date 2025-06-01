import { format } from "date-fns";

export function getDateString(timestamp) {
    return format(new Date(timestamp * 1000), "d.M.yyyy")
}

export function getMaxEndTimestamp(isAdmin) {
    return isAdmin ? Number.MAX_SAFE_INTEGER : Math.floor(Date.now () / 1000)
}

export function decapitalize(str) {
    return str[0].toLowerCase() + str.slice(1)
}

export function getPrettyName(name) {
    const index = name.indexOf("(")
    return index === -1 ? name : name.slice(0, index).trim()
}