import { format } from "date-fns";

export function getDateString(timestamp) {
    return format(new Date(timestamp * 1000), "d.M.yyyy")
}

export function getMaxEndTimestamp(isAdmin) {
    return isAdmin ? Number.MAX_SAFE_INTEGER : Math.round(Date.now() / 1000)
}