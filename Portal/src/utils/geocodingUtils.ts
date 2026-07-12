import type { Coordinates } from "../types/Coordinates.ts"
import type { GeoJSON, Feature, Geometry } from "geojson"

export const EARTH_RADIUS_KILOMETERS = 6371.0

export function getEuclideanDistance(a: Coordinates, b: Coordinates): number {
    return 111.0 * Math.hypot(a.latitude - b.latitude, a.longitude - b.longitude)
}

export function getHaversineDistance(a: Coordinates, b: Coordinates): number {
    const x1 = a.latitude - b.latitude
    const x2 = a.longitude - b.longitude
    const ar = Math.sin(toRadians(x1) / 2) * Math.sin(toRadians(x1) / 2) + Math.cos(toRadians(b.latitude))
        * Math.cos(toRadians(a.latitude)) * Math.sin(toRadians(x2) / 2) * Math.sin(toRadians(x2) / 2)
    const c = 2 * Math.atan2(Math.sqrt(ar), Math.sqrt(1 - ar))

    return EARTH_RADIUS_KILOMETERS * c
}

export function toRadians(degrees: number): number {
    return degrees * Math.PI / 180
}

export function toDegrees(radians: number): number {
    return radians * 180 / Math.PI
}

export function tryExtractPointCoordinates(geoJson: GeoJSON): Coordinates | null {
    if (geoJson.type === "Feature") {
        return geoJson.geometry?.type !== "Point" ? null : {
            latitude: geoJson.geometry.coordinates[1],
            longitude: geoJson.geometry.coordinates[0]
        }
    }

    return geoJson.type !== "Point" ? null : {
        latitude: geoJson.coordinates[1],
        longitude: geoJson.coordinates[0]
    }
}

export function getGeoFeatures(geoJson: GeoJSON): Feature[] {
    if (geoJson.type === "FeatureCollection") {
        return geoJson.features
    }

    if (geoJson.type === "Feature") {
        return [geoJson]
    }

    if (geoJson.type === "GeometryCollection" && geoJson.geometries.length === 1) {
        return [
            {
                type: "Feature",
                properties: {},
                geometry: geoJson.geometries[0]
            }
        ]
    }

    return []
}

export function getGeoJson(geometry: Geometry): GeoJSON {
    return {
        type: "Feature",
        properties: {},
        geometry
    }
}