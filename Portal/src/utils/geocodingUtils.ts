import type { Coordinates } from "../types/Coordinates.ts"

export const EARTH_RADIUS_KILOMETERS = 6371.0

export function getEuclideanDistance(a: Coordinates, b: Coordinates): number {
    return 111.0 * Math.hypot(a.latitude - b.latitude, a.longitude - b.longitude)
}

export function getHaversineDistance(a: Coordinates, b: Coordinates): number {
    const x1 = a.latitude - b.latitude;
    const x2 = a.longitude - b.longitude;
    const ar = Math.sin(toRadians(x1) / 2) * Math.sin(toRadians(x1) / 2) + Math.cos(toRadians(b.latitude))
        * Math.cos(toRadians(a.latitude)) * Math.sin(toRadians(x2) / 2) * Math.sin(toRadians(x2) / 2);
    const c = 2 * Math.atan2(Math.sqrt(ar), Math.sqrt(1 - ar));

    return EARTH_RADIUS_KILOMETERS * c;
}

export function toRadians(degrees: number): number {
    return degrees * Math.PI / 180;
}