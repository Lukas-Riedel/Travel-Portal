import { format } from "date-fns";

export function getMostSpecificCategoryWithMetadata(place) {
    return place.categories.findLast(category => category.metadata != null
        && category.metadata.color != null && category.metadata.unicode != null)
}

export function getDateString(timestamp) {
    return format(new Date(timestamp * 1000), "d.M.yyyy")
}

export function getDistance(p1, p2) {
    const toRad = x => x * Math.PI / 180;
  
    const x1 = p2.latitude - p1.latitude;
    const x2 = p2.longitude - p1.longitude;
    const a = Math.sin(toRad(x1) / 2) * Math.sin(toRad(x1) / 2) + Math.cos(toRad(p1.latitude)) * Math.cos(toRad(p2.latitude)) * Math.sin(toRad(x2) / 2) * Math.sin(toRad(x2) / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return 6378 * c;
}
