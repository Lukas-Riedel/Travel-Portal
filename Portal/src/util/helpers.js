import { format } from "date-fns";

export function getDateString(timestamp) {
    return format(new Date(timestamp * 1000), "d.M.yyyy")
}